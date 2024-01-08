<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") || checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED"))
        {
            // get the caseload ID from POST
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }

            if (verifyCase($conn, $case_id))
            {
                // get the caseload's current data
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
                        $period_id = $caseload["period_id"];
                        $student_id = $caseload["student_id"];
                        if (isset($caseload["therapist_id"])) { $therapist_id = $caseload["therapist_id"]; } else { $therapist_id = "-"; }
                        $starting_uos = $caseload["estimated_uos"];
                        $starting_frequency = $caseload["frequency"];
                        $start_date = date("n/j/Y", strtotime($caseload["start_date"]));
                        $end_date = date("n/j/Y", strtotime($caseload["end_date"]));
                        $extra_ieps = $caseload["extra_ieps"];
                        $extra_evals = $caseload["extra_evaluations"];
                        $remove_iep = $caseload["remove_iep"];

                        // get student display name based on ID
                        $student_name = getStudentDisplayName($conn, $student_id);

                        // get caseload settings
                        $medicaid = isCaseloadMedicaid($conn, $caseload_id);

                        ?>
                            <!-- Dismiss Student Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="dismissStudentModal" data-bs-backdrop="static" aria-labelledby="dismissStudentModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="dismissStudentModalLabel">Dismiss Student</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body px-4">
                                            <p>
                                                Are you sure you want to dismiss <?php echo $student_name; ?> from the caseload? If so, please select a dismissal date, and if an
                                                additional IEP meeting was required in order to dismiss the student. Dismissing the student from your caseload will set the end date
                                                to the dismissal date, and then set the case to inactive.
                                            </p>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Dismissal Date -->
                                                <div class="form-group col-12">
                                                    <label for="dismiss_student-dismissal_date"><span class="required-field">*</span> Dismissal Date:</label>
                                                    <input type="text" class="form-control w-100" id="dismiss_student-dismissal_date" name="dismiss_student-dismissal_date" autocomplete="off" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Dismissal Reasoning -->
                                                <div class="form-group col-12">
                                                    <label for="dismiss_student-reason"><span class="required-field">*</span> Reason For Dismissal:</label>
                                                    <select class="form-select w-100" id="dismiss_student-reason" name="dismiss_student-reason">
                                                        <option></option>
                                                        <?php
                                                            $getReasons = mysqli_query($conn, "SELECT id, reason FROM caseload_dismissal_reasonings WHERE dnq=0");
                                                            if (mysqli_num_rows($getReasons) > 0)
                                                            {
                                                                while ($reason = mysqli_fetch_array($getReasons))
                                                                {
                                                                    echo "<option value='".$reason["id"]."'>".$reason["reason"]."</option>";
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <?php if ($medicaid === true) { ?>
                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Dismissal Reasoning -->
                                                    <div class="form-group col-12">
                                                        <label for="dismiss_student-medicaid_billing"><span class="required-field">*</span> Medicaid Billing Completed?</label>
                                                        <select class="form-select w-100" id="dismiss_student-medicaid_billing" name="dismiss_student-medicaid_billing" required>
                                                            <option value=""></option>
                                                            <option value="0">N/A</option>
                                                            <option value="1">Yes</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            <?php } else { ?>
                                                <input value="0" type="hidden" class="d-none" id="dismiss_student-medicaid_billing" name="dismiss_student-medicaid_billing" aria-hidden="true" required>
                                            <?php } ?>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Additional IEP meeting -->
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="dismiss_student-additional_iep" name="dismiss_student-additional_iep" checked>
                                                    <label class="form-check-label" for="dismiss_student-additional_iep">Was an additional IEP meeting required?</label>
                                                </div>
                                            </div>

                                            <div class="alert alert-danger">
                                                <p class="mb-2">
                                                    Dismissing a student from a caseload is <b>permanent</b>. Once dismissed, you'll not be able to reinstate the student back
                                                    into the caseload, without re-adding the student entirely.
                                                </p>

                                                <p class="mb-2">
                                                    You'll also lose the ability to add additional changes to the case.
                                                    You will only be able to edit the dismissal details.
                                                </p>

                                                <p class="mb-0">
                                                    If there were any planned, or future changes after the dismissal date, those will be <b>permanently deleted</b>.
                                                </p>
                                            </div>

                                            <!-- Required Field Indicator -->
                                            <div class="row justify-content-center">
                                                <div class="col-12 text-center fst-italic">
                                                    <span class="required-field">*</span> indicates a required field
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-danger" onclick="dismissStudent(<?php echo $case_id; ?>);"><i class="fa-solid fa-door-open"></i> Dismiss Student</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Dismiss Student Modal -->
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>