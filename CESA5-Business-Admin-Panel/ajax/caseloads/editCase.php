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

        if (checkUserPermission($conn, "EDIT_CASELOADS"))
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
            {
                // get parameters from POST
                if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
                if (isset($_POST["student_fname"]) && $_POST["student_fname"] <> "") { $student_fname = $_POST["student_fname"]; } else { $student_fname = null; }
                if (isset($_POST["student_lname"]) && $_POST["student_lname"] <> "") { $student_lname = $_POST["student_lname"]; } else { $student_lname = null; }
                if (isset($_POST["student_dob"]) && $_POST["student_dob"] <> "") { $student_dob = $_POST["student_dob"]; } else { $student_dob = null; }
                if (isset($_POST["start_date"]) && $_POST["start_date"] <> "") { $start_date = $_POST["start_date"]; } else { $start_date = null; }
                if (isset($_POST["eval_date"]) && $_POST["eval_date"] <> "") { $eval_date = $_POST["eval_date"]; } else { $eval_date = null; }
                if (isset($_POST["eval_month"]) && is_numeric($_POST["eval_month"])) { $eval_month = $_POST["eval_month"]; } else { $eval_month = 0; }
                if (isset($_POST["medicaid_billing"]) && $_POST["medicaid_billing"] <> "") { $medicaid_billing = $_POST["medicaid_billing"]; } else { $medicaid_billing = null; }
                if (isset($_POST["eval_only_reason"]) && is_numeric($_POST["eval_only_reason"])) { $eval_only_reason = $_POST["eval_only_reason"]; } else { $eval_only_reason = 0; }
                if (isset($_POST["assistant_id"]) && is_numeric($_POST["assistant_id"]) && $_POST["assistant_id"] > 0) { $assistant_id = $_POST["assistant_id"]; } else { $assistant_id = null; }
                if (isset($_POST["residency"]) && $_POST["residency"] <> "") { $residency = $_POST["residency"]; } else { $residency = null; }
                if (isset($_POST["district"]) && $_POST["district"] <> "") { $district = $_POST["district"]; } else { $district = null; }
                if (isset($_POST["school"]) && $_POST["school"] <> "") { $school = $_POST["school"]; } else { $school = null; }
                if (isset($_POST["grade_level"]) && $_POST["grade_level"] <> "") { $grade_level = $_POST["grade_level"]; } else { $grade_level = null; }
                if (isset($_POST["evaluation_method"]) && is_numeric($_POST["evaluation_method"])) { $evaluation_method = $_POST["evaluation_method"]; } else { $evaluation_method = 0; }
                if (isset($_POST["enrollment_type"]) && is_numeric($_POST["enrollment_type"])) { $enrollment_type = $_POST["enrollment_type"]; } else { $enrollment_type = 0; }
                if (isset($_POST["educational_plan"]) && is_numeric($_POST["educational_plan"])) { $educational_plan = $_POST["educational_plan"]; } else { $educational_plan = 0; }
                if (isset($_POST["SOY-frequency"]) && $_POST["SOY-frequency"] <> "") { $frequency = $_POST["SOY-frequency"]; } else { $frequency = null; }
                if (isset($_POST["SOY-UOS"]) && is_numeric($_POST["SOY-UOS"])) { $units = $_POST["SOY-UOS"]; } else { $units = 0; }
                if (isset($_POST["billing-to"]) && is_numeric($_POST["billing-to"])) { $billing_to = $_POST["billing-to"]; } else { $billing_to = 0; }
                if (isset($_POST["billing-type"]) && is_numeric($_POST["billing-type"])) { $billing_type = $_POST["billing-type"]; } else { $billing_type = 0; }
                if (isset($_POST["billing-notes"]) && $_POST["billing-notes"] <> "") { $billing_notes = $_POST["billing-notes"]; } else { $billing_notes = null; }
                if (isset($_POST["membership_days"]) && is_numeric($_POST["membership_days"])) { $membership_days = $_POST["membership_days"]; } else { $membership_days = 0; }
                if (isset($_POST["classroom_id"]) && is_numeric($_POST["classroom_id"])) { $classroom_id = $_POST["classroom_id"]; } else { $classroom_id = null; }
                if (isset($_POST["status"]) && is_numeric($_POST["status"])) { $status = $_POST["status"]; } else { $status = 0; }

                // override the status to inactive (0) if pending evaluation is the evaluation method selected
                if ($evaluation_method != 1 && $evaluation_method != 2) { $status = 0; }

                // verify the caseload exists
                if (verifyCase($conn, $case_id))
                {
                    // get the caseload ID for the case
                    $caseload_id = getCaseloadID($conn, $case_id);

                    if (verifyCaseload($conn, $caseload_id))
                    {
                        // get caseload settings
                        $frequencyEnabled = isCaseloadFrequencyEnabled($conn, $caseload_id);
                        $uosEnabled = isCaseloadUOSEnabled($conn, $caseload_id);
                        $uosRequired = isCaseloadUOSRequired($conn, $caseload_id);
                        $extraIEPsEnabled = isCaseloadExtraIEPSEnabled($conn, $caseload_id);
                        $extraEvalsEnabled = isCaseloadExtraEvalsEnabled($conn, $caseload_id);
                        $allowAssistants = isCaseloadAssistantsEnabled($conn, $caseload_id);
                        $medicaid = isCaseloadMedicaid($conn, $caseload_id);
                        $daysEnabled = isCaseloadDaysEnabled($conn, $caseload_id);

                        // validate frequency and uos based on caseload settings
                        if ($frequency == null || $frequency == "N/A" || $frequencyEnabled === false) { $frequency = null; }
                        if ($uosEnabled === false || $units < 0) { $units = 0; }
                        
                        // validate assistant
                        if ($allowAssistants === false || $assistant_id == -1) { $assistant_id = null; }

                        // validate evaluation month
                        if ($medicaid === true)
                        {
                            // verify month is valid
                            if ($eval_month < 0 || $eval_month > 12) { $eval_month = null; }
                        }
                        else { $eval_month = 0; } // set to N/A (0) if not Medicaid required

                        // validate membership days
                        if ($daysEnabled === false || $billing_type != 2) { $membership_days = 0; }

                        // validate eval only reasoning
                        if (!verifyDismissalReasoning($conn, $eval_only_reason) || $eval_only_reason == 0) { $eval_only_reason = null; }

                        if (($evaluation_method == 1 || $evaluation_method == 2) && $student_dob == null)
                        {
                            echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. If you select an evaluation method other than \"Pending Evaluation\", 
                                you are required to enter the student's date of birth.<br>";
                        }
                        else
                        {
                            // get the ID of the caseload's student
                            $student_id = checkCaseloadStudent($conn, $case_id); 

                            if ($student_id == -1) // student currently does not exist
                            {
                                // verify the student exists
                                if ($student_id = checkForStudent($conn, $student_fname, $student_lname, $student_dob))
                                {
                                    // if student ID is -1; student does not yet exist, add new student if date of birth is provided
                                    if ($student_id == -1)
                                    {
                                        if (isset($student_dob) && $student_dob != null)
                                        {
                                            // add new student
                                            addStudent($conn, $student_fname, $student_lname, 1, $student_dob);

                                            // get the new ID for the student
                                            $student_id = checkForStudent($conn, $student_fname, $student_lname, $student_dob);
                                        }
                                    }
                                }
                            }

                            if (($evaluation_method == 2 && ($medicaid_billing == 0 || $medicaid_billing == 1)) || $evaluation_method == 1)
                            {
                                // verify the residency exists
                                if (verifyCustomer($conn, $residency) || (($evaluation_method == 1 && $evaluation_method != 2) && $residency == null))
                                {
                                    // verify the district exists
                                    if (verifyCustomer($conn, $district) || (($evaluation_method == 1 && $evaluation_method != 2) && $district == null))
                                    {
                                        // verify the school exists
                                        if ((verifySchool($conn, $school) || ($school == -1 || $school == -2 || $school == -3)) || (($evaluation_method == 1 && $evaluation_method != 2) && $school == null))
                                        {
                                            // verify the student exists
                                            if ($student_id != -1 && verifyStudent($conn, $student_id))
                                            {
                                                // validate parameters based on evaluation method
                                                if ($evaluation_method == 1) // Regular
                                                {
                                                    // convert the start and end dates to the correct database format
                                                    $DB_start_date = date("Y-m-d", strtotime($start_date));

                                                    // override eval only reason to null
                                                    $eval_only_reason = 0;

                                                    // set medicaid billing to 0 (N/A)
                                                    $medicaid_billing = 0;
                                                }
                                                else if ($evaluation_method == 2) // Evaluation Only
                                                {
                                                    // convert the start and end dates to the correct database format
                                                    $DB_start_date = date("Y-m-d", strtotime($eval_date));

                                                    // override some fields to default value
                                                    $status = 0; // set status to inactive
                                                    $frequency = null; // set frequency to null
                                                    $units = 0; // set units to 0
                                                }

                                                if ($allowAssistants === false || ($allowAssistants === true && (verifyAssistant($conn, $assistant_id) || $assistant_id == null)))
                                                {
                                                    // check to see if the student is already in the caseload
                                                    $checkForStudent = mysqli_prepare($conn, "SELECT id FROM cases WHERE id!=? AND caseload_id=? AND period_id=? AND student_id=? AND end_date>? AND active=0");
                                                    mysqli_stmt_bind_param($checkForStudent, "iiiis", $case_id, $caseload_id, $period_id, $student_id, $DB_start_date);
                                                    if (mysqli_stmt_execute($checkForStudent))
                                                    {
                                                        $checkForStudentResult = mysqli_stmt_get_result($checkForStudent);
                                                        if (mysqli_num_rows($checkForStudentResult) == 0) // student is currently not active in the caseload already
                                                        {
                                                            // edit the existing case
                                                            $editCase = mysqli_prepare($conn, "UPDATE cases SET student_id=?, assistant_id=?, district_attending=?, school_attending=?, start_date=?, grade_level=?, evaluation_method=?, enrollment_type=?, educational_plan=?, residency=?, bill_to=?, billing_type=?, billing_notes=?, frequency=?, estimated_uos=?, temp_fname=null, temp_lname=null, active=?, medicaid_billed=?, medicaid_evaluation_month=?, membership_days=?, classroom_id=?, dismissal_reasoning_id=? WHERE id=? AND period_id=?");
                                                            mysqli_stmt_bind_param($editCase, "iiiisiiiiiiissdiiiiiiii", $student_id, $assistant_id, $district, $school, $DB_start_date, $grade_level, $evaluation_method, $enrollment_type, $educational_plan, $residency, $billing_to, $billing_type, $billing_notes, $frequency, $units, $status, $medicaid_billing, $eval_month, $membership_days, $classroom_id, $eval_only_reason, $case_id, $period_id);
                                                            if (mysqli_stmt_execute($editCase)) 
                                                            { 
                                                                // display on screen case edit status
                                                                echo "<span class=\"log-success\">Successfully</span> edited the student in the caseload.<br>";
                                                                
                                                                // log case edit
                                                                $message = "Successfully edited the case with the ID of $case_id in the caseload with the ID of $caseload_id.";
                                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                                mysqli_stmt_execute($log);
                                                            }
                                                            else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                                                        }
                                                        else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. Cannot set the start date to a date prior to an existing end date set for the student.<br>"; }
                                                    }
                                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. The assistant selected was invalid!<br>"; }
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. The student does not exist!<br>"; }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. The school selected does not exist!<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. The district selected does not exist!<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. The residency selected does not exist!<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. You must select a valid selection for \"Medicaid Billing Completed?\"<br>"; }
                        }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. The caseload the student was in is not valid!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. The caseload selected does not exist!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. The period selected does not exist!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the student in the caseload. Your account does not have permission to edit students in caseloads.<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>