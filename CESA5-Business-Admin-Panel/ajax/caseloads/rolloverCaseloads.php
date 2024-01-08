<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // initialize counters
            $total_successes = $total_errors = 0;

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get parameters from POST
            if (isset($_POST["start_date"]) && $_POST["start_date"] <> "") { $start_date = $_POST["start_date"]; } else { $start_date = null; }
            if (isset($_POST["end_date"]) && $_POST["end_date"] <> "") { $end_date = $_POST["end_date"]; } else { $end_date = null; }
            if (isset($_POST["period_from"]) && $_POST["period_from"] <> "") { $period_from = $_POST["period_from"]; } else { $period_from = null; }
            if (isset($_POST["period_to"]) && $_POST["period_to"] <> "") { $period_to = $_POST["period_to"]; } else { $period_to = null; }
            if (isset($_POST["caseloads"]) && $_POST["caseloads"] <> "") { $caseloads = json_decode($_POST["caseloads"]); } else { $caseloads = null; }

            if (verifyPeriod($conn, $period_from))
            {
                if (verifyPeriod($conn, $period_to))
                {
                    if ($start_date != null && $end_date != null)
                    {
                        // convert the dates to database format
                        $DB_start_date = date("Y-m-d", strtotime($start_date));
                        $DB_end_date = date("Y-m-d", strtotime($end_date));

                        // get period labels
                        $period_from_label = getPeriodName($conn, $period_from);
                        $period_to_label = getPeriodName($conn, $period_to);

                        // verify that caseloads were selected
                        if (is_array($caseloads) && count($caseloads) > 0)
                        {
                            // for each caseload, rollover cases
                            for ($c = 0; $c < count($caseloads); $c++)
                            {
                                $caseload_id = $caseloads[$c];
                                if (verifyCaseload($conn, $caseload_id))
                                {
                                    // get the caseload name
                                    $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                                    // log caseload divider
                                    echo "<br><b>### $caseload_name</b><br>";

                                    // get a list of all regular evaluationed, active cases for the caseload in the period we are copying cases from
                                    $caseload_successes = $caseload_errors = 0;
                                    $getCases = mysqli_prepare($conn, "SELECT * FROM cases WHERE caseload_id=? AND period_id=? AND active=1 AND evaluation_method=1");
                                    mysqli_stmt_bind_param($getCases, "ii", $caseload_id, $period_from);
                                    if (mysqli_stmt_execute($getCases))
                                    {
                                        $getCasesResults = mysqli_stmt_get_result($getCases);
                                        if (mysqli_num_rows($getCasesResults) > 0)
                                        {
                                            while ($case = mysqli_fetch_array($getCasesResults))
                                            {
                                                // store the case details locally that we will copy over
                                                $case_id = $case["id"];
                                                $student_id = $case["student_id"];
                                                $residency_id = $case["residency"];
                                                $district_id = $case["district_attending"];
                                                $school_id = $case["school_attending"];
                                                $classroom_id = $case["classroom_id"];
                                                $grade_level = $case["grade_level"];
                                                $evaluation_method = $case["evaluation_method"];
                                                $enrollment_type = $case["enrollment_type"];
                                                $educational_plan = $case["educational_plan"];
                                                $bill_to = $case["bill_to"];
                                                $billing_type = $case["billing_type"];
                                                $billing_notes = $case["billing_notes"];
                                                $frequency = $case["frequency"];
                                                $uos = $case["estimated_uos"];
                                                $membership_days = $case["membership_days"];

                                                // set the grade level to the next year
                                                $new_grade_level = $grade_level + 1;

                                                // get the therapist ID based on the caseload ID
                                                $therapist_id = getCaseloadTherapist($conn, $caseload_id);

                                                // get the therapist's display name based on the caseload ID
                                                $therapist_name = getUserDisplayName($conn, $therapist_id);

                                                // get student details
                                                $student_name = getStudentDisplayName($conn, $student_id);

                                                // check to see if the student had changes in their case; if so, get the most recent UOS and frequency
                                                $checkForChanges = mysqli_prepare($conn, "SELECT * FROM case_changes WHERE case_id=? ORDER BY start_date DESC LIMIT 1");
                                                mysqli_stmt_bind_param($checkForChanges, "i", $case_id);
                                                if (mysqli_stmt_execute($checkForChanges))
                                                {
                                                    $checkForChangesResults = mysqli_stmt_get_result($checkForChanges);
                                                    if (mysqli_num_rows($checkForChangesResults) > 0)
                                                    {
                                                        // store necessary change details locally
                                                        $change_details = mysqli_fetch_array($checkForChangesResults);
                                                        $change_frequency = $change_details["frequency"];
                                                        $change_uos = $change_details["uos"];

                                                        // override existing frequency and UOS to the latest changes
                                                        $frequency = $change_frequency;
                                                        $uos = $change_uos;
                                                    }
                                                }

                                                // add the case
                                                $addCase = mysqli_prepare($conn, "INSERT INTO cases (caseload_id, period_id, student_id, district_attending, school_attending, classroom_id, start_date, end_date, grade_level, evaluation_method, enrollment_type, educational_plan, residency, bill_to, billing_type, billing_notes, frequency, extra_ieps, extra_evaluations, estimated_uos, created_by, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, 1)");
                                                mysqli_stmt_bind_param($addCase, "iiiiiissiiiiiiissdi", $caseload_id, $period_to, $student_id, $district_id, $school_id, $classroom_id, $DB_start_date, $DB_end_date, $new_grade_level, $evaluation_method, $enrollment_type, $educational_plan, $residency_id, $bill_to, $billing_type, $billing_notes, $frequency, $uos, $_SESSION["id"]);
                                                if (mysqli_stmt_execute($addCase)) 
                                                { 
                                                    // display on screen successful rollover
                                                    // echo "<span class=\"log-success\">Successfully</span> rolled over $student_name to the $caseload_name caseload.<br>"; 

                                                    // store the new caseload ID
                                                    $new_case_id = mysqli_insert_id($conn);

                                                    // if the billing type is day use, update the membership days to 180
                                                    if ($billing_type == 2)
                                                    {
                                                        $updateDays = mysqli_prepare($conn, "UPDATE cases SET membership_days=180, estimated_uos=0, frequency=null WHERE id=?");
                                                        mysqli_stmt_bind_param($updateDays, "i", $new_case_id);
                                                        if (!mysqli_stmt_execute($updateDays)) { echo "<span class=\"log-fail\">Failed</span> to set the membership days for the student.<br>"; }
                                                    }

                                                    // increment success counter
                                                    $caseload_successes++;
                                                    $total_successes++;
                                                }
                                                else 
                                                {   
                                                    // display error on the screen
                                                    echo "<span class=\"log-fail\">Failed</span> to rollover $student_name into the $caseload_name caseload!<br>"; 

                                                    // increment error counter
                                                    $caseload_errors++;
                                                    $total_errors++;
                                                }
                                            }
                                        }
                                    }

                                    // log and display caseload rollover status
                                    echo "<span class=\"log-success\">Successfully</span> rolled over $caseload_successes cases from $period_from_label to $period_to_label for the $caseload_name caseload.<br>";
                                    $caseload_message = "Successfully rolled over $caseload_successes cases from $period_from_label to $period_to_label for the $caseload_name caseload (period from ID: $period_from; period to ID: $period_to; caseload ID: $caseload_id).";
                                    if ($total_errors > 0) 
                                    { 
                                        echo "<span class=\"log-fail\">Failed</span> to rollover $caseload_errors cases for the $caseload_name caseload!<br>"; 
                                        $caseload_message .= " Failed to rollover $caseload_errors cases for the $caseload_name caseload!";
                                    }
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $caseload_message);
                                    mysqli_stmt_execute($log);
                                }
                            }

                            // display to screen overall status
                            echo "<br><b>### OVERALL ###</b><br>";
                            echo "<span class=\"log-success\">Successfully</span> rolled over $total_successes total cases from $period_from_label to $period_to_label.<br>";
                            if ($total_errors > 0) { echo "<span class=\"log-fail\">Failed</span> to rollover $total_errors cases!<br>"; }

                            // log rollover
                            $message = "Successfully rolled over $total_successes total cases from $period_from_label to $period_to_label. Set the start date to $DB_start_date and end date to $DB_end_date.";
                            if ($total_errors > 0) { $message .= " Failed to rollover $total_errors cases."; }
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to rollover caseloads. No caseloads were selected.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to rollover caseloads. You must select both a start and end date.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to rollover caseloads. The period you are trying to rollover caseloads to does not exist!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to rollover caseloads. The period you are trying to rollover caseloads from does not exist!<br>"; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>