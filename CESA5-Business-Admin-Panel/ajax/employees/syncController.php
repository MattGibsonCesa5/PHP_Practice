<?php 
    session_start();

    // verify user is logged in
    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // verify user is an admin
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get parameters from POST
            if (isset($_POST["queue_id"]) && is_numeric($_POST["queue_id"])) { $queue_id = $_POST["queue_id"]; } else { $queue_id = null; }
            if (isset($_POST["employee_id"]) && is_numeric($_POST["employee_id"])) { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }
            if (isset($_POST["action"]) && is_numeric($_POST["action"]) && $_POST["action"] == 1) { $action = 1; } else { $action = 0; }
            if (isset($_POST["new"]) && is_numeric($_POST["new"]) && $_POST["new"] == 1) { $new = 1; } else { $new = 0; }

            if (($queue_id != null && $new == 0) || ($employee_id != null && $new == 1))
            { 
                // include additional required files
                include("../../includes/config.php");
                include("../../includes/functions.php");

                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                ///////////////////////////////////////////////////////////////////////////////////
                //
                //  QUEUE
                //
                ///////////////////////////////////////////////////////////////////////////////////
                if ($new == 0)
                {
                    $getRequest = mysqli_prepare($conn, "SELECT * FROM sync_queue_employee_compensation WHERE id=?");
                    mysqli_stmt_bind_param($getRequest, "i", $queue_id);
                    if (mysqli_stmt_execute($getRequest))
                    {
                        $getRequestResult = mysqli_stmt_get_result($getRequest);
                        if (mysqli_num_rows($getRequestResult) > 0) // request exists
                        {
                            // store request details locally
                            $request = mysqli_fetch_array($getRequestResult);
                            $employee_id = $request["employee_id"];
                            $period_id = $request["period_id"];
                            $field = $request["field"];
                            $value = $request["value"];
                            $request_time = $request["request_time"];
                            $status = $request["status"];

                            // only continue processing request if status is 0 (pending)
                            if ($status == 0)
                            {
                                // reject the request
                                if ($action == 0)
                                {
                                    $rejectRequest = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET status=2, action_time=CURRENT_TIMESTAMP(), action_user=? WHERE id=?");
                                    mysqli_stmt_bind_param($rejectRequest, "ii", $_SESSION["id"], $queue_id);
                                    if (mysqli_stmt_execute($rejectRequest)) { 
                                        echo 2;
                                    } else {
                                        // failed to reject the request
                                    }
                                }
                                // accept the request
                                else if ($action == 1)
                                {
                                    // get employee's current data
                                    $getEmployeeComp = mysqli_prepare($conn, "SELECT e.most_recent_hire_date, e.most_recent_end_date, e.original_hire_date, e.original_end_date, 
                                                                                    ec.yearly_rate, ec.contract_days, ec.contract_start_date, ec.contract_end_date, ec.calendar_type, ec.number_of_pays, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.active
                                                                                FROM employees e
                                                                                LEFT JOIN employee_compensation ec ON e.id=ec.employee_id
                                                                                WHERE ec.period_id=? AND e.id=?");
                                    mysqli_stmt_bind_param($getEmployeeComp, "ii", $period_id, $employee_id);
                                    if (mysqli_stmt_execute($getEmployeeComp))
                                    {
                                        $getEmployeeCompResult = mysqli_stmt_get_result($getEmployeeComp);
                                        if (mysqli_num_rows($getEmployeeCompResult) > 0) // employee found; continue
                                        {
                                            // store employee details locally
                                            $employee = mysqli_fetch_array($getEmployeeCompResult);
                                            $most_recent_hire_date = $employee["most_recent_hire_date"];
                                            $most_recent_end_date = $employee["most_recent_end_date"];
                                            $original_hire_date = $employee["original_hire_date"];
                                            $original_end_date = $employee["original_end_date"];
                                            $yearly_rate = $employee["yearly_rate"];
                                            $contract_days = $employee["contract_days"];
                                            $contract_start_date = $employee["contract_start_date"];
                                            $contract_end_date = $employee["contract_end_date"];
                                            $calendar_type = $employee["calendar_type"];
                                            $num_of_pays = $employee["number_of_pays"];
                                            $health = $employee["health_insurance"];
                                            $dental = $employee["dental_insurance"];
                                            $wrs = $employee["wrs_eligible"];
                                            $active = $employee["active"];

                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Health Insurance
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "health_insurance")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employee_compensation SET health_insurance=? WHERE employee_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($updateEmp, "iii", $value, $employee_id, $period_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $health, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Dental Insurance
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "dental_insurance")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employee_compensation SET dental_insurance=? WHERE employee_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($updateEmp, "iii", $value, $employee_id, $period_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $dental, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  WRS Eligibility
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "wrs_eligible")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employee_compensation SET wrs_eligible=? WHERE employee_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($updateEmp, "iii", $value, $employee_id, $period_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $wrs, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Active Status
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "active")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employee_compensation SET active=? WHERE employee_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($updateEmp, "iii", $value, $employee_id, $period_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $active, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Most Recent Start Date
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "most_recent_hire_date")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employees SET most_recent_hire_date=? WHERE id=?");
                                                mysqli_stmt_bind_param($updateEmp, "si", $value, $employee_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $most_recent_hire_date, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Most Recent End Date
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "most_recent_end_date")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employees SET most_recent_end_date=? WHERE id=?");
                                                mysqli_stmt_bind_param($updateEmp, "si", $value, $employee_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $most_recent_end_date, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Original Start Date
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "original_hire_date")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employees SET original_hire_date=? WHERE id=?");
                                                mysqli_stmt_bind_param($updateEmp, "si", $value, $employee_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $original_hire_date, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Original End Date
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "original_end_date")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employees SET original_end_date=? WHERE id=?");
                                                mysqli_stmt_bind_param($updateEmp, "si", $value, $employee_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $original_end_date, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Contract Start Date
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "contract_start_date")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employee_compensation SET contract_start_date=? WHERE employee_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($updateEmp, "sii", $value, $employee_id, $period_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $contract_start_date, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Contract End Date
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "contract_end_date")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employee_compensation SET contract_end_date=? WHERE employee_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($updateEmp, "sii", $value, $employee_id, $period_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $contract_end_date, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Yearly Rate
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "yearly_rate")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employee_compensation SET yearly_rate=? WHERE employee_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($updateEmp, "dii", $value, $employee_id, $period_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $yearly_rate, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Contract Days
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "contract_days")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employee_compensation SET contract_days=? WHERE employee_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($updateEmp, "iii", $value, $employee_id, $period_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $contract_days, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Calendar Type
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "calendar_type")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employee_compensation SET calendar_type=? WHERE employee_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($updateEmp, "iii", $value, $employee_id, $period_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $calendar_type, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                            ///////////////////////////////////////////////////////////////////////
                                            //
                                            //  Number Of Pays
                                            //
                                            ///////////////////////////////////////////////////////////////////////
                                            if ($field == "number_of_pays")
                                            {
                                                $updateEmp = mysqli_prepare($conn, "UPDATE employee_compensation SET number_of_pays=? WHERE employee_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($updateEmp, "iii", $value, $employee_id, $period_id);
                                                if (mysqli_stmt_execute($updateEmp)) {
                                                    // successfully accepted the request; update queue
                                                    $updateQueue = mysqli_prepare($conn, "UPDATE sync_queue_employee_compensation SET action_time=CURRENT_TIMESTAMP(), action_user=?, old_value=?, status=1 WHERE id=?");
                                                    mysqli_stmt_bind_param($updateQueue, "isi", $_SESSION["id"], $num_of_pays, $queue_id);
                                                    if (mysqli_stmt_execute($updateQueue)) {
                                                        // return status code 1 (success)
                                                        echo 1;
                                                    } else {
                                                        // failed to update the queue
                                                    }
                                                } else {
                                                    // failed to accept the request
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                ///////////////////////////////////////////////////////////////////////////////////
                //
                //  NEW QUEUE
                //
                ///////////////////////////////////////////////////////////////////////////////////
                else if ($new == 1)
                {
                    // accept the request
                    if ($action == 1)
                    {
                        if (checkExistingEmployee($conn, $employee_id))
                        {
                            $accept = mysqli_prepare($conn, "UPDATE employees SET queued=0 WHERE id=? AND queued=1");
                            mysqli_stmt_bind_param($accept, "i", $employee_id);
                            if (mysqli_stmt_execute($accept)) // successfully accepted sync
                            {
                                // get employee name
                                $employee_name = getEmployeeDisplayName($conn, $employee_id);

                                // log acceptance
                                $message = "Successfully accepted the pending sync to add $employee_name as a new employee (ID: $employee_id).";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // return status code 1 (success)
                                echo 1;

                                // get employee details to create account
                                $getEmployeeDetails = mysqli_prepare($conn, "SELECT lname, fname, email FROM employees WHERE id=?");
                                mysqli_stmt_bind_param($getEmployeeDetails, "i", $employee_id);
                                if (mysqli_stmt_execute($getEmployeeDetails))
                                {
                                    $getEmployeeDetailsResults = mysqli_stmt_get_result($getEmployeeDetails);
                                    if (mysqli_num_rows($getEmployeeDetailsResults) > 0)
                                    {
                                        // store employee details locally
                                        $employee_details = mysqli_fetch_array($getEmployeeDetailsResults);
                                        $lname = $employee_details["lname"];
                                        $fname = $employee_details["fname"];
                                        $email = $employee_details["email"];

                                        // attempt to create user account for new employee
                                        if (trim($email) <> "")
                                        {
                                            $emailUpper = strtoupper($email);
                                            $checkEmail = mysqli_prepare($conn, "SELECT id FROM users WHERE UPPER(email)=? AND status!=2");
                                            mysqli_stmt_bind_param($checkEmail, "s", $emailUpper);
                                            if (mysqli_stmt_execute($checkEmail))
                                            {
                                                $checkEmailResult = mysqli_stmt_get_result($checkEmail);
                                                if (mysqli_num_rows($checkEmailResult) == 0) // email is unique; continue account creation
                                                {
                                                    // add the new user
                                                    $addUser = mysqli_prepare($conn, "INSERT INTO users (lname, fname, email, role_id, created_by, status) VALUES (?, ?, ?, 3, ?, 0)");
                                                    mysqli_stmt_bind_param($addUser, "sssi", $lname, $fname, $email, $_SESSION["id"]);
                                                    if (mysqli_stmt_execute($addUser)) 
                                                    { 
                                                        // get the new user ID
                                                        $user_id = mysqli_insert_id($conn);

                                                        // log the user creation
                                                        $message = "Successfully added the new user with email address $email. Assigned the user the ID $user_id.";
                                                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                        mysqli_stmt_execute($log);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // reject the request
                    else if ($action == 0)
                    {
                        // get employee name
                        $employee_name = getEmployeeDisplayName($conn, $employee_id);
                        
                        // reject sync request
                        $reject = mysqli_prepare($conn, "DELETE FROM employees WHERE id=? AND queued=1");
                        mysqli_stmt_bind_param($reject, "i", $employee_id);
                        if (mysqli_stmt_execute($reject)) // successfully rejected the request
                        {
                            // log acceptance
                            $message = "Successfully rejected the pending sync to add $employee_name as a new employee (ID: $employee_id).";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);

                            // return status code 1 (success)
                            echo 2;

                            // delete compensation queue for employee
                            $dequeue = mysqli_prepare($conn, "DELETE FROM sync_queue_employee_compensation WHERE employee_id=? AND status=0");
                            mysqli_stmt_bind_param($dequeue, "i", $employee_id);
                            mysqli_stmt_execute($dequeue);

                            // delete compensation for employee
                            $clearComp = mysqli_prepare($conn, "DELETE FROM employee_compensation WHERE employee_id=?");
                            mysqli_stmt_bind_param($clearComp, "i", $employee_id);
                            mysqli_stmt_execute($clearComp);
                        }
                    }
                }

                // disconnect from the database
                mysqli_close($conn);
            }
        }
    }
?>