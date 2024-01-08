<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // initialize array to store change requests
        $requests = [];

        // get period name from POST
        if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if ((checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES")) || checkUserPermission($conn, "VIEW_EMPLOYEES_ASSIGNED"))
        {
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                ///////////////////////////////////////////////////////////////////////////////////
                //
                //  ADMIN VIEW
                //
                ///////////////////////////////////////////////////////////////////////////////////
                if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES"))
                {
                    $getRequests = mysqli_prepare($conn, "SELECT * FROM employee_compensation_change_requests WHERE period_id=?");
                    mysqli_stmt_bind_param($getRequests, "i", $period_id);
                    if (mysqli_stmt_execute($getRequests))
                    {
                        $getRequestsResults = mysqli_stmt_get_result($getRequests);
                        if (mysqli_num_rows($getRequestsResults) > 0)
                        {
                            while ($request = mysqli_fetch_array($getRequestsResults))
                            {
                                // store request details locally
                                $request_id = $request["id"];
                                $employee_id = $request["employee_id"];
                                $period_id = $request["period_id"];
                                $days = $request["current_contract_days"];
                                $new_days = $request["new_contract_days"];
                                $salary = $request["current_yearly_salary"];
                                $new_salary = $request["new_yearly_salary"];
                                $reason = $request["reason"];
                                $requested_by = $request["requested_by"];
                                $requested_at = $request["requested_at"];
                                $accepted_by = $request["accepted_by"];
                                $accepted_at = $request["accepted_at"];
                                $status = $request["status"];

                                // set data to 0 if data not found
                                if (!isset($days)) { $days = 0; }
                                if (!isset($salary)) { $salary = 0; }
                                if (!isset($new_days)) { $new_days = 0; }
                                if (!isset($new_salary)) { $new_salary = 0; }

                                // get the employee's display name
                                $employee_name = getEmployeeDisplayName($conn, $employee_id);

                                // get the display name of the user who submitted and/or accepted the change request
                                $requested_username = getUserDisplayName($conn, $requested_by);
                                $accepted_username = getUserDisplayName($conn, $accepted_by);

                                // convert the dates to readable format
                                $DB_Timezone = HOST_TIMEZONE;
                                $display_requested_at = date_convert($requested_at, $DB_Timezone, "America/Chicago", "n/j/Y g:i A");
                                if (isset($accepted_at)) { $display_accepted_at = date_convert($accepted_at, $DB_Timezone, "America/Chicago", "n/j/Y g:i A"); } else { $display_accepted_at = null; }

                                // build the days column
                                if ($new_days > $days) { $days_display = $days." <i class='fa-solid fa-arrow-up' style='color: #006900;'></i> ".$new_days; }
                                else if ($new_days < $days) { $days_display = $days." <i class='fa-solid fa-arrow-down' style='color: #ff0000;'></i> ".$new_days; }
                                else { $days_display = $days." <i class='fa-solid fa-arrow-right'></i> ".$new_days; }

                                // build the salary column
                                if ($new_salary > $salary) { $salary_display = printDollar($salary)." <i class='fa-solid fa-arrow-up' style='color: #006900;'></i> ".printDollar($new_salary); }
                                else if ($new_salary < $salary) { $salary_display =printDollar($salary)." <i class='fa-solid fa-arrow-down' style='color: #ff0000;'></i> ".printDollar($new_salary); }
                                else { $salary_display = printDollar($salary)." <i class='fa-solid fa-arrow-right'></i> ".printDollar($new_salary); }

                                // build the request_details column
                                $request_details = "";
                                $request_details .= $requested_username . "<br>" . $display_requested_at;

                                // build the status column
                                $status_div = "";
                                if ($status == 1) { $status_div = "<div class='active-div text-center px-3 py-1'>Accepted</div>"; }
                                else if ($status == 2) { $status_div = "<div class='inactive-div text-center px-3 py-1'>Rejected</div>"; }
                                else { $status_div = "<div class='pending-div text-center px-3 py-1'>Pending</div>"; }
                                // add the user who accepted/rejected the request
                                if ($status == 1 || $status == 2)
                                {
                                    if ($accepted_username <> "" && $display_accepted_at <> "") 
                                    {
                                        $status_div .= "<div class='my-1'>
                                            $accepted_username<br>
                                            $display_accepted_at
                                        </div>";
                                    }
                                }

                                // build the filter status column
                                $filter_status = "";
                                if ($status == 1) { $filter_status = "Accepted"; }
                                else if ($status == 2) { $filter_status = "Rejected"; }
                                else { $filter_status = "Pending"; }

                                // build the actions column
                                $actions = "";
                                if ($status != 1 && $status != 2)
                                {
                                    $actions = "<div class='d-flex justify-content-end'>
                                        <button class='btn btn-success mx-1' onclick='getAcceptChangeRequestModal($request_id);'>
                                            <i class='fa-solid fa-check'></i>
                                        </button>

                                        <button class='btn btn-danger mx-1' onclick='getRejectChangeRequestModal($request_id);'>
                                            <i class='fa-solid fa-xmark'></i>
                                        </button>
                                    </div>";
                                }

                                // build the temporary array
                                $temp = [];
                                $temp["employee"] = $employee_name;
                                $temp["days"] = $days_display;
                                $temp["salary"] = $salary_display;
                                $temp["reason"] = $reason;
                                $temp["request_details"] = $request_details;
                                $temp["status"] = $status_div;
                                $temp["actions"] = $actions;
                                $temp["filter_status"] = $filter_status;

                                // add the request to master list
                                $requests[] = $temp;
                            }
                        }
                    }
                }
                ///////////////////////////////////////////////////////////////////////////////////
                //
                //  DIRECTOR VIEW
                //
                ///////////////////////////////////////////////////////////////////////////////////
                else if (checkUserPermission($conn, "VIEW_EMPLOYEES_ASSIGNED"))
                {
                    $getRequests = mysqli_prepare($conn, "SELECT * FROM employee_compensation_change_requests WHERE period_id=? AND requested_by=?");
                    mysqli_stmt_bind_param($getRequests, "ii", $period_id, $_SESSION["id"]);
                    if (mysqli_stmt_execute($getRequests))
                    {
                        $getRequestsResults = mysqli_stmt_get_result($getRequests);
                        if (mysqli_num_rows($getRequestsResults) > 0)
                        {
                            while ($request = mysqli_fetch_array($getRequestsResults))
                            {
                                // store request details locally
                                $request_id = $request["id"];
                                $employee_id = $request["employee_id"];
                                $period_id = $request["period_id"];
                                $days = $request["current_contract_days"];
                                $new_days = $request["new_contract_days"];
                                $salary = $request["current_yearly_salary"];
                                $new_salary = $request["new_yearly_salary"];
                                $reason = $request["reason"];
                                $requested_by = $request["requested_by"];
                                $requested_at = $request["requested_at"];
                                $accepted_by = $request["accepted_by"];
                                $accepted_at = $request["accepted_at"];
                                $status = $request["status"];

                                // set data to 0 if data not found
                                if (!isset($days)) { $days = 0; }
                                if (!isset($salary)) { $salary = 0; }
                                if (!isset($new_days)) { $new_days = 0; }
                                if (!isset($new_salary)) { $new_salary = 0; }

                                // get the employee's display name
                                $employee_name = getEmployeeDisplayName($conn, $employee_id);

                                // get the display name of the user who accepted/rejected the change request
                                $accepted_username = getUserDisplayName($conn, $accepted_by);

                                // convert the dates to readable format
                                $DB_Timezone = HOST_TIMEZONE;
                                $display_requested_at = date_convert($requested_at, $DB_Timezone, "America/Chicago", "n/j/Y g:i A");
                                if (isset($accepted_at)) { $display_accepted_at = date_convert($accepted_at, $DB_Timezone, "America/Chicago", "n/j/Y g:i A"); } else { $display_accepted_at = null; }

                                // build the days column
                                if ($new_days > $days) { $days_display = $days." <i class='fa-solid fa-arrow-up' style='color: #006900;'></i> ".$new_days; }
                                else if ($new_days < $days) { $days_display = $days." <i class='fa-solid fa-arrow-down' style='color: #ff0000;'></i> ".$new_days; }
                                else { $days_display = $days." <i class='fa-solid fa-arrow-right'></i> ".$new_days; }

                                // build the salary column
                                if ($new_salary > $salary) { $salary_display = printDollar($salary)." <i class='fa-solid fa-arrow-up' style='color: #006900;'></i> ".printDollar($new_salary); }
                                else if ($new_salary < $salary) { $salary_display =printDollar($salary)." <i class='fa-solid fa-arrow-down' style='color: #ff0000;'></i> ".printDollar($new_salary); }
                                else { $salary_display = printDollar($salary)." <i class='fa-solid fa-arrow-right'></i> ".printDollar($new_salary); }

                                // build the request_details column
                                $request_details = "";
                                $request_details .= $display_requested_at;

                                // build the status column
                                $status_div = "";
                                if ($status == 1) { $status_div = "<div class='active-div text-center px-3 py-1'>Accepted</div>"; }
                                else if ($status == 2) { $status_div = "<div class='inactive-div text-center px-3 py-1'>Rejected</div>"; }
                                else { $status_div = "<div class='pending-div text-center px-3 py-1'>Pending</div>"; }
                                // add the user who accepted/rejected the request
                                if ($status == 1 || $status == 2)
                                {
                                    if ($accepted_username <> "" && $display_accepted_at <> "") 
                                    {
                                        $status_div .= "<div class='my-1'>
                                            $accepted_username<br>
                                            $display_accepted_at
                                        </div>";
                                    }
                                }

                                // build the filter status column
                                $filter_status = "";
                                if ($status == 1) { $filter_status = "Accepted"; }
                                else if ($status == 2) { $filter_status = "Rejected"; }
                                else { $filter_status = "Pending"; }

                                // build the actions column
                                $actions = "";
                                if ($status != 1 && $status != 2)
                                {
                                    $actions = "<div class='d-flex justify-content-end'>
                                        <button class='btn btn-primary mx-1' onclick='getEditChangeRequestModal($request_id);'>
                                            <i class='fa-solid fa-pencil'></i>
                                        </button>
                                    </div>";
                                }

                                // build the temporary array
                                $temp = [];
                                $temp["employee"] = $employee_name;
                                $temp["days"] = $days_display;
                                $temp["salary"] = $salary_display;
                                $temp["reason"] = $reason;
                                $temp["request_details"] = $request_details;
                                $temp["status"] = $status_div;
                                $temp["actions"] = $actions;
                                $temp["filter_status"] = $filter_status;

                                // add the request to master list
                                $requests[] = $temp;
                            }
                        }
                    }
                }
            }
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $requests;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>