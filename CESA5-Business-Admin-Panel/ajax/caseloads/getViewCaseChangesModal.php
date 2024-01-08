<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // get today's date
        $today = date("Y-m-d");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") || checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED"))
        {
            // store user permissions locally
            $view_all = checkUserPermission($conn, "VIEW_CASELOADS_ALL");

            // get the caseload ID from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }

            // verify the case exists
            if (verifyCase($conn, $case_id))
            {
                // get the case's current data
                $getCase = mysqli_prepare($conn, "SELECT * FROM cases WHERE id=?");
                mysqli_stmt_bind_param($getCase, "i", $case_id);
                if (mysqli_stmt_execute($getCase))
                {
                    $getCaseResult = mysqli_stmt_get_result($getCase);
                    if (mysqli_num_rows($getCaseResult) > 0)
                    {
                        // store caseload details locally
                        $case = mysqli_fetch_array($getCaseResult);
                        $case_id = $case["id"];
                        $caseload_id = $case["caseload_id"];
                        $period_id = $case["period_id"];
                        $student_id = $case["student_id"];
                        $starting_uos = $case["estimated_uos"];
                        $starting_frequency = $case["frequency"];
                        $start_date = date("n/j/Y", strtotime($case["start_date"]));
                        $end_date = date("n/j/Y", strtotime($case["end_date"]));
                        $extra_ieps = $case["extra_ieps"];
                        $extra_evals = $case["extra_evaluations"];
                        $remove_iep = $case["remove_iep"];
                        $dismissed = $case["dismissed"];
                        $dismissal_iep = $case["dismissal_iep"];
                        $uos_adjustment = $case["uos_adjustment"];
                        $assistant_id = $case["assistant_id"];

                        // get the category ID
                        $category_id = getCaseloadCategory($conn, $caseload_id);

                        // check to see if the period is editable
                        $is_editable = isPeriodEditable($conn, $period_id);

                        // if the user is a coordinator who is not the primary caseload assignment; disable action buttons
                        if (verifyCoordinator($conn, $_SESSION["id"]) && !isCaseloadAssigned($conn, $_SESSION["id"], $caseload_id)) {
                            $is_editable = false;
                        }

                        // override is_editable if user can only view assigned and the caseload category is locked
                        if (!$view_all && isCaseloadLocked($conn, $caseload_id)) { 
                            $is_editable = false;
                        }

                        // calculate number of days in school year
                        $days_in_year = getDaysInCaseloadTerm($conn, $period_id);

                        // get student details
                        $student_name = "";
                        $getStudent = mysqli_prepare($conn, "SELECT fname, lname FROM caseload_students WHERE id=?");
                        mysqli_stmt_bind_param($getStudent, "i", $student_id);
                        if (mysqli_stmt_execute($getStudent))
                        {
                            $getStudentResult = mysqli_stmt_get_result($getStudent);
                            if (mysqli_num_rows($getStudentResult) > 0) // student found
                            {
                                // store student data locally
                                $student = mysqli_fetch_array($getStudentResult);
                                $fname = $student["fname"];
                                $lname = $student["lname"];

                                // create the name to be displayed
                                $student_name = $lname.", ".$fname;
                            }
                        }

                        // initialize array to store changes
                        $changes = [];
                        $future_changes = []; 

                        // add initial caseload details to changes array
                        $initial_details = [];
                        $initial_details["change_id"] = 0;
                        $initial_details["change_date"] = $start_date;
                        $initial_details["end_date"] = $end_date;
                        $initial_details["frequency"] = $starting_frequency;
                        $initial_details["uos"] = $starting_uos;
                        $initial_details["uos_change"] = "-";
                        $initial_details["additional_iep"] = 0;
                        $initial_details["is_dismissal"] = 0;
                        $changes[] = $initial_details;
                        $future_changes[] = $initial_details;

                        // get a list of all caseload changes for this caseload
                        $getChanges = mysqli_prepare($conn, "SELECT id, start_date, frequency, uos, iep_meeting, is_dismissal FROM case_changes WHERE case_id=? AND start_date<=? ORDER BY start_date ASC");
                        mysqli_stmt_bind_param($getChanges, "is", $case_id, $today);
                        if (mysqli_stmt_execute($getChanges))
                        {
                            $getChangesResults = mysqli_stmt_get_result($getChanges);
                            if (mysqli_num_rows($getChangesResults) > 0)
                            {
                                // initialize the counter to track changes
                                $change_counter = 1;

                                // set the previous units of service to the starting units of service
                                $previous_uos = $starting_uos;

                                while ($change = mysqli_fetch_array($getChangesResults))
                                {
                                    // store change details locally
                                    $change_id = $change["id"];
                                    if (isset($change["start_date"])) { $start_date = date("n/j/Y", strtotime($change["start_date"])); } else { $start_date = "?"; }
                                    $frequency = $change["frequency"];
                                    $uos = $change["uos"];
                                    $uos_change = $uos - $previous_uos;

                                    // create temporary array to store caseload change date
                                    $temp = [];
                                    $temp["change_id"] = $change["id"];
                                    $temp["change_date"] = $start_date;
                                    $temp["end_date"] = $end_date;
                                    $temp["frequency"] = $frequency;
                                    $temp["uos"] = $uos;
                                    $temp["uos_change"] = $uos_change;
                                    $temp["additional_iep"] = $change["iep_meeting"];
                                    $temp["is_dismissal"] = $change["is_dismissal"];

                                    // update the prior entries end date to the start date of the change
                                    $changes[$change_counter - 1]["end_date"] = $start_date;

                                    // add temporary array to changes array
                                    $changes[] = $temp;

                                    // set the previous units of service to this change increment units of service
                                    $previous_uos = $uos;

                                    // increment change counter
                                    $change_counter++;
                                }

                                // add initial caseload details to changes array
                                $initial_details = [];
                                $initial_details["change_id"] = $changes[$change_counter - 1]["change_id"];
                                $initial_details["change_date"] = $changes[$change_counter - 1]["change_date"];
                                $initial_details["end_date"] = $changes[$change_counter - 1]["end_date"];
                                $initial_details["frequency"] = $changes[$change_counter - 1]["frequency"];
                                $initial_details["uos"] = $changes[$change_counter - 1]["uos"];
                                $initial_details["uos_change"] = "-";
                                $initial_details["additional_iep"] = 0;
                                $initial_details["is_dismissal"] = 0;
                                $future_changes[0] = $initial_details;
                            }
                        }

                        // get a list of all planned caseload changes for this caseload
                        $getFutureChanges = mysqli_prepare($conn, "SELECT id, start_date, frequency, uos, iep_meeting, is_dismissal FROM case_changes WHERE case_id=? AND start_date>? ORDER BY start_date ASC");
                        mysqli_stmt_bind_param($getFutureChanges, "is", $case_id, $today);
                        if (mysqli_stmt_execute($getFutureChanges))
                        {
                            $getFutureChangesResults = mysqli_stmt_get_result($getFutureChanges);
                            if (mysqli_num_rows($getFutureChangesResults) > 0)
                            {
                                // initialize the counter to track changes
                                $change_counter = 1;

                                // set the previous units of service to the starting units of service
                                $previous_uos = $starting_uos;

                                while ($future_change = mysqli_fetch_array($getFutureChangesResults))
                                {
                                    // store change details locally
                                    $change_id = $future_change["id"];
                                    $start_date = date("n/j/Y", strtotime($future_change["start_date"]));
                                    $frequency = $future_change["frequency"];
                                    $uos = $future_change["uos"];
                                    $uos_change = $uos - $previous_uos;

                                    // create temporary array to store caseload change date
                                    $temp = [];
                                    $temp["change_id"] = $future_change["id"];
                                    $temp["change_date"] = $start_date;
                                    $temp["end_date"] = $end_date;
                                    $temp["frequency"] = $frequency;
                                    $temp["uos"] = $uos;
                                    $temp["uos_change"] = $uos_change;
                                    $temp["additional_iep"] = $future_change["iep_meeting"];
                                    $temp["is_dismissal"] = $future_change["is_dismissal"];

                                    // update the prior entries end date to the start date of the change
                                    if (isset($future_changes[$change_counter - 1]["end_date"])) { $future_changes[$change_counter - 1]["end_date"] = $start_date; }

                                    // add temporary array to changes array
                                    $future_changes[] = $temp;

                                    // set the previous units of service to this change increment units of service
                                    $previous_uos = $uos;

                                    // increment change counter
                                    $change_counter++;
                                }
                            }
                        }

                        // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                        $ieps_uos = $evals_uos = 0;
                        if (is_numeric($extra_ieps) && $extra_ieps > 0) { $ieps_uos = (12 * $extra_ieps); }
                        if (is_numeric($extra_evals) && $extra_evals > 0) { $evals_uos = (16 * $extra_evals); }

                        // calculate the number of uos to remove if the IEP was completed before a transfer and we must remove
                        $remove_uos = 0;
                        if ($remove_iep == 1) { $remove_uos = -12; }

                        // initialize variable to store total prorated units of service
                        $total_prorated_uos = 0;

                        // get caseload name
                        $therapist_id = getCaseloadTherapist($conn, $caseload_id);
                        $therapist_name = getUserDisplayName($conn, $therapist_id);

                        // get assistant
                        $assistant_name = getAssistantName($conn, $assistant_id);
                        if (trim($assistant_name) == "") { $assistant_name = "None"; }

                        ?>
                            <!-- View Case Changes Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="viewCaseloadChangesModal" data-bs-backdrop="static" aria-labelledby="viewCaseloadChangesModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-xl" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="viewCaseloadChangesModalLabel">View Case Changes</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Student -->
                                                <div class="form-group col-4 px-2">
                                                    <label for="case_changes-student">Student:</label>
                                                    <input class="form-control w-100" id="case_changes-student" name="case_changes-student" value="<?php echo $student_name; ?>" readonly disabled>
                                                </div>

                                                <!-- Therapist -->
                                                <div class="form-group col-4 px-2">
                                                    <label for="case_changes-student">Therapist:</label>
                                                    <input class="form-control w-100" id="case_changes-therapist" name="case_changes-therapist" value="<?php echo $therapist_name; ?>" readonly disabled>
                                                </div>

                                                <!-- Assistant -->
                                                <div class="form-group col-4 px-2">
                                                    <label for="case_changes-student">Assistant:</label>
                                                    <input class="form-control w-100" id="case_changes-assistant" name="case_changes-assistant" value="<?php echo $assistant_name; ?>" readonly disabled>
                                                </div>
                                            </div>
                                            
                                            <!-- Caseload Changes -->
                                            <div class="row">
                                                <table class="report_table w-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center" colspan="8">Current</th>
                                                        </tr>
                                                        <tr>
                                                            <th>Change</th>
                                                            <th>Change Date</th>
                                                            <th>IEP Meeting</th>
                                                            <th>Frequency</th>
                                                            <th>New UOS</th>
                                                            <th>UOS +/-</th>
                                                            <th>UOS Proration</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <?php
                                                            for ($c = 0; $c < count($changes); $c++)
                                                            {
                                                                // store change details locally
                                                                $change_id = $changes[$c]["change_id"];
                                                                $change_date = $changes[$c]["change_date"];
                                                                $end_date = $changes[$c]["end_date"];
                                                                $frequency = $changes[$c]["frequency"];
                                                                $units = $changes[$c]["uos"];
                                                                $units_change = $changes[$c]["uos_change"];
                                                                $is_dismissal = $changes[$c]["is_dismissal"];
                                                                $additional_iep = $changes[$c]["additional_iep"];

                                                                // calculate number of days in "cycle"
                                                                $days_in_cycle = getDaysBetween($end_date, $change_date);

                                                                // calculate percentage of days in current cycle
                                                                $percentage_of_total = $days_in_cycle / $days_in_year;

                                                                // calculate the prorated units
                                                                if ($c == 0 && count($changes) == 1)
                                                                {
                                                                    $prorated_uos = ($percentage_of_total * $units);
                                                                }
                                                                else
                                                                {
                                                                    $prorated_uos = ($percentage_of_total * ($units - 12) + 12);
                                                                }

                                                                if ($additional_iep == 0 && $c > 0) { $prorated_uos -= 12; }

                                                                // add prorated uos to array
                                                                $changes[$c]["prorated_uos"] = $prorated_uos;

                                                                // add prorated uos to total
                                                                $total_prorated_uos += $prorated_uos;
                                                                $most_recent_live_prorated_uos = $prorated_uos;

                                                                // if the change was not the initial caseload
                                                                if ($c != 0)
                                                                {
                                                                    // display the table row
                                                                    ?>
                                                                        <tr>
                                                                            <td><?php echo $c; ?></td>
                                                                            <td><?php echo $change_date; ?></td>
                                                                            <td><?php if ($additional_iep == 1) { echo "Yes"; } else { echo "No"; } ?></td>
                                                                            <td>
                                                                                <?php if ($dismissed == 1 && $is_dismissal == 1) { 
                                                                                    echo "<span class=\"missing-field fw-bold\">Dismissed</span>"; 
                                                                                } else {
                                                                                     echo $frequency; 
                                                                                } ?>
                                                                            </td>
                                                                            <td><?php echo $units; ?></td>
                                                                            <td><?php echo $units_change; ?></td>
                                                                            <td><?php echo round($prorated_uos, 2); ?></td>
                                                                            <td>
                                                                                <div class="d-flex justify-content-end">
                                                                                <?php if (($is_dismissal != 1 && $is_editable === true) && (($dismissed == 1 && $view_all === true) || $dismissed == 0)) { // only show actions if student has not been dismissed ?> 
                                                                                    <button class="btn btn-primary btn-sm mx-1" type="button" onclick="getEditCaseChangeModal(<?php echo $change_id; ?>);"><i class="fa-solid fa-pencil"></i></button>
                                                                                    <?php if ($view_all === true) { ?>
                                                                                        <button class="btn btn-danger btn-sm mx-1" type="button" onclick="removeCaseChange(<?php echo $change_id; ?>);"><i class="fa-solid fa-trash-can"></i></button>
                                                                                    <?php } ?>
                                                                                <?php } else if ($dismissed == 1 && $is_dismissal == 1 && $is_editable === true) { ?>
                                                                                    <button class="btn btn-primary btn-sm mx-1" type="button" onclick="getEditDismissalModal(<?php echo $case_id; ?>);"><i class="fa-solid fa-pencil"></i></button>
                                                                                <?php } ?>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    <?php
                                                                }
                                                                // the change is the inital caseload details
                                                                else
                                                                {
                                                                    // display the table row
                                                                    ?>
                                                                        <tr>
                                                                            <td><b>Start Date</td>
                                                                            <td><?php echo $change_date; ?></td>
                                                                            <td>Yes</td>
                                                                            <td><?php echo $frequency; ?></td>
                                                                            <td><?php echo $units; ?></td>
                                                                            <td>-</td>
                                                                            <td><?php echo round($prorated_uos, 2); ?></td>
                                                                            <td></td>
                                                                        </tr>
                                                                    <?php
                                                                }
                                                            }
                                                        ?>

                                                        <tr>
                                                            <th colspan="8"></th>
                                                        </tr>

                                                        <?php if ($remove_iep == 1) { ?>
                                                            <tr>
                                                                <td><b>*TRANSFER</b> - Remove IEP</td>
                                                                <td></td>
                                                                <td></td>
                                                                <td>1</td>
                                                                <td>-12</td>
                                                                <td></td>
                                                                <td>-12</td>
                                                                <td></td>
                                                            </tr>
                                                        <?php } ?>

                                                        <tr>
                                                            <td>Extra IEPs</td>
                                                            <td></td>
                                                            <td></td>
                                                            <td><input class="form-control py-1 px-2" type='number' id='case_changes-extra_ieps' value='<?php echo $extra_ieps; ?>' min="0" onblur='updateExtraIEPs(<?php echo $case_id; ?>, this.value);'></td>
                                                            <td><?php echo $ieps_uos; ?></td>
                                                            <td></td>
                                                            <td><?php echo $ieps_uos; ?></td>
                                                            <td></td>
                                                        </tr>

                                                        <tr>
                                                            <td>Extra Evaluations</td>
                                                            <td></td>
                                                            <td></td>
                                                            <td><input class="form-control py-1 px-2" type='number' id='case_changes-extra_evals' value='<?php echo $extra_evals; ?>' min="0" onblur='updateExtraEvals(<?php echo $case_id; ?>, this.value);'></td>
                                                            <td><?php echo $evals_uos; ?></td>
                                                            <td></td>
                                                            <td><?php echo $evals_uos; ?></td>
                                                            <td></td>
                                                        </tr>

                                                        <?php if ($dismissed == 1) { ?>
                                                            <tr>
                                                                <td><b>Dismissal</b></td>
                                                                <td></td>
                                                                <td><?php if ($dismissal_iep == 1) { echo "Yes"; } else { echo "No"; } ?></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td><?php if ($dismissal_iep == 1) { echo "+12"; } else { echo "0"; } ?></td>
                                                                <td></td>
                                                            </tr>
                                                        <?php } ?>

                                                        <tr>
                                                            <td>Manual Adjustments</td>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                            <td>
                                                                <?php 
                                                                    if (checkUserPermission($conn, "VIEW_CASELOADS_ALL")) {
                                                                        echo "<input class='form-control py-1 px-2' type='number' id='case_changes-uos_adjustment' value='".$uos_adjustment."' onblur='updateUOSAdjustment(".$case_id.", this.value);'>";
                                                                    } else {
                                                                        echo $uos_adjustment;
                                                                    }
                                                                ?>
                                                            </td>
                                                            <td></td>
                                                        </tr>

                                                        <?php
                                                            // calculate the total prorated change of units
                                                            $prorated_change = ($total_prorated_uos + $evals_uos + $ieps_uos) - $starting_uos + $uos_adjustment;
                                                            if ($dismissed == 1 && $dismissal_iep == 1) { $prorated_change += 12; }

                                                            // calculate EOY units of service
                                                            $EOY_units = $total_prorated_uos + $evals_uos + $ieps_uos + $remove_uos + $uos_adjustment;
                                                            if ($dismissed == 1 && $dismissal_iep == 1) { $EOY_units += 12; }
                                                        ?>

                                                        <tr>
                                                            <th colspan="6">Current UOS to Bill</th>
                                                            <th><?php echo ceil($EOY_units); ?></th>
                                                            <th></th>
                                                        </tr>

                                                        <?php if (count($future_changes) > 1) { ?>
                                                            <tr>
                                                                <th class="text-center" colspan="8">Planned/Future Changes</th>
                                                            </tr>

                                                            <?php
                                                                for ($c = 0; $c < count($future_changes); $c++)
                                                                {
                                                                    // store change details locally
                                                                    $change_id = $future_changes[$c]["change_id"];
                                                                    $change_date = $future_changes[$c]["change_date"];
                                                                    $end_date = $future_changes[$c]["end_date"];
                                                                    $frequency = $future_changes[$c]["frequency"];
                                                                    $units = $future_changes[$c]["uos"];
                                                                    $units_change = $future_changes[$c]["uos_change"];
                                                                    $is_dismissal = $future_changes[$c]["is_dismissal"];
                                                                    $additional_iep = $future_changes[$c]["additional_iep"];

                                                                    // calculate number of days in "cycle"
                                                                    $days_in_cycle = getDaysBetween($end_date, $change_date);

                                                                    // calculate percentage of days in current cycle
                                                                    $percentage_of_total = $days_in_cycle / $days_in_year;

                                                                    // calculate the prorated units
                                                                    if ($c == 0 && count($future_changes) == 1)
                                                                    {
                                                                        $prorated_uos = ($percentage_of_total * $units);
                                                                    }
                                                                    else
                                                                    {
                                                                        $prorated_uos = ($percentage_of_total * ($units - 12) + 12);
                                                                    }

                                                                    if ($additional_iep == 0 && $c > 0) { $prorated_uos -= 12; }

                                                                    // add prorated uos to array
                                                                    $future_changes[$c]["prorated_uos"] = $prorated_uos;

                                                                    // add prorated uos to total
                                                                    $total_prorated_uos += $prorated_uos;

                                                                    // if the change was not the initial caseload
                                                                    if ($c != 0)
                                                                    {
                                                                        // display the table row
                                                                        ?>
                                                                            <tr>
                                                                                <td><?php echo $c; ?></td>
                                                                                <td><?php echo $change_date; ?></td>
                                                                                <td><?php if ($additional_iep == 1) { echo "Yes"; } else { echo "No"; } ?></td>
                                                                                <td><?php echo $frequency; ?></td>
                                                                                <td><?php echo $units; ?></td>
                                                                                <td><?php echo $units_change; ?></td>
                                                                                <td><?php echo round($prorated_uos, 2); ?></td>
                                                                                <td>
                                                                                    <div class="d-flex justify-content-end">
                                                                                    <?php if (($is_dismissal != 1 && $is_editable === true) && (($dismissed == 1 && $view_all === true) || $dismissed == 0)) { // only show actions if student has not been dismissed ?> 
                                                                                        <button class="btn btn-primary btn-sm mx-1" type="button" onclick="getEditCaseChangeModal(<?php echo $change_id; ?>);"><i class="fa-solid fa-pencil"></i></button>
                                                                                        <?php if ($view_all === true) { ?>
                                                                                            <button class="btn btn-danger btn-sm mx-1" type="button" onclick="removeCaseChange(<?php echo $change_id; ?>);"><i class="fa-solid fa-trash-can"></i></button>
                                                                                        <?php } ?>
                                                                                    <?php } else if ($dismissed == 1 && $is_dismissal == 1 && $is_editable === true) { ?>
                                                                                        <button class="btn btn-primary btn-sm mx-1" type="button" onclick="getEditDismissalModal(<?php echo $case_id; ?>);"><i class="fa-solid fa-pencil"></i></button>
                                                                                    <?php } ?>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        <?php
                                                                    }
                                                                    // the change is the inital caseload details
                                                                    else
                                                                    {
                                                                        // display the table row
                                                                        ?>
                                                                            <tr>
                                                                                <td><b>Current</td>
                                                                                <td><?php echo $change_date; ?></td>
                                                                                <td>Yes</td>
                                                                                <td><?php echo $frequency; ?></td>
                                                                                <td><?php echo $units; ?></td>
                                                                                <td>-</td>
                                                                                <td><?php echo round($prorated_uos, 2); ?></td>
                                                                                <td></td>
                                                                            </tr>
                                                                        <?php
                                                                    }
                                                                }
                                                            ?>
                                                        <?php } ?>
                                                    </tbody>

                                                    <?php if (count($future_changes) > 1) { ?>
                                                        <?php
                                                            // calculate EOY units of service
                                                            $EOY_units = $total_prorated_uos - $most_recent_live_prorated_uos + $evals_uos + $ieps_uos + $remove_uos + $uos_adjustment;;
                                                            if ($dismissed == 1 && $dismissal_iep == 1) { $EOY_units += 12; }
                                                        ?>

                                                        <tfoot>
                                                            <tr>
                                                                <th colspan="6">Projected End-Of-Year UOS to Bill</th>
                                                                <th><?php echo ceil($EOY_units); ?></th>
                                                                <th></th>
                                                            </tr>
                                                        </tfoot>
                                                    <?php } ?>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <?php if ($dismissed == 0 && $is_editable === true) { ?>
                                                <button type="button" class="btn btn-danger" onclick="getDismissStudentModal(<?php echo $case_id; ?>);"><i class="fa-solid fa-door-open"></i> Dismiss Student</button>
                                                <button type="button" class="btn btn-primary" onclick="getAddCaseChangeModal(<?php echo $case_id; ?>);"><i class="fa-solid fa-plus"></i> Add Change</button>
                                            <?php } ?>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End View Case Changes Modal -->
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>