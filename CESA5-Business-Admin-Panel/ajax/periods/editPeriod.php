<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get period fields from POST
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }
            if (isset($_POST["name"]) && trim($_POST["name"]) <> "") { $name = trim($_POST["name"]); } else { $name = null; }
            if (isset($_POST["desc"])) { $desc = $_POST["desc"]; } else { $desc = null; }
            if (isset($_POST["start"])) { $start = $_POST["start"]; } else { $start = null; }
            if (isset($_POST["end"])) { $end = $_POST["end"]; } else { $end = null; }
            if (isset($_POST["caseload_term_start"])) { $caseload_term_start = $_POST["caseload_term_start"]; } else { $caseload_term_start = null; }
            if (isset($_POST["caseload_term_end"])) { $caseload_term_end = $_POST["caseload_term_end"]; } else { $caseload_term_end = null; }
            if (isset($_POST["status"]) && (is_numeric($_POST["status"]) && $_POST["status"] == 1)) { $status = $_POST["status"]; } else { $status = 0; }
            if (isset($_POST["comparison"]) && is_numeric($_POST["comparison"])) { $comparison = $_POST["comparison"]; } else { $comparison = 0; }
            if (isset($_POST["editable"]) && is_numeric($_POST["editable"])) { $editable = $_POST["editable"]; } else { $editable = 0; }
            if (isset($_POST["next"]) && is_numeric($_POST["next"])) { $next = $_POST["next"]; } else { $next = 0; }

            // get quarter fields from POST
            if (isset($_POST["q1_label"]) && trim($_POST["q1_label"]) <> "") { $q1_label = trim($_POST["q1_label"]); } else { $q1_label = null; }
            if (isset($_POST["q2_label"]) && trim($_POST["q2_label"]) <> "") { $q2_label = trim($_POST["q2_label"]); } else { $q2_label = null; }
            if (isset($_POST["q3_label"]) && trim($_POST["q3_label"]) <> "") { $q3_label = trim($_POST["q3_label"]); } else { $q3_label = null; }
            if (isset($_POST["q4_label"]) && trim($_POST["q4_label"]) <> "") { $q4_label = trim($_POST["q4_label"]); } else { $q4_label = null; }
            if (isset($_POST["q1_status"]) && (is_numeric($_POST["q1_status"]) && $_POST["q1_status"] == 1)) { $q1_status = $_POST["q1_status"]; } else { $q1_status = 0; }
            if (isset($_POST["q2_status"]) && (is_numeric($_POST["q2_status"]) && $_POST["q2_status"] == 1)) { $q2_status = $_POST["q2_status"]; } else { $q2_status = 0; }
            if (isset($_POST["q3_status"]) && (is_numeric($_POST["q3_status"]) && $_POST["q3_status"] == 1)) { $q3_status = $_POST["q3_status"]; } else { $q3_status = 0; }
            if (isset($_POST["q4_status"]) && (is_numeric($_POST["q4_status"]) && $_POST["q4_status"] == 1)) { $q4_status = $_POST["q4_status"]; } else { $q4_status = 0; }
            
            if ($period_id != null && $name != null && $start != null && $end != null && $caseload_term_start != null && $caseload_term_end != null)
            {
                // create the database dates
                $DB_start = date("Y-m-d", strtotime($start));
                $DB_end = date("Y-m-d", strtotime($end));
                $DB_caseload_start = date("Y-m-d", strtotime($caseload_term_start));
                $DB_caseload_end = date("Y-m-d", strtotime($caseload_term_end));

                if (verifyPeriod($conn, $period_id))
                {
                    $editPeriod = mysqli_prepare($conn, "UPDATE periods SET name=?, description=?, start_date=?, end_date=?, caseload_term_start=?, caseload_term_end=?, active=?, comparison=?, editable=?, next=? WHERE id=?");
                    mysqli_stmt_bind_param($editPeriod, "ssssssiiiii", $name, $desc, $DB_start, $DB_end, $DB_caseload_start, $DB_caseload_end, $status, $comparison, $editable, $next, $period_id);
                    if (mysqli_stmt_execute($editPeriod)) // successfully edited the period
                    {
                        echo "<span class=\"log-success\">Successfully</span> edited the period.<br>";

                        // initialize the quarters for the period
                        $editQuarter1 = mysqli_prepare($conn, "UPDATE quarters SET label=?, locked=? WHERE quarter=1 AND period_id=?");
                        mysqli_stmt_bind_param($editQuarter1, "sii", $q1_label, $q1_status, $period_id);
                        if (mysqli_stmt_execute($editQuarter1)) 
                        {
                            if ($q1_status == 1) // locked the quarter
                            {
                                echo "<span class=\"log-success\">Successfully</span> locked Q1.<br>";
                                $q_msg = "Successfully locked Q1 in the period with ID of $period_id.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $q_msg);
                                mysqli_stmt_execute($log);
                            }
                            else // unlocked the quarter
                            {
                                echo "<span class=\"log-success\">Successfully</span> unlocked Q1.<br>";
                                $q_msg = "Successfully unlocked Q1 in the period with ID of $period_id.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $q_msg);
                                mysqli_stmt_execute($log);
                            }
                        }

                        // initialize the quarters for the period
                        $editQuarter2 = mysqli_prepare($conn, "UPDATE quarters SET label=?, locked=? WHERE quarter=2 AND period_id=?");
                        mysqli_stmt_bind_param($editQuarter2, "sii", $q2_label, $q2_status, $period_id);
                        if (mysqli_stmt_execute($editQuarter2)) 
                        {
                            if ($q2_status == 1) // locked the quarter
                            {
                                echo "<span class=\"log-success\">Successfully</span> locked Q2.<br>";
                                $q_msg = "Successfully locked Q2 in the period with ID of $period_id.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $q_msg);
                                mysqli_stmt_execute($log);
                            }
                            else // unlocked the quarter
                            {
                                echo "<span class=\"log-success\">Successfully</span> unlocked Q2.<br>";
                                $q_msg = "Successfully unlocked Q2 in the period with ID of $period_id.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $q_msg);
                                mysqli_stmt_execute($log);
                            }
                        }

                        // initialize the quarters for the period
                        $editQuarter3 = mysqli_prepare($conn, "UPDATE quarters SET label=?, locked=? WHERE quarter=3 AND period_id=?");
                        mysqli_stmt_bind_param($editQuarter3, "sii", $q3_label, $q3_status, $period_id);
                        if (mysqli_stmt_execute($editQuarter3)) 
                        {
                            if ($q3_status == 1) // locked the quarter
                            {
                                echo "<span class=\"log-success\">Successfully</span> locked Q3.<br>";
                                $q_msg = "Successfully locked Q3 in the period with ID of $period_id.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $q_msg);
                                mysqli_stmt_execute($log);
                            }
                            else // unlocked the quarter
                            {
                                echo "<span class=\"log-success\">Successfully</span> unlocked Q3.<br>";
                                $q_msg = "Successfully unlocked Q3 in the period with ID of $period_id.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $q_msg);
                                mysqli_stmt_execute($log);
                            }
                        }

                        // initialize the quarters for the period
                        $editQuarter4 = mysqli_prepare($conn, "UPDATE quarters SET label=?, locked=? WHERE quarter=4 AND period_id=?");
                        mysqli_stmt_bind_param($editQuarter4, "sii", $q4_label, $q4_status, $period_id);
                        if (mysqli_stmt_execute($editQuarter4)) 
                        {
                            if ($q4_status == 1) // locked the quarter
                            {
                                echo "<span class=\"log-success\">Successfully</span> locked Q4.<br>";
                                $q_msg = "Successfully locked Q4 in the period with ID of $period_id.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $q_msg);
                                mysqli_stmt_execute($log);
                            }
                            else // unlocked the quarter
                            {
                                echo "<span class=\"log-success\">Successfully</span> unlocked Q4.<br>";
                                $q_msg = "Successfully unlocked Q4 in the period with ID of $period_id.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $q_msg);
                                mysqli_stmt_execute($log);
                            }
                        }

                        // set all other periods to inactive if we set this period to active
                        if ($status == 1)
                        {
                            $setInactives = mysqli_prepare($conn, "UPDATE periods SET active=0 WHERE active=1 AND id<>?");
                            mysqli_stmt_bind_param($setInactives, "i", $period_id);
                            if (mysqli_stmt_execute($setInactives)) // successfully set all other periods to inactive
                            {
                                echo "<span class=\"log-success\">Successfully</span> set all other periods as inactive as you set this period as the active period.<br>";
                            }
                        }

                        // set all other periods to not the comparison period if this one is comparison period
                        if ($comparison == 1)
                        {
                            $removeComps = mysqli_prepare($conn, "UPDATE periods SET comparison=0 WHERE comparison=1 AND id<>?");
                            mysqli_stmt_bind_param($removeComps, "i", $period_id);
                            if (mysqli_stmt_execute($removeComps)) // successfully set all other periods to inactive
                            {
                                echo "<span class=\"log-success\">Successfully</span> set all other periods as non-comparison periods as you set this period as the comparison period.<br>";
                            }
                        }

                        // set all other periods to not be the next period if this one is next period
                        if ($next == 1)
                        {
                            $removeNext = mysqli_prepare($conn, "UPDATE periods SET next=0 WHERE next=1 AND id<>?");
                            mysqli_stmt_bind_param($removeNext, "i", $period_id);
                            if (mysqli_stmt_execute($removeNext)) // successfully set all other periods to inactive
                            {
                                echo "<span class=\"log-success\">Successfully</span> set all other periods as non-next periods as you set this period as the next period.<br>";
                            }
                        }

                        // log period edit
                        $message = "Successfully edited the period with the ID of $period_id.";
                        if ($status == 1) { $message .= " Set the period as the active period."; }
                        if ($comparison == 1) { $message .= " Set the period as the comparison period."; }
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the period. An unknown error has occurred.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the period. The period you selected does not exist!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the period. Some parameters you have entered were invalid.<br>"; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>