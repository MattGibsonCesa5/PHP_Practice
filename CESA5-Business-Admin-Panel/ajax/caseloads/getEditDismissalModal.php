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
                        $student_id = $case["student_id"];
                        $dismissal_date = date("n/j/Y", strtotime($case["end_date"]));
                        $dismissal_iep = $case["dismissal_iep"];
                        $reason_id = $case["dismissal_reasoning_id"];
                        $eval_month = $case["medicaid_evaluation_month"];
                        $medicaid_billing_done = $case["medicaid_billing_done"];

                        // get caseload settings
                        $medicaid = isCaseloadMedicaid($conn, $caseload_id);

                        // get student display name based on ID
                        $student_name = getStudentDisplayName($conn, $student_id);

                        ?>
                            <!-- Dismiss Student Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="editDismissStudentModal" data-bs-backdrop="static" aria-labelledby="editDismissStudentModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editDismissStudentModalLabel">Edit Student Dismissal</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Dismissal Date -->
                                                <div class="form-group col-11">
                                                    <label for="edit-dismiss_student-dismissal_date"><span class="required-field">*</span> Dismissal Date:</label>
                                                    <input type="text" class="form-control w-100" id="edit-dismiss_student-dismissal_date" name="edit-dismiss_student-dismissal_date" value="<?php echo date("m/d/Y", strtotime($dismissal_date)); ?>" autocomplete="off" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Dismissal Reasoning -->
                                                <div class="form-group col-11">
                                                    <label for="edit-dismiss_student-reason">Reason For Dismissal:</label>
                                                    <select class="form-select w-100" id="edit-dismiss_student-reason" name="edit-dismiss_student-reason" required>
                                                        <option></option>
                                                        <?php
                                                            $getReasons = mysqli_query($conn, "SELECT id, reason FROM caseload_dismissal_reasonings WHERE dnq=0");
                                                            if (mysqli_num_rows($getReasons) > 0)
                                                            {
                                                                while ($reason = mysqli_fetch_array($getReasons))
                                                                {
                                                                    if ($reason_id == $reason["id"])
                                                                    {
                                                                        echo "<option value='".$reason["id"]."' selected>".$reason["reason"]."</option>";
                                                                    }
                                                                    else
                                                                    {
                                                                        echo "<option value='".$reason["id"]."'>".$reason["reason"]."</option>";
                                                                    }
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <?php if ($medicaid === true) { ?>
                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Dismissal Reasoning -->
                                                    <div class="form-group col-11">
                                                        <label for="edit-dismiss_student-medicaid_billing"><span class="required-field">*</span> Medicaid Billing Completed?</label>
                                                        <select class="form-select w-100" id="edit-dismiss_student-medicaid_billing" name="edit-dismiss_student-medicaid_billing" required>
                                                            <option value=""></option>
                                                            <option value="0" <?php if ($medicaid_billing_done == 0) { echo "selected"; } ?>>N/A</option>
                                                            <option value="1" <?php if ($medicaid_billing_done == 1) { echo "selected"; } ?>>Yes</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-row d-flex justify-content-center align-items-center my-3" id="edit-eval_month-div">
                                                    <!-- Evaluation Month (Medicaid) -->
                                                    <div class="form-group col-11">
                                                        <label for="edit-dismiss_student-eval_month"><span class="required-field">*</span> Month Evaluation Started (Medicaid):</label>
                                                        <div class="input-group h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                            </div>
                                                            <select class="form-select" id="edit-dismiss_student-eval_month" name="edit-dismiss_student-eval_month" required>
                                                                <option value="0" <?php if ($eval_month == 0) { echo "selected"; } ?>>N/A</option>
                                                                <option value="1" <?php if ($eval_month == 1) { echo "selected"; } ?>>January</option>
                                                                <option value="2" <?php if ($eval_month == 2) { echo "selected"; } ?>>February</option>
                                                                <option value="3" <?php if ($eval_month == 3) { echo "selected"; } ?>>March</option>
                                                                <option value="4" <?php if ($eval_month == 4) { echo "selected"; } ?>>April</option>
                                                                <option value="5" <?php if ($eval_month == 5) { echo "selected"; } ?>>May</option>
                                                                <option value="6" <?php if ($eval_month == 6) { echo "selected"; } ?>>June</option>
                                                                <option value="7" <?php if ($eval_month == 7) { echo "selected"; } ?>>July</option>
                                                                <option value="8" <?php if ($eval_month == 8) { echo "selected"; } ?>>August</option>
                                                                <option value="9" <?php if ($eval_month == 9) { echo "selected"; } ?>>September</option>
                                                                <option value="10" <?php if ($eval_month == 10) { echo "selected"; } ?>>October</option>
                                                                <option value="11" <?php if ($eval_month == 11) { echo "selected"; } ?>>November</option>
                                                                <option value="12" <?php if ($eval_month == 12) { echo "selected"; } ?>>December</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } else { ?>
                                                <div class="d-none">
                                                    <input value="0" type="hidden" class="d-none" id="edit-dismiss_student-medicaid_billing" name="edit-dismiss_student-medicaid_billing" aria-hidden="true" required>
                                                    <input class="d-none" type="hidden" id="edit-dismiss_student-eval_month" name="edit-dismiss_student-eval_month" value="0" aria-hidden="true">
                                                </div>
                                            <?php } ?>

                                            <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                <!-- Additional IEP meeting -->
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="edit-dismiss_student-additional_iep" name="edit-dismiss_student-additional_iep" <?php if ($dismissal_iep == 1) { echo "checked"; } ?>>
                                                    <label class="form-check-label" for="edit-dismiss_student-additional_iep">Was an additional IEP meeting required?</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="editStudentDismissal(<?php echo $case_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Edit Dismissal</button>
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