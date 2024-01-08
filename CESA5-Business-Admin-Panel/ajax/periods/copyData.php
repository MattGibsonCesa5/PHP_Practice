<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
            
            // get period parameters from POST
            if (isset($_POST["from_period"]) && is_numeric($_POST["from_period"])) { $from_period = $_POST["from_period"]; } else { $from_period = null; }
            if (isset($_POST["to_period"]) && is_numeric($_POST["to_period"])) { $to_period = $_POST["to_period"]; } else { $to_period = null; }

            // get data to copy from POST 
            if (isset($_POST["employee_compensation"]) && (is_numeric($_POST["employee_compensation"]) && $_POST["employee_compensation"] == 1)) { $copyEmployeeCompensation = 1; } else { $copyEmployeeCompensation = 0; }
            if (isset($_POST["employee_expenses"]) && (is_numeric($_POST["employee_expenses"]) && $_POST["employee_expenses"] == 1)) { $copyEmployeeExpenses = 1; } else { $copyEmployeeExpenses = 0; }
            if (isset($_POST["project_status"]) && (is_numeric($_POST["project_status"]) && $_POST["project_status"] == 1)) { $copyProjectStatus = 1; } else { $copyProjectStatus = 0; }
            if (isset($_POST["project_employees"]) && (is_numeric($_POST["project_employees"]) && $_POST["project_employees"] == 1)) { $copyProjectEmployees = 1; } else { $copyProjectEmployees = 0; }
            if (isset($_POST["project_expenses"]) && (is_numeric($_POST["project_expenses"]) && $_POST["project_expenses"] == 1)) { $copyProjectExpenses = 1; } else { $copyProjectExpenses = 0; }
            if (isset($_POST["service_costs"]) && (is_numeric($_POST["service_costs"]) && $_POST["service_costs"] == 1)) { $copyServiceCosts = 1; } else { $copyServiceCosts = 0; }
            if (isset($_POST["invoices"]) && (is_numeric($_POST["invoices"]) && $_POST["invoices"] == 1)) { $copyInvoices = 1; } else { $copyInvoices = 0; }
            if (isset($_POST["revenues"]) && (is_numeric($_POST["revenues"]) && $_POST["revenues"] == 1)) { $copyRevenues = 1; } else { $copyRevenues = 0; }

            // verify the from period is valid
            if (verifyPeriod($conn, $from_period))
            {
                // verify the to period is valid
                if (verifyPeriod($conn, $to_period))
                {
                    // verify we are not copying into the same period
                    if ($from_period != $to_period)
                    {
                        // get period names
                        $from_period_name = getPeriodName($conn, $from_period);
                        $to_period_name = getPeriodName($conn, $to_period);

                        ///////////////////////////////////////////////////////////////////////////////
                        // 
                        //  EMPLOYEE COMPENSATION
                        //
                        ///////////////////////////////////////////////////////////////////////////////
                        if ($copyEmployeeCompensation == 1)
                        {
                            // display data we are copying for
                            echo "========== <b>EMPLOYEE COMPENSATION</b> ==========<br>";

                            // clear existing data for the period we are copying data into
                            $clearData = mysqli_prepare($conn, "DELETE FROM employee_compensation WHERE period_id=?");
                            mysqli_stmt_bind_param($clearData, "i", $to_period);
                            if (mysqli_stmt_execute($clearData)) // successfully cleared data
                            {
                                // log successful clear
                                echo "<span class=\"log-success\">Successfully</span> cleared employee compensation for $to_period_name.<br>";
                                $message = "Successfully cleared employee compensation for $to_period_name (period ID: $to_period).";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // copy data from the "from period" to the "to period"
                                $copyData = mysqli_prepare($conn, "INSERT INTO employee_compensation (employee_id, yearly_rate, contract_days, contract_type, contract_start_date, contract_end_date, calendar_type, number_of_pays, health_insurance, dental_insurance, wrs_eligible, title_id, assignment_position, sub_assignment, experience, highest_degree, active, period_id) 
                                                                    SELECT employee_id, yearly_rate, contract_days, contract_type, contract_start_date, contract_end_date, calendar_type, number_of_pays, health_insurance, dental_insurance, wrs_eligible, title_id, assignment_position, sub_assignment, experience, highest_degree, active, ? FROM employee_compensation WHERE period_id=?");
                                mysqli_stmt_bind_param($copyData, "ii", $to_period, $from_period);
                                if (mysqli_stmt_execute($copyData))
                                {
                                    // log copy success
                                    echo "<span class=\"log-success\">Successfully</span> copied employee compensation from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Successfully copied employee compensation from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);

                                    /* Disabling adding year of experience via copy (can do in employees list for time being)
                                    // attempt to add a year of experience if the option was selected
                                    if ($addYearOfExperience == 1)
                                    {
                                        // prepare and execute the query to add a year of experience to all employees in the "to period"
                                        $addYear = mysqli_prepare($conn, "UPDATE employee_compensation SET experience=experience+1 WHERE period_id=?");
                                        mysqli_stmt_bind_param($addYear, "i", $to_period);
                                        if (mysqli_stmt_execute($addYear)) 
                                        { 
                                            // log add year of experience success
                                            echo "<span class=\"log-success\">Successfully</span> added a year of experience to all employees for $to_period_name.<br>"; 
                                            $message = "Successfully added a year of experience to all employees for $to_period_name (period ID: $to_period).";
                                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                            mysqli_stmt_execute($log);
                                        }
                                        else 
                                        { 
                                            // log add year of experience error
                                            echo "<span class=\"log-fail\">Failed</span> to add a year of experience to all employees for $to_period_name.<br>"; 
                                            $message = "Failed to add a year of experience to all employees for $to_period_name (period ID: $to_period).";
                                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                            mysqli_stmt_execute($log);
                                        }
                                    }
                                    */
                                }
                                else 
                                { 
                                    // log copy fail
                                    echo "<span class=\"log-fail\">Failed</span> to copy employee compensation from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Failed to copy employee compensation from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to copy employee compensation.<br>"; }

                            // display additional line break
                            echo "<br>";
                        }

                        ///////////////////////////////////////////////////////////////////////////////
                        // 
                        //  PROJECT STATUS
                        //
                        ///////////////////////////////////////////////////////////////////////////////
                        if ($copyProjectStatus == 1)
                        {
                            // display data we are copying for
                            echo "========== <b>PROJECT STATUS</b> ==========<br>";

                            // clear existing data for the period we are copying data into
                            $clearData = mysqli_prepare($conn, "DELETE FROM projects_status WHERE period_id=?");
                            mysqli_stmt_bind_param($clearData, "i", $to_period);
                            if (mysqli_stmt_execute($clearData)) // successfully cleared data
                            {
                                // log successful clear
                                echo "<span class=\"log-success\">Successfully</span> cleared project statuses for $to_period_name.<br>";
                                $message = "Successfully cleared project statuses for $to_period_name (period ID: $to_period).";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // copy data from the "from period" to the "to period"
                                $copyData = mysqli_prepare($conn, "INSERT INTO projects_status (code, period_id, status) 
                                                                    SELECT code, ?, status FROM projects_status WHERE period_id=?");
                                mysqli_stmt_bind_param($copyData, "ii", $to_period, $from_period);
                                if (mysqli_stmt_execute($copyData))
                                {
                                    // log copy success
                                    echo "<span class=\"log-success\">Successfully</span> copied project statuses from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Successfully copied project statuses from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else 
                                { 
                                    // log copy fail
                                    echo "<span class=\"log-fail\">Failed</span> to copy project statuses from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Failed to copy project statuses from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to copy project statuses.<br>"; }

                            // display additional line break
                            echo "<br>";
                        }

                        ///////////////////////////////////////////////////////////////////////////////
                        // 
                        //  PROJECT EMPLOYEES
                        //
                        ///////////////////////////////////////////////////////////////////////////////
                        if ($copyProjectEmployees == 1)
                        {
                            // display data we are copying for
                            echo "========== <b>PROJECT EMPLOYEES</b> ==========<br>";

                            // clear existing data for the period we are copying data into
                            $clearData = mysqli_prepare($conn, "DELETE FROM project_employees WHERE period_id=?");
                            mysqli_stmt_bind_param($clearData, "i", $to_period);
                            if (mysqli_stmt_execute($clearData)) // successfully cleared data
                            {
                                // log successful clear
                                echo "<span class=\"log-success\">Successfully</span> cleared project employees for $to_period_name.<br>";
                                $message = "Successfully cleared project employees for $to_period_name (period ID: $to_period).";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // copy data from the "from period" to the "to period"
                                $copyData = mysqli_prepare($conn, "INSERT INTO project_employees (project_code, employee_id, project_days, fund_code, location_code, object_code, function_code, period_id) 
                                                                    SELECT project_code, employee_id, project_days, fund_code, location_code, object_code, function_code, ? FROM project_employees WHERE period_id=?");
                                mysqli_stmt_bind_param($copyData, "ii", $to_period, $from_period);
                                if (mysqli_stmt_execute($copyData))
                                {
                                    // log copy success
                                    echo "<span class=\"log-success\">Successfully</span> copied project employees from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Successfully copied project employees from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else 
                                { 
                                    // log copy fail
                                    echo "<span class=\"log-fail\">Failed</span> to copy project employees from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Failed to copy project employees from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to copy project employees.<br>"; }

                            // display additional line break
                            echo "<br>";
                        }
                        
                        ///////////////////////////////////////////////////////////////////////////////
                        // 
                        //  PROJECT EXPENSES
                        //
                        ///////////////////////////////////////////////////////////////////////////////
                        if ($copyProjectExpenses == 1)
                        {
                            // display data we are copying for
                            echo "========== <b>PROJECT EXPENSES</b> ==========<br>";

                            // clear existing data for the period we are copying data into
                            $clearData = mysqli_prepare($conn, "DELETE FROM project_expenses WHERE period_id=?");
                            mysqli_stmt_bind_param($clearData, "i", $to_period);
                            if (mysqli_stmt_execute($clearData)) // successfully cleared data
                            {
                                // log successful clear
                                echo "<span class=\"log-success\">Successfully</span> cleared project expenses for $to_period_name.<br>";
                                $message = "Successfully cleared project expenses for $to_period_name (period ID: $to_period).";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // copy data from the "from period" to the "to period"
                                $copyData = mysqli_prepare($conn, "INSERT INTO project_expenses (project_code, expense_id, description, cost, fund_code, function_code, auto, period_id) 
                                                                    SELECT project_code, expense_id, description, cost, fund_code, function_code, auto, ? FROM project_expenses WHERE period_id=?");
                                mysqli_stmt_bind_param($copyData, "ii", $to_period, $from_period);
                                if (mysqli_stmt_execute($copyData))
                                {
                                    // log copy success
                                    echo "<span class=\"log-success\">Successfully</span> copied project expenses from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Successfully copied project expenses from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else 
                                { 
                                    // log copy fail
                                    echo "<span class=\"log-fail\">Failed</span> to copy project expenses from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Failed to copy project expenses from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to copy project expenses.<br>"; }

                            // display additional line break
                            echo "<br>";
                        }

                        ///////////////////////////////////////////////////////////////////////////////
                        // 
                        //  OTHER REVENUES
                        //
                        ///////////////////////////////////////////////////////////////////////////////
                        if ($copyRevenues == 1)
                        {
                            // display data we are copying for
                            echo "========== <b>OTHER REVENUES</b> ==========<br>";

                            // clear existing data for the period we are copying data into
                            $clearData = mysqli_prepare($conn, "DELETE FROM revenues WHERE period_id=?");
                            mysqli_stmt_bind_param($clearData, "i", $to_period);
                            if (mysqli_stmt_execute($clearData)) // successfully cleared data
                            {
                                // log successful clear
                                echo "<span class=\"log-success\">Successfully</span> cleared other revenues for $to_period_name.<br>";
                                $message = "Successfully cleared other revenues for $to_period_name (period ID: $to_period).";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // copy data from the "from period" to the "to period"
                                $copyData = mysqli_prepare($conn, "INSERT INTO revenues (name, description, date, fund_code, location_code, source_code, function_code, project_code, total_cost, quantity, period_id) 
                                                                    SELECT name, description, date, fund_code, location_code, source_code, function_code, project_code, total_cost, quantity, ? FROM revenues WHERE period_id=?");
                                mysqli_stmt_bind_param($copyData, "ii", $to_period, $from_period);
                                if (mysqli_stmt_execute($copyData))
                                {
                                    // log copy success
                                    echo "<span class=\"log-success\">Successfully</span> copied other revenues from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Successfully copied other revenues from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else 
                                { 
                                    // log copy fail
                                    echo "<span class=\"log-fail\">Failed</span> to copy other revenues from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Failed to copy other revenues from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to copy other revenues.<br>"; }

                            // display additional line break
                            echo "<br>";
                        }

                        ///////////////////////////////////////////////////////////////////////////////
                        // 
                        //  GLOBAL EMPLOYEE EXPENSES
                        //
                        ///////////////////////////////////////////////////////////////////////////////
                        if ($copyEmployeeExpenses == 1)
                        {
                            // display data we are copying for
                            echo "========== <b>GLOBAL EMPLOYEE EXPENSES</b> ==========<br>";

                            // clear existing data for the period we are copying data into
                            $clearData = mysqli_prepare($conn, "DELETE FROM global_expenses WHERE period_id=?");
                            mysqli_stmt_bind_param($clearData, "i", $to_period);
                            if (mysqli_stmt_execute($clearData)) // successfully cleared data
                            {
                                // log successful clear
                                echo "<span class=\"log-success\">Successfully</span> cleared global employee expenses for $to_period_name.<br>";
                                $message = "Successfully cleared global employee expenses for $to_period_name (period ID: $to_period).";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // copy data from the "from period" to the "to period"
                                $copyData = mysqli_prepare($conn, "INSERT INTO global_expenses (health_single, health_family, dental_single, dental_family, wrs_rate, FICA, LTD, life, agency_indirect, grant_rate, aidable_supervision, nonaidable_supervision, health_single_code, health_family_code, dental_single_code, dental_family_code, wrs_rate_code, FICA_code, LTD_code, life_code, agency_indirect_code, grant_rate_code, aidable_supervision_code, nonaidable_supervision_code, period_id) 
                                                                    SELECT health_single, health_family, dental_single, dental_family, wrs_rate, FICA, LTD, life, agency_indirect, grant_rate, aidable_supervision, nonaidable_supervision, health_single_code, health_family_code, dental_single_code, dental_family_code, wrs_rate_code, FICA_code, LTD_code, life_code, agency_indirect_code, grant_rate_code, aidable_supervision_code, nonaidable_supervision_code, ? FROM global_expenses WHERE period_id=?");
                                mysqli_stmt_bind_param($copyData, "ii", $to_period, $from_period);
                                if (mysqli_stmt_execute($copyData))
                                {
                                    // log copy success
                                    echo "<span class=\"log-success\">Successfully</span> copied global employee expenses from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Successfully copied global employee expenses from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else 
                                { 
                                    // log copy fail
                                    echo "<span class=\"log-fail\">Failed</span> to copy global employee expenses from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Failed to copy global employee expenses from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to copy global employee expenses.<br>"; }

                            // display additional line break
                            echo "<br>";
                        }

                        ///////////////////////////////////////////////////////////////////////////////
                        // 
                        //  SERVICE COSTS
                        //
                        ///////////////////////////////////////////////////////////////////////////////
                        if ($copyServiceCosts == 1)
                        {
                            // display data we are copying for
                            echo "========== <b>SERVICE COSTS</b> ==========<br>";

                            // clear existing data for the period we are copying data into
                            $clearData = mysqli_prepare($conn, "DELETE FROM costs WHERE period_id=?");
                            mysqli_stmt_bind_param($clearData, "i", $to_period);
                            if (mysqli_stmt_execute($clearData)) // successfully cleared data
                            {
                                // log successful clear
                                echo "<span class=\"log-success\">Successfully</span> cleared service costs for $to_period_name.<br>";
                                $message = "Successfully cleared service costs for $to_period_name (period ID: $to_period).";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // copy data from the "from period" to the "to period"
                                $copyData = mysqli_prepare($conn, "INSERT INTO costs (service_id, cost, min_quantity, max_quantity, variable_order, group_id, in_group, cost_type, period_id) 
                                                                    SELECT service_id, cost, min_quantity, max_quantity, variable_order, group_id, in_group, cost_type, ? FROM costs WHERE period_id=?");
                                mysqli_stmt_bind_param($copyData, "ii", $to_period, $from_period);
                                if (mysqli_stmt_execute($copyData))
                                {
                                    // log copy success
                                    echo "<span class=\"log-success\">Successfully</span> copied service costs from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Successfully copied service costs from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else 
                                { 
                                    // log copy fail
                                    echo "<span class=\"log-fail\">Failed</span> to copy service costs from $from_period_name to $to_period_name.<br>"; 
                                    $message = "Failed to copy service costs from $from_period_name (ID: $from_period) to $to_period_name (ID: $to_period).";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to copy service costs.<br>"; }

                            // display additional line break
                            echo "<br>";
                        }

                        ///////////////////////////////////////////////////////////////////////////////
                        // 
                        //  INVOICES
                        //
                        ///////////////////////////////////////////////////////////////////////////////
                        if ($copyInvoices == 1)
                        {
                            // display data we are copying for
                            echo "========== <b>INVOICES</b> ==========<br>";

                            // get all invoices from the "from" period
                            $getFromInvoices = mysqli_prepare($conn, "SELECT * FROM services_provided WHERE period_id=?");
                            mysqli_stmt_bind_param($getFromInvoices, "i", $from_period);
                            if (mysqli_stmt_execute($getFromInvoices))
                            {
                                $getFromInvoicesResults = mysqli_stmt_get_result($getFromInvoices);
                                if (mysqli_num_rows($getFromInvoicesResults) > 0) // invoices found; continue
                                {
                                    // for each invoice; copy invoice into the "to" period based on the "to" period's costs
                                    while ($invoice = mysqli_fetch_array($getFromInvoicesResults))
                                    {
                                        // store invoice details locally
                                        $service_id = $invoice["service_id"];
                                        $customer_id = $invoice["customer_id"];
                                        $quantity = $invoice["quantity"];

                                        // get service details
                                        $getServiceDetails = mysqli_prepare($conn, "SELECT name, project_code, cost_type, round_costs FROM services WHERE id=?");
                                        mysqli_stmt_bind_param($getServiceDetails, "s", $service_id);
                                        if (mysqli_stmt_execute($getServiceDetails))
                                        {
                                            $getServiceDetailsResult = mysqli_stmt_get_result($getServiceDetails);
                                            if (mysqli_num_rows($getServiceDetailsResult) > 0) // service found; continue
                                            {
                                                // store service details locally
                                                $serviceDetails = mysqli_fetch_array($getServiceDetailsResult);
                                                $service_name = $serviceDetails["name"];
                                                $service_cost_type = $serviceDetails["cost_type"];

                                                // get customer details
                                                if (verifyCustomer($conn, $customer_id) && $customerDetails = getCustomerDetails($conn, $customer_id))
                                                {
                                                    // store customer details locally
                                                    $customer_name = $customerDetails["name"];

                                                    // if cost type if fixed (0)
                                                    if ($service_cost_type == 0)
                                                    {
                                                        // store the current timestamp
                                                        $timestamp = date("Y-m-d H:i:s");
                                                                                
                                                        // create the new invoice
                                                        createInvoice($conn, $service_id, $customer_id, $to_period, "Copied from $from_period_name to $to_period_name on $timestamp.", $timestamp, $quantity);
                                                    }
                                                    // if cost type is variable (1)
                                                    else if ($service_cost_type == 1)
                                                    {
                                                        // store the current timestamp
                                                        $timestamp = date("Y-m-d H:i:s");
                                                                                
                                                        // create the new invoice
                                                        createInvoice($conn, $service_id, $customer_id, $to_period, "Copied from $from_period_name to $to_period_name on $timestamp.", $timestamp, $quantity);
                                                    }
                                                    // if cost type is membership (2)
                                                    else if ($service_cost_type == 2)
                                                    {
                                                        // store the current timestamp
                                                        $timestamp = date("Y-m-d H:i:s");
                                                                                
                                                        // create the new invoice
                                                        createInvoice($conn, $service_id, $customer_id, $to_period, "Copied from $from_period_name to $to_period_name on $timestamp.", $timestamp, $quantity);
                                                    }
                                                    // if cost type is custom (3)
                                                    else if ($service_cost_type == 3)
                                                    {
                                                        // get the custom cost from prior invoice
                                                        $custom_cost = $invoice["total_cost"];
                                                        
                                                        if ($custom_cost != null && is_numeric($custom_cost))
                                                        {
                                                            // store the current timestamp
                                                            $timestamp = date("Y-m-d H:i:s");
                                                                                
                                                            // create the new invoice
                                                            createInvoice($conn, $service_id, $customer_id, $to_period, "Copied from $from_period_name to $to_period_name on $timestamp.", $timestamp, $quantity, $custom_cost);
                                                        }
                                                    }
                                                    // if cost type is rate-based (4)
                                                    else if ($service_cost_type == 4)
                                                    {
                                                        // get the current rate from prior invoice
                                                        $rate_cost = $invoice["total_cost"];

                                                        // get the current rate tier based on current cost
                                                        $getTier = mysqli_prepare($conn, "SELECT variable_order FROM costs WHERE service_id=? AND period_id=? AND cost=? AND cost_type=4");
                                                        mysqli_stmt_bind_param($getTier, "sid", $service_id, $from_period, $rate_cost);
                                                        if (mysqli_stmt_execute($getTier))
                                                        {
                                                            $getTierResult = mysqli_stmt_get_result($getTier);
                                                            if (mysqli_num_rows($getTierResult) > 0) // tier found
                                                            {
                                                                // store the rate tier locally
                                                                $rate_tier = mysqli_fetch_array($getTierResult)["variable_order"];

                                                                // store the current timestamp
                                                                $timestamp = date("Y-m-d H:i:s");
                                                                                    
                                                                // create the new invoice
                                                                createInvoice($conn, $service_id, $customer_id, $to_period, "Copied from $from_period_name to $to_period_name on $timestamp.", $timestamp, $quantity, 0, $rate_tier);
                                                            }
                                                        }
                                                    }
                                                    // if cost type is group-rate-based (5)
                                                    else if ($service_cost_type == 5)
                                                    {
                                                        // get the current rate from prior invoice
                                                        $rate_cost = $invoice["total_cost"];

                                                        // get the cost associated to the selected tier
                                                        $getRateGroup = mysqli_prepare($conn, "SELECT group_id FROM costs WHERE service_id=? AND period_id=? AND variable_order=1 AND cost_type=5 LIMIT 1");
                                                        mysqli_stmt_bind_param($getRateGroup, "si", $service_id, $period_id);
                                                        if (mysqli_stmt_execute($getRateGroup))
                                                        {
                                                            $getRateGroupResult = mysqli_stmt_get_result($getRateGroup);
                                                            if (mysqli_num_rows($getRateGroupResult) > 0) // group found found
                                                            {
                                                                // store the rate group locally
                                                                $rate_group = mysqli_fetch_array($getRateGroupResult)["group_id"];

                                                                // check to see if the customer is a member of the group
                                                                $isMember = 0; // assume customer is not a member of the group 
                                                                $checkMembership = mysqli_prepare($conn, "SELECT id FROM group_members WHERE group_id=? AND customer_id=?");
                                                                mysqli_stmt_bind_param($checkMembership, "ii", $rate_group, $customer_id);
                                                                if (mysqli_stmt_execute($checkMembership))
                                                                {
                                                                    $checkMembershipResult = mysqli_stmt_get_result($checkMembership);
                                                                    if (mysqli_num_rows($checkMembershipResult) > 0) { $isMember = 1; }
                                                                }

                                                                // get the current rate tier based on current cost
                                                                $getTier = mysqli_prepare($conn, "SELECT variable_order FROM costs WHERE service_id=? AND period_id=? AND cost=? AND in_group=? AND cost_type=5");
                                                                mysqli_stmt_bind_param($getTier, "sid", $service_id, $from_period, $rate_cost, $isMember);
                                                                if (mysqli_stmt_execute($getTier))
                                                                {
                                                                    $getTierResult = mysqli_stmt_get_result($getTier);
                                                                    if (mysqli_num_rows($getTierResult) > 0) // tier found
                                                                    {
                                                                        // store the rate tier locally
                                                                        $rate_tier = mysqli_fetch_array($getTierResult)["variable_order"];

                                                                        // store the current timestamp
                                                                        $timestamp = date("Y-m-d H:i:s");
                                                                                    
                                                                        // create the new invoice
                                                                        createInvoice($conn, $service_id, $customer_id, $to_period, "Copied from $from_period_name to $to_period_name on $timestamp.", $timestamp, $quantity, 0, 0, $rate_tier);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // get other services invoices
                            $getOtherServicesInvoices = mysqli_prepare($conn, "SELECT * FROM services_other_provided WHERE period_id=?");
                            mysqli_stmt_bind_param($getOtherServicesInvoices, "i", $from_period);
                            if (mysqli_stmt_execute($getOtherServicesInvoices))
                            {
                                $getOtherServicesInvoicesResults = mysqli_stmt_get_result($getOtherServicesInvoices);
                                if (mysqli_num_rows($getOtherServicesInvoicesResults) > 0) // invoices for "other services" found
                                {
                                    // for each invoice; copy invoice into the "to" period based on the "to" period's costs
                                    while ($invoice = mysqli_fetch_array($getOtherServicesInvoicesResults))
                                    {
                                        // store invoice details locally
                                        $service_id = $invoice["service_id"];
                                        $customer_id = $invoice["customer_id"];
                                        $quantity = $invoice["quantity"];
                                        $project = $invoice["project_code"];
                                        $total_cost = $invoice["total_cost"];
                                        $description = $invoice["description"];
                                        $unit_label = $invoice["unit_label"];

                                        // get service details
                                        $getServiceDetails = mysqli_prepare($conn, "SELECT name FROM services_other WHERE id=?");
                                        mysqli_stmt_bind_param($getServiceDetails, "s", $service_id);
                                        if (mysqli_stmt_execute($getServiceDetails))
                                        {
                                            $getServiceDetailsResult = mysqli_stmt_get_result($getServiceDetails);
                                            if (mysqli_num_rows($getServiceDetailsResult) > 0) // service found; continue
                                            {
                                                // store service details locally
                                                $serviceDetails = mysqli_fetch_array($getServiceDetailsResult);
                                                $service_name = $serviceDetails["name"];

                                                // get customer details
                                                $getCustomerDetails = mysqli_prepare($conn, "SELECT name FROM customers WHERE id=?");
                                                mysqli_stmt_bind_param($getCustomerDetails, "i", $customer_id);
                                                if (mysqli_stmt_execute($getCustomerDetails))
                                                {
                                                    $getCustomerDetailsResult = mysqli_stmt_get_result($getCustomerDetails);
                                                    if (mysqli_num_rows($getCustomerDetailsResult) > 0) // customer found; continue
                                                    {
                                                        // store customer details locally
                                                        $customerDetails = mysqli_fetch_array($getCustomerDetailsResult);
                                                        $customer_name = $customerDetails["name"];

                                                        // store the current timestamp
                                                        $timestamp = date("Y-m-d H:i:s");

                                                        // copy invoice over 1:1 (no cost adjustment)
                                                        $copyInvoice = mysqli_prepare($conn, "INSERT INTO services_other_provided (period_id, service_id, customer_id, total_cost, quantity, description, date_provided, unit_label, project_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                                        mysqli_stmt_bind_param($copyInvoice, "isiddssss", $to_period, $service_id, $customer_id, $total_cost, $quantity, $description, $timestamp, $unit_label, $project);
                                                        if (mysqli_stmt_execute($copyInvoice)) // successfully copied the invoice
                                                        {
                                                            // get the invoice_id for the new service provied
                                                            $invoice_id = mysqli_insert_id($conn);

                                                            // by default, insert the quarterly costs equally divided for all quarters
                                                            $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE period_id=?");
                                                            mysqli_stmt_bind_param($getQuarters, "i", $to_period);
                                                            if (mysqli_stmt_execute($getQuarters))
                                                            {
                                                                $results = mysqli_stmt_get_result($getQuarters);
                                                                $unlockedQuarters = mysqli_num_rows($results);
                                                                
                                                                if ($unlockedQuarters > 0) // at least 1 quarter is unlocked
                                                                {
                                                                    // calculate the quarterly cost
                                                                    $quarterlyCost = number_format((str_replace(",", "", $total_cost) / $unlockedQuarters), 2, ".", "");

                                                                    // insert the quarterly costs into the database for each quarter
                                                                    while ($quarter = mysqli_fetch_array($results))
                                                                    {
                                                                        $insertQuarterlyCosts = mysqli_prepare($conn, "INSERT INTO other_quarterly_costs (other_invoice_id, other_service_id, customer_id, quarter, cost, period_id) VALUES (?, ?, ?, ?, ?, ?)");
                                                                        mysqli_stmt_bind_param($insertQuarterlyCosts, "isiidi", $invoice_id, $service_id, $customer_id, $quarter["quarter"], $quarterlyCost, $to_period);
                                                                        if (mysqli_stmt_execute($insertQuarterlyCosts))
                                                                        {
                                                                            // successfully inserted quarterly cost
                                                                        }
                                                                        else { /* TODO */ } // failed to insert quarterly cost; throw error
                                                                    }
                                                                }
                                                                else { /* TODO */ } // no quarters are unlocked; throw error?
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // log successful copies to screen
                            echo "<span class=\"log-success\">Successfully</span> copied invoices from $from_period_name to $to_period_name.<br>"; 
                        
                            // display additional line break
                            echo "<br>";
                        }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to copy data. You cannot copy data into the period you are copying data from!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to copy data. The period you are trying to copy data to was invalid!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to copy data. The period you are trying to copy data from was invalid!<br>"; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>