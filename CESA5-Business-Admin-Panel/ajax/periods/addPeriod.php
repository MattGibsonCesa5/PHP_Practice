<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/config.php");
            include("../../getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get period parameters from POST
            if (isset($_POST["name"]) && $_POST["name"] <> "") { $name = $_POST["name"]; } else { $name = null; }
            if (isset($_POST["desc"])) { $desc = $_POST["desc"]; } else { $desc = null; }
            if (isset($_POST["start"])) { $start = $_POST["start"]; } else { $start = null; }
            if (isset($_POST["end"])) { $end = $_POST["end"]; } else { $end = null; }
            if (isset($_POST["caseload_term_start"])) { $caseload_term_start = $_POST["caseload_term_start"]; } else { $caseload_term_start = null; }
            if (isset($_POST["caseload_term_end"])) { $caseload_term_end = $_POST["caseload_term_end"]; } else { $caseload_term_end = null; }
            if (isset($_POST["status"]) && (is_numeric($_POST["status"]) && $_POST["status"] == 1)) { $status = 1; } else { $status = 0; }
            if (isset($_POST["comparison"]) && is_numeric($_POST["comparison"])) { $comparison = $_POST["comparison"]; } else { $comparison = 0; }
            if (isset($_POST["editable"]) && is_numeric($_POST["editable"])) { $editable = $_POST["editable"]; } else { $editable = 0; }
            if (isset($_POST["next"]) && is_numeric($_POST["next"])) { $next = $_POST["next"]; } else { $next = 0; }

            // get quarter parameters from POST
            if (isset($_POST["q1_label"]) && trim($_POST["q1_label"]) <> "") { $q1_label = trim($_POST["q1_label"]); } else { $q1_label = null; }
            if (isset($_POST["q2_label"]) && trim($_POST["q2_label"]) <> "") { $q2_label = trim($_POST["q2_label"]); } else { $q2_label = null; }
            if (isset($_POST["q3_label"]) && trim($_POST["q3_label"]) <> "") { $q3_label = trim($_POST["q3_label"]); } else { $q3_label = null; }
            if (isset($_POST["q4_label"]) && trim($_POST["q4_label"]) <> "") { $q4_label = trim($_POST["q4_label"]); } else { $q4_label = null; }
            if (isset($_POST["q1_status"]) && (is_numeric($_POST["q1_status"]) && $_POST["q1_status"] == 1)) { $q1_status = $_POST["q1_status"]; } else { $q1_status = 0; }
            if (isset($_POST["q2_status"]) && (is_numeric($_POST["q2_status"]) && $_POST["q2_status"] == 1)) { $q2_status = $_POST["q2_status"]; } else { $q2_status = 0; }
            if (isset($_POST["q3_status"]) && (is_numeric($_POST["q3_status"]) && $_POST["q3_status"] == 1)) { $q3_status = $_POST["q3_status"]; } else { $q3_status = 0; }
            if (isset($_POST["q4_status"]) && (is_numeric($_POST["q4_status"]) && $_POST["q4_status"] == 1)) { $q4_status = $_POST["q4_status"]; } else { $q4_status = 0; }

            // convert to database readable dates
            $DB_start = date("Y-m-d", strtotime($start));
            $DB_end = date("Y-m-d", strtotime($end));
            $DB_caseload_start = date("Y-m-d", strtotime($caseload_term_start));
            $DB_caseload_end = date("Y-m-d", strtotime($caseload_term_end));

            if ($name != null && $start != null && $end != null && $caseload_term_start != null && $caseload_term_end != null)
            {
                if ($q1_label != null && $q2_label != null && $q3_label != null && $q4_label != null)
                {
                    $addPeriod = mysqli_prepare($conn, "INSERT INTO periods (name, description, start_date, end_date, caseload_term_start, caseload_term_end, active, comparison, editable, next) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($addPeriod, "ssssssiiii", $name, $desc, $DB_start, $DB_end, $DB_caseload_start, $DB_caseload_end, $status, $comparison, $editable, $next);
                    if (mysqli_stmt_execute($addPeriod)) 
                    { 
                        // get the new period ID
                        $period_id = mysqli_insert_id($conn);

                        // initialize the quarters for the period
                        $addQuarter1 = mysqli_prepare($conn, "INSERT INTO quarters (quarter, label, locked, period_id) VALUES (1, ?, ?, ?)");
                        mysqli_stmt_bind_param($addQuarter1, "sii", $q1_label, $q1_status, $period_id);
                        if (!mysqli_stmt_execute($addQuarter1)) { /* TODO - handle failure to add quarter */ }

                        $addQuarter2 = mysqli_prepare($conn, "INSERT INTO quarters (quarter, label, locked, period_id) VALUES (2, ?, ?, ?)");
                        mysqli_stmt_bind_param($addQuarter2, "sii", $q2_label, $q2_status, $period_id);
                        if (!mysqli_stmt_execute($addQuarter2)) { /* TODO - handle failure to add quarter */ }

                        $addQuarter3 = mysqli_prepare($conn, "INSERT INTO quarters (quarter, label, locked, period_id) VALUES (3, ?, ?, ?)");
                        mysqli_stmt_bind_param($addQuarter3, "sii", $q3_label, $q3_status, $period_id);
                        if (!mysqli_stmt_execute($addQuarter3)) { /* TODO - handle failure to add quarter */ }

                        $addQuarter4 = mysqli_prepare($conn, "INSERT INTO quarters (quarter, label, locked, period_id) VALUES (4, ?, ?, ?)");
                        mysqli_stmt_bind_param($addQuarter4, "sii", $q4_label, $q4_status, $period_id);
                        if (!mysqli_stmt_execute($addQuarter4)) { /* TODO - handle failure to add quarter */ }

                        // if we are setting the new period as the active period; set all other periods to inactive
                        if ($status == 1) 
                        { 
                            echo "<span class=\"log-success\">Successfully</span> created the new period and set it as the active period.<br>"; 
                            $updateStatus = mysqli_prepare($conn, "UPDATE periods SET active=0 WHERE active=1 AND id!=?");
                            mysqli_stmt_bind_param($updateStatus, "i", $period_id);
                            if (mysqli_stmt_execute($updateStatus)) { echo "<span class=\"log-success\">Successfully</span> set other periods to inactive.<br>"; }
                            else { echo "<span class=\"log-fail\">Failed</span> to set all other periods to inactive.<br>"; } 
                        }
                        else { echo "<span class=\"log-success\">Successfully</span> created the new period.<br>"; }

                        // set all other periods to not be the comparison period if this one is comparison period
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

                        // log new period creation
                        $message = "Successfully created the new period labeled $name - assigned period ID of $period_id.";
                        if ($status == 1) { $message .= " Set the new period as the active period."; }
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);
                    } 
                    else { echo "<span class=\"log-fail\">Failed</span> to create the new period.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to create the new period. You must provide valid quarter labels for all quarters.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to create the new period. Some parameters you have entered were invalid.<br>"; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>