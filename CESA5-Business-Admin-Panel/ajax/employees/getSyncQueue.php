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
                $getQueue = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, e.most_recent_hire_date, e.most_recent_end_date, e.original_hire_date, e.original_end_date, 
                                                            ec.title_id, ec.yearly_rate, ec.contract_days, ec.contract_start_date, ec.contract_end_date, ec.calendar_type, ec.number_of_pays, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.active,
                                                            sq.id AS queue_id, sq.field, sq.value, sq.request_time, sq.action_time, sq.action_user, sq.status
                                                    FROM sync_queue_employee_compensation sq 
                                                    JOIN employees e ON e.id=sq.employee_id
                                                    LEFT JOIN employee_compensation ec ON sq.employee_id=ec.employee_id AND ec.period_id=sq.period_id
                                                    WHERE ec.period_id=? AND sq.status=0 AND e.queued=0");
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
                            $most_recent_hire_date = $employee["most_recent_hire_date"];
                            $most_recent_end_date = $employee["most_recent_end_date"];
                            $original_hire_date = $employee["original_hire_date"];
                            $original_end_date = $employee["original_end_date"];
                            $title_id = $employee["title_id"];
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
                            $field = $employee["field"];
                            $value = $employee["value"];
                            $request_time = $employee["request_time"];
                            $action_time = $employee["action_time"];
                            $action_user = $employee["action_user"];
                            $status = $employee["status"];
                            
                            // handle contract date validation
                            if (isset($most_recent_hire_date) && $most_recent_hire_date != null) { $most_recent_hire_date = date("m/d/Y", strtotime($most_recent_hire_date)); } else { $most_recent_hire_date = ""; }
                            if (isset($most_recent_end_date) && $most_recent_end_date != null) { $most_recent_end_date = date("m/d/Y", strtotime($most_recent_end_date)); } else { $most_recent_end_date = ""; }
                            if (isset($original_hire_date) && $original_hire_date != null) { $original_hire_date = date("m/d/Y", strtotime($original_hire_date)); } else { $original_hire_date = ""; }
                            if (isset($original_end_date) && $original_end_date != null) { $original_end_date = date("m/d/Y", strtotime($original_end_date)); } else { $original_end_date = ""; }
                            if (isset($contract_start_date) && $contract_start_date != null) { $contract_start_date = date("m/d/Y", strtotime($contract_start_date)); } else { $contract_start_date = ""; }
                            if (isset($contract_end_date) && $contract_end_date != null) { $contract_end_date = date("m/d/Y", strtotime($contract_end_date)); } else { $contract_end_date = ""; }

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
                                if ($health == 1) { $old_display = "Family"; }
                                else if ($health == 2) { $old_display = "Single"; }
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
                                if ($dental == 1) { $old_display = "Family"; }
                                else if ($dental == 2) { $old_display = "Single"; }
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
                                if ($wrs == 1) { $old_display = "Yes"; }
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
                                if ($active == 1) { $old_display = "Active"; }
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
                                $old_display = $most_recent_hire_date;

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
                                $old_display = $most_recent_end_date;

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
                                $old_display = $original_hire_date;

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
                                $old_display = $original_end_date;

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
                                $old_display = $contract_start_date;

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
                                $old_display = $contract_end_date;

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
                                $old_display = printDollar($yearly_rate);

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
                                $old_display = $contract_days;

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
                                if ($calendar_type == 1) { $old_display = "Hourly"; } 
                                else if ($calendar_type == 2) { $old_display = "Salary"; } 
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
                                $old_display = $num_of_pays;

                                // build new display
                                $new_display = $value;
                            }

                            // build the actions column
                            $actions = "";
                            if ($status == 0)
                            {
                                $actions = "<div class='d-flex justify-content-end'>
                                    <button class='btn btn-success btn-sm mx-1' id='btn-action-success-".$queue_id."' onclick='syncAction(".$queue_id.", 1);'>
                                        <i class='fa-solid fa-check'></i>
                                    </button>

                                    <button class='btn btn-danger btn-sm mx-1' id='btn-action-danger-".$queue_id."' onclick='syncAction(".$queue_id.", 0);'>
                                        <i class='fa-solid fa-xmark'></i>
                                    </button>
                                </div>";
                            }

                            // build the temporary array
                            $temp = [];
                            $temp["id"] = $employee_id;
                            $temp["lname"] = $lname;
                            $temp["fname"] = $fname;
                            $temp["field"] = $field;
                            $temp["new"] = $new_display;
                            $temp["old"] = $old_display;
                            $temp["requested"] = date("m/d/Y H:i:s", strtotime($request_time));
                            $temp["status"] = "Pending";
                            $temp["actions"] = $actions;

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