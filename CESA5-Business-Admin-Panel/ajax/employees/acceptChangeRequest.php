<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get the request ID from POST
            if (isset($_POST["request_id"]) && $_POST["request_id"] <> "") { $request_id = $_POST["request_id"]; } else { $request_id = null; }

            if ($request_id != null)
            {
                // get request details
                $getRequest = mysqli_prepare($conn, "SELECT employee_id, period_id FROM employee_compensation_change_requests WHERE id=?");
                mysqli_stmt_bind_param($getRequest, "i", $request_id);
                if (mysqli_stmt_execute($getRequest))
                {
                    $getRequestResult = mysqli_stmt_get_result($getRequest);
                    if (mysqli_num_rows($getRequestResult) > 0) // request exists; continue
                    {
                        // get request details
                        $request = mysqli_fetch_array($getRequestResult);
                        $employee_id = $request["employee_id"];
                        $period_id = $request["period_id"];

                        // get additional request details from POST
                        if (isset($_POST["new_days"]) && is_numeric($_POST["new_days"])) { $new_days = $_POST["new_days"]; } else { $new_days = 0; }
                        if (isset($_POST["new_salary"]) && is_numeric($_POST["new_salary"])) { $new_salary = $_POST["new_salary"]; } else { $new_salary = 0; }
                        if (isset($_POST["reason"]) && $_POST["reason"] <> "") { $reason = $_POST["reason"]; } else { $reason = null; }

                        // verify the employee exists
                        if (checkExistingEmployee($conn, $employee_id))
                        {
                            // get the employee's display name
                            $employee_name = getEmployeeDisplayName($conn, $employee_id);

                            // update the employee's compensation
                            $updateComp = mysqli_prepare($conn, "UPDATE employee_compensation SET yearly_rate=?, contract_days=? WHERE employee_id=? AND period_id=?");
                            mysqli_stmt_bind_param($updateComp, "diii", $new_salary, $new_days, $employee_id, $period_id);
                            if (mysqli_stmt_execute($updateComp))
                            {
                                // get the current time
                                $accepted_at = date("Y-m-d H:i:s");

                                // set the status of the change request to indicate accepted (1)
                                $acceptRequest = mysqli_prepare($conn, "UPDATE employee_compensation_change_requests SET new_contract_days=?, new_yearly_salary=?, reason=?, status=1, accepted_by=?, accepted_at=? WHERE id=?");
                                mysqli_stmt_bind_param($acceptRequest, "idsisi", $new_days, $new_salary, $reason, $_SESSION["id"], $accepted_at, $request_id);
                                if (mysqli_stmt_execute($acceptRequest)) 
                                { 
                                    // log accepted change request 
                                    echo "<span class=\"log-success\">Successfully</span> accepted the change request for $employee_name.<br>"; 
                                    $message = "Successfully accepted the change request for $employee_name (ID: $employee_id), for the period with ID of $period_id, with request ID $request_id. Set contract days to $new_days, and yearly salary to ".printDollar($new_salary);
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to accept the change request for $employee_name. An unexpected error has occurred! Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to accept the change request for $employee_name. An error occurred when attempting to update $employee_name's compensation!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to accept the change request. The employee requested does not exist!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to accept the change request. The request is no longer valid!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to accept the change request. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to accept the change request. The request is invalid!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to accept the change request. Your account does not have permission to perform this action.<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>