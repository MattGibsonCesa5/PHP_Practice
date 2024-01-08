<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_CASELOADS") && (checkUserPermission($conn, "VIEW_CASELOADS_ALL") || checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED")))
        {
            // get parameters from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
            {
                if (verifyCase($conn, $case_id))
                {
                    // initialize an array to store all periods; then get all periods and store in the array
                    $periods = [];
                    $getPeriods = mysqli_prepare($conn, "SELECT id, name, active, start_date, end_date, caseload_term_end FROM `periods` WHERE id=? ORDER BY active DESC, name ASC");
                    mysqli_stmt_bind_param($getPeriods, "i", $period_id);
                    if (mysqli_stmt_execute($getPeriods))
                    {
                        $getPeriodsResult = mysqli_stmt_get_result($getPeriods);
                        if (mysqli_num_rows($getPeriodsResult) > 0) // periods exist
                        {
                            $periodDetails = mysqli_fetch_array($getPeriodsResult);
                            $active_period_label = $periodDetails["name"];
                            $active_start_date = date("m/d/Y", strtotime($periodDetails["start_date"]));
                            $active_end_date = date("m/d/Y", strtotime($periodDetails["end_date"])); 
                            $term_end_date = date("m/d/Y", strtotime($periodDetails["caseload_term_end"]));
                        }
                    }

                    // get the student's current data
                    $getCaseload = mysqli_prepare($conn, "SELECT * FROM cases WHERE id=?");
                    mysqli_stmt_bind_param($getCaseload, "i", $case_id);
                    if (mysqli_stmt_execute($getCaseload))
                    {
                        $getCaseloadResult = mysqli_stmt_get_result($getCaseload);
                        if (mysqli_num_rows($getCaseloadResult) > 0)
                        {
                            // store caseload details locally
                            $caseload = mysqli_fetch_array($getCaseloadResult);
                            $case_id = $caseload["id"];
                            $caseload_id = $caseload["caseload_id"];
                            $student_id = $caseload["student_id"];
                            if (isset($caseload["start_date"])) { $start_date = date("m/d/Y", strtotime($caseload["start_date"])); } else { $start_date = $active_start_date; }
                            if (isset($caseload["end_date"])) { $end_date = date("m/d/Y",  strtotime($caseload["end_date"])); } else { $end_date = $active_end_date; }
                            $frequency = $caseload["frequency"];
                            $units = $caseload["estimated_uos"];

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

                            // get therapist details
                            $therapist_id = getCaseloadTherapist($conn, $caseload_id);
                            $therapist_name = getUserDisplayName($conn, $therapist_id);
                            $category_id = getCaseloadCategory($conn, $caseload_id);
                            $category_name = getCaseloadCategoryName($conn, $category_id);
                            if ($category_name == "" || $category_name == null) { $category_name = "?"; }

                            ?>
                                <!-- Request Caseload Transfer Modal -->
                                <div class="modal fade" tabindex="-1" role="dialog" id="requestCaseloadTransferModal" data-bs-backdrop="static" aria-labelledby="requestCaseloadTransferModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header primary-modal-header">
                                                <h5 class="modal-title primary-modal-title" id="requestCaseloadTransferModalLabel">Request Caseload Transfer</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <input type="hidden" id="transfer_request-case_id" name="transfer_request-case_id" value="<?php echo $case_id; ?>" aria-hidden="true">

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Student -->
                                                    <div class="form-group col-11">
                                                        <label for="transfer_request-student">Student:</label>
                                                        <input type="text" class="form-control" id="transfer_request-student" name="transfer_request-student" value="<?php echo $student_name; ?>" readonly disabled>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Old Therapist -->
                                                    <div class="form-group col-11">
                                                        <label for="transfer_request-previous_therapist">Current Caseload:</label>
                                                        <input type="text" class="form-control" id="transfer_request-previous_therapist" name="transfer_request-previous_therapist" value="<?php echo $therapist_name . " (".$category_name.")"; ?>" readonly disabled>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                                    <!-- New Therapist -->
                                                    <div class="form-group col-11">
                                                        <label for="transfer_request-new_caseload">Transfer To Caseload:</label>
                                                        <select id="transfer_request-new_caseload" name="transfer_request-new_caseload" aria-describedby="caseloadHelpBlock">
                                                            <?php
                                                                // get a list of all caseload categories
                                                                $getCategories = mysqli_query($conn, "SELECT * FROM caseload_categories ORDER BY name ASC");
                                                                if (mysqli_num_rows($getCategories) > 0)
                                                                {
                                                                    // for each category, attempt to bill districts
                                                                    while ($category = mysqli_fetch_array($getCategories))
                                                                    {
                                                                        // store category details locally
                                                                        $category_id = $category["id"];
                                                                        $category_name = $category["name"];

                                                                        // create the option group
                                                                        echo "<optgroup label='".$category_name."'>";

                                                                        $getCaseloads = mysqli_prepare($conn, "SELECT c.id AS caseload_id FROM caseloads c 
                                                                                                            JOIN users u ON u.id=c.employee_id
                                                                                                            LEFT JOIN caseloads_status cs ON c.id=cs.caseload_id
                                                                                                            WHERE cs.status=1 AND cs.period_id=? AND c.category_id=?
                                                                                                            ORDER BY u.lname ASC, u.fname ASC");
                                                                        mysqli_stmt_bind_param($getCaseloads, "ii", $period_id, $category_id);
                                                                        if (mysqli_stmt_execute($getCaseloads))
                                                                        {
                                                                            $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                                                                            if (mysqli_num_rows($getCaseloadsResults) > 0) // caseloads found
                                                                            {
                                                                                while ($caseloads = mysqli_fetch_array($getCaseloadsResults))
                                                                                {
                                                                                    // store caseload details locally
                                                                                    $new_caseload_id = $caseloads["caseload_id"];

                                                                                    // get caseload display name
                                                                                    $caseload_name = getCaseloadDisplayName($conn, $new_caseload_id);

                                                                                    // create the option
                                                                                    echo "<option value='".$new_caseload_id."'>".$caseload_name."</option>";
                                                                                }
                                                                            }
                                                                        }

                                                                        // close the option group
                                                                        echo "</optgroup>";
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div id="caseloadHelpBlock" class="form-text px-3">
                                                    If you are unsure of which caseload to transfer the student into, you can leave it blank.
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Transfer Date -->
                                                    <div class="form-group col-11">
                                                        <label for="transfer_request-transfer_date"><span class="required-field">*</span> Transfer Date:</label>
                                                        <input type="text" class="form-control w-100" id="transfer_request-transfer_date" name="transfer_request-transfer_date" value="<?php echo date("m/d/Y"); ?>" aria-describedby="dateHelpBlock" required>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Comments -->
                                                    <div class="form-group col-11">
                                                        <label for="transfer_request-comments">Comments:</label>
                                                        <textarea type="text" class="form-control" id="transfer_request-comments" name="transfer_request-comments" rows="3"></textarea>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="transfer_request-IEP_status" name="transfer_request-IEP_status">
                                                        <label class="form-check-label" for="transfer_request-IEP_status">Was the IEP completed?</label>
                                                    </div>
                                                </div>

                                                <!-- Required Field Indicator -->
                                                <div class="row justify-content-center">
                                                    <div class="col-11 text-center fst-italic">
                                                        <span class="required-field">*</span> indicates a required field
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-primary" onclick="requestCaseloadTransfer(<?php echo $case_id; ?>);"><i class="fa-solid fa-right-left"></i> Request Caseload Transfer</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Request Caseload Transfer Modal -->
                            <?php
                        }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>