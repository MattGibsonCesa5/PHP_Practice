<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_SERVICES"))
        {
            // get service details from POST
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }
            if (isset($_POST["form_service_id"]) && $_POST["form_service_id"] <> "") { $form_service_id = $_POST["form_service_id"]; } else { $form_service_id = null; }
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }
            if (isset($_POST["service_name"]) && $_POST["service_name"] <> "") { $service_name = $_POST["service_name"]; } else { $service_name = null; }
            if (isset($_POST["description"]) && $_POST["description"] <> "") { $description = $_POST["description"]; } else { $description = null; }
            if (isset($_POST["export_label"]) && $_POST["export_label"] <> "") { $export_label = $_POST["export_label"]; } else { $export_label = null; }
            if (isset($_POST["cost_type"]) && $_POST["cost_type"] <> "") { $cost_type = $_POST["cost_type"]; } else { $cost_type = null; }
            if (isset($_POST["unit_label"]) && $_POST["unit_label"] <> "") { $unit_label = $_POST["unit_label"]; } else { $unit_label = null; }
            if (isset($_POST["fund_code"]) && $_POST["fund_code"] <> "") { $fund_code = $_POST["fund_code"]; } else { $fund_code = null; }
            if (isset($_POST["object_code"]) && $_POST["object_code"] <> "") { $object_code = $_POST["object_code"]; } else { $object_code = null; }
            if (isset($_POST["function_code"]) && $_POST["function_code"] <> "") { $function_code = $_POST["function_code"]; } else { $function_code = null; }
            if (isset($_POST["project_code"]) && $_POST["project_code"] <> "") { $project_code = $_POST["project_code"]; } else { $project_code = null; }
            if (isset($_POST["round_costs"]) && $_POST["round_costs"] <> "") { $round_costs = $_POST["round_costs"]; } else { $round_costs = 0; }

            if ($period_id != null && verifyPeriod($conn, $period_id))
            {
                if ($service_id != null && $form_service_id != null)
                {
                    if ($service_name != null)
                    {
                        if (is_numeric($cost_type) && ($cost_type == 0 || $cost_type == 1 || $cost_type == 2 || $cost_type == 3 || $cost_type == 4 || $cost_type == 5))
                        {
                            if ($fund_code != null && $object_code != null && $function_code != null)
                            {
                                if (is_numeric($fund_code) && ($fund_code >= 10 && $fund_code <= 99))
                                {
                                    if ($unit_label != null)
                                    {
                                        if ($round_costs == 1 || $round_costs == 0)
                                        {
                                            // verify that the service exists given the service ID
                                            $verifyService = mysqli_prepare($conn, "SELECT id FROM services WHERE id=?");
                                            mysqli_stmt_bind_param($verifyService, "s", $service_id);
                                            if (mysqli_stmt_execute($verifyService))
                                            {
                                                $verifyResult = mysqli_stmt_get_result($verifyService);
                                                if (mysqli_num_rows($verifyResult) > 0) // service exists; proceed with edits
                                                {
                                                    // verify the project code if selected
                                                    $project_verified = false; // assume project is not verified
                                                    if ($project_code <> "" && $project_code != null && is_numeric($project_code))
                                                    {
                                                        // verify that the project exists
                                                        $checkProject = mysqli_prepare($conn, "SELECT code FROM projects WHERE code=?");
                                                        mysqli_stmt_bind_param($checkProject, "i", $project_code);
                                                        if (mysqli_stmt_execute($checkProject))
                                                        {
                                                            $checkProjectResult = mysqli_stmt_get_result($checkProject);
                                                            if (mysqli_num_rows($checkProjectResult) > 0) // project exists; proceed with creation
                                                            {
                                                                $project_verified = true;
                                                            }
                                                        }
                                                    }
                                                    else // creating service without assigning to project; bypass verification
                                                    { 
                                                        $project_code = null; // ensure we are setting project code to null
                                                        $project_verified = true; 
                                                    }

                                                    if ($project_verified === true)
                                                    {
                                                        $updateService = mysqli_prepare($conn, "UPDATE services SET name=?, cost_type=?, unit_label=?, description=?, export_label=?, fund_code=?, object_code=?, function_code=?, project_code=?, round_costs=? WHERE id=?");
                                                        mysqli_stmt_bind_param($updateService, "sisssssssis", $service_name, $cost_type, $unit_label, $description, $export_label, $fund_code, $object_code, $function_code, $project_code, $round_costs, $service_id);
                                                        if (mysqli_stmt_execute($updateService)) // successfully updated the services details; attempt to update costs
                                                        {
                                                            echo "<span class=\"log-success\">Successfully</span> updated the service.<br>";

                                                            // delete current costs for the service
                                                            $deleteCosts = mysqli_prepare($conn, "DELETE FROM costs WHERE service_id=? AND period_id=?");
                                                            mysqli_stmt_bind_param($deleteCosts, "si", $service_id, $period_id);
                                                            if (mysqli_stmt_execute($deleteCosts)) // successfully deleted current costs, insert new costs
                                                            {
                                                                // if cost type is fixed (0), get the fixed cost
                                                                if ($cost_type == 0)
                                                                {
                                                                    if (isset($_POST["fixed_cost"]) && $_POST["fixed_cost"] <> "" && is_numeric($_POST["fixed_cost"])) 
                                                                    { 
                                                                        $fixed_cost = $_POST["fixed_cost"]; 
                                                                        $query = mysqli_prepare($conn, "INSERT INTO costs (service_id, cost, cost_type, period_id) VALUES (?, ?, ?, ?)");
                                                                        mysqli_stmt_bind_param($query, "sdii", $service_id, $fixed_cost, $cost_type, $period_id);
                                                                        if (!mysqli_stmt_execute($query)) { echo "<span class=\"log-fail\">Failed</span> to edit the service's cost. An unknown error has occurred. Please try again later.<br>"; } 
                                                                    } 
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the service's cost. No cost was provided.<br>"; }
                                                                }
                                                                // if the cost type is variable (1), get the variable costs
                                                                else if ($cost_type == 1)
                                                                {
                                                                    if (isset($_POST["variable_costs"]))
                                                                    {
                                                                        $variable_costs = json_decode($_POST["variable_costs"]);

                                                                        for ($r = 0; $r < count($variable_costs); $r++)
                                                                        {
                                                                            $order = $variable_costs[$r][0];
                                                                            $min = $variable_costs[$r][1];
                                                                            $max = $variable_costs[$r][2];
                                                                            $cost = $variable_costs[$r][3];

                                                                            // if the max is 0, set to -1 as limitless max
                                                                            if ($max == 0 || $max == null || $max == "") { $max = -1; }

                                                                            // if there is a minimum quantity set with an assigned cost, add to cost grid
                                                                            if (($min <> "" && $cost <> "") && ($min != null && $cost != null))
                                                                            {
                                                                                $addRange = mysqli_prepare($conn, "INSERT INTO costs (service_id, cost, min_quantity, max_quantity, variable_order, cost_type, period_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                                                                mysqli_stmt_bind_param($addRange, "sdiiiii", $service_id, $cost, $min, $max, $order, $cost_type, $period_id);
                                                                                if (!mysqli_stmt_execute($addRange)) { echo "<span class=\"log-fail\">Failed</span> to add a range to the service's variable cost grid. An unknown error has occurred. Please try again later.<br>"; }
                                                                            }
                                                                            else { return; } // break out of function if we are skipping a range
                                                                        }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the service's cost. No costs were provided.<br>"; }
                                                                }
                                                                // if the cost type is a membership (2), get the membership costs parameters
                                                                else if ($cost_type == 2)
                                                                {
                                                                    if (isset($_POST["membership_total_cost"]) && isset($_POST["membership_group"]))
                                                                    {
                                                                        // get POST parameters
                                                                        $membership_total_cost = $_POST["membership_total_cost"];
                                                                        $membership_group = $_POST["membership_group"];

                                                                        // verify group exists
                                                                        $checkGroup = mysqli_prepare($conn, "SELECT id FROM `groups` WHERE id=?");
                                                                        mysqli_stmt_bind_param($checkGroup, "i", $membership_group);
                                                                        if (mysqli_stmt_execute($checkGroup))
                                                                        {
                                                                            $checkGroupResult = mysqli_stmt_get_result($checkGroup);
                                                                            if (mysqli_num_rows($checkGroupResult) > 0) // group exists
                                                                            {
                                                                                $addCost = mysqli_prepare($conn, "INSERT INTO costs (service_id, cost, group_id, cost_type, period_id) VALUES (?, ?, ?, ?, ?)");
                                                                                mysqli_stmt_bind_param($addCost, "sdiii", $service_id, $membership_total_cost, $membership_group, $cost_type, $period_id);
                                                                                if (!mysqli_stmt_execute($addCost)) { echo "<span class=\"log-fail\">Failed</span> to assign the service it's cost.<br>"; }
                                                                            }
                                                                            else { echo "<span class=\"log-fail\">Failed</span> to edit the service's cost. The membership group selected does not exist!<br>"; }
                                                                        }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to create the service's cost. No cost or group were provided.<br>"; }
                                                                }
                                                                else if ($cost_type == 3) { } // custom cost - do nothing during service creation
                                                                // if the cost type is a rates-based cost (4), get the rates
                                                                else if ($cost_type == 4)
                                                                {
                                                                    if (isset($_POST["rates"]))
                                                                    {
                                                                        $rates = json_decode($_POST["rates"]);
                                                                        
                                                                        for ($r = 0; $r < count($rates); $r++)
                                                                        {
                                                                            $tier = $rates[$r][0];
                                                                            $cost = $rates[$r][1];

                                                                            // if there is a cost; add rate
                                                                            if (is_numeric($cost) && $cost > 0)
                                                                            {
                                                                                $addRate = mysqli_prepare($conn, "INSERT INTO costs (service_id, cost, variable_order, cost_type, period_id) VALUES (?, ?, ?, ?, ?)");
                                                                                mysqli_stmt_bind_param($addRate, "sdiii", $service_id, $cost, $tier, $cost_type, $period_id);
                                                                                if (!mysqli_stmt_execute($addRate)) { echo "<span class=\"log-fail\">Failed</span> to assign the service it's rate for tier $tier.<br>"; }
                                                                            }
                                                                            else { return; } // break out of function if we are skipping a range
                                                                        }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the service's cost. No costs were provided.<br>"; }
                                                                }
                                                                // if the cost type is a group-rates-based cost (5), get the group rates
                                                                else if ($cost_type == 5)
                                                                {
                                                                    if (isset($_POST["group_rates"]) && isset($_POST["rate_group"]))
                                                                    {
                                                                        // get the rates
                                                                        $rates = json_decode($_POST["group_rates"]);

                                                                        // get the group
                                                                        $rate_group = $_POST["rate_group"];
                                                                        
                                                                        // verify group exists
                                                                        $checkGroup = mysqli_prepare($conn, "SELECT id FROM `groups` WHERE id=?");
                                                                        mysqli_stmt_bind_param($checkGroup, "i", $rate_group);
                                                                        if (mysqli_stmt_execute($checkGroup))
                                                                        {
                                                                            $checkGroupResult = mysqli_stmt_get_result($checkGroup);
                                                                            if (mysqli_num_rows($checkGroupResult) > 0) // group exists
                                                                            {
                                                                                for ($r = 0; $r < count($rates); $r++)
                                                                                {
                                                                                    $tier = $rates[$r][0];
                                                                                    $inside_cost = $rates[$r][1];
                                                                                    $outside_cost = $rates[$r][2];

                                                                                    // if there is a cost; add rate
                                                                                    if ((is_numeric($inside_cost) && $inside_cost > 0) && (is_numeric($outside_cost) && $outside_cost > 0))
                                                                                    {
                                                                                        $addInsideRate = mysqli_prepare($conn, "INSERT INTO costs (service_id, cost, variable_order, cost_type, group_id, in_group, period_id) VALUES (?, ?, ?, ?, ?, 1, ?)");
                                                                                        mysqli_stmt_bind_param($addInsideRate, "sdiiii", $service_id, $inside_cost, $tier, $cost_type, $rate_group, $period_id);
                                                                                        if (!mysqli_stmt_execute($addInsideRate)) { echo "<span class=\"log-fail\">Failed</span> to assign the service it's inside of group rate for tier $tier.<br>"; }

                                                                                        $addOutsideRate = mysqli_prepare($conn, "INSERT INTO costs (service_id, cost, variable_order, cost_type, group_id, in_group, period_id) VALUES (?, ?, ?, ?, ?, 0, ?)");
                                                                                        mysqli_stmt_bind_param($addOutsideRate, "sdiiii", $service_id, $outside_cost, $tier, $cost_type, $rate_group, $period_id);
                                                                                        if (!mysqli_stmt_execute($addOutsideRate)) { echo "<span class=\"log-fail\">Failed</span> to assign the service it's outside of group rate for tier $tier.<br>"; }
                                                                                    }
                                                                                    else { return; } // break out of function if we are skipping a range
                                                                                }
                                                                            }
                                                                            else { echo "<span class=\"log-fail\">Failed</span> to create the service's cost. The rate group selected does not exist.<br>"; }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to create the service's cost. An unexpected error has occurred! Please try again later.<br>"; }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to create the service's cost. No costs were provided.<br>"; }
                                                                }
                                                                else { echo "<span class=\"log-fail\">Failed</span> to edit the service's cost. An unknown cost type was provided.<br>"; }

                                                                // log service edit
                                                                $message = "Successfully edited the service with ID $service_id.<br>";
                                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                                mysqli_stmt_execute($log);

                                                                if ($service_id != $form_service_id)
                                                                {
                                                                    echo "Attempting to edit the service ID...<br>";

                                                                    if (!verifyService($conn, $form_service_id))
                                                                    {
                                                                        if (!verifyOtherService($conn, $form_service_id))
                                                                        {
                                                                            $updateServiceID = mysqli_prepare($conn, "UPDATE services SET id=? WHERE id=?");
                                                                            mysqli_stmt_bind_param($updateServiceID, "ss", $form_service_id, $service_id);
                                                                            if (mysqli_stmt_execute($updateServiceID))
                                                                            {
                                                                                $updateID = mysqli_prepare($conn, "UPDATE costs SET service_id=? WHERE service_id=?");
                                                                                mysqli_stmt_bind_param($updateID, "ss", $form_service_id, $service_id);
                                                                                if (!mysqli_stmt_execute($updateID)) { /* TODO - handle ID update error */ }

                                                                                $updateID = mysqli_prepare($conn, "UPDATE quarterly_archive SET service_id=? WHERE service_id=?");
                                                                                mysqli_stmt_bind_param($updateID, "ss", $form_service_id, $service_id);
                                                                                if (!mysqli_stmt_execute($updateID)) { /* TODO - handle ID update error */ }

                                                                                $updateID = mysqli_prepare($conn, "UPDATE quarterly_costs SET service_id=? WHERE service_id=?");
                                                                                mysqli_stmt_bind_param($updateID, "ss", $form_service_id, $service_id);
                                                                                if (!mysqli_stmt_execute($updateID)) { /* TODO - handle ID update error */ }

                                                                                $updateID = mysqli_prepare($conn, "UPDATE services_provided SET service_id=? WHERE service_id=?");
                                                                                mysqli_stmt_bind_param($updateID, "ss", $form_service_id, $service_id);
                                                                                if (!mysqli_stmt_execute($updateID)) { /* TODO - handle ID update error */ }

                                                                                // log service ID edit
                                                                                echo "<span class=\"log-success\">Successfully</span> updated the service ID to $form_service_id from ID $service_id.<br>";
                                                                                $message = "Successfully updated the service ID to $form_service_id from ID $service_id.";
                                                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                                                mysqli_stmt_execute($log);
                                                                            }
                                                                            else { echo "<span class=\"log-fail\">Failed</span> to edit the service's ID. An unexpected error has occurred! Please try again later.<br>"; }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to edit the service's ID. An \"other\" service with that ID already exists! Service IDs must be unique!<br>"; }
                                                                    }
                                                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the service's ID. A service with that ID already exists! Service IDs must be unique!<br>"; }
                                                                }
                                                            }
                                                            else { echo "<span class=\"log-fail\">Failed</span> to edit the service's costs. An unknown error has occurred. Please try again later.<br>"; }
                                                        }
                                                        else { echo "<span class=\"log-fail\">Failed</span> to edit the service. An unknown error has occurred. Please try again later.<br>"; }
                                                    }
                                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the service. The project code selected is invalid.<br>"; }
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to edit the service. The service you are trying to edit does not exist.<br>"; }
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to edit the service. An unknown error has occurred. Please try again later.<br>"; }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to edit the service. You must provide a valid round costs option.<br>"; }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the service. You must provide a unit label.<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the service. The fund code must follow the WUFAR convention and be a number within 10 and 99!<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the service. You must provide a fund code, object code, and function code.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the service. The cost type selected was invalid.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the service. You must provide the service a name.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the service. The service ID provided was invalid. Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the service. The period selected was invalid. Please try again later.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the service. Your account does not have permission to edit service!<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>