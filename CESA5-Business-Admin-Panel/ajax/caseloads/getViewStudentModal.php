<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // get period name from POST
        if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
        if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
        if (isset($_POST["student_id"]) && $_POST["student_id"] <> "") { $student_id = $_POST["student_id"]; } else { $student_id = null; }

        if ($period != null && $student_id != null && $case_id != null)
        {
            if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
            {
                if (verifyCase($conn, $case_id)) // verify the case exists
                {
                    if (verifyStudent($conn, $student_id)) // verify the student exists
                    {
                        // get the caseload for the case
                        $caseload_id = getCaseloadID($conn, $case_id);

                        // get the student's name
                        $student_name = getStudentDisplayName($conn, $student_id);

                        // get the caseload name
                        $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                        // evaluation method column
                        $evaluation_method = getCaseEvaluationMethod($conn, $case_id);
                        $display_evaluation_method = "";
                        // if ($evaluation_method == 0) { $display_evaluation_method = "Pending Evaluation"; }
                        if ($evaluation_method == 1) { $display_evaluation_method = "Regular"; }
                        if ($evaluation_method == 2) { $display_evaluation_method = "Evaluation Only"; }

                        // build the enrollment type column
                        $enrollment_type = getCaseEnrollmentType($conn, $case_id);
                        $display_enrollment_type = "";
                        if ($enrollment_type == 1) { $display_enrollment_type = "Resident"; }
                        if ($enrollment_type == 2) { $display_enrollment_type = "Open Enrolled"; }
                        if ($enrollment_type == 3) { $display_enrollment_type = "Placed"; }
                        if ($enrollment_type == 4) { $display_enrollment_type = "66.0301"; }
                        if ($enrollment_type == 5) { $display_enrollment_type = "Other"; }

                        // build the educational plan column
                        $educational_plan = getCaseEducationalPlan($conn, $case_id);
                        $display_educational_plan = "";
                        if ($educational_plan == 1) { $display_educational_plan = "504"; }
                        if ($educational_plan == 2) { $display_educational_plan = "IEP"; }
                        if ($educational_plan == 3) { $display_educational_plan = "ISP"; }
                        if ($educational_plan == 4) { $display_educational_plan = "Other"; }

                        // get the case's billing notes
                        $billing_notes = getCaseBillingNotes($conn, $case_id);
                        
                        // get the end of year units to be billed
                        $units_to_bill = getProratedUOS($conn, $case_id);

                        ?>
                            <!-- View Student Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="viewStudentModal" data-bs-backdrop="static" aria-labelledby="viewStudentModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="viewStudentModalLabel"><?php echo $student_name; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body p-0">
                                            <!-- Divider -->
                                            <div class="row m-0 p-0">
                                                <h1 class="col-9 col-sm-9 col-md-8 col-lg-8 col-xl-6 col-xxl-6 modal-divider my-2 py-2 px-3">
                                                    <div class="d-inline float-end">Case Details</div>
                                                </h1>
                                            </div>

                                            <div class="px-3">
                                                <p class="mb-2" style="font-size: 18px;">
                                                    <b>Year: </b><?php echo $period; ?><br>
                                                    <b>Caseload Name: </b><?php echo $caseload_name; ?><br>
                                                    <b>Evaluation Method: </b><?php echo $display_evaluation_method; ?><br>
                                                    <b>Enrollment Type: </b><?php echo $display_enrollment_type; ?><br>
                                                    <b>Educational Plan: </b><?php echo $display_educational_plan; ?><br>
                                                    <b>Billing Notes: </b><?php echo $billing_notes; ?><br>
                                                    <b>Units Of Service To Bill: </b><?php echo $units_to_bill; ?><br>
                                                </p>
                                            </div>

                                            <!-- Divider -->
                                            <div class="row m-0 p-0">
                                                <h1 class="col-9 col-sm-9 col-md-8 col-lg-8 col-xl-6 col-xxl-6 modal-divider my-2 py-2 px-3">
                                                    <div class="d-inline float-end">Other Services</div>
                                                </h1>
                                            </div>

                                            <div class="px-3 pb-3">
                                                <p class="mb-2" style="font-size: 18px;">
                                                    <?php
                                                        $getServices = mysqli_query($conn, "SELECT id, name FROM caseload_categories ORDER BY name ASC");
                                                        if (mysqli_num_rows($getServices) > 0)
                                                        {
                                                            while ($service = mysqli_fetch_assoc($getServices))
                                                            {
                                                                // store service/category details locally
                                                                $category_id = $service["id"];
                                                                $category_name = $service["name"];

                                                                // find cases/caseloads for this service/category
                                                                $getCaseloads = mysqli_prepare($conn, "SELECT c.caseload_id, cl.employee_id, c.assistant_id, c.active FROM caseloads cl
                                                                                                    JOIN cases c ON cl.id=c.caseload_id
                                                                                                    WHERE c.student_id=? AND c.period_id=? AND cl.category_id=?");
                                                                mysqli_stmt_bind_param($getCaseloads, "iii", $student_id, $period_id, $category_id);
                                                                if (mysqli_stmt_execute($getCaseloads))
                                                                {
                                                                    $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                                                                    if (mysqli_num_rows($getCaseloadsResults) > 0) // student is found within active caseloads
                                                                    {
                                                                        // student enrolled in this service, build service header
                                                                        echo "<h5 class=\"mb-1\">".$category_name."</h5>
                                                                        <ul>";
                                                                            while ($caseload = mysqli_fetch_array($getCaseloadsResults))
                                                                            {
                                                                                // build the list item
                                                                                echo "<li>";

                                                                                // store caseload details locally
                                                                                $caseload_id = $caseload["caseload_id"];
                                                                                $therapist_id = $caseload["employee_id"];
                                                                                $assistant_id = $caseload["assistant_id"];
                                                                                $active = $caseload["active"];

                                                                                // get the caseload name
                                                                                $caseload_name = getUserDisplayName($conn, $therapist_id);

                                                                                // get assistant
                                                                                $assistant_name = getAssistantName($conn, $assistant_id);

                                                                                // display the caseload the student is in
                                                                                echo $caseload_name;
                                                                                if (trim($assistant_name) <> "") { echo " (Assistant: ".$assistant_name.")"; }
                                                                                
                                                                                // end list item
                                                                                echo "</li>";
                                                                            }
                                                                        echo "</ul>";
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End View Student Modal -->
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>