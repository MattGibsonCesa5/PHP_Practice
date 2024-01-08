<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to queue
        $queue = [];

        // verify user is an admin
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // override server settings
            ini_set("max_execution_time", 1800); // cap to 30 minutes
            ini_set("memory_limit", "1024M"); // cap to 1024 MB (1 GB)

            // include additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");
            include("../../getSettings.php");
            
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
            {
                // store if the period is editable
                $is_editable = isPeriodEditable($conn, $period_id);

                // get sync queue
                $getQueue = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, sq.id AS queue_id, sq.field, sq.value, sq.request_time, sq.action_time, sq.action_user, sq.status, sq.old_value
                                                    FROM sync_queue_employee_compensation sq 
                                                    JOIN employees e ON e.id=sq.employee_id
                                                    WHERE sq.period_id=? AND sq.status!=0");
                mysqli_stmt_bind_param($getQueue, "i", $period_id);
                if (mysqli_stmt_execute($getQueue))
                {
                    $getQueueResult = mysqli_stmt_get_result($getQueue);
                    if (mysqli_num_rows($getQueueResult) > 0) // there are employee changes queued to sync
                    {
                        while ($employee = mysqli_fetch_array($getQueueResult))
                        {
                            // store employee details locally
                            $queue_id = $employee["queue_id"];
                            $employee_id = $employee["id"];
                            $fname = $employee["fname"];
                            $lname = $employee["lname"];
                            $field = $employee["field"];
                            $value = $employee["value"];
                            $old_value = $employee["old_value"];
                            $request_time = $employee["request_time"];
                            $action_time = $employee["action_time"];
                            $action_user = $employee["action_user"];
                            $status = $employee["status"];

                            // based on field, get/set values to be displayed
                            $new_display = $old_display = "";
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Health Insurance
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "health_insurance")
                            {
                                // build old display
                                if ($old_value == 1) { $old_display = "Family"; }
                                else if ($old_value == 2) { $old_display = "Single"; }
                                else { $old_display = "None"; }

                                // build old display
                                if ($value == 1) { $new_display = "Family"; }
                                else if ($value == 2) { $new_display = "Single"; }
                                else { $new_display = "None"; }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Dental Insurance
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "dental_insurance")
                            {
                                // build old display
                                if ($old_value == 1) { $old_display = "Family"; }
                                else if ($old_value == 2) { $old_display = "Single"; }
                                else { $old_display = "None"; }

                                // build old display
                                if ($value == 1) { $new_display = "Family"; }
                                else if ($value == 2) { $new_display = "Single"; }
                                else { $new_display = "None"; }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  WRS Eligibility
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "wrs_eligible")
                            {
                                // build old display
                                if ($old_value == 1) { $old_display = "Yes"; }
                                else { $old_display = "No"; }

                                // build old display
                                if ($value == 1) { $new_display = "Yes"; }
                                else { $new_display = "No"; }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Active Status
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "active")
                            {
                                // build old display
                                if ($old_value == 1) { $old_display = "Active"; }
                                else { $old_display = "Inactive"; }

                                // build old display
                                if ($value == 1) { $new_display = "Active"; }
                                else { $new_display = "Inactive"; }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Most Recent Start Date
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "most_recent_hire_date")
                            {
                                // build old display
                                $old_display = $old_value;

                                // build new display
                                if (isset($value) && $value != null && trim($value) <> "")
                                {
                                    $new_display = date("m/d/Y", strtotime($value));
                                } else {
                                    $new_display = "";
                                }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Most Recent End Date
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "most_recent_end_date")
                            {
                                // build old display
                                $old_display = $old_value;

                                // build new display
                                if (isset($value) && $value != null && trim($value) <> "")
                                {
                                    $new_display = date("m/d/Y", strtotime($value));
                                } else {
                                    $new_display = "";
                                }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Original Start Date
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "original_hire_date")
                            {
                                // build old display
                                $old_display = $old_value;

                                // build new display
                                if (isset($value) && $value != null && trim($value) <> "")
                                {
                                    $new_display = date("m/d/Y", strtotime($value));
                                } else {
                                    $new_display = "";
                                }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Original End Date
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "original_end_date")
                            {
                                // build old display
                                $old_display = $old_value;

                                // build new display
                                if (isset($value) && $value != null && trim($value) <> "")
                                {
                                    $new_display = date("m/d/Y", strtotime($value));
                                } else {
                                    $new_display = "";
                                }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Contract Start Date
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "contract_start_date")
                            {
                                // build old display
                                $old_display = $old_value;

                                // build new display
                                if (isset($value) && $value != null && trim($value) <> "")
                                {
                                    $new_display = date("m/d/Y", strtotime($value));
                                } else {
                                    $new_display = "";
                                }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Contract End Date
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "contract_end_date")
                            {
                                // build old display
                                $old_display = $old_value;

                                // build new display
                                if (isset($value) && $value != null && trim($value) <> "")
                                {
                                    $new_display = date("m/d/Y", strtotime($value));
                                } else {
                                    $new_display = "";
                                }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Yearly Rate
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "yearly_rate")
                            {
                                // build old display
                                $old_display = printDollar($old_value);

                                // build new display
                                if (isset($value) && $value != null && $value > 0)
                                {
                                    $new_display = printDollar($value);
                                } else {
                                    $new_display = "$0.00";
                                }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Contract Days
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "contract_days")
                            {
                                // build old display
                                $old_display = $old_value;

                                // build new display
                                $new_display = intval($value);
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Calendar Type
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "calendar_type")
                            {
                                // build old display
                                if ($old_value == 1) { $old_display = "Hourly"; } 
                                else if ($old_value == 2) { $old_display = "Salary"; } 
                                else { $old_display = "N/A"; }

                                // build new display
                                if (isset($value) && intval($value) == 1) { $new_display = "Hourly"; } 
                                else if (isset($value) && intval($value) == 2) { $new_display = "Salary"; } 
                                else { $new_display = "N/A"; }
                            }
                            ///////////////////////////////////////////////////////////////////////
                            //
                            //  Number Of Pays
                            //
                            ///////////////////////////////////////////////////////////////////////
                            if ($field == "number_of_pays")
                            {
                                // build old display
                                $old_display = $old_value;

                                // build new display
                                $new_display = $value;
                            }

                            // build status column
                            $status_div = "";
                            if ($status == 1) { $status_div = "<div class='active-div text-center px-3 py-1'>Accepted</div>"; }
                            else if ($status == 2) { $status_div = "<div class='inactive-div text-center px-3 py-1'>Rejected</div>"; }
                            else if ($status == 3) { $status_div = "<div class='skipped-div text-center px-3 py-1'>Skipped</div>"; }
                            else { $status_div = "<div class='pending-div text-center px-3 py-1'>Unknown</div>"; }

                            // action user
                            $action_div = "";
                            $action_username = getUserDisplayName($conn, $action_user);
                            $display_action_time = $display_request_time = "";
                            // convert the dates to readable format
                            $DB_Timezone = HOST_TIMEZONE;
                            if (isset($action_time)) { $display_action_time = date_convert($action_time, $DB_Timezone, "America/Chicago", "n/j/y g:i:s A"); }
                            if (isset($request_time)) { $display_request_time = date_convert($request_time, $DB_Timezone, "America/Chicago", "n/j/y g:i:s A"); }
                            if ($status == 1 || $status == 2)
                            {
                                // display user
                                if (isset($action_username) && trim($action_username) <> "") {
                                    $action_div .= "<div class='my-1'>".$action_username."</div>";
                                } else {
                                    $action_div .= "<div class='missing-field my-1'>User Unknown</div>";
                                }

                                // display time
                                if (isset($display_action_time) && trim($display_action_time) <> "") { 
                                    $action_div .= "<div class='my-1'>".$display_action_time."</div>";
                                } else {
                                    $action_div .= "<div class='missing-field my-1'>Time Unknown</div>";
                                }
                            } 
                            else if ($status == 3)
                            {
                                // display time
                                if (isset($display_action_time) && trim($display_action_time) <> "") { 
                                    $action_div .= "<div class='my-1'>".$display_action_time."</div>";
                                } else {
                                    $action_div .= "<div class='missing-field my-1'>Time Unknown</div>";
                                }
                            }

                            // build the temporary array
                            $temp = [];
                            $temp["id"] = $employee_id;
                            $temp["lname"] = $lname;
                            $temp["fname"] = $fname;
                            $temp["field"] = $field;
                            $temp["new"] = $new_display;
                            $temp["old"] = $old_display;
                            $temp["requested"] = $display_request_time;
                            $temp["status"] = $status_div;
                            $temp["action"] = $action_div;
                            $temp["action_time"] = strtotime($action_time);
                            $queue[] = $temp;
                        }
                    }
                }
            }
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $queue;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>