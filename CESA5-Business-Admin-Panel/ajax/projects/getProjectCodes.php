<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && ($_SESSION["role"] == 1 || $_SESSION["role"] == 2))
        {
            // get additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");
            include("../../getSettings.php");

            // generate a random 128 character hash
            $user_hash = bin2hex(random_bytes(8));

            // initialize array to store all codes 
            $codes = [];

            // initialize array to store consolidated codes
            $consolidated_codes = [];

            // get the parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($code != null && $period != null)
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    // get the period's details
                    $periodDetails = getPeriodDetails($conn, $period_id);

                    if (verifyProject($conn, $code)) // verify the project exists
                    {  
                        // get the project's fund code
                        $fund = null;
                        $getProjFund = mysqli_prepare($conn, "SELECT fund_code FROM projects WHERE code=?");
                        mysqli_stmt_bind_param($getProjFund, "s", $code);
                        if (mysqli_stmt_execute($getProjFund))
                        {
                            $getProjFundResult = mysqli_stmt_get_result($getProjFund);
                            if (mysqli_num_rows($getProjFundResult) > 0) // fund code found
                            {
                                $fund = mysqli_fetch_array($getProjFundResult)["fund_code"];
                            }
                        }

                        // get the FTE days value
                        $FTE_Days = getFTEDays($conn);

                        // get all employee codes
                        $getProjEmps = mysqli_prepare($conn, "SELECT pe.object_code, pe.function_code, pe.project_days, ec.yearly_rate, ec.contract_days, health_insurance, dental_insurance, wrs_eligible FROM project_employees pe 
                                                            JOIN employee_compensation ec ON pe.employee_id=ec.employee_id AND pe.period_id=ec.period_id
                                                            WHERE pe.project_code=? AND pe.period_id=?");
                        mysqli_stmt_bind_param($getProjEmps, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getProjEmps))
                        {
                            $getProjEmpsResults = mysqli_stmt_get_result($getProjEmps);
                            if (mysqli_num_rows($getProjEmpsResults) > 0) // project employees found
                            {
                                while ($proj_emp = mysqli_fetch_array($getProjEmpsResults))
                                {
                                    // store employee codes locally
                                    $obj = $proj_emp["object_code"];
                                    $func = $proj_emp["function_code"];

                                    // store employee benefits/compensation locally
                                    $project_days = $proj_emp["project_days"];
                                    $contract_days = $proj_emp["contract_days"];
                                    $salary = $proj_emp["yearly_rate"];
                                    $health = $proj_emp["health_insurance"];
                                    $dental = $proj_emp["dental_insurance"];
                                    $wrs = $proj_emp["wrs_eligible"];

                                    // if contract days and project days are > 0; continue process
                                    if ($contract_days > 0 && $project_days > 0)
                                    {
                                        // calculate the employee's daily rate
                                        $daily_rate = $salary / $contract_days;

                                        // calculate the percentage of benefits based on days
                                        if ($contract_days >= $FTE_Days) { $FTE_Benefits_Percentage = 1; }
                                        else { $FTE_Benefits_Percentage = ($contract_days / $FTE_Days); }

                                        // if FTE percentage is <= 50%; set to 0
                                        if ($FTE_Benefits_Percentage < 0.5) { $FTE_Benefits_Percentage = 0; }

                                        $getRates = mysqli_prepare($conn, "SELECT * FROM global_expenses WHERE period_id=?");
                                        mysqli_stmt_bind_param($getRates, "i", $period_id);
                                        if (mysqli_stmt_execute($getRates))
                                        {
                                            $getRatesResult = mysqli_stmt_get_result($getRates);
                                            if (mysqli_num_rows($getRatesResult) > 0) // rates for current period exist
                                            {
                                                // store the rates locally
                                                $rates = mysqli_fetch_array($getRatesResult);

                                                // initialize all the variables
                                                $project_salary = $FICA_Cost = $WRS_Cost = $Health_Cost = $Dental_Cost = $LTD_Cost = $Life_Cost = 0;

                                                // calculate the salary in project
                                                $project_salary = $daily_rate * $project_days;

                                                // calculate the FICE cost
                                                $FICA_Cost = $project_salary * $rates["FICA"];

                                                // calculate WRS cost
                                                if ($wrs == 1) { $WRS_Cost = $project_salary * $rates["wrs_rate"]; }

                                                // calculate health insurance cost
                                                if ($health == 1) { $Health_Cost = ($rates["health_family"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                                else if ($health == 2) { $Health_Cost = ($rates["health_single"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }

                                                // calculate dental insurance cost
                                                if ($dental == 1) { $Dental_Cost = ($rates["dental_family"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                                else if ($dental == 2) { $Dental_Cost = ($rates["dental_single"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }

                                                // calculate LTD cost
                                                $LTD_Cost = ($project_salary / 100) * ($rates["LTD"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); 

                                                // calculate life insurance cost
                                                $Life_Cost = (($project_salary / 1000) * ($rates["life"] * 12 * ($project_days / $contract_days)) * 0.2); 

                                                // create project salary code if > 0
                                                if ($project_salary > 0)
                                                {
                                                    // create temporary array to store the employee's whole code
                                                    $temp = [];
                                                    $temp["fund"] = $fund." E";
                                                    $temp["location"] = 999;
                                                    $temp["object"] = $obj;
                                                    $temp["function"] = $func;
                                                    $temp["project"] = $code;
                                                    $temp["amount"] = number_format($project_salary, 2);
                                                    $temp["type"] = "Salary";

                                                    // add the temporary array to the master
                                                    $codes[] = $temp;
                                                }

                                                // create FICA code if > 0
                                                if ($FICA_Cost > 0)
                                                {
                                                    // create temporary array to store the employee's whole code
                                                    $temp = [];
                                                    $temp["fund"] = $fund." E";
                                                    $temp["location"] = 999;
                                                    $temp["object"] = $rates["FICA_code"];
                                                    $temp["function"] = $func;
                                                    $temp["project"] = $code;
                                                    $temp["amount"] = number_format($FICA_Cost, 2);
                                                    $temp["type"] = "FICA";

                                                    // add the temporary array to the master
                                                    $codes[] = $temp;
                                                }

                                                // create health insurance code if > 0
                                                if ($Health_Cost > 0)
                                                {
                                                    // create temporary array to store the employee's whole code
                                                    $temp = [];
                                                    $temp["fund"] = $fund." E";
                                                    $temp["location"] = 999;
                                                    if ($health == 1) { $temp["object"] = $rates["health_family_code"]; }
                                                    else if ($health == 2) { $temp["object"] = $rates["health_single_code"]; }
                                                    $temp["function"] = $func;
                                                    $temp["project"] = $code;
                                                    $temp["amount"] = number_format($Health_Cost, 2);
                                                    $temp["type"] = "Health";

                                                    // add the temporary array to the master
                                                    $codes[] = $temp;
                                                }

                                                // create dental insurance code if > 0
                                                if ($Dental_Cost > 0)
                                                {
                                                    // create temporary array to store the employee's whole code
                                                    $temp = [];
                                                    $temp["fund"] = $fund." E";
                                                    $temp["location"] = 999;
                                                    if ($dental == 1) { $temp["object"] = $rates["dental_family_code"]; }
                                                    else if ($dental == 2) { $temp["object"] = $rates["dental_single_code"]; }
                                                    $temp["function"] = $func;
                                                    $temp["project"] = $code;
                                                    $temp["amount"] = number_format($Dental_Cost, 2);
                                                    $temp["type"] = "Dental";

                                                    // add the temporary array to the master
                                                    $codes[] = $temp;
                                                }

                                                // create WRS code if > 0
                                                if ($WRS_Cost > 0)
                                                {
                                                    // create temporary array to store the employee's whole code
                                                    $temp = [];
                                                    $temp["fund"] = $fund." E";
                                                    $temp["location"] = 999;
                                                    $temp["object"] = $rates["wrs_rate_code"];
                                                    $temp["function"] = $func;
                                                    $temp["project"] = $code;
                                                    $temp["amount"] = number_format($WRS_Cost, 2);
                                                    $temp["type"] = "WRS";

                                                    // add the temporary array to the master
                                                    $codes[] = $temp;
                                                }

                                                // create LTD code if > 0
                                                if ($LTD_Cost > 0)
                                                {
                                                    // create temporary array to store the employee's whole code
                                                    $temp = [];
                                                    $temp["fund"] = $fund." E";
                                                    $temp["location"] = 999;
                                                    $temp["object"] = $rates["LTD_code"];
                                                    $temp["function"] = $func;
                                                    $temp["project"] = $code;
                                                    $temp["amount"] = number_format($LTD_Cost, 2);
                                                    $temp["type"] = "LTD";

                                                    // add the temporary array to the master
                                                    $codes[] = $temp;
                                                }

                                                // create life insurance code if > 0
                                                if ($Life_Cost > 0)
                                                {
                                                    // create temporary array to store the employee's whole code
                                                    $temp = [];
                                                    $temp["fund"] = $fund." E";
                                                    $temp["location"] = 999;
                                                    $temp["object"] = $rates["life_code"];
                                                    $temp["function"] = $func;
                                                    $temp["project"] = $code;
                                                    $temp["amount"] = number_format($Life_Cost, 2);
                                                    $temp["type"] = "Life";

                                                    // add the temporary array to the master
                                                    $codes[] = $temp;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // get all expenses codes
                        $getProjExps = mysqli_prepare($conn, "SELECT pe.fund_code, e.location_code, e.object_code, pe.function_code, pe.cost FROM expenses e
                                                            JOIN project_expenses pe ON e.id=pe.expense_id
                                                            WHERE pe.project_code=? AND pe.period_id=?");
                        mysqli_stmt_bind_param($getProjExps, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getProjExps))
                        {
                            $getProjExpsResults = mysqli_stmt_get_result($getProjExps);
                            if (mysqli_num_rows($getProjExpsResults) > 0) // project expenses found
                            {
                                while ($proj_exp = mysqli_fetch_array($getProjExpsResults)) 
                                {
                                    // store expense codes locally
                                    $exp_fund = $proj_exp["fund_code"];
                                    $loc = $proj_exp["location_code"];
                                    $obj = $proj_exp["object_code"];
                                    $func = $proj_exp["function_code"];
                                    $cost = $proj_exp["cost"];

                                    // round the cost
                                    if (isset($cost) && $cost != null) { $cost = number_format($cost, 2); } else { $cost = 0.00; }

                                    // create temporary array to store the expense's whole code
                                    $temp = [];
                                    $temp["fund"] = $exp_fund." E";
                                    $temp["location"] = $loc;
                                    $temp["object"] = $obj;
                                    $temp["function"] = $func;
                                    $temp["project"] = $code;
                                    $temp["amount"] = $cost;
                                    $temp["type"] = "Expenses";

                                    // add the temporary array to the master
                                    $codes[] = $temp;
                                }
                            }
                        }

                        // get all revenue codes
                        $getProjRevs = mysqli_prepare($conn, "SELECT fund_code, location_code, source_code, function_code, total_cost FROM revenues WHERE project_code=? AND period_id=?");
                        mysqli_stmt_bind_param($getProjRevs, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getProjRevs))
                        {
                            $getProjRevsResults = mysqli_stmt_get_result($getProjRevs);
                            if (mysqli_num_rows($getProjRevsResults) > 0) // project revenues found
                            {
                                while ($proj_rev = mysqli_fetch_array($getProjRevsResults))
                                {
                                    // store revenue codes locally
                                    $rev_fund = $proj_rev["fund_code"];
                                    $loc = $proj_rev["location_code"];
                                    $src = $proj_rev["source_code"];
                                    $func = $proj_rev["function_code"];
                                    $cost = $proj_rev["total_cost"];

                                    // round the cost
                                    if (isset($cost) && $cost != null) { $cost = number_format($cost, 2); } else { $cost = 0.00; }

                                    // create temporary array to store the expense's whole code
                                    $temp = [];
                                    $temp["fund"] = $rev_fund." R";
                                    $temp["location"] = $loc;
                                    $temp["object"] = $src;
                                    $temp["function"] = $func;
                                    $temp["project"] = $code;
                                    $temp["amount"] = $cost;
                                    $temp["type"] = "Revenue";

                                    // add the temporary array to the master
                                    $codes[] = $temp;
                                }
                            }
                        }

                        // get all service revenue codes
                        $getProjServicesRevs = mysqli_prepare($conn, "SELECT s.fund_code, c.location_code, s.object_code, s.function_code, sp.total_cost FROM services s
                                                                    JOIN services_provided sp ON s.id=sp.service_id
                                                                    JOIN customers c ON sp.customer_id=c.id
                                                                    WHERE s.project_code=? AND sp.period_id=?");
                        mysqli_stmt_bind_param($getProjServicesRevs, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getProjServicesRevs))
                        {
                            $getProjServicesRevsResults = mysqli_stmt_get_result($getProjServicesRevs);
                            if (mysqli_num_rows($getProjServicesRevsResults) > 0) // project service revenues found
                            {
                                while ($proj_rev = mysqli_fetch_array($getProjServicesRevsResults))
                                {
                                    // store revenue codes locally
                                    $service_fund = $proj_rev["fund_code"];
                                    $loc = $proj_rev["location_code"];
                                    $src = $proj_rev["object_code"];
                                    $func = $proj_rev["function_code"];
                                    $cost = $proj_rev["total_cost"];

                                    // round the cost
                                    if (isset($cost) && $cost != null) { $cost = number_format($cost, 2); } else { $cost = 0.00; }

                                    // create temporary array to store the expense's whole code
                                    $temp = [];
                                    $temp["fund"] = $service_fund." R";
                                    $temp["location"] = $loc;
                                    $temp["object"] = $src;
                                    $temp["function"] = $func;
                                    $temp["project"] = $code;
                                    $temp["amount"] = $cost;
                                    $temp["type"] = "Revenue";

                                    // add the temporary array to the master
                                    $codes[] = $temp;
                                }
                            }
                        }

                        // get all service revenue codes
                        $getProjOtherServicesRevs = mysqli_prepare($conn, "SELECT s.fund_code, c.location_code, s.source_code, s.function_code, sp.total_cost FROM services_other s
                                                                    JOIN services_other_provided sp ON s.id=sp.service_id
                                                                    JOIN customers c ON sp.customer_id=c.id
                                                                    WHERE sp.project_code=? AND sp.period_id=?");
                        mysqli_stmt_bind_param($getProjOtherServicesRevs, "si", $code, $period_id);
                        if (mysqli_stmt_execute($getProjOtherServicesRevs))
                        {
                            $getProjOtherServicesRevsResults = mysqli_stmt_get_result($getProjOtherServicesRevs);
                            if (mysqli_num_rows($getProjOtherServicesRevsResults) > 0) // project service revenues found
                            {
                                while ($proj_rev = mysqli_fetch_array($getProjOtherServicesRevsResults))
                                {
                                    // store revenue codes locally
                                    $service_fund = $proj_rev["fund_code"];
                                    $loc = $proj_rev["location_code"];
                                    $src = $proj_rev["source_code"];
                                    $func = $proj_rev["function_code"];
                                    $cost = $proj_rev["total_cost"];

                                    // round the cost
                                    if (isset($cost) && $cost != null) { $cost = number_format($cost, 2); } else { $cost = 0.00; }

                                    // create temporary array to store the expense's whole code
                                    $temp = [];
                                    $temp["fund"] = $service_fund." R";
                                    $temp["location"] = $loc;
                                    $temp["object"] = $src;
                                    $temp["function"] = $func;
                                    $temp["project"] = $code;
                                    $temp["amount"] = $cost;
                                    $temp["type"] = "Revenue";

                                    // add the temporary array to the master
                                    $codes[] = $temp;
                                }
                            }
                        }

                        // for all codes found; insert into temporary database table to consolidate the codes
                        for ($c = 0; $c < count($codes); $c++)
                        {
                            // store code details locally
                            $cat = $codes[$c]["type"];
                            $fund = $codes[$c]["fund"];
                            $loc = $codes[$c]["location"];
                            $obj = $codes[$c]["object"];
                            $func = $codes[$c]["function"];
                            $proj = $codes[$c]["project"];
                            $amount = str_replace(",", "", $codes[$c]["amount"]);

                            if ((isset($fund) && $fund <> "") && (isset($loc) && $loc <> "") && (isset($obj) && $obj <> "") && (isset($func) && $func <> "") && (isset($proj) && $proj <> "") && (isset($amount) && $amount > 0))
                            {
                                // add code to the database
                                $addCode = mysqli_prepare($conn, "INSERT INTO codes_tmp (user_id, user_hash, category, fund_code, location_code, object_code, function_code, project_code, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                mysqli_stmt_bind_param($addCode, "isssssssd", $_SESSION["id"], $user_hash, $cat, $fund, $loc, $obj, $func, $proj, $amount);
                                mysqli_stmt_execute($addCode);
                            }
                        }

                        // query the database to get consolidated code pairs
                        $getConsolidatedCodes = mysqli_prepare($conn, "SELECT DISTINCT category, fund_code, location_code, object_code, function_code, project_code FROM codes_tmp WHERE user_id=? AND user_hash=?");
                        mysqli_stmt_bind_param($getConsolidatedCodes, "is", $_SESSION["id"], $user_hash);
                        if (mysqli_stmt_execute($getConsolidatedCodes))
                        {
                            $getConsolidatedCodesResults = mysqli_stmt_get_result($getConsolidatedCodes);
                            if (mysqli_num_rows($getConsolidatedCodesResults) > 0) // codes found
                            {
                                while ($code_pair = mysqli_fetch_array($getConsolidatedCodesResults))
                                {
                                    // store code pair details locally
                                    $cat = $code_pair["category"];
                                    $fund = $code_pair["fund_code"];
                                    $loc = $code_pair["location_code"];
                                    $obj = $code_pair["object_code"];
                                    $func = $code_pair["function_code"];
                                    $proj = $code_pair["project_code"];

                                    // for the code pair, get the total amount
                                    $consolidated_cost = 0; // assume consolidated cost is 0
                                    $getConsolidatedAmount = mysqli_prepare($conn, "SELECT SUM(amount) AS consolidated_cost FROM codes_tmp WHERE user_id=? AND user_hash=? AND category=? AND fund_code=? AND location_code=? AND object_code=? AND function_code=? AND project_code=?");
                                    mysqli_stmt_bind_param($getConsolidatedAmount, "isssssss", $_SESSION["id"], $user_hash, $cat, $fund, $loc, $obj, $func, $proj);
                                    if (mysqli_stmt_execute($getConsolidatedAmount))
                                    {
                                        $getConsolidatedAmountResult = mysqli_stmt_get_result($getConsolidatedAmount);
                                        if (mysqli_num_rows($getConsolidatedAmountResult) > 0)
                                        {
                                            $consolidated_cost = mysqli_fetch_array($getConsolidatedAmountResult)["consolidated_cost"];
                                        }
                                    }

                                    if ($consolidated_cost > 0)
                                    {
                                        $temp = [];
                                        $temp["type"] = $cat;
                                        if (isset($fund) && is_numeric(substr($fund, 0, 2))) { $temp["fund"] = $fund; } else { $temp["fund"] = "<span class='missing-field'>Missing</span>"; }
                                        $temp["location"] = $loc;
                                        $temp["object"] = $obj;
                                        $temp["function"] = $func;
                                        $temp["project"] = $proj;
                                        $temp["amount"] = printDollar($consolidated_cost);
                                        $temp["amount_calc"] = round($consolidated_cost, 2);

                                        // create the account code string
                                        $account_code = $fund . " " . $loc . " " . $obj . " " . $func . " " . $proj;
                                        $temp["account_code"] = $account_code;

                                        $consolidated_codes[] = $temp;
                                    }
                                }
                            }
                        }

                        // delete the temporary consolidated codes
                        $clearTempCodes = mysqli_prepare($conn, "DELETE FROM codes_tmp WHERE user_id=? AND user_hash=?");
                        mysqli_stmt_bind_param($clearTempCodes, "is", $_SESSION["id"], $user_hash);
                        mysqli_stmt_execute($clearTempCodes);
                    }
                }

                // disconnect from the database
                mysqli_close($conn);
            }

            // send data to be printed
            $fullData = [];
            $fullData["draw"] = 1;
            $fullData["data"] = $consolidated_codes;
            echo json_encode($fullData);
        }
    }
?>