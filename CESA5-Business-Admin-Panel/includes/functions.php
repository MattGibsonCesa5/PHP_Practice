<?php
    // if the session is not yet started, start the session
    if (session_id() == "" || !isset($_SESSION) || session_status() === PHP_SESSION_NONE) { session_start(); }
        
    /** function to print the locked icon if a quarter is locked */
    function printLocked($conn, $quarter, $period_id)
    {
        // check to see if the quarter is locked; if so, print locked icon
        if (checkLocked($conn, $quarter, $period_id)) { echo "<i class=\"fa-solid fa-lock\"></i>"; }
    }

    /** function to print the locked icon if a quarter is locked */
    function checkLocked($conn, $quarter, $period_id)
    {
        // check to see if the quarter is locked; if so, print locked icon
        $checkLocked = mysqli_prepare($conn, "SELECT locked FROM quarters WHERE quarter=? AND period_id=?");
        mysqli_stmt_bind_param($checkLocked, "ii", $quarter, $period_id);
        if (mysqli_stmt_execute($checkLocked))
        {
            $result = mysqli_stmt_get_result($checkLocked);
            if (mysqli_num_rows($result) > 0)
            {
                $locked = mysqli_fetch_array($result)["locked"];
                if ($locked == 1) { return true; }
            }
        }
        // return false if end of function reached without returning
        return false;
    }

    /** function to write to the log */
    function debugging_log($location, $output)
    {
        $log = fopen($location, "a");
        fwrite($log, "[".date("Y-m-d H:i:a")."] ".$output."\n");
        fclose($log);
    }

    /** 
     * function to get the age based on the date provided 
     *  source: https://stackoverflow.com/questions/3776682/php-calculate-age
    */
    function getAge($date) 
    {
        if (isset($date) && ($date <> "" || $date != "?"))
        {
            if ($date == "?") { return 0; }
            else { return intval(date('Y', time() - strtotime($date))) - 1970; }
        }
        else { return 0; }
    }

    /** function to redirect the user to the login page */
    function goToLogin()
    {
        echo "<script>window.location.href='login.php';</script>";
    }

    /** function to deny a user access to the page */
    function denyAccess()
    {
        ?>
            <div class="row access-denied justify-content-center p-2">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-6 col-xxl-4">
                    <div class="alert alert-danger m-0">
                        <h1><i class="fa-solid fa-triangle-exclamation"></i> Access Denied!</h1>
                        <p class="m-0">If you believe you should have access to this page, please contact an administrator for your site.</p>
                    </div>
                </div>
            </div>
        <?php
    }

    /** function to clean up data being imported into the database */
    function clean_data($data)
    {
        // strip any HTML tags from the data and then remove excess whitespace
        return trim(strip_tags($data));
    }

    /** 
     *  function to convert a timestamp from one timezone to another 
     *  - Source: https://stackoverflow.com/questions/50585730/converting-utc-to-cst-in-php
     * 
     *  - Example:
     *      date_convert("2018-05-29 11:44:00", "UTC", "America/Chicago", "Y-m-d H:i:s");
     *      Output: 2018-05-29 05:44:00
    */
    function date_convert($timestamp, $old_timezone, $new_timezone, $format) 
    {
        // create old time
        $d = new \DateTime($timestamp, new \DateTimeZone($old_timezone));

        // convert to new timezone
        $d->setTimezone(new \DateTimeZone($new_timezone));
    
        // output with new format
        return $d->format($format);
    }

    /** function to get the total revenues for the selected period */
    function getPeriodRevenues($conn, $period)
    {
        $total_revenues = 0; // assume total revenues is 0
        $total_revenues += getServiceRevenues($conn, $period);
        $total_revenues += getOtherServiceRevenues($conn, $period);
        $total_revenues += getOtherRevenues($conn, $period);
        return $total_revenues;
    }

    /** function to get a period's revenues from the quarterly costs table (regular services) */
    function getServiceRevenues($conn, $period)
    {
        $total_revenues = 0; // assume total revenues is 0

        // get total revenues from services provided
        $getTotalRevenues = mysqli_prepare($conn, "SELECT SUM(qc.cost) AS total_revenues FROM quarterly_costs qc JOIN services_provided sp ON qc.invoice_id=sp.id JOIN services s ON sp.service_id=s.id WHERE sp.period_id=?");
        mysqli_stmt_bind_param($getTotalRevenues, "i", $period);
        if (mysqli_stmt_execute($getTotalRevenues))
        {
            $getTotalRevenuesResult = mysqli_stmt_get_result($getTotalRevenues);
            if (mysqli_num_rows($getTotalRevenuesResult) > 0)
            {
                $total_revenues += mysqli_fetch_array($getTotalRevenuesResult)["total_revenues"];
            }
        }

        return $total_revenues;
    }

    /** function to get a period's revenues from the other quarterly costs table (other services) */
    function getOtherServiceRevenues($conn, $period)
    {
        $total_revenues = 0; // assume total revenues is 0

        // get total revenues from other services provided
        $getTotalRevenues = mysqli_prepare($conn, "SELECT SUM(qc.cost) AS total_revenues FROM other_quarterly_costs qc JOIN services_other_provided sp ON qc.other_invoice_id=sp.id JOIN services_other s ON sp.service_id=s.id WHERE sp.period_id=?");
        mysqli_stmt_bind_param($getTotalRevenues, "i", $period);
        if (mysqli_stmt_execute($getTotalRevenues))
        {
            $getTotalRevenuesResult = mysqli_stmt_get_result($getTotalRevenues);
            if (mysqli_num_rows($getTotalRevenuesResult) > 0)
            {
                $total_revenues += mysqli_fetch_array($getTotalRevenuesResult)["total_revenues"];
            }
        }

        return $total_revenues;
    }

    /** function to get a period's revenues from the revenues table (other revenues) */
    function getOtherRevenues($conn, $period)
    {
        $total_revenues = 0; // assume total revenues is 0

        // get total revenues from other revenues
        $getTotalRevenues = mysqli_prepare($conn, "SELECT SUM(total_cost) AS total_revenues FROM revenues WHERE period_id=?");
        mysqli_stmt_bind_param($getTotalRevenues, "i", $period);
        if (mysqli_stmt_execute($getTotalRevenues))
        {
            $getTotalRevenuesResult = mysqli_stmt_get_result($getTotalRevenues);
            if (mysqli_num_rows($getTotalRevenuesResult) > 0)
            {
                $total_revenues += mysqli_fetch_array($getTotalRevenuesResult)["total_revenues"];
            }
        }

        return $total_revenues;
    }

    /** function to get the total expenses for the selected period */
    function getPeriodExpenses($conn, $period)
    {
        $total_expenses = 0; // assume total expenses is 0
        $total_expenses += getTotalProjectExpenses($conn, $period);
        $total_expenses += getEmployeeExpenses($conn, $period);
        return $total_expenses;
    }

    /** function to get project expenses */
    function getTotalProjectExpenses($conn, $period_id)
    {
        $total_expenses = 0; // assume total expenses is 0

        /* PROJECT EXPENSES */
        $getProjectExpenses = mysqli_prepare($conn, "SELECT SUM(pe.cost) AS total_cost FROM project_expenses pe 
                                                    JOIN projects_status ps ON pe.project_code=ps.code AND pe.period_id=ps.period_id
                                                    WHERE pe.period_id=? AND ps.status=1");
        mysqli_stmt_bind_param($getProjectExpenses, "i", $period_id);
        if (mysqli_stmt_execute($getProjectExpenses))
        {
            $getProjectExpensesResult = mysqli_stmt_get_result($getProjectExpenses);
            if (mysqli_num_rows($getProjectExpensesResult) > 0)
            {
                $total_expenses += mysqli_fetch_array($getProjectExpensesResult)["total_cost"];
            }
        }

        return $total_expenses;
    }

    /** function to get a project's total expenses */
    function getProjectExpenses($conn, $period_id, $code)
    {
        // initialize the variable to store the project's expenses
        $total_expenses = 0; // assume project has no expenses (0)

        // get the project's total expenses, not including supervision and indirect costs
        $getProjectExpenses = mysqli_prepare($conn, "SELECT SUM(cost) AS total_expenses FROM project_expenses WHERE project_code=? AND period_id=? AND auto=0");
        mysqli_stmt_bind_param($getProjectExpenses, "si", $code, $period_id);
        if (mysqli_stmt_execute($getProjectExpenses))
        {
            $getProjectExpensesResult = mysqli_stmt_get_result($getProjectExpenses);
            if (mysqli_num_rows($getProjectExpensesResult) > 0)
            {
                $total_expenses = mysqli_fetch_array($getProjectExpensesResult)["total_expenses"];
            }
        }

        // return the project's total expenses
        return $total_expenses;
    }

    /** function to get a project's supervision costs */
    function getProjectSupervisionCosts($conn, $period_id, $code)
    {
        // store supervision expense IDs
        $aidable_id = 33;
        $nonaidable_id = 34;

        // initialize variables to store supervision costs
        $total_supervision = $aidable_supervision = $nonaidable_supervision = 0; // assume costs are 0

        // get the project's aidable supervision costs
        $getAidable = mysqli_prepare($conn, "SELECT SUM(cost) AS aidable_supervision FROM project_expenses WHERE project_code=? AND expense_id=? AND period_id=? AND auto=1");
        mysqli_stmt_bind_param($getAidable, "sii", $code, $aidable_id, $period_id);
        if (mysqli_stmt_execute($getAidable))
        {
            $getAidableResult = mysqli_stmt_get_result($getAidable);
            if (mysqli_num_rows($getAidableResult) > 0) // aidable costs found; continue
            {
                $aidable_supervision = mysqli_fetch_array($getAidableResult)["aidable_supervision"];
            }
        }

        // get the project's non-aidable supervision costs
        $getNonaidable = mysqli_prepare($conn, "SELECT SUM(cost) AS nonaidable_supervision FROM project_expenses WHERE project_code=? AND expense_id=? AND period_id=? AND auto=1");
        mysqli_stmt_bind_param($getNonaidable, "sii", $code, $nonaidable_id, $period_id);
        if (mysqli_stmt_execute($getNonaidable))
        {
            $getNonaidableResult = mysqli_stmt_get_result($getNonaidable);
            if (mysqli_num_rows($getNonaidableResult) > 0) // non-aidable costs found; continue
            {
                $nonaidable_supervision = mysqli_fetch_array($getNonaidableResult)["nonaidable_supervision"];
            }
        }

        // calculate the total supervision costs
        $total_supervision = $aidable_supervision + $nonaidable_supervision;

        // return the project's total supervision costs
        return $total_supervision;
    }

    /** function to get a project's indirect costs */
    function getProjectIndirectCosts($conn, $period_id, $code)
    {
        // store supervision expense IDs
        $indirect_id = 35;

        // initialize variables to store supervision costs
        $total_indirect = 0; // assume indirect cost is 0

        // get the project's indirect costs
        $getIndirect = mysqli_prepare($conn, "SELECT SUM(cost) AS project_indirect FROM project_expenses WHERE project_code=? AND expense_id=? AND period_id=? AND auto=1");
        mysqli_stmt_bind_param($getIndirect, "sii", $code, $indirect_id, $period_id);
        if (mysqli_stmt_execute($getIndirect))
        {
            $getIndirectResult = mysqli_stmt_get_result($getIndirect);
            if (mysqli_num_rows($getIndirectResult) > 0) // indirect costs found; continue
            {
                $total_indirect = mysqli_fetch_array($getIndirectResult)["project_indirect"];
            }
        }

        // return the project's total supervision costs
        return $total_indirect;
    }

    /** function to get project expenses */
    function getEmployeeExpenses($conn, $period)
    {
        $total_expenses = 0;

        /* EMPLOYEE EXPENSES */
        $getEmployees = mysqli_prepare($conn, "SELECT DISTINCT employee_id, project_code FROM project_employees WHERE period_id=?");
        mysqli_stmt_bind_param($getEmployees, "i", $period);
        if (mysqli_stmt_execute($getEmployees))
        {
            $getEmployeesResult = mysqli_stmt_get_result($getEmployees);
            while ($employee = mysqli_fetch_array($getEmployeesResult))
            {
                // store employee project details locally
                $employee_id = $employee["employee_id"];
                $project_code = $employee["project_code"];

                // add the employee's project compensation to the total
                $total_expenses += getEmployeesTotalCompensation($conn, $project_code, $employee_id, $period);
            }
        }

        /* TEST EMPLOYEE EXPENSES */ /* temporarily removing including test employees in employee expenses totals
        $getTestEmployeeProjects = mysqli_prepare($conn, "SELECT DISTINCT project_code FROM project_employees_misc WHERE period_id=?");
        mysqli_stmt_bind_param($getTestEmployeeProjects, "i", $period);
        if (mysqli_stmt_execute($getTestEmployeeProjects))
        {
            $getTestEmployeeProjectsResults = mysqli_stmt_get_result($getTestEmployeeProjects);
            if (mysqli_num_rows($getTestEmployeeProjectsResults) > 0) // test employees are found; get their expenses
            {
                while ($project = mysqli_fetch_array($getTestEmployeeProjectsResults))
                {
                    // store the project's code locally
                    $project_code = $project["project_code"];

                    // add in the test employees expenses for each project
                    $total_expenses += getTestProjectEmployeesCompensation($conn, $project_code, $period);
                }
            }
        } */

        return $total_expenses;
    }

    /** get active period label */
    function getActivePeriodLabel($conn)
    {
        $name = "Unknown Period Name";
        $getName = mysqli_query($conn, "SELECT name FROM periods WHERE active=1");
        if (mysqli_num_rows($getName) > 0) { $name = mysqli_fetch_array($getName)["name"]; }
        return $name;
    }

    /** get comparison period label */
    function getCompPeriodLabel($conn)
    {
        $name = "Unknown Period Name";
        $getName = mysqli_query($conn, "SELECT name FROM periods WHERE comparison=1");
        if (mysqli_num_rows($getName) > 0) { $name = mysqli_fetch_array($getName)["name"]; }
        return $name;
    }

    /** function to get the count of total active employees */
    function getTotalActiveEmployees($conn, $period_id)
    {
        $totalEmployees = 0;
        $getTotalEmps = mysqli_prepare($conn, "SELECT COUNT(e.id) AS total_employees_count FROM employees e
                                            JOIN employee_compensation ec ON e.id=ec.employee_id
                                            WHERE ec.active=1 AND ec.period_id=?");
        mysqli_stmt_bind_param($getTotalEmps, "i", $period_id);
        if (mysqli_stmt_execute($getTotalEmps))
        {
            $getTotalEmpsResult = mysqli_stmt_get_result($getTotalEmps);
            if (mysqli_num_rows($getTotalEmpsResult) > 0) // employees found
            {
                $totalEmployees = mysqli_fetch_array($getTotalEmpsResult)["total_employees_count"];
            }
        }
        return $totalEmployees;
    }

    /** function to get the count of total active employees */
    function getTotalInactiveEmployees($conn, $period_id)
    {
        $totalInactiveEmployees = 0;
        $getTotalInactiveEmps = mysqli_prepare($conn, "SELECT COUNT(e.id) AS total_employees_count FROM employees e
                                                    JOIN employee_compensation ec ON e.id=ec.employee_id
                                                    WHERE ec.active=0 AND ec.period_id=?");
        mysqli_stmt_bind_param($getTotalInactiveEmps, "i", $period_id);
        if (mysqli_stmt_execute($getTotalInactiveEmps))
        {
            $getTotalInactiveEmpsResult = mysqli_stmt_get_result($getTotalInactiveEmps);
            if (mysqli_num_rows($getTotalInactiveEmpsResult) > 0) // inactive employees found
            {
                $totalInactiveEmployees = mysqli_fetch_array($getTotalInactiveEmpsResult)["total_employees_count"];
            }
        }
        return $totalInactiveEmployees;
    }

    /** function to get the total amount of contract days */
    function getTotalContractDays($conn, $period)
    {
        $totalContractDays = 0;
        $getTotalContractDays = mysqli_prepare($conn, "SELECT SUM(ec.contract_days) AS total_contract_days FROM employee_compensation ec 
                                                        JOIN employees e ON ec.employee_id=e.id 
                                                        WHERE ec.active=1 AND ec.period_id=?");
        mysqli_stmt_bind_param($getTotalContractDays, "i", $period);
        if (mysqli_stmt_execute($getTotalContractDays))
        {
            $getTotalContractDaysResult = mysqli_stmt_get_result($getTotalContractDays);
            if (mysqli_num_rows($getTotalContractDaysResult) > 0) // employees found; sum total days
            {
                $totalContractDays = mysqli_fetch_array($getTotalContractDaysResult)["total_contract_days"];
            }
        }
        if (!is_numeric($totalContractDays)) { $totalContractDays = 0; } // set the total contract days to 0 if it is not a number
        return $totalContractDays;
    }

    /** function to get the total amount of budgeted days */
    function getTotalBudgetedDays($conn, $period)
    {
        $totalBudgetedDays = 0;
        $getTotalBudgetedDays = mysqli_prepare($conn, "SELECT SUM(pe.project_days) AS total_project_days FROM project_employees pe 
                                                    JOIN employee_compensation ec ON pe.employee_id=ec.employee_id AND pe.period_id=ec.period_id
                                                    WHERE pe.period_id=? AND ec.active=1");
        mysqli_stmt_bind_param($getTotalBudgetedDays, "i", $period);
        if (mysqli_stmt_execute($getTotalBudgetedDays))
        {
            $getTotalBudgetedDaysResults = mysqli_stmt_get_result($getTotalBudgetedDays);
            if (mysqli_num_rows($getTotalBudgetedDaysResults) > 0) // employees found; sum total days
            {
                $totalBudgetedDays = mysqli_fetch_array($getTotalBudgetedDaysResults)["total_project_days"];
            }
        }
        if (!is_numeric($totalBudgetedDays)) { $totalBudgetedDays = 0; } // set the total budgeted days to 0 if it is not a number
        return $totalBudgetedDays;
    }

    /** function to get the count of employees who have been misbudgeted */
    function getMisbudgetedEmployeesCount($conn, $period, $user_id)
    {
        // initialize variable to store the count of employees who have been misbudgeted
        $misbudgetedEmps = 0;

        if (checkUserPermission($conn, "DASHBOARD_SHOW_BUDGET_ERRORS_ALL_TILE")) // admin
        {
            // get a list of all employees (both active and inactive)
            $getEmps = mysqli_prepare($conn, "SELECT DISTINCT e.id, ec.contract_days FROM employee_compensation ec JOIN employees e ON ec.employee_id=e.id WHERE ec.period_id=?");
            mysqli_stmt_bind_param($getEmps, "i", $period);
            if (mysqli_stmt_execute($getEmps))
            {
                $getEmpsResults = mysqli_stmt_get_result($getEmps);
                if (mysqli_num_rows($getEmpsResults) > 0) // employees found; continue
                {
                    // for each employee, get the amount of days they've been budgeted
                    while ($emp = mysqli_fetch_array($getEmpsResults))
                    {
                        // store employee details locally
                        $emp_id = $emp["id"];
                        $days = $emp["contract_days"];

                        // get employee's budgeted days count
                        $budgeted_days = getBudgetedDays($conn, $emp_id, $period);

                        if ($days != $budgeted_days) { $misbudgetedEmps++; } // employee has been misbudgeted
                    }
                }
            }
        }
        else if (checkUserPermission($conn, "DASHBOARD_SHOW_BUDGET_ERRORS_ASSIGNED_TILE")) // director
        {
            // get a list of all employees (both active and inactive)
            $getEmps = mysqli_prepare($conn, "SELECT DISTINCT e.id, ec.contract_days FROM employees e 
                                            JOIN employee_compensation ec ON e.id=ec.employee_id
                                            JOIN department_members dm ON e.id=dm.employee_id
                                            JOIN departments d ON dm.department_id=d.id
                                            WHERE (d.director_id=? OR d.secondary_director_id=?) AND ec.period_id=?");
            mysqli_stmt_bind_param($getEmps, "iii", $user_id, $user_id, $period);
            if (mysqli_stmt_execute($getEmps))
            {
                $getEmpsResult = mysqli_stmt_get_result($getEmps);
                if (mysqli_num_rows($getEmpsResult) > 0) // employees exist; continue
                {
                    // for each employee, get the amount of days they've been budgeted
                    while ($emp = mysqli_fetch_array($getEmpsResult))
                    {
                        // store employee details locally
                        $emp_id = $emp["id"];
                        $days = $emp["contract_days"];

                        // get employee's budgeted days count
                        $budgeted_days = $budgeted_days = getBudgetedDays($conn, $emp_id, $period);

                        if ($days != $budgeted_days) { $misbudgetedEmps++; } // employee has been misbudgeted
                    }
                }
            }
        }

        // set the misbudgeted emps to 0 if it is not a number
        if (!is_numeric($misbudgetedEmps)) { $misbudgetedEmps = 0; } 

        // return the number of misbudgeted employees
        return $misbudgetedEmps;
    }

    /** get how many departmentts the director has */
    function getDirectorDepartmentsCount($conn, $director_id)
    {
        $dept_count = 0; // assume director is not assigned to any departments
        $getDeptCount = mysqli_prepare($conn, "SELECT COUNT(id) AS dept_count FROM departments WHERE director_id=? OR secondary_director_id=?");
        mysqli_stmt_bind_param($getDeptCount, "ii", $director_id, $director_id);
        if (mysqli_stmt_execute($getDeptCount))
        {
            $getDeptCountResult = mysqli_stmt_get_result($getDeptCount);
            if (mysqli_num_rows($getDeptCountResult) > 0) // director has departments; get count
            {
                $dept_count = mysqli_fetch_array($getDeptCountResult)["dept_count"];
            }
        }
        return $dept_count;
    }

    /** function to get the count of total test employees */
    function getTestEmployeesCount($conn, $period)
    {
        $test_employees = 0;
        $getTestEmployees = mysqli_prepare($conn, "SELECT COUNT(id) AS test_employees_count FROM project_employees_misc WHERE period_id=?");
        mysqli_stmt_bind_param($getTestEmployees, "i", $period);
        if (mysqli_stmt_execute($getTestEmployees))
        {
            $getTestEmployeesResult = mysqli_stmt_get_result($getTestEmployees);
            if (mysqli_num_rows($getTestEmployeesResult) > 0) // test employees found
            {
                $test_employees = mysqli_fetch_array($getTestEmployeesResult)["test_employees_count"];
            } 
        }
        return $test_employees;
    }

    /** function to get the count of test employees that are included to included in budgeting costs */
    function getIncludedTestEmployeesCount($conn, $period)
    {
        $included_test_employees = 0;
        $getIncludedTestEmployees = mysqli_prepare($conn, "SELECT COUNT(id) AS included_test_employees_count FROM project_employees_misc WHERE period_id=? AND costs_inclusion=1");
        mysqli_stmt_bind_param($getIncludedTestEmployees, "i", $period);
        if (mysqli_stmt_execute($getIncludedTestEmployees))
        {
            $getIncludedTestEmployeesResult = mysqli_stmt_get_result($getIncludedTestEmployees);
            if (mysqli_num_rows($getIncludedTestEmployeesResult) > 0) // test employees found
            {
                $included_test_employees = mysqli_fetch_array($getIncludedTestEmployeesResult)["included_test_employees_count"];
            } 
        }
        return $included_test_employees;
    }

    /** function to calculate the new quarterly costs */
    function setQuarterlyCosts($conn, $invoice_id, $service_id, $customer_id, $total_cost, $period)
    {
        // get any locked quarters payment
        $total_paid = 0; // assume none has been paid off
        $getPaid = mysqli_prepare($conn, "SELECT SUM(qc.cost) AS paid_costs FROM quarterly_costs qc 
                                            JOIN quarters q ON q.quarter=qc.quarter 
                                            WHERE q.locked=1 AND q.period_id=? AND qc.invoice_id=?");
        mysqli_stmt_bind_param($getPaid, "ii", $period, $invoice_id);
        if (mysqli_stmt_execute($getPaid))
        {
            $getPaidResult = mysqli_stmt_get_result($getPaid);
            if (mysqli_num_rows($getPaidResult) > 0) // payment found
            {
                $total_paid = mysqli_fetch_array($getPaidResult)["paid_costs"];
            }
        }

        // by default, insert the quarterly costs equally divided for quarters that are unlocked
        $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE locked=0 AND period_id=?");
        mysqli_stmt_bind_param($getQuarters, "i", $period);
        if (mysqli_stmt_execute($getQuarters))
        {
            $results = mysqli_stmt_get_result($getQuarters);
            $unlockedQuarters = mysqli_num_rows($results);
            
            if ($unlockedQuarters > 0) // at least 1 quarter is unlocked
            {
                // calculate the quarterly cost
                $quarterlyCost = (($total_cost - $total_paid) / $unlockedQuarters);

                // insert the quarterly costs into the database for each quarter
                while ($quarter = mysqli_fetch_array($results))
                {
                    // delete the current quarterly cost associated with the invoice if the quarter is unlocked; then insert updated quarterly cost
                    $deleteQuarterlyCosts = mysqli_prepare($conn, "DELETE FROM quarterly_costs WHERE invoice_id=? AND quarter=?");
                    mysqli_stmt_bind_param($deleteQuarterlyCosts, "ii", $invoice_id, $quarter["quarter"]);
                    if (mysqli_stmt_execute($deleteQuarterlyCosts)) // successfully deleted old quarterly costs; insert updated quarterly costs
                    {
                        $insertQuarterlyCosts = mysqli_prepare($conn, "INSERT INTO quarterly_costs (invoice_id, service_id, customer_id, quarter, cost, period_id, updated_user) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        mysqli_stmt_bind_param($insertQuarterlyCosts, "isiidii", $invoice_id, $service_id, $customer_id, $quarter["quarter"], $quarterlyCost, $period, $_SESSION["id"]);
                        mysqli_stmt_execute($insertQuarterlyCosts);
                    }
                }
            }
        }        
    }

    /** function to update the quarterly cost of a single quarter */
    function updateQuarterlyCost($conn, $invoice_id, $quarter, $quarter_cost, $period_id)
    {
        // get the current time in UTC
        date_default_timezone_set("UTC");
        $update_time = date("Y-m-d H:i:s");

        if (!checkLocked($conn, $quarter, $period_id)) // quarter is unlocked; continue update process
        {
            // check to see if there is a quarterly cost set for this quarter
            $checkQuarter = mysqli_prepare($conn, "SELECT id FROM quarterly_costs WHERE invoice_id=? AND quarter=?");
            mysqli_stmt_bind_param($checkQuarter, "ii", $invoice_id, $quarter);
            if (mysqli_stmt_execute($checkQuarter))
            {
                $checkQuarterResult = mysqli_stmt_get_result($checkQuarter);
                if (mysqli_num_rows($checkQuarterResult) > 0) // quarterly cost already set; update cost
                {
                    // update existing quarterly cost
                    $updateCost = mysqli_prepare($conn, "UPDATE quarterly_costs SET cost=?, updated_time=?, updated_user=? WHERE invoice_id=? AND quarter=?");
                    mysqli_stmt_bind_param($updateCost, "dsiii", $quarter_cost, $update_time, $_SESSION["id"], $invoice_id, $quarter);
                    if (mysqli_stmt_execute($updateCost)) { return true; }
                }
                else // quarterly cost not set; insert new quarterly cost
                {
                    // get the service ID and customer ID based on the invoice ID
                    $getIDs = mysqli_prepare($conn, "SELECT service_id, customer_id FROM services_provided WHERE id=?");
                    mysqli_stmt_bind_param($getIDs, "i", $invoice_id);
                    if (mysqli_stmt_execute($getIDs))
                    {
                        $getIDsResult = mysqli_stmt_get_result($getIDs);
                        if (mysqli_num_rows($getIDsResult) > 0) // service exists
                        {
                            // store the IDs locally
                            $IDs = mysqli_fetch_array($getIDsResult);
                            $service_id = $IDs["service_id"];
                            $customer_id = $IDs["customer_id"];

                            // insert new quarterly cost
                            $addCost = mysqli_prepare($conn, "INSERT INTO quarterly_costs (invoice_id, service_id, customer_id, quarter, cost, period_id, updated_user) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            mysqli_stmt_bind_param($addCost, "isiidii", $invoice_id, $service_id, $customer_id, $quarter, $quarter_cost, $period_id, $_SESSION["id"]);
                            if (mysqli_stmt_execute($addCost)) { return true; }
                        }
                    }
                }
            }
        }
        else { return true; } // quarter is locked; do nothing but return true

        // return false if we don't return
        return false;
    }

    /** function to update the quarterly cost of a single quarter */
    function updateOtherQuarterlyCost($conn, $invoice_id, $quarter, $quarter_cost, $period_id)
    {
        if (!checkLocked($conn, $quarter, $period_id)) // quarter is unlocked; continue update process
        {
            // check to see if there is a quarterly cost set for this quarter
            $checkQuarter = mysqli_prepare($conn, "SELECT id FROM other_quarterly_costs WHERE other_invoice_id=? AND quarter=?");
            mysqli_stmt_bind_param($checkQuarter, "ii", $invoice_id, $quarter);
            if (mysqli_stmt_execute($checkQuarter))
            {
                $checkQuarterResult = mysqli_stmt_get_result($checkQuarter);
                if (mysqli_num_rows($checkQuarterResult) > 0) // quarterly cost already set; update cost
                {
                    // update existing quarterly cost
                    $updateCost = mysqli_prepare($conn, "UPDATE other_quarterly_costs SET cost=? WHERE other_invoice_id=? AND quarter=?");
                    mysqli_stmt_bind_param($updateCost, "dii", $quarter_cost, $invoice_id, $quarter);
                    if (mysqli_stmt_execute($updateCost)) { return true; }
                }
                else // quarterly cost not set; insert new quarterly cost
                {
                    // get the service ID and customer ID based on the invoice ID
                    $getIDs = mysqli_prepare($conn, "SELECT service_id, customer_id FROM services_other_provided WHERE id=?");
                    mysqli_stmt_bind_param($getIDs, "i", $invoice_id);
                    if (mysqli_stmt_execute($getIDs))
                    {
                        $getIDsResult = mysqli_stmt_get_result($getIDs);
                        if (mysqli_num_rows($getIDsResult) > 0) // service exists
                        {
                            // store the IDs locally
                            $IDs = mysqli_fetch_array($getIDsResult);
                            $service_id = $IDs["service_id"];
                            $customer_id = $IDs["customer_id"];

                            // insert new quarterly cost
                            $addCost = mysqli_prepare($conn, "INSERT INTO other_quarterly_costs (other_invoice_id, other_service_id, customer_id, quarter, cost, period_id) VALUES (?, ?, ?, ?, ?, ?)");
                            mysqli_stmt_bind_param($addCost, "isiidi", $invoice_id, $service_id, $customer_id, $quarter, $quarter_cost, $period_id);
                            if (mysqli_stmt_execute($addCost)) { return true; }
                        }
                    }
                }
            }
        }
        else { return true; } // quarter is locked; do nothing but return true

        // return false if we don't return
        return false;
    }

    /** function to get the user's role */
    function getUserRole($conn, $user_id)
    {
        $role = 0; // assume the role is not found until found
        if ($user_id == 0) { $role = 1; } // user is the super admin; assign admin account type
        else // user is not super admin; check role from employees table
        {
            $getRole = mysqli_prepare($conn, "SELECT role_id FROM users WHERE id=?");
            mysqli_stmt_bind_param($getRole, "i", $user_id);
            if (mysqli_stmt_execute($getRole))
            {
                $getRoleResult = mysqli_stmt_get_result($getRole);
                if (mysqli_num_rows($getRoleResult) > 0) // role found
                {
                    // store the role locally
                    $role = mysqli_fetch_array($getRoleResult)["role_id"];
                }
            }
        }
        return $role; // return the user's role ID
    }

    /**
     *  functions to verify a user has access to the project 
     *  (1) Admins - have access to all projects
     *  (2) Directors - only have access to projects where the project is assigned to the director's department(s)
    */
    function verifyUserProject($conn, $user_id, $project_code)
    {
        // initialize the verified variable
        $verified = false; // assume the user is not verified until proven verified

        // verify the user based on account role
        if (checkUserPermission($conn, "BUDGET_PROJECTS_ALL")) { $verified = true; } // if user can budget any project, auto-verify
        else if (checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED")) // if user can only budget assigned projects, check if user has accsess to the project
        {
            $verifyDirector = mysqli_prepare($conn, "SELECT p.code FROM projects p JOIN departments d ON p.department_id=d.id WHERE (d.director_id=? OR d.secondary_director_id=?) AND p.code=?");
            mysqli_stmt_bind_param($verifyDirector, "iis", $user_id, $user_id, $project_code);
            if (mysqli_stmt_execute($verifyDirector))
            {
                $verifyDirectorResult = mysqli_stmt_get_result($verifyDirector);
                if (mysqli_num_rows($verifyDirectorResult) > 0) { $verified = true; } // director is head of department; continue
            }
        }

        // return the verification status
        return $verified;
    }

    function verifyUserCanViewProject($conn, $user_id, $project_code)
    {
        // initialize the verified variable
        $verified = false; // assume the user is not verified until proven verified

        // verify the user based on account role
        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL")) { $verified = true; } // if user can budget any project, auto-verify
        else if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED")) // if user can only budget assigned projects, check if user has accsess to the project
        {
            $verifyDirector = mysqli_prepare($conn, "SELECT p.code FROM projects p JOIN departments d ON p.department_id=d.id WHERE (d.director_id=? OR d.secondary_director_id=?) AND p.code=?");
            mysqli_stmt_bind_param($verifyDirector, "iis", $user_id, $user_id, $project_code);
            if (mysqli_stmt_execute($verifyDirector))
            {
                $verifyDirectorResult = mysqli_stmt_get_result($verifyDirector);
                if (mysqli_num_rows($verifyDirectorResult) > 0) { $verified = true; } // director is head of department; continue
            }
        }

        // return the verification status
        return $verified;
    }

    function verifyUserCanBudgetProject($conn, $user_id, $project_code)
    {
        // initialize the verified variable
        $verified = false; // assume the user is not verified until proven verified

        // verify the user based on account role
        if (checkUserPermission($conn, "BUDGET_PROJECTS_ALL")) { $verified = true; } // if user can budget any project, auto-verify
        else if (checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED")) // if user can only budget assigned projects, check if user has accsess to the project
        {
            $verifyDirector = mysqli_prepare($conn, "SELECT p.code FROM projects p JOIN departments d ON p.department_id=d.id WHERE (d.director_id=? OR d.secondary_director_id=?) AND p.code=?");
            mysqli_stmt_bind_param($verifyDirector, "iis", $user_id, $user_id, $project_code);
            if (mysqli_stmt_execute($verifyDirector))
            {
                $verifyDirectorResult = mysqli_stmt_get_result($verifyDirector);
                if (mysqli_num_rows($verifyDirectorResult) > 0) { $verified = true; } // director is head of department; continue
            }
        }

        // return the verification status
        return $verified;
    }

    /** 
     *  function to verify a user has access to the project 
     *  (1) Admins - have access to all services
     *  (2) Directors - only have access to services that have a director's project assigned to that service
    */
    function verifyUserService($conn, $user_id, $service_id)
    {
        // initialize the verified variable
        $verified = false; // assume the user is not verified until proven verified

        // get the user's role
        $role = getUserRole($conn, $user_id);

        // verify the user based on their account role
        if ($role == 1) { $verified = true; } // auto-verify admins
        else if ($role == 2) // director must be assigned service via their assigned projects
        {
            $verifyDirector = mysqli_prepare($conn, "SELECT p.code FROM services s JOIN projects p JOIN departments d ON p.department_id=d.id WHERE d.director_id=? OR d.secondary_director_id=? AND s.id=?");
            mysqli_stmt_bind_param($verifyDirector, "iis", $user_id, $user_id, $service_id);
            if (mysqli_stmt_execute($verifyDirector))
            {
                $verifyDirectorResult = mysqli_stmt_get_result($verifyDirector);
                if (mysqli_num_rows($verifyDirectorResult) > 0) { $verified = true; } // director is head of department; continue
            }
        }
        else if ($role == 3) // TODO - employee
        {

        }
        else if ($role == 4) { $verified = true; } // auto-verify maintenance

        // return the verification status
        return $verified;
    }

    /**
     *  function to verify the user has access to the employee
     *  (1) Admins - have access to make changes to all employees
     *  (2) Directors - can only make changes to employees in their departments
     *      --> Changes include: adding and removing from projects  
    */
    function verifyUserEmployee($conn, $user_id, $employee_id)
    {
        // initialize the verified variable
        $verified = false; // assume the user is not verified until proven verified

        // get the user's role
        $role = getUserRole($conn, $user_id);

        // verify that the user is allowed to make adjustments to this employee
        if ($role == 1) { $verified = true; } // auto-verify admins
        else if ($role == 2) // user is a director; employee must be within a director's department to verify
        {
            // verify that the employee selected exists and is active
            $checkEmployee = mysqli_prepare($conn, "SELECT e.id FROM employees e JOIN department_members dm ON e.id=dm.employee_id JOIN departments d ON dm.department_id=d.id WHERE e.id=? AND ((d.director_id=? OR d.secondary_director_id=?) OR e.global=1)");
            mysqli_stmt_bind_param($checkEmployee, "iii", $employee_id, $user_id, $user_id);
            if (mysqli_stmt_execute($checkEmployee))
            {
                $checkEmployeeResult = mysqli_stmt_get_result($checkEmployee);
                if (mysqli_num_rows($checkEmployeeResult) > 0) // director does have access to employee; verify user to make changes
                {
                    $verified = true; 
                }
            }
        }
        else if ($role == 3) // TODO - employee
        {

        }
        else if ($role == 4) { } // TODO - maintenance
        {
            
        }

        // return the verification status
        return $verified;
    }

    /**
     *  function to create an invoice / provide a service for a customer
    */
    function createInvoice($conn, $service_id, $customer_id, $period_id, $description, $date, $quantity = 0, $cost = 0, $rate_tier = 0, $group_rate_tier = 0, $origin = null)
    {
        // initialize the cost of the invoice to 0
        $invoice_cost = 0;

        // attempt to get details of the service
        $service = getServiceDetails($conn, $service_id);

        if (is_array($service)) // service exists; continue
        {                
            // store service details locally
            $service_name = $service["name"];
            $service_cost_type = $service["cost_type"];
            $service_round_costs = $service["round_costs"];
            $service_project_code = $service["project_code"];

            // attempt to get details of the customer
            $customer = getCustomerDetails($conn, $customer_id);

            if (is_array($customer)) // customer exists; continue
            {
                // store customer details locally
                $customer_name = $customer["name"];
                $customer_members = $customer["members"];

                // get the cost of the invoice
                $invoice_cost = getInvoiceCost($conn, $service_id, $customer_id, $period_id, $service_cost_type, $service_round_costs, $quantity, $cost, $customer_members, $rate_tier, $group_rate_tier);

                // set the quantiy of the service to 1 if it is the custom cost, membership cost, or rate-based cost type
                if (($service_cost_type == 2 || $service_cost_type == 3 || $service_cost_type == 4 || $service_cost_type == 5) && $origin == "upload") { $quantity = 1; }

                // insert the invoice into the database
                $addInvoice = mysqli_prepare($conn, "INSERT INTO services_provided (period_id, service_id, customer_id, quantity, description, date_provided, total_cost, updated_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($addInvoice, "isidssdi", $period_id, $service_id, $customer_id, $quantity, $description, $date, $invoice_cost, $_SESSION["id"]);
                if (mysqli_stmt_execute($addInvoice)) // successfully created the invoice
                { 
                    // get the invoice_id for the new service provied
                    $invoice_id = mysqli_insert_id($conn);

                    // get the period name
                    $period_name = getPeriodName($conn, $period_id);

                    // edit the project last updated time
                    if (isset($service_project_code) && $service_project_code != null) { updateProjectEditTimestamp($conn, $service_project_code); }

                    // set/update the quarterly costs
                    setQuarterlyCosts($conn, $invoice_id, $service_id, $customer_id, $invoice_cost, $period_id);
                    
                    // successfully created the invoice; return true, log invoice, and display
                    echo "<span class=\"log-success\">Successfully</span> invoiced $customer_name ".printDollar($invoice_cost)." for the service $service_name.<br>";
                    $message = "Successfully invoiced $customer_name (customer ID: $customer_id) ".printDollar($invoice_cost)." for the service $service_name (service ID: $service_id) for the $period_name period (period ID: $period_id). Assigned the invoice the ID $invoice_id.";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);
                    return true;
                }
                else // failed to create the invoice
                { 
                    // failed to create the invoice; return false and display
                    echo "<span class=\"log-fail\">Failed</span> to invoice $customer_name for ".printDollar($invoice_cost)." for the service $service_name. An unexpected error has occurred! Please try again later.<br>";
                    return false; 
                }
            }
            else 
            { 
                // customer does not exist; return false and display error
                echo "<span class=\"log-fail\">Failed</span> to invoice the customer with the ID $customer_id for the service $service_name. The customer does not exist!<br>"; 
                return false;
            }
        }
        else 
        { 
            // service does not exist; return false and display error
            echo "<span class=\"log-fail\">Failed</span> to invoice the customer with the ID $customer_id. The service with ID $service_id does not exist!<br>"; 
            return false;
        }
    }

    /**
     *  function to edit an invoice 
    */
    function editInvoice($conn, $invoice_id, $service_id, $customer_id, $period_id, $description, $date, $allow_zero = 0, $quantity = 0, $cost = 0, $rate_tier = 0, $group_rate_tier = 0)
    {
        // initialize the cost of the invoice to 0
        $invoice_cost = 0;

        // get the current time in UTC
        date_default_timezone_set("UTC");
        $update_time = date("Y-m-d H:i:s");

        // attempt to get details of the service
        $service = getServiceDetails($conn, $service_id);

        if (is_array($service)) // service exists; continue
        {                
            // store service details locally
            $service_name = $service["name"];
            $service_cost_type = $service["cost_type"];
            $service_round_costs = $service["round_costs"];
            $service_project_code = $service["project_code"];

            // attempt to get details of the customer
            $customer = getCustomerDetails($conn, $customer_id);

            if (is_array($customer)) // customer exists; continue
            {
                // store customer details locally
                $customer_name = $customer["name"];
                $customer_members = $customer["members"];

                // get the cost of the invoice
                $invoice_cost = getInvoiceCost($conn, $service_id, $customer_id, $period_id, $service_cost_type, $service_round_costs, $quantity, $cost, $customer_members, $rate_tier, $group_rate_tier);

                // insert the invoice into the database
                $editInvoice = mysqli_prepare($conn, "UPDATE services_provided SET quantity=?, description=?, date_provided=?, total_cost=?, allow_zero=?, updated_time=?, updated_user=? WHERE id=?");
                mysqli_stmt_bind_param($editInvoice, "dssdisii", $quantity, $description, $date, $invoice_cost, $allow_zero, $update_time, $_SESSION["id"], $invoice_id);
                if (mysqli_stmt_execute($editInvoice)) // successfully created the invoice
                { 
                    // edit the project last updated time
                    if (isset($service_project_code) && $service_project_code != null) { updateProjectEditTimestamp($conn, $service_project_code); }

                    // get the period name
                    $period_name = getPeriodName($conn, $period_id);

                    // log to screen successful invoice
                    echo "<span class=\"log-success\">Successfully</span> updated the invoice for $customer_name for ".printDollar($invoice_cost)." for the service $service_name.<br>";
                    $message = "Successfully updated the invoice (invoice ID: $invoice_id) for $customer_name (customer ID: $customer_id) ".printDollar($invoice_cost)." for the service $service_name (service ID: $service_id) for the $period_name period (period ID: $period_id).";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);

                    // set/update the quarterly costs
                    setQuarterlyCosts($conn, $invoice_id, $service_id, $customer_id, $invoice_cost, $period_id);                    
                }
                else { echo "<span class=\"log-fail\">Failed</span> to update the invoice for $customer_name for ".printDollar($invoice_cost)." for the service $service_name. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the invoice. The customer does not exist!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the invoice. The service with ID $service_id does not exist!<br>"; }        
    }

    /** 
     *  function to get the cost of an invoice  
    */
    function getInvoiceCost($conn, $service_id, $customer_id, $period_id, $service_cost_type, $service_round_costs, $quantity = 0, $cost = 0, $customer_members = 0, $rate_tier = 0, $group_rate_tier = 0)
    {
        // initialize the invoice cost
        $invoice_cost = 0;

        // get the cost of the invoice based on the service's cost type
        if ($service_cost_type == 0) { $invoice_cost = getFixedCost($conn, $service_id, $period_id, $quantity); }
        else if ($service_cost_type == 1) { $invoice_cost = getVariableCost($conn, $service_id, $period_id, $quantity); }
        else if ($service_cost_type == 2) { $invoice_cost = getMembershipCost($conn, $service_id, $period_id, $customer_members); }
        else if ($service_cost_type == 3) { $invoice_cost = $cost; }
        else if ($service_cost_type == 4) { $invoice_cost = getRateCost($conn, $service_id, $period_id, $rate_tier); }
        else if ($service_cost_type == 5) { $invoice_cost = getGroupRateCost($conn, $service_id, $period_id, $customer_id, $group_rate_tier, $quantity); }

        // round the invoice if the service is set to round costs
        if ($service_round_costs == 1) { $invoice_cost = round($invoice_cost); } // round to nearest whole dollar
        else { $invoice_cost = round($invoice_cost, 2); } // round to 2 decimal points

        // return the invoice cost
        return $invoice_cost;
    }

    /**
     *  function to get details of a service
    */
    function getServiceDetails($conn, $service_id)
    {
        // get service details
        $getServiceDetails = mysqli_prepare($conn, "SELECT * FROM services WHERE id=?");
        mysqli_stmt_bind_param($getServiceDetails, "s", $service_id);
        if (mysqli_stmt_execute($getServiceDetails))
        {
            $getServiceDetailsResult = mysqli_stmt_get_result($getServiceDetails);
            if (mysqli_num_rows($getServiceDetailsResult) > 0) // service exists
            {
                // return an array that stores the service's details
                return mysqli_fetch_array($getServiceDetailsResult); 
            }
            else // service does not exist
            {
                // return false; service does not exist
                return false;
            }
        }        
    }

    /**
     *  function to get details of a customer
    */
    function getCustomerDetails($conn, $customer_id)
    {
        $getCustomerDetails = mysqli_prepare($conn, "SELECT * FROM customers WHERE id=?");
        mysqli_stmt_bind_param($getCustomerDetails, "i", $customer_id);
        if (mysqli_stmt_execute($getCustomerDetails))
        {
            $getCustomerDetailsResult = mysqli_stmt_get_result($getCustomerDetails);
            if (mysqli_num_rows($getCustomerDetailsResult) > 0) // customer exists
            {
                // return an array that stores the customer's details
                return mysqli_fetch_array($getCustomerDetailsResult);
            }
            else // customer does not exist
            {
                // return false; customer does not exist
                return false;
            }
        }
    }

    /**
     *  function to get the cost of an invoice for a fixed service
    */
    function getFixedCost($conn, $service_id, $period_id, $quantity)
    {
        // get the cost of the service
        $invoice_cost = $service_cost = 0; // assume the invoice cost and service cost are 0
        $getCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND period_id=? AND cost_type=0");
        mysqli_stmt_bind_param($getCost, "si", $service_id, $period_id);
        if (mysqli_stmt_execute($getCost))
        {
            $getCostResult = mysqli_stmt_get_result($getCost);
            if (mysqli_num_rows($getCostResult) > 0) // cost found
            {
                $service_cost = mysqli_fetch_array($getCostResult)["cost"];
            }
        }

        // calculate the cost of the invoice
        $invoice_cost = $service_cost * $quantity;

        // return the cost of the invoice
        return $invoice_cost;
    }

    /**
     *  function to get the cost of an invoice for a variable-cost service
    */
    function getVariableCost($conn, $service_id, $period_id, $quantity)
    {
        // get the cost of the service
        $invoice_cost = $service_cost = 0; // assume the invoice cost and service cost are 0
        $getCost = mysqli_prepare($conn, "SELECT cost, min_quantity, max_quantity FROM costs WHERE service_id=? AND period_id=? AND cost_type=1 ORDER BY min_quantity ASC");
        mysqli_stmt_bind_param($getCost, "si", $service_id, $period_id);
        if (mysqli_stmt_execute($getCost))
        {
            $getCostResult = mysqli_stmt_get_result($getCost);
            if (mysqli_num_rows($getCostResult) > 0) // cost found
            {
                // go through each cost range; if quantity is between the range
                $break = 0;
                while (($range = mysqli_fetch_array($getCostResult)) && $break == 0)
                {
                    $min = $range["min_quantity"];
                    $max = $range["max_quantity"];
                    $cost = $range["cost"];

                    if ($max != -1) // max is set
                    {
                        if ($quantity >= $min && $quantity <= $max) // quantity is within the range
                        {
                            // calculate the total annual cost
                            $service_cost = $cost;
                            $break = 1; // break while loop
                        }
                    }
                    else // no max is set
                    {
                        // calculate the total annual cost
                        $service_cost = $cost;
                        $break = 1; // break while loop
                    }
                }
            }
        }

        // calculate the cost of the invoice
        $invoice_cost = $service_cost * $quantity;

        // return the cost of the invoice
        return $invoice_cost;
    }

    /**
     *  function to get the cost of an invoice for a membership-based service
    */
    function getMembershipCost($conn, $service_id, $period_id, $members)
    {
        // initialize the membership group
        $membership_group = null;

        // get the cost of the service
        $invoice_cost = $service_cost = 0; // assume the invoice cost and service cost are 0
        $getCost = mysqli_prepare($conn, "SELECT cost, group_id FROM costs WHERE service_id=? AND period_id=? AND cost_type=2");
        mysqli_stmt_bind_param($getCost, "si", $service_id, $period_id);
        if (mysqli_stmt_execute($getCost))
        {
            $getCostResult = mysqli_stmt_get_result($getCost);
            if (mysqli_num_rows($getCostResult) > 0) // cost found
            {
                $cost_details = mysqli_fetch_array($getCostResult);
                $service_cost = $cost_details["cost"];
                $membership_group = $cost_details["group_id"];
            }
        }

        // if the membership group was found; get the total count of all group members
        $total_members = 0; // assume total group membership is at 0
        if ($membership_group != null && is_numeric($membership_group))
        {
            $getTotalMembers = mysqli_prepare($conn, "SELECT SUM(c.members) AS total_members FROM customers c
                                                    JOIN group_members g ON c.id=g.customer_id
                                                    WHERE g.group_id=?");
            mysqli_stmt_bind_param($getTotalMembers, "i", $membership_group);
            if (mysqli_stmt_execute($getTotalMembers))
            {
                $getTotalMembersResult = mysqli_stmt_get_result($getTotalMembers);
                if (mysqli_num_rows($getTotalMembersResult) > 0) // members found
                {
                    $total_members = mysqli_fetch_array($getTotalMembersResult)["total_members"];
                }
            }
        }

        // get the percentage of members the customer has in comparison to the rest of the group
        $membership_percentage = 0;
        if ($total_members != 0) { $membership_percentage = ($members / $total_members); }

        // calculate the cost of the invoice
        $invoice_cost = $service_cost * $membership_percentage;

        // return the cost of the invoice
        return $invoice_cost;
    }

    /**
     *  function to get the cost of an invoice for a rate-based service
    */
    function getRateCost($conn, $service_id, $period_id, $rate_tier)
    {
        // initialize variables
        $invoice_cost = $service_cost = 0; // assume the invoice cost and service cost are 0
        
        if ($rate_tier != null && is_numeric($rate_tier)) // rate tier is set
        {
            // get the cost associated to the selected tier
            $getCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND period_id=? AND variable_order=? AND cost_type=4");
            mysqli_stmt_bind_param($getCost, "sii", $service_id, $period_id, $rate_tier);
            if (mysqli_stmt_execute($getCost))
            {
                $getCostResult = mysqli_stmt_get_result($getCost);
                if (mysqli_num_rows($getCostResult) > 0) // cost found
                {
                    $service_cost = mysqli_fetch_array($getCostResult)["cost"];
                }
            }
        }

        // calculate, then return the invoice cost
        $invoice_cost = $service_cost;
        return $invoice_cost;
    }

    /**
     *  function to get the cost of an invoice for a group rate service
    */
    function getGroupRateCost($conn, $service_id, $period_id, $customer_id, $rate_tier, $quantity = 1)
    {
        // initialize variables
        $invoice_cost = $service_cost = 0; // assume the invoice cost and service cost are 0
        
        if ($rate_tier != null && is_numeric($rate_tier)) // rate tier is set
        {
            // get the cost associated to the selected tier
            $getRateGroup = mysqli_prepare($conn, "SELECT group_id FROM costs WHERE service_id=? AND period_id=? AND variable_order=? AND cost_type=5");
            mysqli_stmt_bind_param($getRateGroup, "sii", $service_id, $period_id, $rate_tier);
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

                    // get the services cost based on if the customer is a member or not
                    $getCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND period_id=? AND variable_order=? AND in_group=? AND cost_type=5");
                    mysqli_stmt_bind_param($getCost, "siii", $service_id, $period_id, $rate_tier, $isMember);
                    if (mysqli_stmt_execute($getCost))
                    {
                        $getCostResult = mysqli_stmt_get_result($getCost);
                        if (mysqli_num_rows($getCostResult) > 0) // cost found
                        {
                            $service_cost = mysqli_fetch_array($getCostResult)["cost"];
                        }
                    }
                }
            }
        }

        // calculate, then return the invoice cost
        $invoice_cost = $service_cost * $quantity;
        return $invoice_cost;
    }

    /**
     *  function to create the div to filter a table by any search term (all columns) 
    */
    function createSearchFilter()
    {
        ?>
            <div class="col-sm-12 col-md-6 col-lg-3 col-xl-3 col-xxl-2 justify-content-center px-2">
                <label class="filter-label text-center w-100" for="search-all">Search table contents:</label>
                <div class="input-group w-100 h-auto">
                    <div class="input-group-prepend">
                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    </div>
                    <input type="text" class="form-control" id="search-all" name="search-all" autocomplete="false">
                </div>
            </div>
        <?php
    }

    /**
     *  function to create the div to filter a table by department
    */
    function createDepartmentFilter($conn, $label, $user_id, $required = 0, $no_dept = 0)
    {
        // get the user's role
        $role = getUserRole($conn, $user_id);

        ?>
            <!-- filter by department -->
            <div class="col-sm-12 col-md-6 col-lg-3 col-xl-3 col-xxl-2 justify-content-center px-2">
                <label class="filter-label text-center w-100" for="search-dept"><?php if ($required == 1) { echo "<span class='required-field'>*</span> "; } ?>Filter <?php echo $label; ?> by department:</label>
                <select class="form-select" id="search-dept" name="search-dept">
                    <option></option>
                    <?php if ($no_dept == 0) { echo "<option>No primary department assigned</option>"; } ?>
                    <?php
                        if ($role == 1 || $role == 4) // admin and maintenance departments list
                        { 
                            $getDepts = mysqli_query($conn, "SELECT id, name FROM departments ORDER BY name ASC");
                            if (mysqli_num_rows($getDepts) > 0) // departments found
                            {
                                while ($dept = mysqli_fetch_array($getDepts))
                                {
                                    echo "<option>".$dept["name"]."</option>";
                                }
                            }
                        }
                        else if ($role == 2) // director's department list
                        {
                            $getDepts = mysqli_prepare($conn, "SELECT id, name FROM departments WHERE director_id=? OR secondary_director_id=? ORDER BY name ASC");
                            mysqli_stmt_bind_param($getDepts, "ii", $_SESSION["id"], $_SESSION["id"]);
                            if (mysqli_stmt_execute($getDepts))
                            {
                                $getDeptsResults = mysqli_stmt_get_result($getDepts);
                                if (mysqli_num_rows($getDeptsResults) > 0) // departments found; populate list
                                {
                                    while ($dept = mysqli_fetch_array($getDeptsResults))
                                    {
                                        echo "<option>".$dept["name"]."</option>";
                                    }
                                }
                            }
                        }
                    ?>
                </select>
            </div>
        <?php
    }

    /**
     *  function to create the div to filter a table by group
    */
    function createGroupFilter($conn, $label, $prefix = null)
    {
        ?>
            <!-- filter by group -->
            <div class="col-2 justify-content-center px-2">
                <label class="filter-label text-center w-100" for="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-groups">Search <?php echo $label; ?> by group:</label>
                <div class="input-group w-100 h-auto">
                    <div class="input-group-prepend">
                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    </div>
                    <select class="form-select" id="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-groups" name="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-groups">
                        <option></option>
                        <?php
                            $getGroups = mysqli_query($conn, "SELECT id, name FROM `groups` ORDER BY name ASC");
                            if (mysqli_num_rows($getGroups) > 0) // groups exist
                            {
                                while ($group = mysqli_fetch_array($getGroups))
                                {
                                    echo "<option value='".$group["name"]."'>".$group["name"]."</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
            </div>
        <?php
    }

    /**
     *  function to create the div to filter a table by internal title
    */
    function createTitleFilter($conn, $label, $required = 0)
    {
        ?>
            <!-- filter by title -->
            <div class="col-sm-12 col-md-6 col-lg-3 col-xl-3 col-xxl-2 justify-content-center px-2">
                <label class="filter-label text-center w-100" for="search-title"><?php if ($required == 1) { echo "<span class='required-field'>*</span> "; } ?>Filter <?php echo $label; ?> by title:</label>
                <div class="input-group w-100 h-auto">
                    <div class="input-group-prepend">
                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    </div>
                    <select class="form-select" id="search-title" name="search-title">
                        <option></option>
                        <?php
                            $getTitles = mysqli_query($conn, "SELECT * FROM employee_titles ORDER BY name ASC");
                            if (mysqli_num_rows($getTitles) > 0)
                            {
                                while ($title = mysqli_fetch_array($getTitles))
                                {
                                    // store title details locally
                                    $title_id = $title["id"];
                                    $title_name = $title["name"];

                                    // build dropdown option
                                    echo "<option value='".$title_id."'>".$title_name."</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
            </div>
        <?php
    }

    /** 
     *  function to create the div to clear filters 
    */
    function createClearFilters($prefix = null)
    {
        ?>
            <!-- clear filters -->
            <div class="col-sm-12 col-md-6 col-lg-2 col-xl-2 col-xxl-2 justify-content-center px-2">
                <label class="text-center w-100" for="<?php if (isset($prefix)) { echo $prefix."-"; } ?>clearFilters"></label> <!-- label is spacer -->
                <button class="btn btn-secondary w-100" id="<?php if (isset($prefix)) { echo $prefix."-"; } ?>clearFilters"><i class="fa-solid fa-xmark"></i> Clear Filters</button>
            </div>
        <?php
    }

    /**
     *  function to get and store all periods
    */
    function getPeriods($conn)
    {
        // initialize an array to store all periods; then get all periods and store in the array
        $periods = [];
        $getPeriods = mysqli_query($conn, "SELECT id, name, active FROM `periods` ORDER BY active DESC, name ASC");
        if (mysqli_num_rows($getPeriods) > 0) // periods exist
        {
            while ($period = mysqli_fetch_array($getPeriods))
            {
                // store period's data in array
                $periods[] = $period;
            }
        }
        return $periods;
    }

    /** function to get DPI positions */
    function getDPIPositions($conn)
    {
        $positions = [];
        $getPositions = mysqli_query($conn, "SELECT DISTINCT(position_code), position_name FROM dpi_positions ORDER BY position_code ASC");
        if (mysqli_num_rows($getPositions) > 0)
        {
            while ($position = mysqli_fetch_array($getPositions))
            {
                // store degree data in array
                $positions[] = $position;
            }
        }
        return $positions;
    }

    /** function to get DPI areas for a given position */
    function getPositionAreas($conn, $position)
    {
        $areas = [];
        $getAreas = mysqli_prepare($conn, "SELECT DISTINCT(area_code), area_name FROM dpi_positions WHERE position_code=? ORDER BY area_code ASC");
        mysqli_stmt_bind_param($getAreas, "s", $position);
        if (mysqli_stmt_execute($getAreas))
        {
            $getAreasResults = mysqli_stmt_get_result($getAreas);
            if (mysqli_num_rows($getAreasResults) > 0)
            {
                while ($area = mysqli_fetch_array($getAreasResults))
                {
                    // store degree data in array
                    $areas[] = $area;
                }
            }
        }
        return $areas;
    }

    /** function to get all degrees */
    function getDegrees($conn)
    {
        $degrees = [];
        $getDegrees = mysqli_query($conn, "SELECT * FROM degrees ORDER BY code ASC");
        if (mysqli_num_rows($getDegrees) > 0)
        {
            while ($degree = mysqli_fetch_array($getDegrees))
            {
                // store degree data in array
                $degrees[] = $degree;
            }
        }
        return $degrees;
    }

    /**
     *  function to create the div to filter salaries by DPI position
    */
    function createPositionsFilter($conn, $label = null, $prefix = null, $filter = 0, $required = 0)
    {
        if ($filter == 0)
        {
            ?>
                <!-- filter by position -->
                <div class="col-2 justify-content-center px-2">
                    <label class="filter-label text-center w-100" for="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-position"><?php if ($required == 1) { ?><span class="required-field">*</span> <b><?php } ?>Select position assignment:<?php if ($required == 1) { ?></b><?php } ?></label>
                    <select class="form-select" id="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-position" name="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-position" onchange="getAreas();">
                        <option></option>
                        <?php
                            $positions = getDPIPositions($conn);
                            for ($p = 0; $p < count($positions); $p++)
                            {
                                echo "<option value='".$positions[$p]["position_code"]."'>".$positions[$p]["position_code"]." - ".$positions[$p]["position_name"]."</option>";
                            }
                        ?>
                    </select>
                </div>
            <?php
        }
        else
        {
            ?>
                <!-- filter by position -->
                <div class="col-2 justify-content-center px-2">
                    <label class="filter-label text-center w-100" for="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-position"><?php if ($required == 1) { ?><span class="required-field">*</span> <b><?php } ?>Filter <?php if ($label != null) { echo $label; } ?> by position assignment:<?php if ($required == 1) { ?></b><?php } ?></label>
                    <select class="form-select" id="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-position" name="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-position" onchange="getAreas();">
                        <option></option>
                        <?php
                            $positions = getDPIPositions($conn);
                            for ($p = 0; $p < count($positions); $p++)
                            {
                                echo "<option value='".$positions[$p]["position_code"]."'>".$positions[$p]["position_code"]." - ".$positions[$p]["position_name"]."</option>";
                            }
                        ?>
                    </select>
                </div>
            <?php
        }
    }

    /**
     *  function to create the div to filter salaries by DPI position
    */
    function createAreasFilter($conn = null, $label = null, $prefix = null, $filter = 0, $required = 0)
    {
        if ($filter == 0)
        {
            ?>
                <!-- filter by position -->
                <div class="col-2 justify-content-center px-2">
                    <label class="filter-label text-center w-100" for="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-area"><?php if ($required == 1) { ?><span class="required-field">*</span> <b><?php } ?>Select position area:<?php if ($required == 1) { ?></b><?php } ?></label>
                    <select class="form-select" id="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-area" name="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-area">
                        <option></option>
                    </select>
                </div>
            <?php
        }
        else
        {
            ?>
                <!-- filter by position -->
                <div class="col-2 justify-content-center px-2">
                    <label class="filter-label text-center w-100" for="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-area"><?php if ($required == 1) { ?><span class="required-field">*</span> <b><?php } ?>Filter <?php if ($label != null) { echo $label; } ?> by position area:<?php if ($required == 1) { ?></b><?php } ?></label>
                    <select class="form-select" id="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-area" name="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-area">
                        <option></option>
                    </select>
                </div>
            <?php
        }
    }

    /**
     *  function to create the div to filter salaries by DPI work type
    */
    function createWorkTypeFilter($conn = null, $label = null, $prefix = null)
    {
        ?>
            <!-- filter by work type -->
            <div class="col-2 justify-content-center px-2">
                <label class="filter-label text-center w-100" for="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-work_type">Select work type:</label>
                <div class="input-group w-100 h-auto">
                    <div class="input-group-prepend">
                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    </div>
                    <select class="form-select" id="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-work_type" name="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-work_type">
                        <option></option>
                        <?php
                            $getWorkTypes = mysqli_query($conn, "SELECT DISTINCT work_type FROM dpi_employees ORDER BY work_type ASC");
                            if (mysqli_num_rows($getWorkTypes) > 0)
                            {
                                while ($work_type = mysqli_fetch_array($getWorkTypes))
                                {
                                    echo "<option>".$work_type["work_type"]."</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
            </div>
        <?php
    }

    /**
     *  function to create the div to filter salaries by DPI work county
    */
    function createWorkCountyFilter($conn = null, $label = null, $prefix = null)
    {
        ?>
            <!-- filter by work type -->
            <div class="col-2 justify-content-center px-2">
                <label class="filter-label text-center w-100" for="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-work_county">Select work county:</label>
                <div class="input-group w-100 h-auto">
                    <div class="input-group-prepend">
                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    </div>
                    <select class="form-select" id="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-work_county" name="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-work_county">
                        <option></option>
                        <?php
                            $getWorkCounties = mysqli_query($conn, "SELECT DISTINCT work_county FROM dpi_employees ORDER BY work_county ASC");
                            if (mysqli_num_rows($getWorkCounties) > 0)
                            {
                                while ($work_county = mysqli_fetch_array($getWorkCounties))
                                {
                                    echo "<option>".$work_county["work_county"]."</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
            </div>
        <?php
    }

    /**
     *  function to create the div to filter salaries by DPI work level
    */
    function createWorkLevelFilter($conn = null, $label = null, $prefix = null)
    {
        ?>
            <!-- filter by work type -->
            <div class="col-2 justify-content-center px-2">
                <label class="filter-label text-center w-100" for="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-work_level">Select work level:</label>
                <div class="input-group w-100 h-auto">
                    <div class="input-group-prepend">
                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    </div>
                    <select class="form-select" id="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-work_level" name="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-work_level">
                        <option></option>
                        <?php
                            $getWorkLevels = mysqli_query($conn, "SELECT DISTINCT work_level FROM dpi_employees WHERE work_level!='' ORDER BY work_level ASC");
                            if (mysqli_num_rows($getWorkLevels) > 0)
                            {
                                while ($work_level = mysqli_fetch_array($getWorkLevels))
                                {
                                    echo "<option>".$work_level["work_level"]."</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
            </div>
        <?php
    }

    /** functon to create the div to filter by if we are building a customer's contract or not */
    function createBuildContractsFilter($conn = null, $label = null, $prefix = null)
    {
        ?>
            <!-- filter by build contract -->
            <div class="col-sm-12 col-md-6 col-lg-3 col-xl-3 col-xxl-2 justify-content-center px-2">
                <label class="filter-label text-center w-100" for="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-build_contract">Filter <?php echo $label; ?> by build status:</label>
                <div class="input-group w-100 h-auto">
                    <div class="input-group-prepend">
                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    </div>
                    <select class="form-select" id="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-build_contract" name="<?php if (isset($prefix)) { echo $prefix."-"; } ?>search-build_contract">
                        <option></option>
                        <option value=0>Not building contract</option>
                        <option value=1>Building contract</option>
                    </select>
                </div>
            </div>
        <?php
    }

    /** function to create the DPI positions string (position code with position name) */
    function getPositionString($conn, $code)
    {
        $position = null;
        $checkPosition = mysqli_prepare($conn, "SELECT position_name FROM dpi_positions WHERE position_code=?");
        mysqli_stmt_bind_param($checkPosition, "s", $code);
        if (mysqli_stmt_execute($checkPosition))
        {
            $checkPositionResult = mysqli_stmt_get_result($checkPosition);
            if (mysqli_num_rows($checkPositionResult) > 0) // position exists
            {
                // store the position details locally
                $position_details = mysqli_fetch_array($checkPositionResult);
                $name = $position_details["position_name"];

                // create the positions string
                $position = $code . " - " . $name;
            }
        }
        return $position;
    }

    /** function to create the DPI areas string (area code with area name) */
    function getAreaString($conn, $code)
    {
        $area = null;
        $checkArea = mysqli_prepare($conn, "SELECT area_name FROM dpi_positions WHERE area_code=?");
        mysqli_stmt_bind_param($checkArea, "s", $code);
        if (mysqli_stmt_execute($checkArea))
        {
            $checkAreaResult = mysqli_stmt_get_result($checkArea);
            if (mysqli_num_rows($checkAreaResult) > 0) // area exists
            {
                // store the area details locally
                $area_details = mysqli_fetch_array($checkAreaResult);
                $name = $area_details["area_name"];

                // create the area string
                $area = $code . " - " . $name;
            }
        }
        return $area;
    }

    /** function to add a new employee */
    function addEmployee($conn, $period, $id = null, $fname = null, $lname = null, $email = null, $phone = null, $birthday = null, $gender = 0, $marital_status = 0, 
                                $line1 = null, $line2 = null, $city = null, $state = null, $zip = null, 
                                $title = null, $department = null, $supervisor = null, $hire_date = null, $end_date = null, $original_hire_date = null, $original_end_date = null,
                                $salary = 0, $contract_days = 0, $contract_type = 0, $contract_start_date = null, $contract_end_date = null, $calendar_type = 0, $num_of_pays = 0, 
                                $health = 0, $dental = 0, $wrs = 0, $position = null, $area = null, $experience = 0, $experience_adjustment = 0, $degree = null, $role = 3, $global = 0, $status = 1)
    {
        // initialize variables
        $errors = 0;
        $report = "";
        $reportSegments = [];
        
        // find errors in required fields
        if ($id == null) { $reportSegments[] = "ID"; $errors++; }
        if ($fname == null) { $reportSegments[] = "first name"; $errors++; }
        if ($lname == null) { $reportSegments[] = "last name"; $errors++; }
        if ($email == null) { $reportSegments[] = "email"; $errors++; }

        // no errors found in required fields; continue
        if ($errors == 0)
        {
            // convert birthday
            if ($birthday != null) { $birthday = date("Y-m-d", strtotime($birthday)); } else { $birthday = date("Y-m-d"); }

            // validate gender
            if (!is_numeric($gender) || ($gender != 0 && $gender != 1 && $gender != 2)) { $gender = 0; }

            // validate marital status
            if (!is_numeric($marital_status) || ($marital_status != 0 && $marital_status != 1 && $marital_status != 2)) { $marital_status = 0; }

            // validate hire dates and end dates
            if (isset($hire_date) && $hire_date != null) { $hire_date = date("Y-m-d", strtotime($hire_date)); } else { $hire_date = null; }
            if (isset($end_date) && $end_date != null) { $end_date = date("Y-m-d", strtotime($end_date)); } else { $end_date = null; }
            if (isset($original_hire_date) && $original_hire_date != null) { $original_hire_date = date("Y-m-d", strtotime($original_hire_date)); } else { $original_hire_date = null; }
            if (isset($original_end_date) && $original_end_date != null) { $original_end_date = date("Y-m-d", strtotime($original_end_date)); } else { $original_end_date = null; }

            // validate active status
            if (!is_numeric($status) || ($status != 0 && $status != 1)) { $status = 0; }

            // validate global employee status
            if (!is_numeric($global) || ($global != 0 && $global != 1)) { $global = 0; }

            // validate employee role; set to "Employee" by default if no role was selected
            if (!is_numeric($role)) { $role = 3; }

            if (!checkExistingEmployee($conn, $id))
            {
                // attempt to add the new employee to the database
                $addEmployee = mysqli_prepare($conn, "INSERT INTO employees (id, fname, lname, email, phone, birthday, gender, marital_status, original_hire_date, original_end_date, most_recent_hire_date, most_recent_end_date, role_id, global) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($addEmployee, "isssssiissssii", $id, $fname, $lname, $email, $phone, $birthday, $gender, $marital_status, $original_hire_date, $original_end_date, $hire_date, $end_date, $role, $global);
                if (mysqli_stmt_execute($addEmployee)) // successfully added the employee
                {
                    // log status to screen
                    echo "<span class=\"log-success\">Successfully</span> added $fname $lname.<br>";

                    // add employee's benefits, compensation, and additional role details
                    if (!setEmployeeCompensation($conn, $period, $id, $supervisor, $salary, $contract_days, $contract_type, $contract_start_date, $contract_end_date, $calendar_type, $num_of_pays, $health, $dental, $wrs, $title, $position, $area, $experience, $experience_adjustment, $degree, $status)) 
                    { 
                        echo "<span class=\"log-fail\">Failed</span> to set $fname $lname's benefits, compensation, and role details. An unexpected error has occurred. Please try again later!<br>"; 
                    }

                    // edit the employee's primary department
                    if (!setPrimaryDepartment($conn, $id, $department)) { echo "<span class=\"log-fail\">Failed</span> to set $fname $lname's primary department. An unexpected error has occurred. Please try again later!<br>"; }

                    // add employee's address
                    if (!setEmployeeAddress($conn, $id, $line1, $line2, $city, $state, $zip)) { echo "<span class=\"log-fail\">Failed</span> to set $fname $lname's address. An unexpected error has occurred. Please try again later!<br>"; }
                
                    // log employee creation
                    $message = "Successfully added $fname $lname as a new employee with the ID of $id.";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);

                    // attempt to create user account for new employee
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
                                echo "<span class=\"log-success\">Successfully</span> created user account for the new employee user with email address $email. Assigned the user the ID $user_id. The user has been set as inactive. To activate the user account, please go to the Users page.<br>"; 
                                $message = "Successfully added the new user with email address $email. Assigned the user the ID $user_id.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to add the new user. An unexpected error has occurred! Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to add the new user. A user with that email already exists!<br>"; } // email is already taken
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to add the new user. An unexpected error has occurred! Please try again later.<br>"; }

                    // return true after successful employee creation
                    return true;
                }
                else { echo "<span class=\"log-fail\">Failed</span> to add the new employee. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the new employee. An employee with the ID $id already exists!<br>"; }
        }
        else
        {
            $report = "Failed to add the employee. The following field(s) are required: ";
            for ($s = 0; $s < count($reportSegments); $s++)
            {
                if ($s == count($reportSegments) - 1) { $report .= ", and ".$reportSegments[$s].".<br>"; }
                else if ($s == 0) { $report .= $reportSegments[$s]; }
                else { $report .= ", ".$reportSegments[$s]; }
            }
            return false;
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to edit an existing employee */
    function editEmployee($conn, $period, $id = null, $fname = null, $lname = null, $email = null, $phone = null, $birthday = null, $gender = 0, $marital_status = 0, 
                                $line1 = null, $line2 = null, $city = null, $state = null, $zip = null, 
                                $title = null, $department = null, $supervisor = null, $hire_date = null, $end_date = null, $original_hire_date = null, $original_end_date = null,
                                $salary = 0, $contract_days = 0, $contract_type = 0, $contract_start_date = null, $contract_end_date = null, $calendar_type = 0, $num_of_pays = 0, 
                                $health = 0, $dental = 0, $wrs = 0, $position = null, $area = null, $experience = 0, $experience_adjustment = 0, $degree = null, $role = 3, $global = 0, $status = 1)
    {
        // initialize variables
        $errors = 0;
        $report = "";
        $reportSegments = [];
        
        // find errors in required fields
        if ($id == null) { $reportSegments[] = "ID"; $errors++; }
        if ($fname == null) { $reportSegments[] = "first name"; $errors++; }
        if ($lname == null) { $reportSegments[] = "last name"; $errors++; }
        if ($email == null) { $reportSegments[] = "email"; $errors++; }

        // no errors found in required fields; continue
        if ($errors == 0)
        {
            // convert birthday
            if ($birthday != null) { $birthday = date("Y-m-d", strtotime($birthday)); } else { $birthday = date("Y-m-d"); }

            // validate gender
            if (!is_numeric($gender) || ($gender != 0 && $gender != 1 && $gender != 2)) { $gender = 0; }

            // validate marital status
            if (!is_numeric($marital_status) || ($marital_status != 0 && $marital_status != 1 && $marital_status != 2)) { $marital_status = 0; }

            // validate hire dates and end dates
            if (isset($hire_date) && $hire_date != null) { $hire_date = date("Y-m-d", strtotime($hire_date)); } else { $hire_date = null; }
            if (isset($end_date) && $end_date != null) { $end_date = date("Y-m-d", strtotime($end_date)); } else { $end_date = null; }
            if (isset($original_hire_date) && $original_hire_date != null) { $original_hire_date = date("Y-m-d", strtotime($original_hire_date)); } else { $original_hire_date = null; }
            if (isset($original_end_date) && $original_end_date != null) { $original_end_date = date("Y-m-d", strtotime($original_end_date)); } else { $original_end_date = null; }

            // validate active status
            if (!is_numeric($status) || ($status != 0 && $status != 1)) { $status = 0; }

            // validate global employee status
            if (!is_numeric($global) || ($global != 0 && $global != 1)) { $global = 0; }

            // validate employee role; set to "Employee" by default
            if (!is_numeric($role)) { $role = 3; }

            if (checkExistingEmployee($conn, $id))
            {
                // get the current timestamp
                $update_time = date("Y-m-d H:i:s");

                // get employee's current email
                $current_email = getEmployeeEmail($conn, $id);

                // attempt to edit the existing employee in the database
                $editEmployee = mysqli_prepare($conn, "UPDATE employees SET fname=?, lname=?, email=?, phone=?, birthday=?, gender=?, marital_status=?, original_hire_date=?, original_end_date=?, most_recent_hire_date=?, most_recent_end_date=?, role_id=?, global=?, updated=? WHERE id=?");
                mysqli_stmt_bind_param($editEmployee, "sssssiissssiisi", $fname, $lname, $email, $phone, $birthday, $gender, $marital_status, $original_hire_date, $original_end_date, $hire_date, $end_date, $role, $global, $update_time, $id);
                if (mysqli_stmt_execute($editEmployee)) // successfully edited the employee
                {
                    // log status to screen
                    echo "<span class=\"log-success\">Successfully</span> edited $fname $lname.<br>";

                    // edit employee's benefits, compensation, and additional role details
                    if (!setEmployeeCompensation($conn, $period, $id, $supervisor, $salary, $contract_days, $contract_type, $contract_start_date, $contract_end_date, $calendar_type, $num_of_pays, $health, $dental, $wrs, $title, $position, $area, $experience, $experience_adjustment, $degree, $status))
                    { 
                        echo "<span class=\"log-fail\">Failed</span> to set $fname $lname's benefits, compensation, and role details. An unexpected error has occurred. Please try again later!<br>"; 
                    }

                    // edit the employee's primary department
                    if (!setPrimaryDepartment($conn, $id, $department)) { echo "<span class=\"log-fail\">Failed</span> to set $fname $lname's primary department. An unexpected error has occurred. Please try again later!<br>"; }

                    // edit employee's address
                    if (!setEmployeeAddress($conn, $id, $line1, $line2, $city, $state, $zip)) { echo "<span class=\"log-fail\">Failed</span> to set $fname $lname's address. An unexpected error has occurred. Please try again later!<br>"; }
                
                    // log employee creation
                    $message = "Successfully edited $fname $lname (employee ID: $id).";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);

                    // attempt to edit user account for the employee
                    if ($current_email != $email)
                    {
                        // check to see if the email is unique
                        $emailUpper = strtoupper($email);
                        $checkEmail = mysqli_prepare($conn, "SELECT id FROM users WHERE UPPER(email)=? AND status!=2");
                        mysqli_stmt_bind_param($checkEmail, "s", $emailUpper);
                        if (mysqli_stmt_execute($checkEmail))
                        {
                            $checkEmailResult = mysqli_stmt_get_result($checkEmail);
                            if (mysqli_num_rows($checkEmailResult) == 0) // email is unique; continue account creation
                            {
                                // edit the user
                                $editUser = mysqli_prepare($conn, "UPDATE users SET email=?, lname=?, fname=? WHERE email=?");
                                mysqli_stmt_bind_param($editUser, "ssss", $email, $lname, $fname, $current_email);
                                if (mysqli_stmt_execute($editUser))
                                {
                                    // log the user creation
                                    echo "<span class=\"log-success\">Successfully</span> edited the user account for the employee. Updated the account sign-in email to $email.<br>"; 
                                    $message = "Successfully edited the user via editing an employee with old email address of $current_email. Updated user sign-in email to $email.";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the user. An unexpected error has occurred! Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the user. A user with that email address already exists!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the user. An unexpected error has occurred! Please try again later.<br>"; }
                    }

                    // return true after successful employee update
                    return true;
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the employee. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit employee. An employee with the ID $id does not exist!<br>"; }
        }
        else
        {
            $report = "Failed to edit the employee. The following field(s) are required: ";
            for ($s = 0; $s < count($reportSegments); $s++)
            {
                if ($s == count($reportSegments) - 1) { $report .= ", and ".$reportSegments[$s].".<br>"; }
                else if ($s == 0) { $report .= $reportSegments[$s]; }
                else { $report .= ", ".$reportSegments[$s]; }
            }
            return false;
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to check if an employee already exists */
    function checkExistingEmployee($conn, $employee_id)
    {
        $exists = false; // assume an employee with the ID already exists
        $checkEmployee = mysqli_prepare($conn, "SELECT id FROM employees WHERE id=?");
        mysqli_stmt_bind_param($checkEmployee, "i", $employee_id);
        if (mysqli_stmt_execute($checkEmployee))
        {
            $checkEmployeeResult = mysqli_stmt_get_result($checkEmployee);
            if (mysqli_num_rows($checkEmployeeResult) > 0) { $exists = true; } // employee already exists
        }
        return $exists;
    }

    /** function to set an employee's address */
    function setEmployeeAddress($conn, $employee_id, $line1, $line2, $city, $state, $zip)
    {
        // verify the state provided exists
        if ($state_id = checkStateLocation($conn, $state))
        {
            // check to see if an employee with this ID already has an address saved
            $checkAddress = mysqli_prepare($conn, "SELECT id FROM employee_addresses WHERE employee_id=?");
            mysqli_stmt_bind_param($checkAddress, "i", $employee_id);
            if (mysqli_stmt_execute($checkAddress))
            {
                $checkAddressResult = mysqli_stmt_get_result($checkAddress);
                if (mysqli_num_rows($checkAddressResult) == 0) // address does not exist; add new address
                {
                    // add new address
                    $addAddress = mysqli_prepare($conn, "INSERT INTO employee_addresses (employee_id, line1, line2, city, state_id, zip) VALUES (?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($addAddress, "isssis", $employee_id, $line1, $line2, $city, $state_id, $zip);
                    if (mysqli_stmt_execute($addAddress)) 
                    { 
                        // store the newly created address ID
                        $address_id = mysqli_insert_id($conn);

                        // set the employee's address ID
                        $setAddress = mysqli_prepare($conn, "UPDATE employees SET address_id=? WHERE id=?");
                        mysqli_stmt_bind_param($setAddress, "ii", $address_id, $employee_id);
                        if (mysqli_stmt_execute($setAddress)) { return true; }
                    }
                }
                else // address already exists; update existing address
                {
                    // store the currently saved address ID
                    $address_id = mysqli_fetch_array($checkAddressResult)["id"];

                    // update existing address
                    $editAddress = mysqli_prepare($conn, "UPDATE employee_addresses SET line1=?, line2=?, city=?, state_id=?, zip=? WHERE id=?");
                    mysqli_stmt_bind_param($editAddress, "sssisi", $line1, $line2, $city, $state_id, $zip, $address_id);
                    if (mysqli_stmt_execute($editAddress)) { return true; }
                }
            }
        }

        // if we haven't returned yet; return false
        return false;
    }

    /** function to set an employee's benefits, compensation, and role */
    function setEmployeeCompensation($conn, $period, $employee_id, $supervisor_id = null, $salary = 0, $contract_days = 0, $contract_type = 0, $contract_start_date = null, $contract_end_date = null, $calendar_type = 0, $num_of_pays = 0, $health = 0, $dental = 0, $wrs = 0, $title_id = null, $position = null, $area = null, $experience = 0, $experience_adjustment = 0, $degree = null, $active = 0)
    {
        // get the employee's name
        $employee_name = getEmployeeDisplayName($conn, $employee_id);

        // validate salary
        if (!is_numeric($salary) || ($salary < 0)) { $salary = 0; }

        // validate contract days
        if (!is_numeric($contract_days) || ($contract_days < 0)) { $contract_days = 0; }

        // validate contract type
        if (!is_numeric($contract_type) || ($contract_type != 0 && $contract_type != 1 && $contract_type != 2 && $contract_type != 3 && $contract_type != 4)) { $contract_type = 0; }

        // validate experience
        if (!is_numeric($experience) || ($experience < 0)) { $experience = 0; }

        // validate health insurance
        if (!is_numeric($health) || ($health != 0 && $health != 1 && $health != 2)) { $health = 0; }

        // validate dental insurance
        if (!is_numeric($dental) || ($dental != 0 && $dental != 1 && $dental != 2)) { $dental = 0; }

        // validate WRS eligibility
        if (!is_numeric($wrs) || ($wrs != 0 && $wrs != 1)) { $wrs = 0; }

        // validate active status
        if (!is_numeric($active) || ($active != 0 && $active != 1)) { $active = 0; }

        // validate title 
        if (!verifyTitle($conn, $title_id)) { $title_id = null; }

        // validate number of pays
        if (!is_numeric($num_of_pays) || $num_of_pays < 0) { $num_of_pays = 0; }

        // valudate calendar type
        if (!is_numeric($calendar_type) || ($calendar_type != 1 && $calendar_type != 2)) { $calendar_type = 0; }

        // validate contract start and end date
        if (isset($contract_start_date) && $contract_start_date != null) { $contract_start_date = date("Y-m-d", strtotime($contract_start_date)); } else { $contract_start_date = null; }
        if (isset($contract_end_date) && $contract_end_date != null) { $contract_end_date = date("Y-m-d", strtotime($contract_end_date)); } else { $contract_end_date = null; }

        // validate DPI assignments
        if ($assignments = checkDPIAssignments($conn, $position, $area)) // DPI assignment pair exists
        {
            $position = $assignments["position"];
            $area = $assignments["area"];
        }
        else // DPI assignment pair does not exist
        {
            $position = null;
            $area = null; 
            echo "<span class=\"log-fail\">Failed</span> to set the DPI position for $employee_name.<br>";
        }

        // validate degree
        if (!checkDegree($conn, $degree)) 
        { 
            $degree = null; 
            echo "<span class=\"log-fail\">Failed</span> to set the highest degree obtained for $employee_name.<br>";
        }

        // validate supervisor
        if ($supervisor_id != null && !verifyUser($conn, $supervisor_id))
        {
            $supervisor_id = null;
            echo "<span class=\"log-fail\">Failed</span> to set the supervisor for $employee_name.<br>";
        }

        // check to see if employee compensation for the period provided exists
        $checkCompensation = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE employee_id=? AND period_id=?");
        mysqli_stmt_bind_param($checkCompensation, "ii", $employee_id, $period);
        if (mysqli_stmt_execute($checkCompensation))
        {
            $checkCompensationResult = mysqli_stmt_get_result($checkCompensation); 
            if (mysqli_num_rows($checkCompensationResult) == 0) // employee compensation for the period does not exist; insert new entry
            {
                // add new compensation entry
                $addCompensation = mysqli_prepare($conn, "INSERT INTO employee_compensation (employee_id, supervisor_id, yearly_rate, contract_days, contract_type, contract_start_date, contract_end_date, calendar_type, number_of_pays, health_insurance, dental_insurance, wrs_eligible, title_id, assignment_position, sub_assignment, experience, experience_adjustment, highest_degree, period_id, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($addCompensation, "iidiissiiiiiissiisii", $employee_id, $supervisor_id, $salary, $contract_days, $contract_type, $contract_start_date, $contract_end_date, $calendar_type, $num_of_pays, $health, $dental, $wrs, $title_id, $position, $area, $experience, $experience_adjustment, $degree, $period, $active);
                if (mysqli_stmt_execute($addCompensation)) { return true; }
            }
            else // employee compensation for the period does exist; update existing entry
            {
                // store the compensation ID locally
                $compensation_id = mysqli_fetch_array($checkCompensationResult)["id"];

                // attempt to edit the existing compensation entry
                $editCompensation = mysqli_prepare($conn, "UPDATE employee_compensation SET supervisor_id=?, yearly_rate=?, contract_days=?, contract_type=?, contract_start_date=?, contract_end_date=?, calendar_type=?, number_of_pays=?, health_insurance=?, dental_insurance=?, wrs_eligible=?, title_id=?, assignment_position=?, sub_assignment=?, experience=?, experience_adjustment=?, highest_degree=?, active=? WHERE id=?");
                mysqli_stmt_bind_param($editCompensation, "idiissiiiiiissiisii", $supervisor_id, $salary, $contract_days, $contract_type, $contract_start_date, $contract_end_date, $calendar_type, $num_of_pays, $health, $dental, $wrs, $title_id, $position, $area, $experience, $experience_adjustment, $degree, $active, $compensation_id);
                if (mysqli_stmt_execute($editCompensation)) { return true; }
            }
        }

        // if we haven't returned yet; return false
        return false;
    }

    /** function to set an employee's primary department */
    function setPrimaryDepartment($conn, $employee_id, $department)
    {
        // if the department name is provided, continue search; otherwise, remove user from department if assigned primary department
        if ($department <> "")
        {
            // initialize the department ID field
            $department_id = null;

            // check to see if primary department selected exists based on ID
            $checkDepartment = mysqli_prepare($conn, "SELECT id FROM departments WHERE id=?");
            mysqli_stmt_bind_param($checkDepartment, "i", $department);
            if (mysqli_stmt_execute($checkDepartment))
            {
                $checkDepartmentResult = mysqli_stmt_get_result($checkDepartment);
                if (mysqli_num_rows($checkDepartmentResult) > 0) // department exists; update employee's primary department
                {
                    $department_id = mysqli_fetch_array($checkDepartmentResult)["id"];
                }
            }

            // if we did not find department ID yet; check for department based on department name
            if ($department_id == null)
            {
                // check to see if primary department selected exists based on name
                $checkDepartment = mysqli_prepare($conn, "SELECT id FROM departments WHERE name=?");
                mysqli_stmt_bind_param($checkDepartment, "s", $department);
                if (mysqli_stmt_execute($checkDepartment))
                {
                    $checkDepartmentResult = mysqli_stmt_get_result($checkDepartment);
                    if (mysqli_num_rows($checkDepartmentResult) > 0) // department exists; update employee's primary department
                    {
                        $department_id = mysqli_fetch_array($checkDepartmentResult)["id"];
                    }
                }
            }

            // if department ID is found; add employee to primary department
            if ($department_id != null)
            {
                $clearPrimaryDept = mysqli_prepare($conn, "UPDATE department_members SET is_primary=0 WHERE employee_id=?");
                mysqli_stmt_bind_param($clearPrimaryDept, "i", $employee_id);
                if (mysqli_stmt_execute($clearPrimaryDept)) // reset employee's primary department
                {
                    // check to see if the employee is in the department
                    $checkEmpDept = mysqli_prepare($conn, "SELECT id FROM department_members WHERE employee_id=? AND department_id=?");
                    mysqli_stmt_bind_param($checkEmpDept, "ii", $employee_id, $department_id);
                    if (mysqli_stmt_execute($checkEmpDept))
                    {
                        $checkEmpDeptResult = mysqli_stmt_get_result($checkEmpDept);
                        if (mysqli_num_rows($checkEmpDeptResult) > 0) // employee is already in departmet; update
                        {
                            $setPrimaryDept = mysqli_prepare($conn, "UPDATE department_members SET is_primary=1 WHERE employee_id=? AND department_id=?");
                            mysqli_stmt_bind_param($setPrimaryDept, "ii", $employee_id, $department_id);
                            if (mysqli_stmt_execute($setPrimaryDept)) { return true; } // successfully set the primary department; return true
                        }
                        else // employee is not in the department; add employee to the department
                        {
                            $addPrimaryDept = mysqli_prepare($conn, "INSERT INTO department_members (department_id, employee_id, is_primary) VALUES (?, ?, 1)");
                            mysqli_stmt_bind_param($addPrimaryDept, "ii", $department_id, $employee_id);
                            if (mysqli_stmt_execute($addPrimaryDept)) { return true; } // successfully set the primary department; return true
                        }
                    }
                }
            }

            // return false if we did not return elsewhere
            return false;
        }
        else 
        { 
            $clearPrimaryDept = mysqli_prepare($conn, "UPDATE department_members SET is_primary=0 WHERE employee_id=?");
            mysqli_stmt_bind_param($clearPrimaryDept, "i", $employee_id);
            if (mysqli_stmt_execute($clearPrimaryDept)) { return true; } // successfully removed primary department for employee
            else { return false; } // failed to remove primary department for employee
        }
    }

    /** 
     * function to check if a state exists given a state name, abbreviation, or ID 
     * --> returns the state ID if it exists; otherwise, returns false
    */
    function checkStateLocation($conn, $state = null)
    {
        $checkState = mysqli_prepare($conn, "SELECT id FROM states WHERE id=? OR state=? OR abbreviation=?");
        mysqli_stmt_bind_param($checkState, "iss", $state, $state, $state);
        if (mysqli_stmt_execute($checkState))
        {
            $checkStateResult = mysqli_stmt_get_result($checkState);
            if (mysqli_num_rows($checkStateResult) > 0) // state found; get state ID
            {
                $state_id = mysqli_fetch_array($checkStateResult)["id"];
                return $state_id;
            }
        }
        return false;
    }

    /** function to check if the employee's position and area are valid */
    function checkDPIAssignments($conn, $position = null, $area = null)
    {
        if ((isset($position) && $position != null) && (isset($area) && $area != null))
        {
            // extract the position and area codes from their whole strings
            $position_code = substr($position, 0, 2);
            $area_code = substr($area, 0, 4);

            // validate position and area
            $checkPosition = mysqli_prepare($conn, "SELECT id, position_name, area_name FROM dpi_positions WHERE position_code=? AND area_code=?");
            mysqli_stmt_bind_param($checkPosition, "ss", $position_code, $area_code);
            if (mysqli_stmt_execute($checkPosition))
            {
                $checkPositionResult = mysqli_stmt_get_result($checkPosition);
                if (mysqli_num_rows($checkPositionResult) > 0) // position exists; now validate area
                {
                    // store the position details locally
                    $position_details = mysqli_fetch_array($checkPositionResult);
                    $position_name = $position_details["position_name"];
                    $area_name = $position_details["area_name"];

                    $position_str = $position_code . " - " . $position_name;
                    $area_str = $area_code . " - " . $area_name;

                    // create a temporary array that will be returned to store the position and area strings
                    $temp = [];
                    $temp["position"] = $position_str;
                    $temp["area"] = $area_str;
                    
                    // return the area storing position and area strings
                    return $temp;
                }
            }
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to check the validity of the degree */
    function checkDegree($conn, $degree)
    {
        // initialize an array to store valid degrees
        $valid_degrees = [];

        // check the validity of the provided degree
        $valid = false; // assume degree is invalid
        $checkDegree = mysqli_query($conn, "SELECT code, label FROM degrees");
        if (mysqli_num_rows($checkDegree) > 0) // degrees found; continue validation process
        {
            while ($degree_result = mysqli_fetch_array($checkDegree))
            {
                $degree_str = $degree_result["code"]." - ".$degree_result["label"];
                $valid_degrees[] = $degree_str;
            }
        }
        if (in_array($degree, $valid_degrees)) { $valid = true; }

        // return the validity of the degree
        return $valid;
    }

    /** function to get the ID of a period based on the period's name */
    function getPeriodID($conn, $period_name)
    {
        // get the period ID based on the period label
        $getPeriodID = mysqli_prepare($conn, "SELECT id FROM periods WHERE name=?");
        mysqli_stmt_bind_param($getPeriodID, "s", $period_name);
        if (mysqli_stmt_execute($getPeriodID))
        {
            $getPeriodIDResult = mysqli_stmt_get_result($getPeriodID);
            if (mysqli_num_rows($getPeriodIDResult) > 0) // period exists
            {
                // store period ID locally
                $period_id = mysqli_fetch_array($getPeriodIDResult)["id"];
                return $period_id;
            }
        }
        return false; // return false if we don't get the period ID
    }

    /** function to verify a project exists */
    function verifyProject($conn, $project_code)
    {
        // verify the project exists
        $verifyProject = mysqli_prepare($conn, "SELECT code FROM projects WHERE code=?");
        mysqli_stmt_bind_param($verifyProject, "s", $project_code);
        if (mysqli_stmt_execute($verifyProject))
        {
            $verifyProjectResult = mysqli_stmt_get_result($verifyProject);
            if (mysqli_num_rows($verifyProjectResult) > 0) // project exists; return true
            {
                return true;
            }
        }
        return false; // return false if we don't find the project
    }

    /** function to verify a period exists */
    function verifyPeriod($conn, $period_id)
    {
        // verify period exists
        $verifyPeriod = mysqli_prepare($conn, "SELECT id FROM periods WHERE id=?");
        mysqli_stmt_bind_param($verifyPeriod, "i", $period_id);
        if (mysqli_stmt_execute($verifyPeriod))
        {
            $verifyPeriodResult = mysqli_stmt_get_result($verifyPeriod);
            if (mysqli_num_rows($verifyPeriodResult) > 0) // period exists; return true
            {
                return true;
            }
        }
        return false; // return false if we don't find the period
    }
    
    /** function to verify a revenue exists */
    function verifyRevenue($conn, $revenue_id)
    {
        // verify the revenue exists
        $verifyRevenue = mysqli_prepare($conn, "SELECT id FROM revenues WHERE id=?");
        mysqli_stmt_bind_param($verifyRevenue, "i", $revenue_id);
        if (mysqli_stmt_execute($verifyRevenue))
        {
            $verifyRevenueResult = mysqli_stmt_get_result($verifyRevenue);
            if (mysqli_num_rows($verifyRevenueResult) > 0) // revenue exists; continue
            {
                return true;
            }
        }
        return false; // return false if we don't find the revenue
    }

    /** function to get period details */
    function getPeriodDetails($conn, $period_id)
    {
        if (verifyPeriod($conn, $period_id))
        {
            $getDetails = mysqli_prepare($conn, "SELECT * FROM periods WHERE id=?");
            mysqli_stmt_bind_param($getDetails, "i", $period_id);
            if (mysqli_stmt_execute($getDetails))
            {
                $results = mysqli_stmt_get_result($getDetails);
                if (mysqli_num_rows($results) > 0) // details found; return array storing details
                {
                    $details = mysqli_fetch_array($results);
                    return $details;
                }
            }
        }
        return false; // return false if no period details found
    }

    /** function to get the string to display for the user who last updated a field */
    function getUpdateUser($conn, $user_id = null)
    {
        $user_str = "Unknown";
        if (isset($user_id))
        {
            if ($user_id == 0) { $user_str = "SUPER ADMIN"; }
            else
            {
                // get the user's first and last name if the ID is valid
                $getUser = mysqli_prepare($conn, "SELECT fname, lname FROM employees WHERE id=?");
                mysqli_stmt_bind_param($getUser, "i", $user_id);
                if (mysqli_stmt_execute($getUser))
                {
                    $getUserResult = mysqli_stmt_get_result($getUser);
                    if (mysqli_num_rows($getUserResult) > 0) // user exists
                    {
                        // store user details locally 
                        $user_details = mysqli_fetch_array($getUserResult);
                        $fname = $user_details["fname"];
                        $lname = $user_details["lname"];

                        // build the string
                        $user_str = $fname." ".$lname;
                    }
                }
            }
        }
        return $user_str;
    }

    /** function to get an employee's name to be dislayed */
    function getEmployeeDisplayName($conn, $id = null)
    {  
        $name = "";
        if ($id == 0) { $name = "SUPER ADMIN"; }
        else
        {
            $getName = mysqli_prepare($conn, "SELECT fname, lname FROM employees WHERE id=?");
            mysqli_stmt_bind_param($getName, "i", $id);
            if (mysqli_stmt_execute($getName))
            {
                $getNameResult = mysqli_stmt_get_result($getName);
                if (mysqli_num_rows($getNameResult) > 0) // employee exists
                {
                    // store employee details locally
                    $details = mysqli_fetch_array($getNameResult);
                    $fname = $details["fname"];
                    $lname = $details["lname"];
                    $name = $fname." ".$lname;
                }
            }
        }
        return $name;
    }

    /** function to get the FTE_days value from the database */
    function getFTEDays($conn)
    {
        $FTE_days = 190; // default to 190
        $getFTE = mysqli_query($conn, "SELECT FTE_days FROM settings WHERE id=1");
        if (mysqli_num_rows($getFTE) > 0) // setting found
        {
            $FTE_days  = mysqli_fetch_array($getFTE)["FTE_days"];
        }
        return $FTE_days;
    }

    /** function to get an employee's budgeted salary within a project */
    function getEmployeeProjectSalary($conn, $period_id, $code, $employee_id)
    {
        // initialize the variable to store the employee's project salary
        $project_salary = 0; // assume the salary is 0

        if (verifyPeriod($conn, $period_id)) // period exists; continue
        {
            if (verifyProject($conn, $code)) // project exists; continue
            {
                if (checkExistingEmployee($conn, $employee_id)) // employee exists; continue
                {
                    // get the employees salary from the employee_compensation table
                    $getSalary = mysqli_prepare($conn, "SELECT yearly_rate, contract_days FROM employee_compensation WHERE employee_id=? AND period_id=?");
                    mysqli_stmt_bind_param($getSalary, "ii", $employee_id, $period_id);
                    if (mysqli_stmt_execute($getSalary))
                    {
                        $getSalaryResult = mysqli_stmt_get_result($getSalary);
                        if (mysqli_num_rows($getSalaryResult) > 0) // salary found; continue
                        {
                            // store the employee's yearly salary and total contract days locally
                            $employee_details = mysqli_fetch_array($getSalaryResult);
                            $yearly_salary = $employee_details["yearly_rate"];
                            $contract_days = $employee_details["contract_days"];

                            // convert the employee's yearly salary into daily salary
                            $daily_salary = 0; // assume the employee's daily salary is 0
                            if ($contract_days > 0) { $daily_salary = $yearly_salary / $contract_days; }

                            // get the employee's days within the selected project
                            $getProjectDays = mysqli_prepare($conn, "SELECT SUM(project_days) AS days_in_project FROM project_employees WHERE project_code=? AND employee_id=? AND period_id=?");
                            mysqli_stmt_bind_param($getProjectDays, "sii", $code, $employee_id, $period_id);
                            if (mysqli_stmt_execute($getProjectDays))
                            {
                                $getProjectDaysResult = mysqli_stmt_get_result($getProjectDays);
                                if (mysqli_num_rows($getProjectDaysResult) > 0) // project days found
                                {
                                    // store the employees days within the project locally
                                    $project_days = mysqli_fetch_array($getProjectDaysResult)["days_in_project"];

                                    // calculate the employee's salary within the project
                                    $project_salary = $daily_salary * $project_days;
                                }
                            }
                        }
                    }
                }
            }
        }

        // return the employee's project salary
        return $project_salary;
    }

    /** function to get an employee's budgeted benefits within a project */
    function getEmployeeProjectBenefits($conn, $period_id, $code, $employee_id, $FTE_days)
    {
        // initialize the variable to store the employee's project benefits
        $project_benefits = 0; // assume the salary is 0

        if (verifyPeriod($conn, $period_id)) // period exists; continue
        {
            if (verifyProject($conn, $code)) // project exists; continue
            {
                if (checkExistingEmployee($conn, $employee_id)) // employee exists; continue
                {
                    // get the employees salary from the employee_compensation table
                    $getSalary = mysqli_prepare($conn, "SELECT yearly_rate, contract_days, health_insurance, dental_insurance, wrs_eligible FROM employee_compensation WHERE employee_id=? AND period_id=?");
                    mysqli_stmt_bind_param($getSalary, "ii", $employee_id, $period_id);
                    if (mysqli_stmt_execute($getSalary))
                    {
                        $getSalaryResult = mysqli_stmt_get_result($getSalary);
                        if (mysqli_num_rows($getSalaryResult) > 0) // salary found; continue
                        {
                            // store the employee's benefits and compensation locally
                            $employee_details = mysqli_fetch_array($getSalaryResult);
                            $yearly_salary = $employee_details["yearly_rate"];
                            $contract_days = $employee_details["contract_days"];
                            $health = $employee_details["health_insurance"];
                            $dental = $employee_details["dental_insurance"];
                            $wrs = $employee_details["wrs_eligible"];

                            // convert the employee's yearly salary into daily salary
                            $daily_salary = 0; // assume the employee's daily salary is 0
                            if ($contract_days > 0) { $daily_salary = $yearly_salary / $contract_days; }

                            // get the employee's days within the selected project
                            $getProjectDays = mysqli_prepare($conn, "SELECT SUM(project_days) AS days_in_project FROM project_employees WHERE project_code=? AND employee_id=? AND period_id=?");
                            mysqli_stmt_bind_param($getProjectDays, "sii", $code, $employee_id, $period_id);
                            if (mysqli_stmt_execute($getProjectDays))
                            {
                                $getProjectDaysResult = mysqli_stmt_get_result($getProjectDays);
                                if (mysqli_num_rows($getProjectDaysResult) > 0) // project days found
                                {
                                    // store the employees days within the project locally
                                    $project_days = mysqli_fetch_array($getProjectDaysResult)["days_in_project"];

                                    // calculate the employee's salary within the project
                                    $project_salary = $daily_salary * $project_days;
                                    
                                    // calculate the percentage of benefits based on days
                                    if ($contract_days >= $FTE_days) { $FTE_Benefits_Percentage = 1; }
                                    else { $FTE_Benefits_Percentage = ($contract_days / $FTE_days); }

                                    // if percentage is < 50%; set to 0
                                    if ($FTE_Benefits_Percentage < 0.5) { $FTE_Benefits_Percentage = 0; }

                                    if ($contract_days > 0) // if the employee is contracted to work (prevents divide by 0 error)
                                    {
                                        // get the global expenses rates
                                        $getRates = mysqli_prepare($conn, "SELECT * FROM global_expenses WHERE period_id=?");
                                        mysqli_stmt_bind_param($getRates, "i", $period_id);
                                        if (mysqli_stmt_execute($getRates))
                                        {
                                            $getRatesResult = mysqli_stmt_get_result($getRates);
                                            if (mysqli_num_rows($getRatesResult) > 0) // rates for selected period exist
                                            {
                                                // store the rates array
                                                $rates = mysqli_fetch_array($getRatesResult);

                                                $FICA_Cost = $project_salary * $rates["FICA"];

                                                if ($wrs == 1) { $WRS_Cost = $project_salary * $rates["wrs_rate"]; }
                                                else { $WRS_Cost = 0; }
                                                
                                                if ($health == 1) { $Health_Cost = ($rates["health_family"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                                else if ($health == 2) { $Health_Cost = ($rates["health_single"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                                else { $Health_Cost = 0; }

                                                if ($dental == 1) { $Dental_Cost = ($rates["dental_family"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                                else if ($dental == 2) { $Dental_Cost = ($rates["dental_single"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                                else { $Dental_Cost = 0; }

                                                $LTD_Cost = ($project_salary / 100) * ($rates["LTD"] * $FTE_Benefits_Percentage * ($project_days / $contract_days));

                                                $Life_Cost = (($project_salary / 1000) * ($rates["life"] * 12 * ($project_days / $contract_days)) * 0.2);

                                                $project_benefits = $FICA_Cost + $WRS_Cost + $Health_Cost + $Dental_Cost + $LTD_Cost + $Life_Cost;
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

        // return the employee's project salary
        return $project_benefits;
    }

    /** function to get the combined employee salary within a project */
    function getProjectSalary($conn, $period_id, $code)
    {
        // initialize the variable to store the total project salary
        $project_salary = 0; // assume the total project salary is 0

        if (verifyPeriod($conn, $period_id)) // period exists; continue
        {
            if (verifyProject($conn, $code)) // project exists; continue
            {
                // get the employees within the project
                $project_employees = getProjectEmployees($conn, $code, $period_id);

                // for each employee within the project; get their salary within the project
                for ($e = 0; $e < count($project_employees); $e++)
                {
                    // add the employee's project salary to the total
                    $project_salary += getEmployeeProjectSalary($conn, $period_id, $code, $project_employees[$e]);
                }
            }
        } 

        // return the total project salary
        return $project_salary;
    }

    /** function to get the combined employee benefits within a project */
    function getProjectBenefits($conn, $period_id, $code)
    {
        // initialize the variable to store the total project benefits
        $project_beneftis = 0; // assume the total project benefits is 0
            
        // get the FTE days value
        $FTE_days = getFTEDays($conn);

        if (verifyPeriod($conn, $period_id)) // period exists; continue
        {
            if (verifyProject($conn, $code)) // project exists; continue
            {
                // get the employees within the project
                $project_employees = getProjectEmployees($conn, $code, $period_id);

                // for each employee within the project; get their salary within the project
                for ($e = 0; $e < count($project_employees); $e++)
                {
                    // add the employee's project salary to the total
                    $project_beneftis += getEmployeeProjectBenefits($conn, $period_id, $code, $project_employees[$e], $FTE_days);
                }
            }
        } 

        // return the total project salary
        return $project_beneftis;
    }

    /** function to get the total overhead costs within a project */
    function getProjectOverhead($conn, $period_id, $code, $fund)
    {
        // initialize the variable to store the project's overhead costs
        $overhead_costs = 0;

        if (verifyPeriod($conn, $period_id)) // period exists; continue
        {
            if (verifyProject($conn, $code)) // project exists; continue
            {
                // if the project's fund is the overhead fund, assume all employees expenses are overhead costs
                $overhead_employees = 0; // initialize overhead employees to 0
                $checkProjectFund = mysqli_prepare($conn, "SELECT fund_code FROM projects WHERE code=?");
                mysqli_stmt_bind_param($checkProjectFund, "s", $code);
                if (mysqli_stmt_execute($checkProjectFund))
                {
                    $checkProjectFundResult = mysqli_stmt_get_result($checkProjectFund);
                    if (mysqli_num_rows($checkProjectFundResult) > 0)
                    {
                        $project_fund = mysqli_fetch_array($checkProjectFundResult)["fund_code"];
                        if ($project_fund == $fund) // only calculate employee overhead if project fund code is the overhead fund
                        {
                            $overhead_employees += getProjectSalary($conn, $period_id, $code);
                            $overhead_employees += getProjectBenefits($conn, $period_id, $code);
                        }
                    }
                }

                // sum only expenses that have the overhead fund code
                $overhead_expenses = 0; // initialize overhead expenses to 0
                $getOverheadExpenses = mysqli_prepare($conn, "SELECT SUM(cost) AS overhead_costs FROM project_expenses WHERE project_code=? AND fund_code=? AND period_id=?");
                mysqli_stmt_bind_param($getOverheadExpenses, "ssi", $code, $fund, $period_id);
                if (mysqli_stmt_execute($getOverheadExpenses))
                {
                    $getOverheadExpensesResult = mysqli_stmt_get_result($getOverheadExpenses);
                    if (mysqli_num_rows($getOverheadExpensesResult) > 0) // overhead expenses found
                    {
                        $overhead_expenses = mysqli_fetch_array($getOverheadExpensesResult)["overhead_costs"];
                    }
                }

                // sum overhead employee expenses and other overhead project expenses
                $overhead_costs = $overhead_employees + $overhead_expenses;
            }
        }

        // return the project's overhead cost
        return $overhead_costs;
    }

    /** function to get the total nonoverhead costs within a project */
    function getProjectNonoverhead($conn, $period_id, $code, $fund)
    {
        // initialize the variable to store the project's nonoverhead costs
        $nonoverhead_costs = 0;

        if (verifyPeriod($conn, $period_id)) // period exists; continue
        {
            if (verifyProject($conn, $code)) // project exists; continue
            {
                // if the project's fund is the nonoverhead fund, assume all employees expenses are nonoverhead costs
                $nonoverhead_employees = 0; // initialize nonoverhead employees to 0
                $checkProjectFund = mysqli_prepare($conn, "SELECT fund_code FROM projects WHERE code=?");
                mysqli_stmt_bind_param($checkProjectFund, "s", $code);
                if (mysqli_stmt_execute($checkProjectFund))
                {
                    $checkProjectFundResult = mysqli_stmt_get_result($checkProjectFund);
                    if (mysqli_num_rows($checkProjectFundResult) > 0)
                    {
                        $project_fund = mysqli_fetch_array($checkProjectFundResult)["fund_code"];
                        if ($project_fund != $fund) // only calculate employee nonoverhead if project fund code is not the overhead fund
                        {
                            $nonoverhead_employees += getProjectSalary($conn, $period_id, $code);
                            $nonoverhead_employees += getProjectBenefits($conn, $period_id, $code);
                        }
                    }
                }

                // sum only expenses that do not have the overhead fund code
                $nonoverhead_expenses = 0; // initialize nonoverhead expenses to 0
                $getNonoverheadExpenses = mysqli_prepare($conn, "SELECT SUM(cost) AS nonoverhead_costs FROM project_expenses WHERE project_code=? AND (fund_code<>? OR fund_code IS NULL) AND period_id=?");
                mysqli_stmt_bind_param($getNonoverheadExpenses, "ssi", $code, $fund, $period_id);
                if (mysqli_stmt_execute($getNonoverheadExpenses))
                {
                    $getNonoverheadExpensesResult = mysqli_stmt_get_result($getNonoverheadExpenses);
                    if (mysqli_num_rows($getNonoverheadExpensesResult) > 0) // nonoverhead expenses found
                    {
                        $nonoverhead_expenses = mysqli_fetch_array($getNonoverheadExpensesResult)["nonoverhead_costs"];
                    }
                }

                // sum overhead employee expenses and other overhead project expenses
                $nonoverhead_costs = $nonoverhead_employees + $nonoverhead_expenses;
            }
        }

        // return the project's overhead cost
        return $nonoverhead_costs;
    }

    /** function to check if a user has new or unread messages */
    function checkNewMessages($conn, $user_id)
    {
        $newMessages = 0; // assume user has no new messages
        $checkNewMessages = mysqli_prepare($conn, "SELECT COUNT(id) AS newMessages FROM messages WHERE recipient_id=? AND read_by_recipient=0");
        mysqli_stmt_bind_param($checkNewMessages, "i", $user_id);
        if (mysqli_stmt_execute($checkNewMessages))
        {
            $checkNewMessagesResult = mysqli_stmt_get_result($checkNewMessages);
            $newMessages = mysqli_fetch_array($checkNewMessagesResult)["newMessages"];
        }
        return $newMessages;
    }

    /** function to print a date */
    function printDate($date)
    {
        // initialize the variable to store the return date string
        $return_date_str = "";

        // get the current year
        $current_year = date("Y");

        // get the year of the date recieved
        $date_year = date("Y", strtotime($date));

        if ($current_year == $date_year)
        {
            // get the current day of year
            $current_day = date("z");

            // get the date's day of year
            $date_day = date("z", strtotime($date));

            if ($current_day == $date_day)
            {
                $return_date_str = date("M j g:i A", strtotime($date));
            }
            else
            {
                $return_date_str = date("M j", strtotime($date));
            }
        }
        else
        {
            $return_date_str = date("m/j/Y", strtotime($date));
        }

        // return the date in string format
        return $return_date_str;
    }

    /** function to get an employee's total expected health costs for a given period */
    function getEmployeeHealthCosts($conn, $employee_id, $period_id)
    {
        // initialize the variable to store an employee's total health costs
        $total_health = 0; // assume 0

        // get the benefit's rate(s) for the provided period
        $getRates = mysqli_prepare($conn, "SELECT health_single AS single, health_family AS family FROM global_expenses WHERE period_id=?");
        mysqli_stmt_bind_param($getRates, "i", $period_id);
        if (mysqli_stmt_execute($getRates))
        {
            $getRatesResult = mysqli_stmt_get_result($getRates);
            if (mysqli_num_rows($getRatesResult) > 0) // rates exist; continue
            {
                // store the rates locally
                $rates = mysqli_fetch_array($getRatesResult);
                $single = $rates["single"];
                $family = $rates["family"];

                // get the employee's benefit type for the provided period
                $getBenefit = mysqli_prepare($conn, "SELECT contract_days, health_insurance AS benefit FROM employee_compensation WHERE employee_id=? AND period_id=?");
                mysqli_stmt_bind_param($getBenefit, "ii", $employee_id, $period_id);
                if (mysqli_stmt_execute($getBenefit))
                {
                    $getBenefitResult = mysqli_stmt_get_result($getBenefit);
                    if (mysqli_num_rows($getBenefitResult) > 0) // benefit found; continue
                    {
                        // store the employee's benefit type locally
                        $compensation = mysqli_fetch_array($getBenefitResult);
                        $contract_days = $compensation["contract_days"];
                        $benefit = $compensation["benefit"];
                        
                        // get the FTE days value
                        $FTE_Days = getFTEDays($conn);

                        // calculate the percentage of benefits based on days
                        if ($contract_days >= $FTE_Days) { $FTE_Benefits_Percentage = 1; }
                        else { $FTE_Benefits_Percentage = ($contract_days / $FTE_Days); }

                        // if percentage is <= 50%; set to 0
                        if ($FTE_Benefits_Percentage < 0.5) { $FTE_Benefits_Percentage = 0; }

                        // calculate the employee's benefit cost depending on benefit type
                        if ($benefit == 1) { $total_health = $family * $FTE_Benefits_Percentage; } // family plan
                        else if ($benefit == 2) { $total_health = $single * $FTE_Benefits_Percentage; } // single plan
                    }
                }
            }
        }
        
        // return the employee's total health costs
        return $total_health;
    }

    /** function to get an employee's total expected dental costs for a given period */
    function getEmployeeDentalCosts($conn, $employee_id, $period_id)
    {
        // initialize the variable to store an employee's total dental costs
        $total_dental = 0; // assume 0

        // get the benefit's rate(s) for the provided period
        $getRates = mysqli_prepare($conn, "SELECT dental_single AS single, dental_family AS family FROM global_expenses WHERE period_id=?");
        mysqli_stmt_bind_param($getRates, "i", $period_id);
        if (mysqli_stmt_execute($getRates))
        {
            $getRatesResult = mysqli_stmt_get_result($getRates);
            if (mysqli_num_rows($getRatesResult) > 0) // rates exist; continue
            {
                // store the rates locally
                $rates = mysqli_fetch_array($getRatesResult);
                $single = $rates["single"];
                $family = $rates["family"];

                // get the employee's benefit type for the provided period
                $getBenefit = mysqli_prepare($conn, "SELECT contract_days, dental_insurance AS benefit FROM employee_compensation WHERE employee_id=? AND period_id=?");
                mysqli_stmt_bind_param($getBenefit, "ii", $employee_id, $period_id);
                if (mysqli_stmt_execute($getBenefit))
                {
                    $getBenefitResult = mysqli_stmt_get_result($getBenefit);
                    if (mysqli_num_rows($getBenefitResult) > 0) // benefit found; continue
                    {
                        // store the employee's benefit type locally
                        $compensation = mysqli_fetch_array($getBenefitResult);
                        $contract_days = $compensation["contract_days"];
                        $benefit = $compensation["benefit"];
                        
                        // get the FTE days value
                        $FTE_Days = getFTEDays($conn);

                        // calculate the percentage of benefits based on days
                        if ($contract_days >= $FTE_Days) { $FTE_Benefits_Percentage = 1; }
                        else { $FTE_Benefits_Percentage = ($contract_days / $FTE_Days); }

                        // if percentage is <= 50%; set to 0
                        if ($FTE_Benefits_Percentage < 0.5) { $FTE_Benefits_Percentage = 0; }

                        // calculate the employee's benefit cost depending on benefit type
                        if ($benefit == 1) { $total_dental = $family * $FTE_Benefits_Percentage; } // family plan
                        else if ($benefit == 2) { $total_dental = $single * $FTE_Benefits_Percentage; } // single plan
                    }
                }
            }
        }
        
        // return the employee's total dental costs
        return $total_dental;
    }

    /** function to get an employee's total expected WRS costs for a given period */
    function getEmployeeWRSCosts($conn, $employee_id, $period_id, $salary = null)
    {
        // initialize the variable to store an employee's total WRS costs
        $total_wrs = 0; // assume 0

        // get the benefit's rate(s) for the provided period
        $getRates = mysqli_prepare($conn, "SELECT wrs_rate FROM global_expenses WHERE period_id=?");
        mysqli_stmt_bind_param($getRates, "i", $period_id);
        if (mysqli_stmt_execute($getRates))
        {
            $getRatesResult = mysqli_stmt_get_result($getRates);
            if (mysqli_num_rows($getRatesResult) > 0) // rates exist; continue
            {
                // store the rates locally
                $rates = mysqli_fetch_array($getRatesResult);
                $wrs = $rates["wrs_rate"];

                // get the employee's benefit type for the provided period
                $getBenefit = mysqli_prepare($conn, "SELECT yearly_rate, wrs_eligible AS benefit FROM employee_compensation WHERE employee_id=? AND period_id=?");
                mysqli_stmt_bind_param($getBenefit, "ii", $employee_id, $period_id);
                if (mysqli_stmt_execute($getBenefit))
                {
                    $getBenefitResult = mysqli_stmt_get_result($getBenefit);
                    if (mysqli_num_rows($getBenefitResult) > 0) // benefit found; continue
                    {
                        // store the employee's benefit type locally
                        $compensation = mysqli_fetch_array($getBenefitResult);
                        if ($salary == null) { $salary = $compensation["yearly_rate"]; }
                        $benefit = $compensation["benefit"];

                        // calculate the employee's benefit cost depending on benefit type
                        if ($benefit == 1) { $total_wrs = $salary * $wrs; } // WRS eligible
                    }
                }
            }
        }
        
        // return the employee's total WRS costs
        return $total_wrs;
    }

    /** function to get an employee's total expected FICA costs for a given period */
    function getEmployeeFICACosts($conn, $employee_id, $period_id, $salary = null)
    {
        // initialize the variable to store an employee's total FICA costs
        $total_fica = 0; // assume 0

        // get the benefit's rate(s) for the provided period
        $getRates = mysqli_prepare($conn, "SELECT FICA FROM global_expenses WHERE period_id=?");
        mysqli_stmt_bind_param($getRates, "i", $period_id);
        if (mysqli_stmt_execute($getRates))
        {
            $getRatesResult = mysqli_stmt_get_result($getRates);
            if (mysqli_num_rows($getRatesResult) > 0) // rates exist; continue
            {
                // store the rates locally
                $rates = mysqli_fetch_array($getRatesResult);
                $FICA = $rates["FICA"];

                // get the employee's benefit type for the provided period
                $getBenefit = mysqli_prepare($conn, "SELECT yearly_rate FROM employee_compensation WHERE employee_id=? AND period_id=?");
                mysqli_stmt_bind_param($getBenefit, "ii", $employee_id, $period_id);
                if (mysqli_stmt_execute($getBenefit))
                {
                    $getBenefitResult = mysqli_stmt_get_result($getBenefit);
                    if (mysqli_num_rows($getBenefitResult) > 0) // benefit found; continue
                    {
                        // store the employee's benefit type locally
                        $compensation = mysqli_fetch_array($getBenefitResult);
                        if ($salary == null) { $salary = $compensation["yearly_rate"]; }

                        // calculate the employee's benefit cost depending on benefit type
                        $total_fica = $salary * $FICA;
                    }
                }
            }
        }

        // return the employee's total FICA costs
        return $total_fica;
    }

    /** function to get an employee's total expected LTD costs for a given period */
    function getEmployeeLTDCosts($conn, $employee_id, $period_id, $salary = null)
    {
        // initialize the variable to store an employee's total LTD costs
        $total_ltd = 0; // assume 0

        // get the benefit's rate(s) for the provided period
        $getRates = mysqli_prepare($conn, "SELECT LTD FROM global_expenses WHERE period_id=?");
        mysqli_stmt_bind_param($getRates, "i", $period_id);
        if (mysqli_stmt_execute($getRates))
        {
            $getRatesResult = mysqli_stmt_get_result($getRates);
            if (mysqli_num_rows($getRatesResult) > 0) // rates exist; continue
            {
                // store the rates locally
                $rates = mysqli_fetch_array($getRatesResult);
                $LTD = $rates["LTD"];

                // get the employee's benefit type for the provided period
                $getBenefit = mysqli_prepare($conn, "SELECT yearly_rate, contract_days FROM employee_compensation WHERE employee_id=? AND period_id=?");
                mysqli_stmt_bind_param($getBenefit, "ii", $employee_id, $period_id);
                if (mysqli_stmt_execute($getBenefit))
                {
                    $getBenefitResult = mysqli_stmt_get_result($getBenefit);
                    if (mysqli_num_rows($getBenefitResult) > 0) // benefit found; continue
                    {
                        // store the employee's benefit type locally
                        $compensation = mysqli_fetch_array($getBenefitResult);
                        if ($salary == null) { $salary = $compensation["yearly_rate"]; }
                        $contract_days = $compensation["contract_days"];
                        
                        // get the FTE days value
                        $FTE_Days = getFTEDays($conn);

                        // calculate the percentage of benefits based on days
                        if ($contract_days >= $FTE_Days) { $FTE_Benefits_Percentage = 1; }
                        else { $FTE_Benefits_Percentage = ($contract_days / $FTE_Days); }

                        // if percentage is <= 50%; set to 0
                        if ($FTE_Benefits_Percentage < 0.5) { $FTE_Benefits_Percentage = 0; }

                        // calculate the employee's benefit cost depending on benefit type
                        $total_ltd = ($salary / 100) * ($LTD * $FTE_Benefits_Percentage);
                    }
                }
            }
        }
        
        // return the employee's total LTD costs
        return $total_ltd;
    }

    /** function to get an employee's total expected life insurance costs for a given period */
    function getEmployeeLifeCosts($conn, $employee_id, $period_id, $salary = null)
    {
        // initialize the variable to store an employee's total life costs
        $total_life = 0; // assume 0

        // get the benefit's rate(s) for the provided period
        $getRates = mysqli_prepare($conn, "SELECT life FROM global_expenses WHERE period_id=?");
        mysqli_stmt_bind_param($getRates, "i", $period_id);
        if (mysqli_stmt_execute($getRates))
        {
            $getRatesResult = mysqli_stmt_get_result($getRates);
            if (mysqli_num_rows($getRatesResult) > 0) // rates exist; continue
            {
                // store the rates locally
                $rates = mysqli_fetch_array($getRatesResult);
                $life = $rates["life"];

                // get the employee's benefit type for the provided period
                $getBenefit = mysqli_prepare($conn, "SELECT yearly_rate FROM employee_compensation WHERE employee_id=? AND period_id=?");
                mysqli_stmt_bind_param($getBenefit, "ii", $employee_id, $period_id);
                if (mysqli_stmt_execute($getBenefit))
                {
                    $getBenefitResult = mysqli_stmt_get_result($getBenefit);
                    if (mysqli_num_rows($getBenefitResult) > 0) // benefit found; continue
                    {
                        // store the employee's benefit type locally
                        $compensation = mysqli_fetch_array($getBenefitResult);
                        if ($salary == null) { $salary = $compensation["yearly_rate"]; }

                        // calculate the employee's benefit cost depending on benefit type
                        $total_life = (($salary / 1000) * ($life * 12) * 0.2);
                    }
                }
            }
        }
        
        // return the employee's total life costs
        return $total_life;
    }

    /** function to get an employee's total fringe for a given period */
    function getEmployeeTotalFringe($conn, $employee_id, $period_id)
    {
        // initialize the variable to store an employee's total fringe
        $total_fringe = 0; // assume 0

        // add each fringe benefit to the total fringe
        $total_fringe += getEmployeeHealthCosts($conn, $employee_id, $period_id);
        $total_fringe += getEmployeeDentalCosts($conn, $employee_id, $period_id);
        $total_fringe += getEmployeeWRSCosts($conn, $employee_id, $period_id);
        $total_fringe += getEmployeeFICACosts($conn, $employee_id, $period_id);
        $total_fringe += getEmployeeLTDCosts($conn, $employee_id, $period_id);
        $total_fringe += getEmployeeLifeCosts($conn, $employee_id, $period_id);

        // return the employee's total fringe
        return $total_fringe;
    }

    /** function to print a number in dollar format */
    function printDollar($number, $forceNegative = false)
    {
        if (is_numeric($number)) 
        { 
            if ($number < 0 || $forceNegative === true)
            {
                return "($".number_format(abs($number), 2).")";
            }
            else
            {
                return "$".number_format($number, 2);
            }
        }
        else { return "$0.00"; }
    }

    /** 
     * function that aids in printing numbers with the correct suffix
     * SOURCE: https://stackoverflow.com/questions/3109978/display-numbers-with-ordinal-suffix-in-php
    */
    function printNumber($number)
    {
        $suffixes = array("th", "st", "nd", "rd", "th", "th", "th", "th", "th", "th"); // array the stores numbers suffixes
        if (($number % 100) >= 11 && ($number % 100) <= 13) { return $number."th"; } // 11-13 all end with th, out of the ordinary
        else { return $number.$suffixes[$number % 10]; } // return any other number with suffix from array
    }

    /** function to print a grade level */
    function printGradeLevel($grade, $includeString = 0)
    { 
        $gradeStr = "";
        if ($grade > 0 && $grade <= 12) 
        { 
            $gradeStr .= printNumber($grade); 
            if ($includeString == 1) { $gradeStr .= " Grade"; }
        }
        else if ($grade == 0) { $gradeStr .= "K"; }
        else if ($grade == -1) { $gradeStr .= "PK"; }
        else if ($grade == -2) { $gradeStr .= "4K"; }
        else if ($grade == -3) { $gradeStr .= "3K"; }
        else if ($grade == 13) { $gradeStr .= "Post 12th Grade"; }
        return $gradeStr;
    }

    /** function to create the container to store the contents of the table footer */
    function createTableFooter($table_id, $includeButtons = true, $showFirstAndLast = true)
    {
        ?>
            <!-- <?php echo $table_id; ?> Table Footer -->
            <div class="row table-footer d-flex align-items-center m-0 p-2">
                <!-- Table Information & Details -->
                <div class="<?php if ($includeButtons === true) { echo "col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4 col-xxl-4"; } else { echo "col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6"; } ?>">
                    <div id="<?php echo $table_id; ?>-DT_Details"></div>
                </div>

                <?php if ($includeButtons === true) { ?>
                <!-- Table Export Buttons -->
                <div class="col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4 col-xxl-4 d-flex justify-content-center" id="export-buttons-div"></div>
                <?php } ?>

                <!-- Table Page Selection -->
                <div class="<?php if ($includeButtons === true) { echo "col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4 col-xxl-4"; } else { echo "col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6"; } ?>">
                    <div class="float-end">
                        <?php if ($showFirstAndLast === true) { ?><button class="table-page_selection-btn" onclick="goToFirstPage('<?php echo $table_id; ?>');" title="Jump to first page."><i class="fas fa-angle-double-left fa-lg"></i></button><?php } ?>
                        <button class="table-page_selection-btn" onclick="goToPrevPage('<?php echo $table_id; ?>');" title="Go to previous page."><i class="fas fa-angle-left fa-lg"></i></button>
                        <div class="d-inline" id="<?php echo $table_id; ?>-DT_PageChange"></div>
                        <button class="table-page_selection-btn" onclick="goToNextPage('<?php echo $table_id; ?>');" title="Go to next page."><i class="fas fa-angle-right fa-lg"></i></button>
                        <?php if ($showFirstAndLast === true) { ?><button class="table-page_selection-btn" onclick="goToLastPage('<?php echo $table_id; ?>');" title="Jump to last page."><i class="fas fa-angle-double-right fa-lg"></i></button><?php } ?>
                    </div>
                </div>
            </div>
        <?php
    }

    /** function to create the container to store the contents of the table footer */
    function createTableFooterV2($table_id, $sessionStorageName = "BAP_Default_PageLength", $userPageLength = 10, $includePageLength = true, $showFirstAndLast = true)
    {
        ?>
            <!-- <?php echo $table_id; ?> Table Footer -->
            <div class="row table-footer d-flex align-items-center m-0 p-2">
                <!-- Table Information & Details -->
                <div class="<?php if ($includePageLength === true) { echo "col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4 col-xxl-4"; } else { echo "col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6"; } ?>">
                    <div id="<?php echo $table_id; ?>-DT_Details"></div>
                </div>

                <!-- Table Page Selection -->
                <div class="<?php if ($includePageLength === true) { echo "col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4 col-xxl-4"; } else { echo "col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6"; } ?>">
                    <div class="text-center">
                        <?php if ($showFirstAndLast === true) { ?><button class="table-page_selection-btn" onclick="goToFirstPage('<?php echo $table_id; ?>');" title="Jump to first page."><i class="fas fa-angle-double-left fa-lg"></i></button><?php } ?>
                        <button class="table-page_selection-btn" onclick="goToPrevPage('<?php echo $table_id; ?>');" title="Go to previous page."><i class="fas fa-angle-left fa-lg"></i></button>
                        <div class="d-inline" id="<?php echo $table_id; ?>-DT_PageChange"></div>
                        <button class="table-page_selection-btn" onclick="goToNextPage('<?php echo $table_id; ?>');" title="Go to next page."><i class="fas fa-angle-right fa-lg"></i></button>
                        <?php if ($showFirstAndLast === true) { ?><button class="table-page_selection-btn" onclick="goToLastPage('<?php echo $table_id; ?>');" title="Jump to last page."><i class="fas fa-angle-double-right fa-lg"></i></button><?php } ?>
                    </div>
                </div>

                <?php if ($includePageLength === true) { createPageLengthContainerV2($table_id, $sessionStorageName, $userPageLength); } ?>
            </div>
        <?php
    }

    /** function to create the container to store the contents of the table footer */
    function createTableFooterV3($table_id, $columns, $sessionStorageName = "BAP_Default_PageLength", $userPageLength = 10, $includePageLength = true, $showFirstAndLast = true, $buttonsDiv = false)
    {
        ?>
            <!-- <?php echo $table_id; ?> Table Footer -->
            <tfoot class="table-footer p-0">
                <tr>
                    <td class="py-2" colspan="<?php echo $columns; ?>" style="text-align: left !important;">
                        <div class="row d-flex align-items-center m-0">
                            <!-- Table Information & Details -->
                            <div class="<?php if ($includePageLength === true) { echo "col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4 col-xxl-4"; } else { echo "col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6"; } ?>">
                                <div id="<?php echo $table_id; ?>-DT_Details"></div>
                            </div>

                            <!-- Table Page Selection -->
                            <div class="<?php if ($includePageLength === true) { echo "col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4 col-xxl-4"; } else { echo "col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6"; } ?>">
                                <div class="text-center">
                                    <?php if ($showFirstAndLast === true) { ?><button class="table-page_selection-btn" onclick="goToFirstPage('<?php echo $table_id; ?>');" title="Jump to first page."><i class="fas fa-angle-double-left fa-lg"></i></button><?php } ?>
                                    <button class="table-page_selection-btn" onclick="goToPrevPage('<?php echo $table_id; ?>');" title="Go to previous page."><i class="fas fa-angle-left fa-lg"></i></button>
                                    <div class="d-inline" id="<?php echo $table_id; ?>-DT_PageChange"></div>
                                    <button class="table-page_selection-btn" onclick="goToNextPage('<?php echo $table_id; ?>');" title="Go to next page."><i class="fas fa-angle-right fa-lg"></i></button>
                                    <?php if ($showFirstAndLast === true) { ?><button class="table-page_selection-btn" onclick="goToLastPage('<?php echo $table_id; ?>');" title="Jump to last page."><i class="fas fa-angle-double-right fa-lg"></i></button><?php } ?>
                                </div>
                            </div>

                            <?php if ($includePageLength === true) { createPageLengthContainerV2($table_id, $sessionStorageName, $userPageLength); } ?>
                        </div> 
                    </td>
                </tr>
            </tfoot>
        <?php
    }

    /** function to create a page length selection container */
    function createPageLengthContainer($table_id, $session_storage = "", $default_page_length = 10)
    {
        ?>
            <span class="table-page_length-div float-start">
                Show&nbsp;
                <select class="form-select d-inline w-auto" id="<?php echo $table_id; ?>-DT_PageLength" onchange="updatePageLength('<?php echo $table_id; ?>', '<?php echo $session_storage; ?>')">
                    <option value="10" <?php if ($default_page_length == 10) { echo "selected"; } ?>>10</option>
                    <option value="25" <?php if ($default_page_length == 25) { echo "selected"; } ?>>25</option>
                    <option value="50" <?php if ($default_page_length == 50) { echo "selected"; } ?>>50</option>
                    <option value="100" <?php if ($default_page_length == 100) { echo "selected"; } ?>>100</option>
                    <option value="250" <?php if ($default_page_length == 250) { echo "selected"; } ?>>250</option>
                    <option value="500" <?php if ($default_page_length == 500) { echo "selected"; } ?>>500</option>
                    <option value="1000" <?php if ($default_page_length == 1000) { echo "selected"; } ?>>1000</option>
                    <option value="-1" <?php if ($default_page_length == -1) { echo "selected"; } ?>>All</option>
                </select>
                &nbsp;entries
            </span>
        <?php
    }

    /** function to create a page length selection container */
    function createPageLengthContainerV2($table_id, $session_storage = "BAP_Default_PageLength", $default_page_length = 10)
    {
        ?>
            <div class="col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4 col-xxl-4">
                <span class="table-page_length-div float-end">
                    Show&nbsp;
                    <select class="form-select d-inline w-auto" id="<?php echo $table_id; ?>-DT_PageLength" onchange="updatePageLength('<?php echo $table_id; ?>', '<?php echo $session_storage; ?>')">
                        <option value="10" <?php if ($default_page_length == 10) { echo "selected"; } ?>>10</option>
                        <option value="25" <?php if ($default_page_length == 25) { echo "selected"; } ?>>25</option>
                        <option value="50" <?php if ($default_page_length == 50) { echo "selected"; } ?>>50</option>
                        <option value="100" <?php if ($default_page_length == 100) { echo "selected"; } ?>>100</option>
                        <option value="250" <?php if ($default_page_length == 250) { echo "selected"; } ?>>250</option>
                        <option value="500" <?php if ($default_page_length == 500) { echo "selected"; } ?>>500</option>
                        <option value="1000" <?php if ($default_page_length == 1000) { echo "selected"; } ?>>1000</option>
                        <option value="-1" <?php if ($default_page_length == -1) { echo "selected"; } ?>>All</option>
                    </select>
                    &nbsp;entries
                </span>
            </div>
        <?php
    }

    /** function to add a student */
    function addStudent($conn, $fname, $lname, $status, $date_of_birth, $gender = 0)
    {
        // trim the first and last name
        $fname = trim($fname);
        $lname = trim($lname);

        // if both first and last name are set; continue student creation process
        if (($fname <> "" && $fname != null) && ($lname <> "" && $lname != null))
        {
            // verify the date of birth is set
            if (isset($date_of_birth) && $date_of_birth != null)
            { 
                // covert the student's date of birth to the database format
                $DB_DOB = date("Y-m-d", strtotime($date_of_birth));

                try
                {
                    // add the student to the database
                    $addStudent = mysqli_prepare($conn, "INSERT INTO caseload_students (fname, lname, status, date_of_birth, gender, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($addStudent, "ssisii", $fname, $lname, $status, $DB_DOB, $gender, $_SESSION["id"]);
                    if (mysqli_stmt_execute($addStudent)) 
                    { 
                        echo "<span class=\"log-success\">Successfully</span> added $fname $lname.<br>";
                        return true; 
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to add $fname $lname. An unexpected error has occurred! Please try again later.<br>"; }
                }
                catch (Exception $e)
                {
                    echo "<span class=\"log-fail\">Failed</span> to add $fname $lname. An unexpected error has occurred! Please try again later.<br>";
                }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add the new student. You are required to enter the student's date of birth.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to add the new student. You are required to enter both a first and last name!<br>"; }

        // return false if we have reached the end without returning
        return false;
    }

    /** function to edit a student */
    function editStudent($conn, $id, $fname, $lname, $status, $date_of_birth, $gender = 0)
    {
        if (verifyStudent($conn, $id))
        {
            // trim the first and last name
            $fname = trim($fname);
            $lname = trim($lname);

            if (($fname <> "" && $fname != null) && ($lname <> "" && $lname != null))
            {
                // verify the date of birth is set
                if (isset($date_of_birth) && $date_of_birth != null)
                { 
                    // covert the student's date of birth to the database format
                    $DB_DOB = date("Y-m-d", strtotime($date_of_birth));

                    try
                    {
                        // add the student to the database
                        $editStudent = mysqli_prepare($conn, "UPDATE caseload_students SET fname=?, lname=?, status=?, date_of_birth=?, gender=? WHERE id=?");
                        mysqli_stmt_bind_param($editStudent, "ssisii", $fname, $lname, $status, $DB_DOB, $gender, $id);
                        if (mysqli_stmt_execute($editStudent)) 
                        { 
                            echo "<span class=\"log-success\">Successfully</span> edited $fname $lname.<br>";
                            return true; 
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit $fname $lname. An unexpected error has occurred! Please try again later.<br>"; }
                    }
                    catch (Exception $e)
                    {
                        echo "<span class=\"log-fail\">Failed</span> to edit $fname $lname. An unexpected error has occurred! Please try again later.<br>";
                    }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the student. You are required to enter the student's date of birth.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the student. You are required to enter both a first and last name!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the student. The student you are trying to edit does not exist!<br>"; }

        // return false if we have reached the end without returning
        return false;
    }

    /** function to delete a student */
    function deleteStudent($conn, $id)
    {
        $deleteStudent = mysqli_prepare($conn, "DELETE FROM caseload_students WHERE id=?");
        mysqli_stmt_bind_param($deleteStudent, "i", $id);
        if (mysqli_stmt_execute($deleteStudent))
        {
            // print deletion status
            echo "<span class=\"log-success\">Successfully</span> deleted the student. Attempting to remove the student from caseloads... <br>";

            // delete caseload data
            $cases_in = 0;
            $getStudentCaseloads = mysqli_prepare($conn, "SELECT id FROM cases WHERE student_id=?");
            mysqli_stmt_bind_param($getStudentCaseloads, "i", $id);
            if (mysqli_stmt_execute($getStudentCaseloads))
            {
                $getStudentCaseloadsResult = mysqli_stmt_get_result($getStudentCaseloads);
                if (($cases_in = mysqli_num_rows($getStudentCaseloadsResult)) > 0) // cases found for student
                {
                    while ($caseload = mysqli_fetch_array($getStudentCaseloadsResult))
                    {
                        // store caseload ID locally
                        $case_id = $caseload["id"];

                        // delete all caseload changes associated with this caseload
                        $deleteCaseChanges = mysqli_prepare($conn, "DELETE FROM case_changes WHERE case_id=?");
                        mysqli_stmt_bind_param($deleteCaseChanges, "i", $case_id);
                        if (mysqli_stmt_execute($deleteCaseChanges)) // successfully deleted caseload changes, now delete the caseload
                        {
                            $deleteCase = mysqli_prepare($conn, "DELETE FROM cases WHERE id=?");
                            mysqli_stmt_bind_param($deleteCase, "i", $case_id);
                            if (!mysqli_stmt_execute($deleteCase)) { echo "<span class=\"log-fail\">Failed</span> to remove the student from the caseload with ID of $case_id.<br>"; } // failed to delele caseload associated with the student
                        }
                    }
                }
            }

            // print deletion status
            echo "Processed removing the student from caseloads.<br>";
            echo "Student deletion complete!<br>";
            
            // log student deletion
            $message = "Deleted student with the ID of $id and removed them from associated caseloads. Deleted $cases_in cases for this student.";            
            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
            mysqli_stmt_execute($log);
            
            // return true, indicating action is complete
            return true;
        }
        
        // return false if we have reached the end without returning
        return false;
    }

    // function to verify if a student exists
    function verifyStudent($conn, $id)
    {
        $checkStudent = mysqli_prepare($conn, "SELECT id FROM caseload_students WHERE id=?");
        mysqli_stmt_bind_param($checkStudent, "i", $id);
        if (mysqli_stmt_execute($checkStudent))
        {
            $checkStudentResult = mysqli_stmt_get_result($checkStudent);
            if (mysqli_num_rows($checkStudentResult) > 0) // student exists
            {
                return true;
            }
        }

        // return false if we have reached the end without returning
        return false;
    }

    /** get a student's display name */
    function getStudentDisplayName($conn, $id)
    {
        $name = "";
        $getName = mysqli_prepare($conn, "SELECT fname, lname FROM caseload_students WHERE id=?");
        mysqli_stmt_bind_param($getName, "i", $id);
        if (mysqli_stmt_execute($getName))
        {
            $getNameResult = mysqli_stmt_get_result($getName);
            if (mysqli_num_rows($getNameResult) > 0)
            {
                $student = mysqli_fetch_array($getNameResult);
                $fname = $student["fname"];
                $lname = $student["lname"];
                $name = $lname.", ".$fname;
            }
        }
        return $name;
    }

    /** verify a customer/district exists */
    function verifyCustomer($conn, $id)
    {
        $checkCustomer = mysqli_prepare($conn, "SELECT id FROM customers WHERE id=?");
        mysqli_stmt_bind_param($checkCustomer, "i", $id);
        if (mysqli_stmt_execute($checkCustomer))
        {
            $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
            if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; return true
            {
                return true;
            }
        }

        // return false if we have reached the end without returning
        return false;
    }

    /** verify a case exists and the user has access to it */
    function verifyCase($conn, $case_id)
    {
        // get the caseload which the case is assigned to if it exists
        $checkCase = mysqli_prepare($conn, "SELECT id, caseload_id FROM cases WHERE id=?");
        mysqli_stmt_bind_param($checkCase, "i", $case_id);
        if (mysqli_stmt_execute($checkCase))
        {
            $checkCaseResult = mysqli_stmt_get_result($checkCase);
            if (mysqli_num_rows($checkCaseResult) > 0)
            {
                // user can view all cases, retun true as case exists
                if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
                {
                    return true;
                }
                // user can view only assigned cases, verify user has access to the caseload which the case is assigned to
                else if (checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED"))
                {
                    // store case details
                    $case = mysqli_fetch_array($checkCaseResult);
                    $caseload_id = $case["caseload_id"];

                    // verify user has access to the caseload
                    if (verifyUserCaseload($conn, $caseload_id))
                    {
                        return true; 
                    }
                }
            }
        }

        // return false if we have reached the end without returning
        return false;
    }

    /** function to check if the user is the caseload manager */
    function isUserCaseloadManage($conn, $user_id, $caseload_id)
    {
        $checkCaseloadManager = mysqli_prepare($conn, "SELECT id FROM caseloads WHERE id=? AND employee_id=?");
        mysqli_stmt_bind_param($checkCaseloadManager, "ii", $caseload_id, $user_id);
        if (mysqli_stmt_execute($checkCaseloadManager))
        {
            $checkCaseloadManagerResult = mysqli_stmt_get_result($checkCaseloadManager);
            if (mysqli_num_rows($checkCaseloadManagerResult) > 0) // user is the manager of the caseload; return true
            {
                return true;
            }
        }

        // return false if we have reached the end without returning
        return false;
    }

    /** function to get the case ID based on a change ID */
    function getCaseIDFromChange($conn, $change_id)
    {
        $case_id = -1;
        $getCaseID = mysqli_prepare($conn, "SELECT case_id FROM case_changes WHERE id=?");
        mysqli_stmt_bind_param($getCaseID, "i", $change_id);
        if (mysqli_stmt_execute($getCaseID))
        {
            $getCaseIDResult = mysqli_stmt_get_result($getCaseID);
            if (mysqli_num_rows($getCaseIDResult) > 0)
            {
                $case_id = mysqli_fetch_array($getCaseIDResult)["case_id"];
            }
        }
        return $case_id;
    }

    /** 
     * function to get the number of days between 2 dates 
     * source: https://www.includehelp.com/php/code-to-get-number-of-days-between-two-dates.aspx
    */
    function getDaysBetween($date1, $date2)
    {
        if (isset($date1) && isset($date2))
        {
            $date1_ts = strtotime($date1);
            $date2_ts = strtotime($date2);
            $diff_ts = $date2_ts - $date1_ts;
            return round($diff_ts / 86400);
        }
        else { return 0; }
    }

    /** function to calculate the prorated units of service total for a caseload */
    function getProratedUOS($conn, $case_id)
    {
        // initialize variable to store total prorated units of service
        $total_prorated_uos = 0;

        // get today's date
        $today = date("Y-m-d");

        // verify the caseload exists
        if (verifyCase($conn, $case_id))
        {
            // get the caseload's current data
            $getCaseload = mysqli_prepare($conn, "SELECT * FROM cases WHERE id=?");
            mysqli_stmt_bind_param($getCaseload, "i", $case_id);
            if (mysqli_stmt_execute($getCaseload))
            {
                $getCaseloadResult = mysqli_stmt_get_result($getCaseload);
                if (mysqli_num_rows($getCaseloadResult) > 0)
                {
                    // store caseload details locally
                    $caseload = mysqli_fetch_array($getCaseloadResult);
                    $case_id = $caseload["id"];
                    $period_id = $caseload["period_id"];
                    $evaluation_method = $caseload["evaluation_method"];
                    $starting_uos = $caseload["estimated_uos"];
                    $starting_frequency = $caseload["frequency"];
                    if (isset($caseload["start_date"])) { $start_date = date("n/j/Y", strtotime($caseload["start_date"])); } else { $start_date = strtotime("n/j/Y"); }
                    if (isset($caseload["end_date"])) { $end_date = date("n/j/Y", strtotime($caseload["end_date"])); } else { $end_date = strtotime("n/j/Y"); }
                    $remove_iep = $caseload["remove_iep"];
                    $dismissed = $caseload["dismissed"];
                    $dismissal_iep = $caseload["dismissal_iep"];
                    $uos_adjustment = $caseload["uos_adjustment"];

                    // if the start date is before or on today, calculate UOS projection
                    if (strtotime($start_date) <= strtotime($today))
                    {
                        // calculate number of days in school year
                        $days_in_year = getDaysInCaseloadTerm($conn, $period_id);

                        // initialize array to store changes
                        $changes = []; 

                        // add initial caseload details to changes array
                        $initial_details = [];
                        $initial_details["change_id"] = 0;
                        $initial_details["change_date"] = $start_date;
                        $initial_details["end_date"] = $end_date;
                        $initial_details["frequency"] = $starting_frequency;
                        $initial_details["uos"] = $starting_uos;
                        $initial_details["uos_change"] = "-";
                        $initial_details["additional_iep"] = 0;
                        $changes[] = $initial_details;

                        // REGULAR EVALUATION
                        if ($evaluation_method == 1)
                        {
                            // get a list of all caseload changes for this caseload
                            $getChanges = mysqli_prepare($conn, "SELECT id, start_date, frequency, uos, iep_meeting FROM case_changes WHERE case_id=? AND start_date<=? ORDER BY start_date ASC");
                            mysqli_stmt_bind_param($getChanges, "is", $case_id, $today);
                            if (mysqli_stmt_execute($getChanges))
                            {
                                $getChangesResults = mysqli_stmt_get_result($getChanges);
                                if (mysqli_num_rows($getChangesResults) > 0)
                                {
                                    // initialize the counter to track changes
                                    $change_counter = 1;

                                    // set the previous units of service to the starting units of service
                                    $previous_uos = $starting_uos;

                                    while ($change = mysqli_fetch_array($getChangesResults))
                                    {
                                        // store change details locally
                                        $change_id = $change["id"];
                                        if (isset($change["start_date"])) { $start_date = date("n/j/Y", strtotime($change["start_date"])); } else { $start_date = "?"; }
                                        $frequency = $change["frequency"];
                                        $uos = $change["uos"];
                                        $uos_change = $uos - $previous_uos;

                                        // create temporary array to store caseload change date
                                        $temp = [];
                                        $temp["change_id"] = $change["id"];
                                        $temp["change_date"] = $start_date;
                                        $temp["end_date"] = $end_date;
                                        $temp["frequency"] = $frequency;
                                        $temp["uos"] = $uos;
                                        $temp["uos_change"] = $uos_change;
                                        $temp["additional_iep"] = $change["iep_meeting"];

                                        // update the prior entries end date to the start date of the change
                                        $changes[$change_counter - 1]["end_date"] = $start_date;

                                        // add temporary array to changes array
                                        $changes[] = $temp;

                                        // set the previous units of service to this change increment units of service
                                        $previous_uos = $uos;

                                        // increment change counter
                                        $change_counter++;
                                    }
                                }
                            }

                            for ($c = 0; $c < count($changes); $c++)
                            {
                                // store change details locally
                                $change_id = $changes[$c]["change_id"];
                                $change_date = $changes[$c]["change_date"];
                                $end_date = $changes[$c]["end_date"];
                                $frequency = $changes[$c]["frequency"];
                                $units = $changes[$c]["uos"];
                                $units_change = $changes[$c]["uos_change"];
                                $additional_iep = $changes[$c]["additional_iep"];

                                // calculate number of days in "cycle"
                                $days_in_cycle = getDaysBetween($end_date, $change_date);

                                // calculate percentage of days in current cycle
                                if ($days_in_year != 0) { $percentage_of_total = $days_in_cycle / $days_in_year; } else { $percentage_of_total = 0; }

                                // calculate the prorated units
                                if ($evaluation_method == 1 || $evaluation_method == 2)
                                {
                                    if ($c == 0 && count($changes) == 1)
                                    {
                                        $prorated_uos = ($percentage_of_total * $units);
                                    }
                                    else
                                    {
                                        $prorated_uos = ($percentage_of_total * ($units - 12) + 12);
                                    }

                                    if ($additional_iep == 0 && $c > 0) { $prorated_uos -= 12; }
                                }
                                else
                                {
                                    $prorated_uos = 0;
                                }

                                // add prorated uos to array
                                $changes[$c]["prorated_uos"] = $prorated_uos;

                                // add prorated uos to total
                                $total_prorated_uos += $prorated_uos;
                            }

                            if ($remove_iep == 1) { $total_prorated_uos -= 12; }
                            if ($dismissed == 1 && $dismissal_iep == 1) { $total_prorated_uos += 12; }

                            $total_prorated_uos += $uos_adjustment;
                        }
                        // EVALUATION ONLY
                        else if ($evaluation_method == 2)
                        {
                            $total_prorated_uos = 16;
                        }
                        // OTHER
                        else
                        {
                            $total_prorated_uos = 0;
                        }
                    }
                    else
                    {
                        $total_prorated_uos = 0;
                    }
                }
            }
        }

        // if the total UOS is below 0, set to 0
        if ($total_prorated_uos < 0) { $total_prorated_uos = 0; }

        // return the total prorated units of service for the caseload
        return ceil($total_prorated_uos);
    }

    /** function to get the number of days an employee has been budgeted */
    function getBudgetedDays($conn, $employee_id, $period_id)
    {
        // get employee's budgeted days count
        $budgeted_days = 0; // assume employee has not been budgeted
        $getBudgetedDays = mysqli_prepare($conn, "SELECT SUM(project_days) AS budgeted_days FROM project_employees WHERE employee_id=? AND period_id=?");
        mysqli_stmt_bind_param($getBudgetedDays, "ii", $employee_id, $period_id);
        if (mysqli_stmt_execute($getBudgetedDays))
        {
            $getBudgetedDaysResult = mysqli_stmt_get_result($getBudgetedDays);
            if (mysqli_num_rows($getBudgetedDaysResult) > 0) // employee was found in budgets; get sum of days
            {
                $budgeted_days = mysqli_fetch_array($getBudgetedDaysResult)["budgeted_days"];
            }
        }
        return $budgeted_days;
    }

     /** function to set a permission for a role */
    function setPermission($conn, $role_id, $permission_name)
    {
        if (verifyRole($conn, $role_id))
        {
            try
            {
                // attempt to set the permission for the role
                $setPermission = mysqli_prepare($conn, "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, (SELECT id FROM `permissions` WHERE name=?))");
                mysqli_stmt_bind_param($setPermission, "is", $role_id, $permission_name);
                if (mysqli_stmt_execute($setPermission)) { return true; } else { return false; } // return true if we successfully set the permission for the role; otherwise, return false
            }
            catch (Exception $e)
            {

            }
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to remove a permission for a role */
    function removePermission($conn, $role_id, $permission_name)
    {
        if (verifyRole($conn, $role_id))
        {
            try
            {
                // attempt to set the permission for the role
                $removePermission = mysqli_prepare($conn, "DELETE FROM role_permissions WHERE role_id=? AND permission_id=(SELECT id FROM `permissions` WHERE name=?)");
                mysqli_stmt_bind_param($removePermission, "is", $role_id, $permission_name);
                if (mysqli_stmt_execute($removePermission)) { return true; } else { return false; } // return true if we successfully set the permission for the role; otherwise, return false
            }
            catch (Exception $e)
            {

            }
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to verify the role exists */
    function verifyRole($conn, $role_id)
    {
        // query the database to see if a role with the ID provided exists
        $checkRole = mysqli_prepare($conn, "SELECT id FROM roles WHERE id=?");
        mysqli_stmt_bind_param($checkRole, "i", $role_id);
        if (mysqli_stmt_execute($checkRole))
        {
            $checkRoleResult = mysqli_stmt_get_result($checkRole);
            if (mysqli_num_rows($checkRoleResult) > 0) // role exists
            {
                // return true; role exists
                return true;
            }
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to check to see if a role is default generated */
    function isRoleDefaultGenerated($conn, $role_id)
    {
        if (verifyRole($conn, $role_id))
        {
            $checkDefaultGenerated = mysqli_prepare($conn, "SELECT default_generated FROM roles WHERE id=?");
            mysqli_stmt_bind_param($checkDefaultGenerated, "i", $role_id);
            if (mysqli_stmt_execute($checkDefaultGenerated))
            {
                $checkDefaultGeneratedResult = mysqli_stmt_get_result($checkDefaultGenerated);
                if (mysqli_num_rows($checkDefaultGeneratedResult) > 0) // role found; check if default generated
                {
                    // store default generated field locally
                    $default_generated = mysqli_fetch_array($checkDefaultGeneratedResult)["default_generated"];

                    // role was generated by default, return true
                    if ($default_generated == 1) { return true; }
                }
            }
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to check if the user has access/permission for the given page/action */
    function checkUserPermission($conn, $permission_name)
    {
        if (isset($_SESSION["id"]))
        {
            // if the user is the SUPER ADMIN; grant permission by default
            if ($_SESSION["id"] == 0 && strtoupper($_SESSION["email"]) == "SUPER@CESA5.ORG") { return true; }

            // get the user's role based on their user ID, the user must also be active
            $checkUser = mysqli_prepare($conn, "SELECT role_id FROM users WHERE id=? AND status=1");
            mysqli_stmt_bind_param($checkUser, "i", $_SESSION["id"]);
            if (mysqli_stmt_execute($checkUser))
            {
                $checkUserResult = mysqli_stmt_get_result($checkUser);
                if (mysqli_num_rows($checkUserResult) > 0) // user exists
                {
                    // store role_id locally
                    $role_id = mysqli_fetch_array($checkUserResult)["role_id"];

                    // check to see if the role gives user access/permission for the given page/action
                    $checkPermission = mysqli_prepare($conn, "SELECT id FROM role_permissions WHERE role_id=? AND permission_id=(SELECT id FROM `permissions` WHERE name=?)");
                    mysqli_stmt_bind_param($checkPermission, "is", $role_id, $permission_name);
                    if (mysqli_stmt_execute($checkPermission))
                    {
                        $checkPermissionResult = mysqli_stmt_get_result($checkPermission);
                        if (mysqli_num_rows($checkPermissionResult) > 0) // permission found for the role, grant user access/permission for the action/page
                        {
                            // return true; indicating that the user has access/permission
                            return true;
                        }
                    }
                }
            }
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to get a project's total revenue */
    function getProjectsTotalRevenue($conn, $code, $period)
    {
        // initialize the total cost
        $total_revenue = 0;

        // get the total cost of each service
        $getServiceRevenue = mysqli_prepare($conn, "SELECT SUM(qc.cost) AS total_cost FROM quarterly_costs qc JOIN services_provided sp ON qc.invoice_id=sp.id JOIN services s ON sp.service_id=s.id JOIN projects p ON s.project_code=p.code WHERE sp.period_id=? AND p.code=?");
        mysqli_stmt_bind_param($getServiceRevenue, "is", $period, $code);
        if (mysqli_stmt_execute($getServiceRevenue))
        {
            $getServiceRevenueResult = mysqli_stmt_get_result($getServiceRevenue);
            if (mysqli_num_rows($getServiceRevenueResult) > 0) 
            { 
                $total_revenue += mysqli_fetch_array($getServiceRevenueResult)["total_cost"];
            }
        }

        // get the additional revenues for the project
        $getProjectRevenues = mysqli_prepare($conn, "SELECT SUM(total_cost) AS total_cost FROM revenues WHERE project_code=? AND period_id=?");
        mysqli_stmt_bind_param($getProjectRevenues, "si", $code, $period);
        if (mysqli_stmt_execute($getProjectRevenues))
        {
            $getProjectRevenuesResult = mysqli_stmt_get_result($getProjectRevenues);
            if (mysqli_num_rows($getProjectRevenuesResult) > 0) 
            { 
                $total_revenue += mysqli_fetch_array($getProjectRevenuesResult)["total_cost"];
            }
        }

        // get "other services" revenues
        $getOtherServiceRevenue = mysqli_prepare($conn, "SELECT SUM(oqc.cost) AS total_cost FROM other_quarterly_costs oqc JOIN services_other_provided sop ON oqc.other_invoice_id=sop.id JOIN projects p ON sop.project_code=p.code WHERE sop.period_id=? AND p.code=?");
        mysqli_stmt_bind_param($getOtherServiceRevenue, "is", $period, $code);
        if (mysqli_stmt_execute($getOtherServiceRevenue))
        {
            $getOtherServiceRevenueResult = mysqli_stmt_get_result($getOtherServiceRevenue);
            if (mysqli_num_rows($getOtherServiceRevenueResult) > 0) 
            { 
                $total_revenue += mysqli_fetch_array($getOtherServiceRevenueResult)["total_cost"];
            }
        }

        // return the project's total revenue
        return $total_revenue;
    }

    /** function to get a project's total expenses */
    function getProjectsTotalExpenses($conn, $code, $period)
    {
        // initialize the total cost
        $total_expenses = 0;

        /* PROJECT EXPENSES */
        $getProjectExpenses = mysqli_prepare($conn, "SELECT SUM(pe.cost) AS total_cost FROM project_expenses pe
                                                    JOIN projects_status ps ON pe.project_code=ps.code AND pe.period_id=ps.period_id 
                                                    WHERE pe.project_code=? AND pe.period_id=? AND ps.status=1");
        mysqli_stmt_bind_param($getProjectExpenses, "si", $code, $period);
        if (mysqli_stmt_execute($getProjectExpenses))
        {
            $getProjectExpensesResult = mysqli_stmt_get_result($getProjectExpenses);
            if (mysqli_num_rows($getProjectExpensesResult) > 0)
            {
                $total_expenses += mysqli_fetch_array($getProjectExpensesResult)["total_cost"];
            }
        }

        /* EMPLOYEE EXPENSES */
        // get a list of the employees within the project and the sum of their total compensation
        $employees = getProjectEmployees($conn, $code, $period);
        for ($e = 0; $e < count($employees); $e++)
        {
            $total_expenses += getEmployeesTotalCompensation($conn, $code, $employees[$e], $period);
        }

        /* TEST EMPLOYEE EXPENSES */ /* temporarily removing test employees from total calculations
        // get total compensation for test employees, only if the test employee was selected to include their costs
        $total_expenses += getTestProjectEmployeesCompensation($conn, $code, $period);
        */

        // send back the total expenses
        return $total_expenses;
    }

    /** function to get a project's total expenses */
    function getProjectsTotalDays($conn, $code, $period)
    {
        // initialize the total days
        $total_days = 0;
        $getProjectDays = mysqli_prepare($conn, "SELECT SUM(pe.project_days) AS total_days FROM project_employees pe
                                                    JOIN projects_status ps ON pe.project_code=ps.code AND pe.period_id=ps.period_id 
                                                    WHERE pe.project_code=? AND pe.period_id=? AND ps.status=1");
        mysqli_stmt_bind_param($getProjectDays, "si", $code, $period);
        if (mysqli_stmt_execute($getProjectDays))
        {
            $getProjectDaysResult = mysqli_stmt_get_result($getProjectDays);
            if (mysqli_num_rows($getProjectDaysResult) > 0)
            {
                $total_days = mysqli_fetch_array($getProjectDaysResult)["total_days"];
            }
        }
        return $total_days;
    }

    /** function to create a projects automated expenses */
    function createAutomatedExpenses($conn, $code, $period)
    {
        $default_cost = 0.00;
        $default_desc = "Autocalculated";
        $aidable_id = 33;
        $nonaidable_id = 34;
        $indirect_id = 35;

        // check to see if the project has supervision costs
        $supervision_costs = 0; // assume supervision costs are disabled for this project
        $checkSupervision = mysqli_prepare($conn, "SELECT supervision_costs FROM projects WHERE code=?");
        mysqli_stmt_bind_param($checkSupervision, "s", $code);
        if (mysqli_stmt_execute($checkSupervision))
        {
            $checkSupervisionResult = mysqli_stmt_get_result($checkSupervision);
            if (mysqli_num_rows($checkSupervisionResult) > 0) // supervision cost setting found
            {
                $supervision_costs = mysqli_fetch_array($checkSupervisionResult)["supervision_costs"];
            }
        }

        if ($supervision_costs == 1) // only add supervision costs if enabled for the project
        {
            // create the automated expenses
            $addAidableSupervision = mysqli_prepare($conn, "INSERT INTO project_expenses (project_code, expense_id, description, cost, period_id, auto) VALUES (?, ?, ?, ?, ?, 1)");
            mysqli_stmt_bind_param($addAidableSupervision, "sisdi", $code, $aidable_id, $default_desc, $default_cost, $period);
            mysqli_stmt_execute($addAidableSupervision);

            $addNonaidableSupervision = mysqli_prepare($conn, "INSERT INTO project_expenses (project_code, expense_id, description, cost, period_id, auto) VALUES (?, ?, ?, ?, ?, 1)");
            mysqli_stmt_bind_param($addNonaidableSupervision, "sisdi", $code, $nonaidable_id, $default_desc, $default_cost, $period);
            mysqli_stmt_execute($addNonaidableSupervision);
        }
        
        $addProjectIndirect = mysqli_prepare($conn, "INSERT INTO project_expenses (project_code, expense_id, description, cost, period_id, auto) VALUES (?, ?, ?, ?, ?, 1)");
        mysqli_stmt_bind_param($addProjectIndirect, "sisdi", $code, $indirect_id, $default_desc, $default_cost, $period);
        mysqli_stmt_execute($addProjectIndirect);
    }

    /** function to recalculate the automated expenses */
    function recalculateAutomatedExpenses($conn, $code, $period)
    {
        // run the function to clear supervision costs for all projects if their project has supervision costs disabled
        clearSupervisionCosts($conn, $period);

        // run the function to clear supervision costs for all projects if their project has indirect costs disabled
        clearIndirectCosts($conn, $period);

        // get the rates from the global_expenses table
        $getRates = mysqli_prepare($conn, "SELECT aidable_supervision, nonaidable_supervision, agency_indirect FROM global_expenses WHERE period_id=?");
        mysqli_stmt_bind_param($getRates, "i", $period);
        if (mysqli_stmt_execute($getRates))
        {
            $getRatesResult = mysqli_stmt_get_result($getRates);
            if (mysqli_num_rows($getRatesResult) > 0) // rates for the active period exist; continue
            {
                // get the rates
                $rates = mysqli_fetch_array($getRatesResult);

                // get the grant project indirect rate
                $grant_indirect_rate = getGrantIndirectRate($conn);
                $dpi_grant_indirect_rate = getDPIGrantIndirectRate($conn, $period);

                // initialize variables
                $total_compensation = 0;

                // get a list of the employees within the project and the sum of their total compensation
                $employees = getProjectEmployees($conn, $code, $period);
                for ($e = 0; $e < count($employees); $e++)
                {
                    $total_compensation += getEmployeesTotalCompensation($conn, $code, $employees[$e], $period);
                }

                // get the total compensation of all test employees that have cost inclusion enabled within this project
                $total_compensation += getTestProjectEmployeesCompensation($conn, $code, $period);

                // get the total expenses of the current project
                $total_expenses = 0;
                $getTotalExpenses = mysqli_prepare($conn, "SELECT SUM(cost) AS total_expenses FROM project_expenses WHERE project_code=? AND period_id=? AND auto=0");
                mysqli_stmt_bind_param($getTotalExpenses, "si", $code, $period);
                if (mysqli_stmt_execute($getTotalExpenses))
                {
                    $getTotalExpensesResult = mysqli_stmt_get_result($getTotalExpenses);
                    if (mysqli_num_rows($getTotalExpensesResult) > 0) { $total_expenses = mysqli_fetch_array($getTotalExpensesResult)["total_expenses"]; }
                }

                // check to see if the project has supervision costs and indirect costs enabled or disabled
                $supervision_costs = $indirect_costs = 0; // assume supervision and indirect costs are disabled for this project
                $checkCosts = mysqli_prepare($conn, "SELECT supervision_costs, indirect_costs FROM projects WHERE code=?");
                mysqli_stmt_bind_param($checkCosts, "s", $code);
                if (mysqli_stmt_execute($checkCosts))
                {
                    $checkCostsResults = mysqli_stmt_get_result($checkCosts);
                    if (mysqli_num_rows($checkCostsResults) > 0) // supervision cost setting found
                    {
                        $calcCosts = mysqli_fetch_array($checkCostsResults);
                        $supervision_costs = $calcCosts["supervision_costs"];
                        $indirect_costs = $calcCosts["indirect_costs"];
                    }
                }

                /* AIDABLE SUPERVISION */
                if ($supervision_costs == 1) { $aidable_supervision = $rates["aidable_supervision"] * $total_compensation; }
                else { $aidable_supervision = 0; }

                /* NON-AIDABLE SUPERVISION */
                if ($supervision_costs == 1) { $nonaidable_supervision = $rates["nonaidable_supervision"] * $total_compensation; }
                else { $nonaidable_supervision = 0; }

                $nonpersonnel_expenses = $total_expenses + $aidable_supervision + $nonaidable_supervision;
                if ($indirect_costs == 1) { $indirect_NPE = $nonpersonnel_expenses * $rates["agency_indirect"]; }
                else if ($indirect_costs == 2) { $indirect_NPE = $nonpersonnel_expenses * $grant_indirect_rate; } 
                else if ($indirect_costs == 3) { $indirect_NPE = $nonpersonnel_expenses * $dpi_grant_indirect_rate; } 

                $personnel_expenses = $total_compensation;
                if ($indirect_costs == 1) { $indirect_PE = $personnel_expenses * $rates["agency_indirect"]; }
                else if ($indirect_costs == 2) { $indirect_PE = $personnel_expenses * $grant_indirect_rate; } 
                else if ($indirect_costs == 3) { $indirect_PE = $personnel_expenses * $dpi_grant_indirect_rate; } 

                /* PROJECT INDIRECT */
                if ($indirect_costs == 1 || $indirect_costs == 2 || $indirect_costs == 3) { $project_indirect = $indirect_PE + $indirect_NPE; }
                else { $project_indirect = 0; }

                /* 
                 * update the expenses
                */
                // initialize expense IDs
                $aidable_id = 33;
                $nonaidable_id = 34;
                $indirect_id = 35;

                // initialize the default description of automated costs
                $default_desc = "Autocalculated";

                // only update supervision costs if enabled for this project
                if ($supervision_costs == 1)
                {
                    // update (or insert) the aidable supervision expense
                    $checkAidable = mysqli_prepare($conn, "SELECT id FROM project_expenses WHERE project_code=? AND expense_id=? AND period_id=? AND auto=1");
                    mysqli_stmt_bind_param($checkAidable, "sii", $code, $aidable_id, $period);
                    if (mysqli_stmt_execute($checkAidable))
                    {
                        $checkAidableResult = mysqli_stmt_get_result($checkAidable);
                        if (mysqli_num_rows($checkAidableResult) > 0) // expense already exists; update current expense
                        {
                            $updateAidable = mysqli_prepare($conn, "UPDATE project_expenses SET cost=? WHERE project_code=? AND expense_id=? AND period_id=? AND auto=1");
                            mysqli_stmt_bind_param($updateAidable, "dsii", $aidable_supervision, $code, $aidable_id, $period);
                            mysqli_stmt_execute($updateAidable);
                        }
                        else // expense does not exist; insert new automated expense
                        {
                            $addAidable = mysqli_prepare($conn, "INSERT INTO project_expenses (project_code, expense_id, description, cost, period_id, auto) VALUES (?, ?, ?, ?, ?, 1)");
                            mysqli_stmt_bind_param($addAidable, "sisdi", $code, $aidable_id, $default_desc, $aidable_supervision, $period);
                            mysqli_stmt_execute($addAidable);
                        }
                    }

                    // update the non-aidable supervision expense
                    $checkNonaidable = mysqli_prepare($conn, "SELECT id FROM project_expenses WHERE project_code=? AND expense_id=? AND period_id=? AND auto=1");
                    mysqli_stmt_bind_param($checkNonaidable, "sii", $code, $nonaidable_id, $period);
                    if (mysqli_stmt_execute($checkNonaidable))
                    {
                        $checkNonaidableResult = mysqli_stmt_get_result($checkNonaidable);
                        if (mysqli_num_rows($checkNonaidableResult) > 0) // expense already exists; update current expense
                        {
                            $updateNonaidable = mysqli_prepare($conn, "UPDATE project_expenses SET cost=? WHERE project_code=? AND expense_id=? AND period_id=? AND auto=1");
                            mysqli_stmt_bind_param($updateNonaidable, "dsii", $nonaidable_supervision, $code, $nonaidable_id, $period);
                            mysqli_stmt_execute($updateNonaidable);
                        }
                        else // expense does not exist; insert new automated expense
                        {
                            $addNonaidable = mysqli_prepare($conn, "INSERT INTO project_expenses (project_code, expense_id, description, cost, period_id, auto) VALUES (?, ?, ?, ?, ?, 1)");
                            mysqli_stmt_bind_param($addNonaidable, "sisdi", $code, $nonaidable_id, $default_desc, $nonaidable_supervision, $period);
                            mysqli_stmt_execute($addNonaidable);
                        }
                    }
                }

                // only update indirect costs if enabled for this project
                if ($indirect_costs == 1 || $indirect_costs == 2 || $indirect_costs == 3)
                {
                    // update the project indirect expense
                    $checkIndirect = mysqli_prepare($conn, "SELECT id FROM project_expenses WHERE project_code=? AND expense_id=? AND period_id=? AND auto=1");
                    mysqli_stmt_bind_param($checkIndirect, "sii", $code, $indirect_id, $period);
                    if (mysqli_stmt_execute($checkIndirect))
                    {
                        $checkIndirectResult = mysqli_stmt_get_result($checkIndirect);
                        if (mysqli_num_rows($checkIndirectResult) > 0) // expense already exists; update current expense
                        {
                            $updateIndirect = mysqli_prepare($conn, "UPDATE project_expenses SET cost=? WHERE project_code=? AND expense_id=? AND period_id=? AND auto=1");
                            mysqli_stmt_bind_param($updateIndirect, "dsii", $project_indirect, $code, $indirect_id, $period);
                            mysqli_stmt_execute($updateIndirect);
                        }
                        else // expense does not exist; insert new automated expense
                        {
                            $addIndirect = mysqli_prepare($conn, "INSERT INTO project_expenses (project_code, expense_id, description, cost, period_id, auto) VALUES (?, ?, ?, ?, ?, 1)");
                            mysqli_stmt_bind_param($addIndirect, "sisdi", $code, $indirect_id, $default_desc, $project_indirect, $period);
                            mysqli_stmt_execute($addIndirect);
                        }
                    }
                }

                // edit the project last updated time
                updateProjectEditTimestamp($conn, $code);
            }    
        }
    }

    /** function get an employee's total compensation */
    function getEmployeesTotalCompensation($conn, $code, $employee_id, $period)
    {
        // initialize variables
        $project_compensation = 0;

        // get the FTE days value
        $FTE_Days = getFTEDays($conn);

        $getEmployees = mysqli_prepare($conn, "SELECT SUM(pe.project_days) AS project_days FROM project_employees pe
                                                JOIN projects_status ps ON pe.project_code=ps.code AND pe.period_id=ps.period_id
                                                WHERE pe.project_code=? AND pe.employee_id=? AND pe.period_id=?");
        mysqli_stmt_bind_param($getEmployees, "sii", $code, $employee_id, $period);
        if (mysqli_stmt_execute($getEmployees))
        {
            $getEmployeesResult = mysqli_stmt_get_result($getEmployees);
            while ($employee = mysqli_fetch_array($getEmployeesResult))
            {
                $project_days = $employee["project_days"];

                // get additional employee details based on the employee ID
                $getEmployeeDetails = mysqli_prepare($conn, "SELECT * FROM employees WHERE id=?");
                mysqli_stmt_bind_param($getEmployeeDetails, "i", $employee_id);
                if (mysqli_stmt_execute($getEmployeeDetails))
                {
                    $getEmployeeDetailsResult = mysqli_stmt_get_result($getEmployeeDetails);
                    if (mysqli_num_rows($getEmployeeDetailsResult) > 0) // employee exists; continue
                    {
                        // get the employee's compensation
                        $health = $dental = $wrs = $rate = $contract_days = 0; // initialize benefits and compensation to 0
                        $getCompensation = mysqli_prepare($conn, "SELECT * FROM employee_compensation WHERE employee_id=? AND period_id=?");
                        mysqli_stmt_bind_param($getCompensation, "ii", $employee_id, $period);
                        if (mysqli_stmt_execute($getCompensation))
                        {
                            $getCompensationResult = mysqli_stmt_get_result($getCompensation);
                            if (mysqli_num_rows($getCompensationResult) > 0) // benefits and compensation found
                            {
                                // store the employee's benefits and compensation locally
                                $employeeCompensation = mysqli_fetch_array($getCompensationResult);
                                $health = $employeeCompensation["health_insurance"];
                                $dental = $employeeCompensation["dental_insurance"];
                                $wrs = $employeeCompensation["wrs_eligible"];
                                $rate = $employeeCompensation["yearly_rate"];
                                $contract_days = $employeeCompensation["contract_days"];
                            }
                        }

                        // calculate the employee's daily rate
                        if ($contract_days != 0) { $daily_rate = $rate / $contract_days; }
                        else { $daily_rate = 0; }

                        // calculate the percentage of benefits based on days
                        if ($contract_days >= $FTE_Days) { $FTE_Benefits_Percentage = 1; }
                        else { $FTE_Benefits_Percentage = ($contract_days / $FTE_Days); }

                        // if percentage is <= 50%; set to 0
                        if ($FTE_Benefits_Percentage < 0.5) { $FTE_Benefits_Percentage = 0; }

                        // EMPLOYEE COSTS
                        $getRates = mysqli_prepare($conn, "SELECT * FROM global_expenses WHERE period_id=?");
                        mysqli_stmt_bind_param($getRates, "i", $period);
                        if (mysqli_stmt_execute($getRates))
                        {
                            $getRatesResult = mysqli_stmt_get_result($getRates);
                            if (mysqli_num_rows($getRatesResult) > 0) // rates for current period exist
                            {
                                $rates = mysqli_fetch_array($getRatesResult);

                                $project_salary = $daily_rate * $project_days;

                                $FICA_Cost = $project_salary * $rates["FICA"];

                                if ($wrs == 1) { $WRS_Cost = $project_salary * $rates["wrs_rate"]; }
                                else { $WRS_Cost = 0; }

                                if ($contract_days != 0)
                                {
                                    if ($health == 1) { $Health_Cost = ($rates["health_family"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                    else if ($health == 2) { $Health_Cost = ($rates["health_single"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                    else { $Health_Cost = 0; }
                                }
                                else { $Health_Cost = 0; }
    
                                if ($contract_days != 0)
                                {
                                    if ($dental == 1) { $Dental_Cost = ($rates["dental_family"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                    else if ($dental == 2) { $Dental_Cost = ($rates["dental_single"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                    else { $Dental_Cost = 0; }
                                }
                                else { $Dental_Cost = 0; }
    
                                if ($contract_days != 0) { $LTD_Cost = ($project_salary / 100) * ($rates["LTD"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                else { $LTD_Cost = 0; }
    
                                if ($contract_days != 0) { $Life_Cost = (($project_salary / 1000) * ($rates["life"] * 12 * ($project_days / $contract_days)) * 0.2); }
                                else { $Life_Cost = 0; }

                                $project_benefits = $FICA_Cost + $WRS_Cost + $Health_Cost + $Dental_Cost + $LTD_Cost + $Life_Cost;

                                $project_compensation = $project_salary + $project_benefits;
                            }
                            else // no rates for the current period exist; default to 0
                            {
                                $project_salary = $daily_rate * $project_days;
                                $FICA_Cost = $WRS_Cost = $Health_Cost = $Dental_Cost = $LTD_Cost = $Life_Cost = 0;
                                $project_benefits = $FICA_Cost + $WRS_Cost + $Health_Cost + $Dental_Cost + $LTD_Cost + $Life_Cost;
                                $project_compensation = $project_salary + $project_benefits;
                            }
                        }
                    }
                }
            }
        }

        // return the employee's total project compensation
        return $project_compensation;
    }

    /** function to get the compensation of test employees if the test employee was selected to include their costs */
    function getTestProjectEmployeesCompensation($conn, $code, $period)
    {
        // initialize the total compensation
        $total_compensation = 0; // assume $0.00

        // get the FTE days value
        $FTE_Days = getFTEDays($conn);

        // get total compensation for test employees, only if the test employee was selected to include their costs
        $getTestEmployees = mysqli_prepare($conn, "SELECT * FROM project_employees_misc WHERE project_code=? AND period_id=? AND costs_inclusion=1");
        mysqli_stmt_bind_param($getTestEmployees, "si", $code, $period);
        if (mysqli_stmt_execute($getTestEmployees))
        {
            $getTestEmployeesResults = mysqli_stmt_get_result($getTestEmployees);
            if (mysqli_num_rows($getTestEmployeesResults) > 0) // test employees found to include costs
            {
                while ($employee = mysqli_fetch_array($getTestEmployeesResults))
                {
                    // store test employee data locally
                    $yearly_rate = $employee["yearly_rate"];
                    $project_days = $employee["project_days"];
                    $contract_days = $employee["project_days"];
                    $health = $employee["health_insurance"];
                    $dental = $employee["dental_insurance"];
                    $wrs = $employee["wrs_eligible"];

                    // calculate the employee's daily rate
                    if ($contract_days != 0) { $daily_rate = $yearly_rate / $contract_days; }
                    else { $daily_rate = 0; }

                    // calculate the percentage of benefits based on days
                    if ($contract_days >= $FTE_Days) { $FTE_Benefits_Percentage = 1; }
                    else { $FTE_Benefits_Percentage = ($contract_days / $FTE_Days); }

                    // if percentage is <= 50%; set to 0
                    if ($FTE_Benefits_Percentage < 0.5) { $FTE_Benefits_Percentage = 0; }

                    // EMPLOYEE COSTS
                    $getRates = mysqli_prepare($conn, "SELECT * FROM global_expenses WHERE period_id=?");
                    mysqli_stmt_bind_param($getRates, "i", $period);
                    if (mysqli_stmt_execute($getRates))
                    {
                        $getRatesResult = mysqli_stmt_get_result($getRates);
                        if (mysqli_num_rows($getRatesResult) > 0) // rates for current period exist
                        {
                            $rates = mysqli_fetch_array($getRatesResult);

                            $project_salary = $daily_rate * $project_days;

                            $FICA_Cost = $project_salary * $rates["FICA"];

                            if ($wrs == 1) { $WRS_Cost = $project_salary * $rates["wrs_rate"]; }
                            else { $WRS_Cost = 0; }

                            if ($contract_days != 0)
                            {
                                if ($health == 1) { $Health_Cost = ($rates["health_family"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                else if ($health == 2) { $Health_Cost = ($rates["health_single"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                else { $Health_Cost = 0; }
                            }
                            else { $Health_Cost = 0; }

                            if ($contract_days != 0)
                            {
                                if ($dental == 1) { $Dental_Cost = ($rates["dental_family"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                else if ($dental == 2) { $Dental_Cost = ($rates["dental_single"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                                else { $Dental_Cost = 0; }
                            }
                            else { $Dental_Cost = 0; }

                            if ($contract_days != 0) { $LTD_Cost = ($project_salary / 100) * ($rates["LTD"] * $FTE_Benefits_Percentage * ($project_days / $contract_days)); }
                            else { $LTD_Cost = 0; }

                            if ($contract_days != 0) { $Life_Cost = (($project_salary / 1000) * ($rates["life"] * 12 * ($project_days / $contract_days)) * 0.2); }
                            else { $Life_Cost = 0; }

                            $project_benefits = $FICA_Cost + $WRS_Cost + $Health_Cost + $Dental_Cost + $LTD_Cost + $Life_Cost;

                            $total_compensation += ($project_salary + $project_benefits);
                        }
                        else // no rates for the current period exist; default to 0
                        {
                            $project_salary = $daily_rate * $project_days;
                            $FICA_Cost = $WRS_Cost = $Health_Cost = $Dental_Cost = $LTD_Cost = $Life_Cost = 0;
                            $project_benefits = $FICA_Cost + $WRS_Cost + $Health_Cost + $Dental_Cost + $LTD_Cost + $Life_Cost;
                            $total_compensation += ($project_salary + $project_benefits);
                        }
                    }
                }
            }
        }

        // return the total compensation for all test employees within the project with cost inclusion enabled
        return $total_compensation;
    }

    /** function to get an array of the ID of employees within the provided project */
    function getProjectEmployees($conn, $code, $period_id)
    {
        $employees = [];
        $getProjectEmployees = mysqli_prepare($conn, "SELECT DISTINCT employee_id FROM project_employees WHERE project_code=? AND period_id=?");
        mysqli_stmt_bind_param($getProjectEmployees, "si", $code, $period_id);
        if (mysqli_stmt_execute($getProjectEmployees))
        {
            $getProjectEmployeesResults = mysqli_stmt_get_result($getProjectEmployees);
            if (mysqli_num_rows($getProjectEmployeesResults) > 0)
            {
                while ($employee = mysqli_fetch_array($getProjectEmployeesResults)) { $employees[] = $employee["employee_id"]; }
            }
        }
        return $employees;
    }

    /** function to update the timestamp for when the project was last updated */
    function updateProjectEditTimestamp($conn, $code)
    {
        // get the time we updated the project (current timestamp)
        date_default_timezone_set("America/Chicago");
        $timestamp = date("Y-m-d H:i:s");
        $updateTime = mysqli_prepare($conn, "UPDATE projects SET updated=? WHERE code=?");
        mysqli_stmt_bind_param($updateTime, "ss", $timestamp, $code);
        mysqli_stmt_execute($updateTime);
    }

    /** function to clear out all supervision costs in the period provided if their project has supervision costs disabled */
    function clearSupervisionCosts($conn, $period)
    {
        // initialize variables
        $aidable_id = 33;
        $nonaidable_id = 34;

        // get a list of all projects that have supervision costs disabled
        $getProjects = mysqli_query($conn, "SELECT code FROM projects WHERE supervision_costs=0");
        if (mysqli_num_rows($getProjects) > 0) // projects found; continue
        {
            // for each project; clear supervision costs if the project had any
            while ($project = mysqli_fetch_array($getProjects))
            {
                // store the project code locally
                $code = $project["code"];

                // clear out supervision costs from the project for the period provided
                $clearSupervisionCosts = mysqli_prepare($conn, "DELETE FROM project_expenses WHERE period_id=? AND project_code=? AND (expense_id=? OR expense_id=?) AND auto=1");
                mysqli_stmt_bind_param($clearSupervisionCosts, "isii", $period, $code, $aidable_id, $nonaidable_id);
                mysqli_stmt_execute($clearSupervisionCosts);
            }
        }
    }

    /** function to clear out all indirect costs in the period provided if their project has indirect costs disabled */
    function clearIndirectCosts($conn, $period)
    {
        // initialize variables
        $indirect_id = 35;

        // get a list of all projects that have indirect costs disabled
        $getProjects = mysqli_query($conn, "SELECT code FROM projects WHERE indirect_costs=0");
        if (mysqli_num_rows($getProjects) > 0) // projects found; continue
        {
            // for each project; clear indirect costs if the project had any
            while ($project = mysqli_fetch_array($getProjects))
            {
                // store the project code locally
                $code = $project["code"];

                // clear out indirect costs from the project for the period provided
                $clearIndirectCosts = mysqli_prepare($conn, "DELETE FROM project_expenses WHERE period_id=? AND project_code=? AND expense_id=? AND auto=1");
                mysqli_stmt_bind_param($clearIndirectCosts, "isi", $period, $code, $indirect_id);
                mysqli_stmt_execute($clearIndirectCosts);
            }
        }
    }

    /** function to see if a period is editable */
    function isPeriodEditable($conn, $period_id)
    {
        $checkEditable = mysqli_prepare($conn, "SELECT editable FROM periods WHERE id=?");
        mysqli_stmt_bind_param($checkEditable, "i", $period_id);
        if (mysqli_stmt_execute($checkEditable))
        {
            $checkEditableResult = mysqli_stmt_get_result($checkEditable);
            if (mysqli_num_rows($checkEditableResult) > 0)
            {
                $editable = mysqli_fetch_array($checkEditableResult)["editable"];
                if ($editable == 1) { return true; } // return true as period is editable
            }
        }

        // return false if we have reached the end without returning
        return false;
    }

    /** function to check if an employee is active for the provided period */
    function isEmployeeActive($conn, $employee_id, $period_id)
    {
        $checkActive = mysqli_prepare($conn, "SELECT active FROM employee_compensation WHERE employee_id=? AND period_id=?");
        mysqli_stmt_bind_param($checkActive, "ii", $employee_id, $period_id);
        if (mysqli_stmt_execute($checkActive))
        {
            $checkActiveResult = mysqli_stmt_get_result($checkActive);
            if (mysqli_num_rows($checkActiveResult) > 0)
            {
                $active = mysqli_fetch_array($checkActiveResult)["active"];
                if ($active == 1) { return true; } // employee is active; return true
            }
        }

        // return false if we have reached the end without returning
        return false;
    }

    /** function to get the number of therapists that have students assigend to them in the period provided */
    function getTherapistsWithStudentsCount($conn, $period_id)
    {
        $count = 0;
        $getCount = mysqli_prepare($conn, "SELECT DISTINCT cl.employee_id FROM caseloads cl 
                                            JOIN cases c ON cl.id=c.caseload_id WHERE c.period_id=?");
        mysqli_stmt_bind_param($getCount, "i", $period_id);
        if (mysqli_stmt_execute($getCount))
        {
            $getCountResult = mysqli_stmt_get_result($getCount);
            $count = mysqli_num_rows($getCountResult);
        }
        return $count;
    }

    /** function to get the number of students in caseloads in the period provided */
    function getStudentsInCaseloadsCount($conn, $period_id)
    {
        $count = 0;
        $getCount = mysqli_prepare($conn, "SELECT DISTINCT student_id FROM cases WHERE period_id=?");
        mysqli_stmt_bind_param($getCount, "i", $period_id);
        if (mysqli_stmt_execute($getCount))
        {
            $getCountResult = mysqli_stmt_get_result($getCount);
            $count = mysqli_num_rows($getCountResult);
        }
        return $count;
    }

    /** function to get the total units of service for all caseloads in the provided period */
    function getTotalCaseloadUnits($conn, $period_id)
    {
        $total_units = 0;
        $getCaseloads = mysqli_prepare($conn, "SELECT id, extra_evaluations, extra_ieps FROM cases WHERE period_id=?");
        mysqli_stmt_bind_param($getCaseloads, "i", $period_id);
        if (mysqli_stmt_execute($getCaseloads))
        {
            $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
            if (mysqli_num_rows($getCaseloadsResults) > 0) // cases found
            {
                while ($caseload = mysqli_fetch_array($getCaseloadsResults))
                {
                    // store caseload details locally
                    $case_id = $caseload["id"];
                    $extra_evals = $caseload["extra_evaluations"];
                    $extra_ieps = $caseload["extra_ieps"];

                    // get the end of year units of service (prorated based on changes)
                    $EOY_units = getProratedUOS($conn, $case_id);

                    // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                    $additional_units = 0;
                    if (is_numeric($extra_ieps) && $extra_ieps > 0) { $additional_units += (12 * $extra_ieps); }
                    if (is_numeric($extra_evals) && $extra_evals > 0) { $additional_units += (16 * $extra_evals); }
                    $EOY_units += $additional_units;

                    // add this caseloads EOY units to the total
                    $total_units += $EOY_units;
                }
            }
        }
        return $total_units;
    }

    /** function to get the total units of service for all caseloads in the provided period */
    function getCaseloadUnits($conn, $caseload_id, $period_id)
    {
        // initialize total units
        $total_units = 0;

        if (verifyPeriod($conn, $period_id))
        {
            if (verifyCaseload($conn, $caseload_id))
            {
                // get caseload category
                $category_id = getCaseloadCategory($conn, $caseload_id);

                if (verifyCaseloadCategory($conn, $category_id))
                {
                    // get category details
                    $category_details = getCaseloadCategorySettings($conn, $category_id);

                    ///////////////////////////////////////////////////////////////////////////////////////////
                    //
                    //  Classrooms
                    //
                    ///////////////////////////////////////////////////////////////////////////////////////////
                    if ($category_details["is_classroom"] == 1)
                    {
                        $getCases = mysqli_prepare($conn, "SELECT id, membership_days FROM cases WHERE period_id=? AND caseload_id=?");
                        mysqli_stmt_bind_param($getCases, "ii", $period_id, $caseload_id);
                        if (mysqli_stmt_execute($getCases))
                        {
                            $getCasesResults = mysqli_stmt_get_result($getCases);
                            if (mysqli_num_rows($getCasesResults) > 0) // cases found
                            {
                                while ($caseload = mysqli_fetch_array($getCasesResults))
                                {
                                    // store caseload details locally
                                    $case_id = $caseload["id"];
                                    $days = $caseload["membership_days"];

                                    // add days to the total
                                    $total_units += $days;
                                }
                            }
                        }
                    }
                    ///////////////////////////////////////////////////////////////////////////////////////////
                    //
                    //  Units Of Service
                    //
                    ///////////////////////////////////////////////////////////////////////////////////////////
                    else if ($category_details["uos_enabled"] == 1)
                    {
                        $getCases = mysqli_prepare($conn, "SELECT id, extra_evaluations, extra_ieps FROM cases WHERE period_id=? AND caseload_id=?");
                        mysqli_stmt_bind_param($getCases, "ii", $period_id, $caseload_id);
                        if (mysqli_stmt_execute($getCases))
                        {
                            $getCasesResults = mysqli_stmt_get_result($getCases);
                            if (mysqli_num_rows($getCasesResults) > 0) // cases found
                            {
                                while ($caseload = mysqli_fetch_array($getCasesResults))
                                {
                                    // store caseload details locally
                                    $case_id = $caseload["id"];
                                    $extra_evals = $caseload["extra_evaluations"];
                                    $extra_ieps = $caseload["extra_ieps"];

                                    // get the end of year units of service (prorated based on changes)
                                    $EOY_units = getProratedUOS($conn, $case_id);

                                    // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                                    $additional_units = 0;
                                    if (is_numeric($extra_ieps) && $extra_ieps > 0) { $additional_units += (12 * $extra_ieps); }
                                    if (is_numeric($extra_evals) && $extra_evals > 0) { $additional_units += (16 * $extra_evals); }
                                    $EOY_units += $additional_units;

                                    // add this caseloads EOY units to the total
                                    $total_units += $EOY_units;
                                }
                            }
                        }
                    }
                }
            }
        }

        // return total units
        return $total_units;
    }

    /** function to check if an existing student based on name and date of birth */
    function checkForStudent($conn, $fname, $lname, $dob)
    {
        // initialize the student ID
        $student_id = -1;

        // trim the first and last name
        $fname = trim($fname);
        $lname = trim($lname);

        // if date of birth is set (required field), check for the student
        if (isset($dob) && $dob != null)
        {
            // convert date of birth date to database format
            $dob = date("Y-m-d", strtotime($dob));

            // check all existing students to see if student matches exactly only fname, lname, dob
            $checkExistingStudent = mysqli_prepare($conn, "SELECT id FROM caseload_students WHERE fname=? AND lname=? AND date_of_birth=?");
            mysqli_stmt_bind_param($checkExistingStudent, "sss", $fname, $lname, $dob);
            if (mysqli_stmt_execute($checkExistingStudent))
            {
                $checkExistingStudentResult = mysqli_stmt_get_result($checkExistingStudent);
                if (mysqli_num_rows($checkExistingStudentResult) > 0) // student exists
                {
                    // store student ID locally
                    $student_id = mysqli_fetch_array($checkExistingStudentResult)["id"];
                }
            }
        }

        // return the student ID
        return $student_id;
    }

    /** function to get the student ID (if exists) for the caseload */
    function checkCaseloadStudent($conn, $case_id)
    {
        // initialize the student ID
        $student_id = -1;

        // check to see if the caseload has an existing student or a placeholder student
        $checkStudent = mysqli_prepare($conn, "SELECT student_id FROM cases WHERE id=?");
        mysqli_stmt_bind_param($checkStudent, "i", $case_id);
        if (mysqli_stmt_execute($checkStudent))
        {
            $checkStudentResult = mysqli_stmt_get_result($checkStudent);
            if (mysqli_num_rows($checkStudentResult) > 0)
            {
                $student_id = mysqli_fetch_array($checkStudentResult)["student_id"];
                if (!isset($student_id) || $student_id == null) { $student_id = -1; }
            }
        }

        // return the student ID
        return $student_id;
    }

    /** function to get the number of days in the caseload term for the given period */
    function getDaysInCaseloadTerm($conn, $period_id)
    {
        $days_in_term = 0; 
        $getTermDates = mysqli_prepare($conn, "SELECT caseload_term_start, caseload_term_end FROM periods WHERE id=?");
        mysqli_stmt_bind_param($getTermDates, "i", $period_id);
        if (mysqli_stmt_execute($getTermDates))
        {
            $getTermDatesResult = mysqli_stmt_get_result($getTermDates);
            if (mysqli_num_rows($getTermDatesResult) > 0)
            {
                $term = mysqli_fetch_array($getTermDatesResult);
                $start = $term["caseload_term_start"];
                $end = $term["caseload_term_end"];
                $days_in_term = getDaysBetween($end, $start);
            }
        }
        return $days_in_term;
    }

    /** function to verify if a school exists */
    function verifySchool($conn, $school_id)
    {
        $checkSchool = mysqli_prepare($conn, "SELECT id FROM schools WHERE id=?");
        mysqli_stmt_bind_param($checkSchool, "i", $school_id);
        if (mysqli_stmt_execute($checkSchool))
        {
            $checkSchoolResult = mysqli_stmt_get_result($checkSchool);
            if (mysqli_num_rows($checkSchoolResult) > 0)
            {
                // return true, school exists
                return true;
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to get the name of a school based on it's ID */
    function getSchoolName($conn, $school_id)
    {
        $name = "";
        if ($school_id == -1)
        {
            $name = "Other";
        }
        else if ($school_id == -2)
        {
            $name = "External Tutor";
        }
        else if ($school_id == -3)
        {
            $name = "Home";
        }
        else if ($school_id > 0)
        {
            $getSchool = mysqli_prepare($conn, "SELECT name FROM schools WHERE id=?");
            mysqli_stmt_bind_param($getSchool, "i", $school_id);
            if (mysqli_stmt_execute($getSchool))
            {
                $getSchoolResult = mysqli_stmt_get_result($getSchool);
                if (mysqli_num_rows($getSchoolResult) > 0)
                {
                    $name = mysqli_fetch_array($getSchoolResult)["name"];
                }
            }
        }
        return $name;
    }

    /** function to verify the caseload category exists */
    function verifyCaseloadCategory($conn, $category_id)
    {
        $checkCategory = mysqli_prepare($conn, "SELECT id FROM caseload_categories WHERE id=?");
        mysqli_stmt_bind_param($checkCategory, "i", $category_id);
        if (mysqli_stmt_execute($checkCategory))
        {
            $checkCategoryResult = mysqli_stmt_get_result($checkCategory);
            if (mysqli_num_rows($checkCategoryResult) > 0) // category exists; return true
            {
                return true;
            }
        }
        
        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to verify the subcategory exists and is valid for the category provided */
    function verifyCaseloadSubcategory($conn, $category_id, $subcategory_id)
    {
        if ($subcategory_id == null) { return true; } // subcategory not provided; do not check; return true
        else
        {
            $checkSubcategory = mysqli_prepare($conn, "SELECT id FROM caseload_subcategories WHERE id=? AND category_id=?");
            mysqli_stmt_bind_param($checkSubcategory, "ii", $subcategory_id, $category_id);
            if (mysqli_stmt_execute($checkSubcategory))
            {
                $checkSubcategoryResult = mysqli_stmt_get_result($checkSubcategory);
                if (mysqli_num_rows($checkSubcategoryResult) > 0) // subcategory exists; return true
                {
                    return true;
                }
            }
        }
        
        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to get the name of the category based on it's ID */
    function getCaseloadCategoryName($conn, $category_id)
    {
        $name = "";
        $getCategoryName = mysqli_prepare($conn, "SELECT name FROM caseload_categories WHERE id=?");
        mysqli_stmt_bind_param($getCategoryName, "i", $category_id);
        if (mysqli_stmt_execute($getCategoryName))
        {
            $getCategoryNameResult = mysqli_stmt_get_result($getCategoryName);
            if (mysqli_num_rows($getCategoryNameResult) > 0) // category exists; return true
            {
                $name = mysqli_fetch_array($getCategoryNameResult)["name"];
            }
        }
        return $name;
    }

    /** function to get the subcategory ID for the caseload */
    function getCaseloadSubcategoryName($conn, $subcategory_id)
    {
        $name = "";
        $getSubcategoryName = mysqli_prepare($conn, "SELECT name FROM caseload_subcategories WHERE id=?");
        mysqli_stmt_bind_param($getSubcategoryName, "i", $subcategory_id);
        if (mysqli_stmt_execute($getSubcategoryName))
        {
            $getSubcategoryNameResult = mysqli_stmt_get_result($getSubcategoryName);
            if (mysqli_num_rows($getSubcategoryNameResult) > 0) // subcategory exists; return true
            {
                $name = mysqli_fetch_array($getSubcategoryNameResult)["name"];
            }
        }
        return $name;
    }

    /** function to verify the user has access to this caseload */
    function verifyUserCaseload($conn, $caseload_id)
    {
        if (isset($_SESSION["id"]))
        {
            if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
            {
                // user can view all caseloads; return true
                return true; 
            }
            else if (checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED"))
            {
                // regular caseload
                if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
                {
                    // user can only view caseloads assigned to them
                    $checkCaseloadAssignment = mysqli_prepare($conn, "SELECT id FROM caseloads WHERE id=? AND employee_id=?");
                    mysqli_stmt_bind_param($checkCaseloadAssignment, "ii", $caseload_id, $_SESSION["id"]);
                    if (mysqli_stmt_execute($checkCaseloadAssignment))
                    {
                        $checkCaseloadAssignmentResult = mysqli_stmt_get_result($checkCaseloadAssignment);
                        if (mysqli_num_rows($checkCaseloadAssignmentResult) == 1) // caseload is assigned to current user; return true
                        {
                            return true;
                        }
                        else // caseload is not assigned to the current user; check to see if the user is a coordinator for the caseload
                        {
                            if (isCoordinatorAssigned($conn, $_SESSION["id"], $caseload_id))
                            {
                                return true;
                            }
                        }
                    }
                }
                // demo caseloads
                else if ($caseload_id < 0)
                {
                    // get category ID based on caseload ID
                    $category_id = abs($caseload_id);
                    
                    // verify category exists
                    if (verifyCaseloadCategory($conn, $category_id)) // category exists; return false, unlocked
                    {
                        return true;
                    }
                }
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to get the therapist ID for the caseload */
    function getCaseloadTherapist($conn, $caseload_id)
    {
        $therapist_id = null;
        $getTherapistID = mysqli_prepare($conn, "SELECT employee_id FROM caseloads WHERE id=?");
        mysqli_stmt_bind_param($getTherapistID, "i", $caseload_id);
        if (mysqli_stmt_execute($getTherapistID))
        {
            $getTherapistIDResult = mysqli_stmt_get_result($getTherapistID);
            if (mysqli_num_rows($getTherapistIDResult) > 0)
            {
                $caseload_details = mysqli_fetch_array($getTherapistIDResult);
                $therapist_id = $caseload_details["employee_id"];
            }
        }
        return $therapist_id;
    }

    /** function to get the category ID for the caseload */
    function getCaseloadCategory($conn, $caseload_id)
    {
        $category_id = null;
        $getCategoryID = mysqli_prepare($conn, "SELECT category_id FROM caseloads WHERE id=?");
        mysqli_stmt_bind_param($getCategoryID, "i", $caseload_id);
        if (mysqli_stmt_execute($getCategoryID))
        {
            $getCategoryIDResult = mysqli_stmt_get_result($getCategoryID);
            if (mysqli_num_rows($getCategoryIDResult) > 0)
            {
                $caseload_details = mysqli_fetch_array($getCategoryIDResult);
                $category_id = $caseload_details["category_id"];
            }
        }
        return $category_id;
    }

    /** function to get the subcategory ID for the caseload */
    function getCaseloadSubcategory($conn, $caseload_id)
    {
        $subcategory_id = null;
        $getSubcategoryID = mysqli_prepare($conn, "SELECT subcategory_id FROM caseloads WHERE id=?");
        mysqli_stmt_bind_param($getSubcategoryID, "i", $caseload_id);
        if (mysqli_stmt_execute($getSubcategoryID))
        {
            $getSubcategoryIDResult = mysqli_stmt_get_result($getSubcategoryID);
            if (mysqli_num_rows($getSubcategoryIDResult) > 0)
            {
                $caseload_details = mysqli_fetch_array($getSubcategoryIDResult);
                $subcategory_id = $caseload_details["subcategory_id"];
            }
        }
        return $subcategory_id;
    }

    /** function to verify a caseload exists */
    function verifyCaseload($conn, $caseload_id)
    {
        // regular caseload
        if ($caseload_id > 0)
        {
            $checkCaseload = mysqli_prepare($conn, "SELECT id FROM caseloads WHERE id=?");
            mysqli_stmt_bind_param($checkCaseload, "i", $caseload_id);
            if (mysqli_stmt_execute($checkCaseload))
            {
                $checkCaseloadResult = mysqli_stmt_get_result($checkCaseload);
                if (mysqli_num_rows($checkCaseloadResult) > 0) // caseload exists; return true
                {
                    return true;
                }
            }
        }
        // demo caseloads
        else if ($caseload_id < 0)
        {
            // get category ID based on caseload ID
            $category_id = abs($caseload_id);
            
            // verify category exists
            if (verifyCaseloadCategory($conn, $category_id)) // category exists; return true
            {
                return true;
            }
        }
        
        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to get the caseload term based on period */
    function getCaseloadTerm($conn, $period_id)
    {
        $term = [];
        $term["start"] = "";
        $term["end"] = "";
        if (verifyPeriod($conn, $period_id))
        {
            $getTerm = mysqli_prepare($conn, "SELECT caseload_term_start, caseload_term_end FROM periods WHERE id=?");
            mysqli_stmt_bind_param($getTerm, "i", $period_id);
            if (mysqli_stmt_execute($getTerm))
            {
                $getTermResult = mysqli_stmt_get_result($getTerm);
                if (mysqli_num_rows($getTermResult) > 0)
                {
                    $caseloadTerm = mysqli_fetch_assoc($getTermResult);
                    $term["start"] = $caseloadTerm["caseload_term_start"];
                    $term["end"] = $caseloadTerm["caseload_term_end"];
                }
            }
        }
        return $term;
    }

    /** function to get the name of a caseload */
    function getCaseloadDisplayName($conn, $caseload_id)
    {
        // initialize variable to store caseload name
        $caseload_name = "";

        if ($caseload_id >= 0)
        {
            // get caseload details
            $therapist_id = getCaseloadTherapist($conn, $caseload_id);
            if (isset($therapist_id) && $therapist_id != null && $therapist_id != 0)
            {
                $therapist_name = getUserDisplayName($conn, $therapist_id);
                $category_id = getCaseloadCategory($conn, $caseload_id);
                $category_name = getCaseloadCategoryName($conn, $category_id);
                $subcategory_id = getCaseloadSubcategory($conn, $caseload_id);
                $subcategory_name = getCaseloadSubcategoryName($conn, $subcategory_id);

                // build the caseload name
                $caseload_name .= $therapist_name;
                if ($category_name <> "")
                {
                    $caseload_name .= " - ".$category_name;
                    if ($subcategory_name <> "")
                    {
                        $caseload_name .= " (".$subcategory_name.")";
                    }
                }
            }
            else { $caseload_name = "Unknown"; }
        }
        else
        {
            // get category ID
            $category_id = abs($caseload_id);

            // get category name
            $category_name = getCaseloadCategoryName($conn, $category_id);

            // build caseload name
            $caseload_name = $category_name." (DEMO)";
        }

        // return the name of the caseload
        return $caseload_name;
    }

    /** function to verify an employee title exists */
    function verifyTitle($conn, $title_id)
    {
        $checkTitle = mysqli_prepare($conn, "SELECT id FROM employee_titles WHERE id=?");
        mysqli_stmt_bind_param($checkTitle, "i", $title_id);
        if (mysqli_stmt_execute($checkTitle))
        {
            $checkTitleResult = mysqli_stmt_get_result($checkTitle);
            if (mysqli_num_rows($checkTitleResult) > 0) // title exists; return true
            {
                return true;
            }
        }
        
        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to get the title name based on ID */
    function getTitleName($conn, $title_id)
    {
        $name = "";
        $getTitleName = mysqli_prepare($conn, "SELECT name FROM employee_titles WHERE id=?");
        mysqli_stmt_bind_param($getTitleName, "i", $title_id);
        if (mysqli_stmt_execute($getTitleName))
        {
            $getTitleNameResult = mysqli_stmt_get_result($getTitleName);
            if (mysqli_num_rows($getTitleNameResult) > 0) // title exists; return name
            {
                $name = mysqli_fetch_array($getTitleNameResult)["name"];
            }
        }
        return $name;
    }

    /** function to get the grant indirect rate */
    function getGrantIndirectRate($conn)
    {
        $grant_indirect_rate = 0;
        $getGrantIndirectRate = mysqli_query($conn, "SELECT grant_indirect_rate FROM settings WHERE id=1");
        if (mysqli_num_rows($getGrantIndirectRate) > 0)
        {
            $grant_indirect_rate = mysqli_fetch_array($getGrantIndirectRate)["grant_indirect_rate"];
        }
        return $grant_indirect_rate;
    }

    /** function to get the grant indirect rate */
    function getDPIGrantIndirectRate($conn, $period_id)
    {
        $grant_indirect_rate = 0;
        $getGrantIndirectRate = mysqli_prepare($conn, "SELECT dpi_grant_rate FROM global_expenses WHERE period_id=?");
        mysqli_stmt_bind_param($getGrantIndirectRate, "i", $period_id);
        if (mysqli_stmt_execute($getGrantIndirectRate))
        {
            $getGrantIndirectRateResult = mysqli_stmt_get_result($getGrantIndirectRate);
            if (mysqli_num_rows($getGrantIndirectRateResult) > 0)
            {
                $grant_indirect_rate = mysqli_fetch_array($getGrantIndirectRateResult)["dpi_grant_rate"];
            }
        }
        return $grant_indirect_rate;
    }
    
    /** function to check if the project expense is autocalculated */
    function isExpenseAutocaclulated($conn, $project_expense_id)
    {
        $checkAuto = mysqli_prepare($conn, "SELECT auto FROM project_expenses WHERE id=?");
        mysqli_stmt_bind_param($checkAuto, "i", $project_expense_id);
        if (mysqli_stmt_execute($checkAuto))
        {
            $checkAutoResult = mysqli_stmt_get_result($checkAuto);
            if (mysqli_num_rows($checkAutoResult) > 0)
            {
                $auto = mysqli_fetch_array($checkAutoResult)["auto"];
                if ($auto == 1) { return true; }
            }
        }
        
        // if we've reached the end of the function without returning, return false
        return false;
    }

    /** function to get a period's name */
    function getPeriodName($conn, $period_id)
    {
        $period_name = "";
        if (verifyPeriod($conn, $period_id))
        {
            $getPeriodName = mysqli_prepare($conn, "SELECT name FROM periods WHERE id=?");
            mysqli_stmt_bind_param($getPeriodName, "i", $period_id);
            if (mysqli_stmt_execute($getPeriodName))
            {
                $getPeriodNameResult = mysqli_stmt_get_result($getPeriodName);
                if (mysqli_num_rows($getPeriodNameResult) > 0) { $period_name = mysqli_fetch_array($getPeriodNameResult)["name"]; }
                else { $period_name = "PERIOD_DOES_NOT_EXIST"; }
            }
            else { $period_name = "PERIOD_DOES_NOT_EXIST"; }
        }
        else { $period_name = "PERIOD_DOES_NOT_EXIST"; }
        return $period_name;
    }

    /** function to verify a dismissal reasoning */
    function verifyDismissalReasoning($conn, $reason_id)
    {
        if ($reason_id == null) { return true; } // all null reasoning
        {
            $checkReason = mysqli_prepare($conn, "SELECT id FROM caseload_dismissal_reasonings WHERE id=?");
            mysqli_stmt_bind_param($checkReason, "i", $reason_id);
            if (mysqli_stmt_execute($checkReason))
            {
                $checkReasonResult = mysqli_stmt_get_result($checkReason);
                if (mysqli_num_rows($checkReasonResult) > 0) // reason exists; return true
                {
                    return true;
                }
            }
        }
        
        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to check if the caseload category is classroom based */
    function isCaseloadClassroom($conn, $caseload_id)
    {
        // verify caseload exists
        if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
        {
            // get caseload category
            $category_id = getCaseloadCategory($conn, $caseload_id);

            // get classroom-based setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT is_classroom FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $isClassroom = mysqli_fetch_array($checkSettingResult)["is_classroom"];
                    if ($isClassroom == 1) { return true; } else { return false; }
                }
            }
        }
        // demo caseload
        else if ($caseload_id < 0)
        {
            // get category ID for demo caseload 
            $category_id = abs($caseload_id);

            // get classroom-based setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT is_classroom FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $isClassroom = mysqli_fetch_array($checkSettingResult)["is_classroom"];
                    if ($isClassroom == 1) { return true; } else { return false; }
                }
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to check if frequency is enabled for the caseload */
    function isCaseloadFrequencyEnabled($conn, $caseload_id)
    {
        // verify caseload exists
        if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
        {
            // get caseload category
            $category_id = getCaseloadCategory($conn, $caseload_id);

            // get frequency setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT frequency_enabled FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $frequencyEnabled = mysqli_fetch_array($checkSettingResult)["frequency_enabled"];
                    if ($frequencyEnabled == 1) { return true; } else { return false; }
                }
            }
        }
        // demo caseload
        else if ($caseload_id < 0)
        {
            // get category ID for demo caseload 
            $category_id = abs($caseload_id);

            // get frequency setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT frequency_enabled FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $frequencyEnabled = mysqli_fetch_array($checkSettingResult)["frequency_enabled"];
                    if ($frequencyEnabled == 1) { return true; } else { return false; }
                }
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to check if UOS is enabled for the caseload */
    function isCaseloadUOSEnabled($conn, $caseload_id)
    {
        // verify caseload exists
        if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
        {
            // get caseload category
            $category_id = getCaseloadCategory($conn, $caseload_id);

            // get uos_enabled setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT uos_enabled FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $uosEnabled = mysqli_fetch_array($checkSettingResult)["uos_enabled"];
                    if ($uosEnabled == 1) { return true; } else { return false; }
                }
            }
        }
        // demo caseload
        else if ($caseload_id < 0)
        {
            // get category ID for demo caseload 
            $category_id = abs($caseload_id);

            // get uos_enabled setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT uos_enabled FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $uosEnabled = mysqli_fetch_array($checkSettingResult)["uos_enabled"];
                    if ($uosEnabled == 1) { return true; } else { return false; }
                }
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to check if UOS is required for the caseload */
    function isCaseloadUOSRequired($conn, $caseload_id)
    {
        // verify caseload exists
        if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
        {
            // get caseload category
            $category_id = getCaseloadCategory($conn, $caseload_id);

            // get uos_required setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT uos_required FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $uosRequired = mysqli_fetch_array($checkSettingResult)["uos_required"];
                    if ($uosRequired == 1) { return true; } else { return false; }
                }
            }
        }
        // demo caseload
        else if ($caseload_id < 0)
        {
            // get category ID for demo caseload 
            $category_id = abs($caseload_id);

            // get uos_required setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT uos_required FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $uosRequired = mysqli_fetch_array($checkSettingResult)["uos_required"];
                    if ($uosRequired == 1) { return true; } else { return false; }
                }
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to check if extra IEPs is enabled for the caseload */
    function isCaseloadExtraIEPSEnabled($conn, $caseload_id)
    {
        // verify caseload exists
        if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
        {
            // get caseload category
            $category_id = getCaseloadCategory($conn, $caseload_id);

            // get extra_ieps setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT extra_ieps_enabled FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $uosEnabled = mysqli_fetch_array($checkSettingResult)["extra_ieps_enabled"];
                    if ($uosEnabled == 1) { return true; } else { return false; }
                }
            }
        }
        // demo caseload
        else if ($caseload_id < 0)
        {
            // get category ID for demo caseload 
            $category_id = abs($caseload_id);

            // get extra_ieps setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT extra_ieps_enabled FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $extraIEPs = mysqli_fetch_array($checkSettingResult)["extra_ieps_enabled"];
                    if ($extraIEPs == 1) { return true; } else { return false; }
                }
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to check if extra evaluations is enabled for the caseload */
    function isCaseloadExtraEvalsEnabled($conn, $caseload_id)
    {
        // verify caseload exists
        if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
        {
            // get caseload category
            $category_id = getCaseloadCategory($conn, $caseload_id);

            // get extra_evals setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT extra_evals_enabled FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $extraEvals = mysqli_fetch_array($checkSettingResult)["extra_evals_enabled"];
                    if ($extraEvals == 1) { return true; } else { return false; }
                }
            }
        }
        // demo caseload
        else if ($caseload_id < 0)
        {
            // get category ID for demo caseload 
            $category_id = abs($caseload_id);

            // get extra_evals_enabled setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT extra_evals_enabled FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $extraEvals = mysqli_fetch_array($checkSettingResult)["extra_evals_enabled"];
                    if ($extraEvals == 1) { return true; } else { return false; }
                }
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to check if the caseload allows assistants */
    function isCaseloadAssistantsEnabled($conn, $caseload_id)
    {
        // verify caseload exists
        if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
        {
            // get caseload category
            $category_id = getCaseloadCategory($conn, $caseload_id);

            // get allow_assistants setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT allow_assistants FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $allowAssistants = mysqli_fetch_array($checkSettingResult)["allow_assistants"];
                    if ($allowAssistants == 1) { return true; } else { return false; }
                }
            }
        }
        // demo caseload
        else if ($caseload_id < 0)
        {
            // get category ID for demo caseload 
            $category_id = abs($caseload_id);

            // get allow_assistants setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT allow_assistants FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $allowAssistants = mysqli_fetch_array($checkSettingResult)["allow_assistants"];
                    if ($allowAssistants == 1) { return true; } else { return false; }
                }
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to check if the caseload is medicaid based */
    function isCaseloadMedicaid($conn, $caseload_id)
    {
        // verify caseload exists
        if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
        {
            // get caseload category
            $category_id = getCaseloadCategory($conn, $caseload_id);

            // get extra_evals setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT medicaid FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $medicaid = mysqli_fetch_array($checkSettingResult)["medicaid"];
                    if ($medicaid == 1) { return true; } else { return false; }
                }
            }
        }
        // demo caseload
        else if ($caseload_id < 0)
        {
            // get category ID for demo caseload 
            $category_id = abs($caseload_id);

            // get medicaid setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT medicaid FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $medicaid = mysqli_fetch_array($checkSettingResult)["medicaid"];
                    if ($medicaid == 1) { return true; } else { return false; }
                }
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to get the caseload ID from a case */
    function getCaseloadID($conn, $case_id)
    {
        if (verifyCase($conn, $case_id))
        {
            $getCaseload = mysqli_prepare($conn, "SELECT caseload_id FROM cases WHERE id=?");
            mysqli_stmt_bind_param($getCaseload, "i", $case_id);
            if (mysqli_stmt_execute($getCaseload))
            {
                $getCaseloadResult = mysqli_stmt_get_result($getCaseload);
                if (mysqli_num_rows($getCaseloadResult) > 0)
                {
                    $caseload_id = mysqli_fetch_array($getCaseloadResult)["caseload_id"];
                    if (verifyCaseload($conn, $caseload_id)) { return $caseload_id; } else { return false; }
                }
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to get the ID of the title */
    function getTitleID($conn, $title_label)
    {
        $title_id = 0;
        $getTitleID = mysqli_prepare($conn, "SELECT id FROM employee_titles WHERE name=?");
        mysqli_stmt_bind_param($getTitleID, "s", $title_label);
        if (mysqli_stmt_execute($getTitleID))
        {
            $getTitleIDResult = mysqli_stmt_get_result($getTitleID);
            if (mysqli_num_rows($getTitleIDResult) > 0)
            {
                $title_id = mysqli_fetch_array($getTitleIDResult)["id"];
            }
        }
        return $title_id;
    }

    /** function to get the ID of the role */
    function getRoleID($conn, $role_label)
    {
        $role_id = 3;
        $getRoleID = mysqli_prepare($conn, "SELECT id FROM roles WHERE name=?");
        mysqli_stmt_bind_param($getRoleID, "s", $role_label);
        if (mysqli_stmt_execute($getRoleID))
        {
            $getRoleIDResult = mysqli_stmt_get_result($getRoleID);
            if (mysqli_num_rows($getRoleIDResult) > 0)
            {
                $role_id = mysqli_fetch_array($getRoleIDResult)["id"];
            }
        }
        return $role_id;
    }

    /** function to get the dismissal reasoning */
    function getDismissalReasoning($conn, $reasoning_id)
    {
        $reasoning = "";
        $getReasoning = mysqli_prepare($conn, "SELECT reason FROM caseload_dismissal_reasonings WHERE id=?");
        mysqli_stmt_bind_param($getReasoning, "i", $reasoning_id);
        if (mysqli_stmt_execute($getReasoning))
        {
            $getReasoningResult = mysqli_stmt_get_result($getReasoning);
            if (mysqli_num_rows($getReasoningResult) > 0)
            {
                $reasoning = mysqli_fetch_array($getReasoningResult)["reason"];
            }
        }
        return $reasoning;
    }

    /** function to verify the assistant ID */
    function verifyAssistant($conn, $assistant_id)
    {
        $checkAssistantID = mysqli_prepare($conn, "SELECT id FROM caseload_assistants WHERE id=?");
        mysqli_stmt_bind_param($checkAssistantID, "i", $assistant_id);
        if (mysqli_stmt_execute($checkAssistantID))
        {
            $checkAssistantIDResult = mysqli_stmt_get_result($checkAssistantID);
            if (mysqli_num_rows($checkAssistantIDResult) > 0) // assistant ID exists; return true
            {
                return true;
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to get the name of an assistant */
    function getAssistantName($conn, $assistant_id)
    {
        $name = "";
        if (verifyAssistant($conn, $assistant_id))
        {
            $getEmployeeID = mysqli_prepare($conn, "SELECT employee_id FROM caseload_assistants WHERE id=?");
            mysqli_stmt_bind_param($getEmployeeID, "i", $assistant_id);
            if (mysqli_stmt_execute($getEmployeeID))
            {
                $getEmployeeIDResult = mysqli_stmt_get_result($getEmployeeID);
                if (mysqli_num_rows($getEmployeeIDResult) > 0)
                {
                    $employee_id = mysqli_fetch_array($getEmployeeIDResult)["employee_id"];
                    $name = getEmployeeDisplayName($conn, $employee_id);
                }
            }
        }
        return $name;
    }

    /** function to get the category of an assistant */
    function getAssistantCategory($conn, $assistant_id)
    {
        $name = "";
        if (verifyAssistant($conn, $assistant_id))
        {
            $getCategoryID = mysqli_prepare($conn, "SELECT category_id FROM caseload_assistants WHERE id=?");
            mysqli_stmt_bind_param($getCategoryID, "i", $assistant_id);
            if (mysqli_stmt_execute($getCategoryID))
            {
                $getCategoryIDResult = mysqli_stmt_get_result($getCategoryID);
                if (mysqli_num_rows($getCategoryIDResult) > 0)
                {
                    $category_id = mysqli_fetch_array($getCategoryIDResult)["category_id"];
                    $name = getCaseloadCategoryName($conn, $category_id);
                }
            }
        }
        return $name;
    }

    /** function to get if days are enabled for a caseload */
    function isCaseloadDaysEnabled($conn, $caseload_id)
    {
        // verify caseload exists
        if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
        {
            // get caseload category
            $category_id = getCaseloadCategory($conn, $caseload_id);

            // get days setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT days FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $daysEnabled = mysqli_fetch_array($checkSettingResult)["days"];
                    if ($daysEnabled == 1) { return true; } else { return false; }
                }
            }
        }
        // demo caseload
        else if ($caseload_id < 0)
        {
            // get category ID for demo caseload 
            $category_id = abs($caseload_id);

            // get days setting based on the category
            $checkSetting = mysqli_prepare($conn, "SELECT days FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($checkSetting, "i", $category_id);
            if (mysqli_stmt_execute($checkSetting))
            {
                $checkSettingResult = mysqli_stmt_get_result($checkSetting);
                if (mysqli_num_rows($checkSettingResult) > 0)
                {
                    $daysEnabled = mysqli_fetch_array($checkSettingResult)["days"];
                    if ($daysEnabled == 1) { return true; } else { return false; }
                }
            }
        }

        // if we have reached the end of the function without returning, return false
        return false;
    }

    /** function to get teh name of a caseload classroom */
    function getCaseloadClassroomName($conn, $classroom_id)
    {
        $classroom_name = "";
        $getClassroomName = mysqli_prepare($conn, "SELECT name FROM caseload_classrooms WHERE id=?");
        mysqli_stmt_bind_param($getClassroomName, "i", $classroom_id);
        if (mysqli_stmt_execute($getClassroomName))
        {
            $getClassroomNameResult = mysqli_stmt_get_result($getClassroomName);
            if (mysqli_num_rows($getClassroomNameResult) > 0)
            {
                $classroom_name = mysqli_fetch_array($getClassroomNameResult)["name"];
            }
        }
        return $classroom_name;
    }

    /** function to get the status of a project */
    function getProjectStatus($conn, $code, $period_id)
    {
        $status = 0;
        if (verifyProject($conn, $code))
        {
            if (verifyPeriod($conn, $period_id))
            {
                $getStatus = mysqli_prepare($conn, "SELECT status FROM projects_status WHERE code=? AND period_id=?");
                mysqli_stmt_bind_param($getStatus, "si", $code, $period_id);
                if (mysqli_stmt_execute($getStatus))
                {
                    $getStatusResult = mysqli_stmt_get_result($getStatus);
                    if (mysqli_num_rows($getStatusResult) > 0)
                    {
                        $status = mysqli_fetch_array($getStatusResult)["status"];
                    }
                }
            }
        }
        return $status;
    }

    /** 
     *  function to fix projects for V3.14.0 
     *  -- function call removed in V3.14.2
    */
    function fixProjects($conn)
    {
        // for each period set each project's current status as active
        $getPeriods = mysqli_query($conn, "SELECT id FROM periods");
        if (mysqli_num_rows($getPeriods) > 0)
        {
            while ($period = mysqli_fetch_array($getPeriods))
            {
                // store period ID
                $period_id = $period["id"];

                // get all projects
                $getProjects = mysqli_prepare($conn, "SELECT code FROM projects WHERE code NOT IN (SELECT ps.code FROM projects_status ps WHERE ps.period_id=?)");
                mysqli_stmt_bind_param($getProjects, "i", $period_id);
                if (mysqli_stmt_execute($getProjects))
                {
                    $getProjectsResults = mysqli_stmt_get_result($getProjects);
                    if (mysqli_num_rows($getProjectsResults) > 0)
                    {   
                        while ($project = mysqli_fetch_array($getProjectsResults))
                        {
                            // store project code
                            $code = $project["code"];

                            // set project as active for the looped period
                            $setStatus = mysqli_prepare($conn, "INSERT INTO projects_status (code, period_id, status) VALUES (?, ?, 1)");
                            mysqli_stmt_bind_param($setStatus, "si", $code, $period_id);
                            mysqli_stmt_execute($setStatus);
                        }
                    }
                }
            }
        }
    }

    /** function to return the array of settings for a caseload category */
    function getCaseloadCategorySettings($conn, $category_id)
    {
        $settings = [];
        $getSettings = mysqli_prepare($conn, "SELECT * FROM caseload_categories WHERE id=?");
        mysqli_stmt_bind_param($getSettings, "i", $category_id);
        if (mysqli_stmt_execute($getSettings))
        {
            $getSettingsResults = mysqli_stmt_get_result($getSettings);
            if (mysqli_num_rows($getSettingsResults) > 0)
            {
                $settings = mysqli_fetch_array($getSettingsResults);
            }
        }
        return $settings;
    }
    
    /** function to get the project's leave time days */
    function getProjectLeaveTimeDays($conn, $code)
    {
        // initialize days array
        $daysArray = [];
        $daysArray["FTE_days"] = 250;
        $daysArray["leave_time"] = 0;
        $daysArray["prep_work"] = 0;
        $daysArray["personal_development"] = 0;

        // get the days array
        $getDaysArray = mysqli_prepare($conn, "SELECT FTE_days, leave_time, prep_work, personal_development FROM projects WHERE code=?");
        mysqli_stmt_bind_param($getDaysArray, "s", $code);
        if (mysqli_stmt_execute($getDaysArray))
        {
            $getDaysArrayResult = mysqli_stmt_get_result($getDaysArray);
            if (mysqli_num_rows($getDaysArrayResult) > 0)
            {
                $daysResult = mysqli_fetch_array($getDaysArrayResult);
                $daysArray["FTE_days"] = $daysResult["FTE_days"];
                $daysArray["leave_time"] = $daysResult["leave_time"];
                $daysArray["prep_work"] = $daysResult["prep_work"];
                $daysArray["personal_development"] = $daysResult["personal_development"];
            }
        }

        // return the days array
        return $daysArray;
    }

    /** function to get the number of days an employee is budgeted */
    function getEmployeeBudgetedDays($conn, $employee_id, $period_id)
    {
        // initialize total budgeted days variable
        $totalBudgetedDays = 0;

        // verify the employee exists
        if (checkExistingEmployee($conn, $employee_id))
        {
            if (verifyPeriod($conn, $period_id))
            {
                // get the total number of days the employee is budgeted for the period
                $getTotalBudgetedDays = mysqli_prepare($conn, "SELECT SUM(project_days) AS totalBudgetedDays FROM project_employees WHERE employee_id=? AND period_id=?");
                mysqli_stmt_bind_param($getTotalBudgetedDays, "ii", $employee_id, $period_id);
                if (mysqli_stmt_execute($getTotalBudgetedDays))
                {
                    $getTotalBudgetedDaysResult = mysqli_stmt_get_result($getTotalBudgetedDays);
                    if (mysqli_num_rows($getTotalBudgetedDaysResult) > 0)
                    {
                        $totalBudgetedDays = mysqli_fetch_array($getTotalBudgetedDaysResult)["totalBudgetedDays"];
                    }
                }
            }
        }

        // return the total number of days the employee is budgeted for the selected period
        return $totalBudgetedDays;
    }

    /** 
     *  function to assign project employees a fund code for the V3.15.0 update 
     *  -- function call removed in V3.15.1
    */
    function fixProjectEmployees($conn)
    {
        $getBudgetedEmployees = mysqli_query($conn, "SELECT pe.id, pe.project_code, p.fund_code FROM project_employees pe 
                                                    JOIN projects p ON pe.project_code=p.code
                                                    WHERE pe.fund_code IS NULL");
        if (mysqli_num_rows($getBudgetedEmployees) > 0)
        {
            while ($budgetedEmployee = mysqli_fetch_array($getBudgetedEmployees))
            {
                // store project budget details locally
                $budget_id = $budgetedEmployee["id"];
                $project_code = $budgetedEmployee["project_code"];
                $fund_code = $budgetedEmployee["fund_code"];

                // set the fund code for the project employee
                $setFund = mysqli_prepare($conn, "UPDATE project_employees SET fund_code=? WHERE id=?");
                mysqli_stmt_bind_param($setFund, "ii", $fund_code, $budget_id);
                mysqli_stmt_execute($setFund);
            }
        }
    }

    /** function to check if a service exists */
    function verifyService($conn, $service_id)
    {
        // verify the service exists
        $verifyService = mysqli_prepare($conn, "SELECT id FROM services WHERE id=?");
        mysqli_stmt_bind_param($verifyService, "s", $service_id);
        if (mysqli_stmt_execute($verifyService))
        {
            $verifyServiceResult = mysqli_stmt_get_result($verifyService);
            if (mysqli_num_rows($verifyServiceResult) > 0) // service exists; return true
            {
                return true;
            }
        }
        return false; // return false if we don't find the service
    }

    /** function to check if an "other" service exists */
    function verifyOtherService($conn, $service_id)
    {
        // verify the service exists
        $verifyService = mysqli_prepare($conn, "SELECT id FROM services_other WHERE id=?");
        mysqli_stmt_bind_param($verifyService, "s", $service_id);
        if (mysqli_stmt_execute($verifyService))
        {
            $verifyServiceResult = mysqli_stmt_get_result($verifyService);
            if (mysqli_num_rows($verifyServiceResult) > 0) // "other" service exists; return true
            {
                return true;
            }
        }
        return false; // return false if we don't find the service
    }

    /** function to get a caseloads status for a given period */
    function getCaseloadStatus($conn, $caseload_id, $period_id)
    {
        $status = 0;
        if (verifyCaseload($conn, $caseload_id))
        {
            if (verifyPeriod($conn, $period_id))
            {
                $getStatus = mysqli_prepare($conn, "SELECT status FROM caseloads_status WHERE caseload_id=? AND period_id=?");
                mysqli_stmt_bind_param($getStatus, "ii", $caseload_id, $period_id);
                if (mysqli_stmt_execute($getStatus))
                {
                    $getStatusResult = mysqli_stmt_get_result($getStatus);
                    if (mysqli_num_rows($getStatusResult) > 0)
                    {
                        $status = mysqli_fetch_array($getStatusResult)["status"];
                    }
                }
            }
        }
        return $status;
    }

    /** 
     *  function to fix projects for V3.16.0 
     *  -- function call removed in V3.16.1
    */
    function fixCaseloadsStatus($conn)
    {
        // for each period set each caseload's current status as active
        $getPeriods = mysqli_query($conn, "SELECT id FROM periods");
        if (mysqli_num_rows($getPeriods) > 0)
        {
            while ($period = mysqli_fetch_array($getPeriods))
            {
                // store period ID
                $period_id = $period["id"];

                // get all caseloads
                $getCaseloads = mysqli_prepare($conn, "SELECT id FROM caseloads WHERE id NOT IN (SELECT cs.caseload_id FROM caseloads_status cs WHERE cs.period_id=?)");
                mysqli_stmt_bind_param($getCaseloads, "i", $period_id);
                if (mysqli_stmt_execute($getCaseloads))
                {
                    $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                    if (mysqli_num_rows($getCaseloadsResults) > 0)
                    {   
                        while ($caseload = mysqli_fetch_array($getCaseloadsResults))
                        {
                            // store caseload ID
                            $caseload_id = $caseload["id"];

                            // set caseload as active for the looped period
                            $setStatus = mysqli_prepare($conn, "INSERT INTO caseloads_status (caseload_id, period_id, status) VALUES (?, ?, 1)");
                            mysqli_stmt_bind_param($setStatus, "ii", $caseload_id, $period_id);
                            mysqli_stmt_execute($setStatus);
                        }
                    }
                }
            }
        }
    }

    /** function to get the name of a service */
    function getServiceName($conn, $service_id)
    {
        $name = "";
        if (verifyService($conn, $service_id))
        {
            $getName = mysqli_prepare($conn, "SELECT name FROM services WHERE id=?");
            mysqli_stmt_bind_param($getName, "s", $service_id);
            if (mysqli_stmt_execute($getName))
            {
                $getNameResult = mysqli_stmt_get_result($getName);
                if (mysqli_num_rows($getNameResult) > 0)
                {
                    $name = mysqli_fetch_array($getNameResult)["name"];
                }
            }
        }
        return $name;
    }

    /** function to print an employee's address */
    function printEmployeeAddress($conn, $address_id, $employee_id)
    {
        // build the employee address
        $address = "<div class='address-card'>";
        $line1 = $line2 = $city = $state = $zip = "";
        $getAddress = mysqli_prepare($conn, "SELECT ea.line1, ea.line2, ea.city, s.abbreviation, ea.zip FROM employee_addresses ea JOIN states s ON ea.state_id=s.id WHERE ea.id=? AND ea.employee_id=?");
        mysqli_stmt_bind_param($getAddress, "ii", $address_id, $employee_id);
        if (mysqli_stmt_execute($getAddress))
        {
            $result = mysqli_stmt_get_result($getAddress);
            if (mysqli_num_rows($result) > 0)
            {
                // store the employee's address locally
                $addressDetails = mysqli_fetch_array($result);
                $line1 = $addressDetails["line1"];
                $line2 = $addressDetails["line2"];
                $city = $addressDetails["city"];
                $state = $addressDetails["abbreviation"];
                $zip = $addressDetails["zip"];

                // build the display
                $address = $line1 . "<br>";
                if ($line2 <> "") { $address .= $line2 . "<br>"; }
                $address .= $city . ", " . $state . " " . $zip;
            }
            else { $address .= "<span class='missing-field'>Missing</span>"; }
        }
        else { $address .= "<span class='missing-field'>Missing</span>"; }
        $address .= "</div>";
        return $address;
    }

    /** function to verify a user exists */
    function verifyUser($conn, $user_id)
    {
        // check to see if a user with the provided ID exists
        $verifyUser = mysqli_prepare($conn, "SELECT id FROM users WHERE id=?");
        mysqli_stmt_bind_param($verifyUser, "i", $user_id);
        if (mysqli_stmt_execute($verifyUser))
        {
            $verifyUserResult = mysqli_stmt_get_result($verifyUser);
            if (mysqli_num_rows($verifyUserResult) > 0)
            {
                return true;
            }
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to verify if the user is active */
    function isUserActive($conn, $user_id)
    {
        // check to see if the user with the provided ID is active
        $verifyUserStatus = mysqli_prepare($conn, "SELECT id FROM users WHERE id=? AND status=1");
        mysqli_stmt_bind_param($verifyUserStatus, "i", $user_id);
        if (mysqli_stmt_execute($verifyUserStatus))
        {
            $verifyUserStatusResult = mysqli_stmt_get_result($verifyUserStatus);
            if (mysqli_num_rows($verifyUserStatusResult) > 0)
            {
                return true;
            }
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to get a user's display name */
    function getUserDisplayName($conn, $user_id = null)
    {
        $name = "";
        if (isset($user_id) && is_numeric($user_id))
        {
            if ($user_id == 0) { $name = "SUPER ADMIN"; }
            else
            {
                $getName = mysqli_prepare($conn, "SELECT fname, lname FROM users WHERE id=?");
                mysqli_stmt_bind_param($getName, "i", $user_id);
                if (mysqli_stmt_execute($getName))
                {
                    $getNameResult = mysqli_stmt_get_result($getName);
                    if (mysqli_num_rows($getNameResult) > 0) // employee exists
                    {
                        // store employee details locally
                        $details = mysqli_fetch_array($getNameResult);
                        $fname = $details["fname"];
                        $lname = $details["lname"];
                        $name = $lname.", ".$fname;
                    }
                }
            }
        }
        else { $name = "Unknown"; }
        return $name;
    }

    /** function to verify a user is a director */
    function verifyDirector($conn, $director_id)
    {
        if (verifyUser($conn, $director_id))
        {
            $verifyDirector = mysqli_prepare($conn, "SELECT id FROM directors WHERE user_id=?");
            mysqli_stmt_bind_param($verifyDirector, "i", $director_id);
            if (mysqli_stmt_execute($verifyDirector))
            {
                $verifyDirectorResult = mysqli_stmt_get_result($verifyDirector);
                if (mysqli_num_rows($verifyDirectorResult) > 0) // user is a director; return true
                {
                    return true;
                }
            }
        }
        
        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to verify a user is a therapist */
    function verifyTherapist($conn, $therapist_id)
    {
        if (verifyUser($conn, $therapist_id))
        {
            $verifyTherapist = mysqli_prepare($conn, "SELECT id FROM therapists WHERE user_id=?");
            mysqli_stmt_bind_param($verifyTherapist, "i", $therapist_id);
            if (mysqli_stmt_execute($verifyTherapist))
            {
                $verifyTherapistResult = mysqli_stmt_get_result($verifyTherapist);
                if (mysqli_num_rows($verifyTherapistResult) > 0) // user is a therapist; return true
                {
                    return true;
                }
            }
        }
        
        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to verify a user is a coordinator */
    function verifyCoordinator($conn, $coordinator_id)
    {
        if (verifyUser($conn, $coordinator_id))
        {
            $verifyCoordinator = mysqli_prepare($conn, "SELECT id FROM caseload_coordinators WHERE user_id=?");
            mysqli_stmt_bind_param($verifyCoordinator, "i", $coordinator_id);
            if (mysqli_stmt_execute($verifyCoordinator))
            {
                $verifyCoordinatorResult = mysqli_stmt_get_result($verifyCoordinator);
                if (mysqli_num_rows($verifyCoordinatorResult) > 0) // user is a coordinator; return true
                {
                    return true;
                }
            }
        }
        
        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to see if a coordinator is assigned to the caseload */
    function isCoordinatorAssigned($conn, $coordinator_id, $caseload_id)
    {
        if (verifyCoordinator($conn, $coordinator_id))
        {
            if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
            {
                $checkAssignments = mysqli_prepare($conn, "SELECT id FROM caseload_coordinators_assignments WHERE user_id=? AND caseload_id=?");
                mysqli_stmt_bind_param($checkAssignments, "ii", $coordinator_id, $caseload_id);
                if (mysqli_stmt_execute($checkAssignments))
                {
                    $checkAssignmentsResults = mysqli_stmt_get_result($checkAssignments);
                    if (mysqli_num_rows($checkAssignmentsResults) > 0) // coordinator is assigned to the caseload
                    {
                        return true;
                    }
                }
            }
            // demo caseload
            else if ($caseload_id < 0)
            {
                // get category ID based on caseload ID
                $category_id = abs($caseload_id);

                // verify category exists
                if (verifyCaseloadCategory($conn, $category_id))
                {
                    return true;
                }
            }
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to get a list of all folders from a parent directory, recursively */
    function scanGoogleDrive($client_service, $folder_gid = null)
    {
        // initialize the array of directories/folders found
        $folders_found = []; 

        // if a parent folder ID is set; scan only within that folder and it's children folders; otherwise, scan entire Drive
        if (isset($folder_id) && trim($folder_gid) != "")
        {
            // recursively scan the Google Drive folder
            $pageToken = null;
            do {
                $response = $client_service->files->listFiles(array(
                    "q" => "mimeType = 'application/vnd.google-apps.folder' and '$folder_gid' in parents",
                    "spaces" => "drive",
                    "pageSize" => 1000,
                    "pageToken" => $pageToken,
                    "fields" => "nextPageToken, files(id, name)"
                ));

                foreach ($response->files as $file) {
                    // store the child GID locally
                    $child_gid = $file->id;

                    // add the child folder to the array
                    $folders_found[] = $child_gid; 

                    // scan folder found for subfolders
                    $child_folders_found = [];
                    $child_folders_found = scanGoogleDrive($client_service, $child_gid);

                    // flatten the array
                    for ($c = 0; $c < count($child_folders_found); $c++) { $folders_found[] = $child_folders_found[$c]; }
                }

                $pageToken = $response->nextPageToken;
            } while ($pageToken != null);
        } else {
            // scan the entire Google Drive
            $pageToken = null;
            do {
                $response = $client_service->files->listFiles(array(
                    "q" => "mimeType = 'application/vnd.google-apps.folder'",
                    "spaces" => "drive",
                    "pageSize" => 1000,
                    "pageToken" => $pageToken,
                    "fields" => "nextPageToken, files(id, name)"
                ));

                foreach ($response->files as $file) {
                    // store the child GID locally
                    $gid = $file->id;

                    // add the child folder to the array
                    $folders_found[] = $gid; 
                }

                $pageToken = $response->nextPageToken;
            } while ($pageToken != null);
        }

        // return the array of folders found
        return $folders_found;
    }

    /** function to verify if the caseload is assigned to the user */
    function isCaseloadAssigned($conn, $user_id, $caseload_id)
    {
        // verify the user exists
        if (verifyUser($conn, $user_id))
        {
            // verify the caseload exists
            if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
            {
                // verify the user is assigned to the caseload
                $verifyAssignment = mysqli_prepare($conn, "SELECT id FROM caseloads WHERE id=? AND employee_id=?");
                mysqli_stmt_bind_param($verifyAssignment, "ii", $caseload_id, $user_id);
                if (mysqli_stmt_execute($verifyAssignment))
                {
                    $verifyAssignmentResult = mysqli_stmt_get_result($verifyAssignment);
                    if (mysqli_num_rows($verifyAssignmentResult) > 0)
                    {
                        return true;
                    }
                }
            }
            // demo caseload
            else if ($caseload_id < 0)
            {
                // get category ID based on caseload ID
                $category_id = abs($caseload_id);

                // verify category exists
                if (verifyCaseloadCategory($conn, $category_id))
                {
                    return true;
                }
            }
        }

        // return false if we've reached the end of the function without returning
        return false;
    }

    /** function to get the case's evaluation method */
    function getCaseEvaluationMethod($conn, $case_id)
    {
        $evaluation_method = 0;
        if (verifyCase($conn, $case_id))
        {
            $query = mysqli_prepare($conn, "SELECT evaluation_method FROM cases WHERE id=?");
            mysqli_stmt_bind_param($query, "i", $case_id);
            if (mysqli_stmt_execute($query))
            {
                $result = mysqli_stmt_get_result($query);
                if (mysqli_num_rows($result) > 0)
                {
                    $evaluation_method = mysqli_fetch_array($result)["evaluation_method"];
                }
            }
        }
        return $evaluation_method;
    }

    /** function to get the case's enrollment type method */
    function getCaseEnrollmentType($conn, $case_id)
    {
        $enrollment_type = 0;
        if (verifyCase($conn, $case_id))
        {
            $query = mysqli_prepare($conn, "SELECT enrollment_type FROM cases WHERE id=?");
            mysqli_stmt_bind_param($query, "i", $case_id);
            if (mysqli_stmt_execute($query))
            {
                $result = mysqli_stmt_get_result($query);
                if (mysqli_num_rows($result) > 0)
                {
                    $enrollment_type = mysqli_fetch_array($result)["enrollment_type"];
                }
            }
        }
        return $enrollment_type;
    }

    /** function to get the case's evaluation method */
    function getCaseEducationalPlan($conn, $case_id)
    {
        $educational_plan = 0;
        if (verifyCase($conn, $case_id))
        {
            $query = mysqli_prepare($conn, "SELECT educational_plan FROM cases WHERE id=?");
            mysqli_stmt_bind_param($query, "i", $case_id);
            if (mysqli_stmt_execute($query))
            {
                $result = mysqli_stmt_get_result($query);
                if (mysqli_num_rows($result) > 0)
                {
                    $educational_plan = mysqli_fetch_array($result)["educational_plan"];
                }
            }
        }
        return $educational_plan;
    }

    /** function to get the case's billing notes */
    function getCaseBillingNotes($conn, $case_id)
    {
        $billing_notes = 0;
        if (verifyCase($conn, $case_id))
        {
            $query = mysqli_prepare($conn, "SELECT billing_notes FROM cases WHERE id=?");
            mysqli_stmt_bind_param($query, "i", $case_id);
            if (mysqli_stmt_execute($query))
            {
                $result = mysqli_stmt_get_result($query);
                if (mysqli_num_rows($result) > 0)
                {
                    $billing_notes = mysqli_fetch_array($result)["billing_notes"];
                }
            }
        }
        return $billing_notes;
    }

    /** function to get the period of a case */
    function getCasePeriod($conn, $case_id)
    {
        $period_id = null;
        if (verifyCase($conn, $case_id))
        {
            $getPeriod = mysqli_prepare($conn, "SELECT period_id FROM cases WHERE id=?");
            mysqli_stmt_bind_param($getPeriod, "i", $case_id);
            if (mysqli_stmt_execute($getPeriod))
            {
                $getPeriodResult = mysqli_stmt_get_result($getPeriod);
                if (mysqli_num_rows($getPeriodResult) > 0)
                {
                    $period_id = mysqli_fetch_array($getPeriodResult)["period_id"];
                }
            }
        }
        return $period_id;
    }

    /** function to get an employee's yearly salary for the given period */
    function getEmployeeSalary($conn, $employee_id, $period_id)
    {
        // initialize salary to 0
        $salary = 0;

        // get the employee's salary
        $getSalary = mysqli_prepare($conn, "SELECT yearly_rate FROM employee_compensation WHERE employee_id=? AND period_id=?");
        mysqli_stmt_bind_param($getSalary, "ii", $employee_id, $period_id);
        if (mysqli_stmt_execute($getSalary))
        {
            $getSalaryResult = mysqli_stmt_get_result($getSalary);
            if (mysqli_num_rows($getSalaryResult) > 0)
            {
                $salary = mysqli_fetch_array($getSalaryResult)["yearly_rate"];
            }
        }

        // return the salary
        return $salary;
    }

    /** function to get an employee's contract days for the given period */
    function getEmployeeContractDays($conn, $employee_id, $period_id)
    {
        // initialize contract days to 0
        $days = 0;

        // get the employee's contract days
        $getDays = mysqli_prepare($conn, "SELECT contract_days FROM employee_compensation WHERE employee_id=? AND period_id=?");
        mysqli_stmt_bind_param($getDays, "ii", $employee_id, $period_id);
        if (mysqli_stmt_execute($getDays))
        {
            $getDaysResult = mysqli_stmt_get_result($getDays);
            if (mysqli_num_rows($getDaysResult) > 0)
            {
                $days = mysqli_fetch_array($getDaysResult)["contract_days"];
            }
        }

        // return the contract days
        return $days;
    }

    /** function to take a snapshot of a quarter */
    function snapshotQuarter($conn, $period, $quarter, $month = null, $automation = 0)
    {
        // for each service; take a snapshot of the current data
        $getServices = mysqli_query($conn, "SELECT id, name FROM services");
        if (mysqli_num_rows($getServices) > 0) // services found
        {
            while ($service = mysqli_fetch_array($getServices))
            {
                // store service details locally
                $service_id = $service["id"];

                // get a list of customers and invoices for who we provided this service to for the fiscal period
                $getInvoices = mysqli_prepare($conn, "SELECT customer_id, SUM(quantity) AS quantity, SUM(total_cost) AS projected_annual_cost 
                                                        FROM services_provided 
                                                        WHERE period_id=? AND service_id=? GROUP BY customer_id");
                mysqli_stmt_bind_param($getInvoices, "is", $period, $service_id);
                if (mysqli_stmt_execute($getInvoices))
                {
                    $getInvoicesResults = mysqli_stmt_get_result($getInvoices);
                    if (mysqli_num_rows($getInvoicesResults) > 0) // invoice data found
                    {
                        // for all aggregate customer data found, get aggregate quarterly data
                        while ($customer = mysqli_fetch_array($getInvoicesResults))
                        {
                            // store customer details locally
                            $customer_id = $customer["customer_id"];
                            $qty = $customer["quantity"];
                            $projected_cost = $customer["projected_annual_cost"];

                            // get aggregate quarterly data
                            $getQuarterly = mysqli_prepare($conn, "SELECT SUM(cost) AS quarterly_cost FROM quarterly_costs WHERE service_id=? AND customer_id=? AND period_id=? AND quarter=? GROUP BY customer_id");
                            mysqli_stmt_bind_param($getQuarterly, "siii", $service_id, $customer_id, $period, $quarter);
                            if (mysqli_stmt_execute($getQuarterly))
                            {
                                $getQuarterlyResult = mysqli_stmt_get_result($getQuarterly);
                                if (mysqli_num_rows($getQuarterlyResult) > 0) // quarterly data found
                                {
                                    // store quarterly details locally
                                    $quarterly = mysqli_fetch_array($getQuarterlyResult);
                                    $quarterly_cost = $quarterly["quarterly_cost"];

                                    // check to see if there is already an active snapshot for the period and quarter
                                    $checkSnapshot = mysqli_prepare($conn, "SELECT id FROM snapshots WHERE period_id=? AND quarter=? AND month=? AND service_id=? AND customer_id=? AND status=1");
                                    mysqli_stmt_bind_param($checkSnapshot, "iiisi", $period, $quarter, $month, $service_id, $customer_id);
                                    if (mysqli_stmt_execute($checkSnapshot))
                                    {
                                        $checkSnapshotResult = mysqli_stmt_get_result($checkSnapshot);
                                        if (mysqli_num_rows($checkSnapshotResult) == 0) // snapshot not yet taken
                                        {
                                            // attempt to take a quarterly snapshot of the aggregate quarterly data for the customer and service
                                            $takeSnapshot = mysqli_prepare($conn, "INSERT INTO snapshots (period_id, quarter, month, service_id, customer_id, projected_annual_revenue, quarterly_revenue, total_quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
                                            mysqli_stmt_bind_param($takeSnapshot, "iiisiddd", $period, $quarter, $month, $service_id, $customer_id, $projected_cost, $quarterly_cost, $qty);
                                            if (mysqli_stmt_execute($takeSnapshot))
                                            {
                                                
                                            }
                                            else
                                            {
                                                // return false on error
                                                return false;
                                            }
                                        }
                                        else // snapshot already taken
                                        {
                                            // store the current snapshot ID
                                            $snapshot_id = mysqli_fetch_array($checkSnapshotResult)["id"];

                                            // set the existing snapshot to inactive
                                            $inactiveSnapshot = mysqli_prepare($conn, "UPDATE snapshots SET status=0 WHERE id=?");
                                            mysqli_stmt_bind_param($inactiveSnapshot, "i", $snapshot_id);
                                            if (mysqli_stmt_execute($inactiveSnapshot))
                                            {
                                                // attempt to take a new quarterly snapshot of the aggregate quarterly data for the customer and service
                                                $takeSnapshot = mysqli_prepare($conn, "INSERT INTO snapshots (period_id, quarter, month, service_id, customer_id, projected_annual_revenue, quarterly_revenue, total_quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
                                                mysqli_stmt_bind_param($takeSnapshot, "iiisiddd", $period, $quarter, $month, $service_id, $customer_id, $projected_cost, $quarterly_cost, $qty);
                                                if (mysqli_stmt_execute($takeSnapshot))
                                                {
                                                    
                                                }
                                                else
                                                {
                                                    // return false on error
                                                    return false;
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

        // log based on if taken from automation
        if ($automation == 1)
        {
            // log that we took a snapshot of the quarter
            $message = "Successfully took a snapshot of Q$quarter for the period with ID of $period via automation.";
            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (-2, ?)");
            mysqli_stmt_bind_param($log, "s", $message);
            mysqli_stmt_execute($log);
        } 
        else 
        {
            // log that we took a snapshot of the quarter
            $message = "Successfully took a snapshot of Q$quarter for the period with ID of $period.";
            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
            mysqli_stmt_execute($log);
        }

        // return true if we've reached the end of the function without breaking
        return true;

        /*
        // for each "other service"; take a snapshot of the current data
        $getOtherServices = mysqli_query($conn, "SELECT id, name FROM services_other");
        if (mysqli_num_rows($getOtherServices) > 0) // services found
        {
            while ($other_service = mysqli_fetch_array($getOtherServices))
            {
                // store service details locally
                $other_service_id = $other_service["id"];

                // initialize the counts and totals to 0
                $customer_count = $total_cost = $total_qty = 0;

                // for the service, took a snapshot of the quarterly data
                $getQuarterly = mysqli_prepare($conn, "SELECT COUNT(customer_id) AS customer_count, SUM(cost) AS total_cost FROM other_quarterly_costs WHERE other_service_id=? AND quarter=? AND period_id=?");
                mysqli_stmt_bind_param($getQuarterly, "sii", $other_service_id, $quarter, $period);
                if (mysqli_stmt_execute($getQuarterly))
                {
                    $getQuarterlyResult = mysqli_stmt_get_result($getQuarterly);
                    if (mysqli_num_rows($getQuarterlyResult) > 0)
                    {
                        // store data locally
                        $quarterly_data = mysqli_fetch_array($getQuarterlyResult);
                        if (isset($quarterly_data["customer_count"])) { $customer_count = $quarterly_data["customer_count"]; } else { $customer_count = 0; }
                        if (isset($quarterly_data["total_cost"])) { $total_cost = $quarterly_data["total_cost"]; } else { $total_cost = 0; }

                        // get the current quantity of the service
                        $getQuantity = mysqli_prepare($conn, "SELECT COUNT(quantity) AS total_qty FROM services_other_provided WHERE service_id=? AND period_id=?");
                        mysqli_stmt_bind_param($getQuantity, "si", $other_service_id, $period);
                        if (mysqli_stmt_execute($getQuantity))
                        {
                            $getQuantityResult = mysqli_stmt_get_result($getQuantity);
                            if (mysqli_num_rows($getQuantityResult) > 0)
                            {
                                // store data locally
                                $quantity_data = mysqli_fetch_array($getQuantityResult);
                                if (isset($quarterly_data["total_cost"])) { $total_qty = $quantity_data["total_qty"]; } else { $total_qty = 0; }
                            }
                        }
                    }
                }

                // take snapshot
                $takeSnapshot = mysqli_prepare($conn, "INSERT INTO quarterly_archive (period_id, quarter, service_id, total_revenue, total_quantity, total_customers) VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($takeSnapshot, "iisddi", $period, $quarter, $other_service_id, $total_cost, $total_qty, $customer_count);
                if (mysqli_stmt_execute($takeSnapshot)) { } else { }
            }
        }
        */
    }

    /** function to build a clickable link to jump to a project's budget */
    function getProjectLink($code, $period, $center = false)
    {
        // build the code div
        $div = "<div class='my-1'>
            <form class='w-100' method='POST' action='projects_budget.php'>
                <input type='hidden' id='project_code' name='project_code' value='".$code."' aria-hidden='true'>
                <input type='hidden' id='period_id' name='period_id' value='".$period."' aria-hidden='true'>";
                if (!$center) { 
                    $div .= "<button class='btn btn-link btn-therapist_caseload text-start text-nowrap w-100' type='submit'>
                        ".$code."
                    </button>";
                } else { 
                    $div .= "<button class='btn btn-link btn-therapist_caseload text-nowrap w-100' type='submit'>
                        ".$code."
                    </button>";
                }
            $div .= "</form>
        </div>";

        // return the div
        return $div;
    }

    /** function to get the total units of service for a caseload category for a specific district */
    function getDistrictUnitsTotalByCategory($conn, $district_id, $category_id, $period_id)
    {
        // initialize total units
        $total_units = 0;

        // verify the period exists
        if (verifyPeriod($conn, $period_id))
        {
            // verify the customer exists
            if (verifyCustomer($conn, $district_id))
            {
                // verify the category exists
                if (verifyCaseloadCategory($conn, $category_id))
                {
                    // get category settings
                    $category_settings = getCaseloadCategorySettings($conn, $category_id);

                    ///////////////////////////////////////////////////////////////////////////////////
                    // 
                    //  classroom-based caseload
                    //
                    ///////////////////////////////////////////////////////////////////////////////////
                    if ($category_settings["is_classroom"] == 1)
                    {
                        // get all cases for the customer where the student is attending the district and being billed
                        $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                    JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                    WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?");
                        mysqli_stmt_bind_param($getCasesByDistrict, "iiii", $period_id, $district_id, $district_id, $category_id);
                        if (mysqli_stmt_execute($getCasesByDistrict))
                        {
                            $getCasesByDistrictResults = mysqli_stmt_get_result($getCasesByDistrict);
                            if (mysqli_num_rows($getCasesByDistrictResults) > 0) // cases exist; continue
                            {
                                while ($case = mysqli_fetch_array($getCasesByDistrictResults))
                                {
                                    // store case data locally
                                    $case_id = $case["id"];
                                    $case_days = $case["membership_days"];

                                    // calculate the FTE - round to nearest whole quarter // TODO - in future, allow custom FTE
                                    $case_fte = (floor(($case_days / 180) * 4) / 4);

                                    // add the case FTEs to the total units
                                    $total_units += $case_fte;
                                }
                            }
                        }
                    }
                    ///////////////////////////////////////////////////////////////////////////////////
                    // 
                    //  unit-based caseload
                    //
                    ///////////////////////////////////////////////////////////////////////////////////
                    else if ($category_settings["uos_enabled"] == 1)
                    {
                        // get all cases for the customer where the student is attending the district and being billed
                        $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                    JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                    WHERE c.period_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?");
                        mysqli_stmt_bind_param($getCasesByDistrict, "iiii", $period_id, $district_id, $district_id, $category_id);
                        if (mysqli_stmt_execute($getCasesByDistrict))
                        {
                            $getCasesByDistrictResults = mysqli_stmt_get_result($getCasesByDistrict);
                            if (mysqli_num_rows($getCasesByDistrictResults) > 0) // cases exist; continue
                            {
                                while ($case = mysqli_fetch_array($getCasesByDistrictResults))
                                {
                                    // store case data locally
                                    $case_id = $case["id"];
                                    $evaluation_method = $case["evaluation_method"];
                                    $extra_ieps = $case["extra_ieps"];
                                    $extra_evals = $case["extra_evaluations"];

                                    // get the end of year units of service (prorated based on changes)
                                    $case_units = 0;
                                    if ($evaluation_method == 1) { $case_units = getProratedUOS($conn, $case_id); }
                                    else if ($evaluation_method == 2) { $case_units = 16; }

                                    // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                                    $additional_units = 0;
                                    if (is_numeric($extra_ieps) && $extra_ieps > 0) { $additional_units += (12 * $extra_ieps); }
                                    if (is_numeric($extra_evals) && $extra_evals > 0) { $additional_units += (16 * $extra_evals); }
                                    $case_units += $additional_units;

                                    // add units to sum
                                    $total_units += $case_units;
                                }
                            }
                        }
                    }
                }
            }
        }

        // return the total units
        return $total_units;
    }

    /** function to get the total units of service for a caseload classroom for a specific district */
    function getDistrictUnitsTotalByClassroom($conn, $district_id, $category_id, $classroom_id, $period_id)
    {
        // initialize total units
        $total_units = 0;

        // verify the period exists
        if (verifyPeriod($conn, $period_id))
        {
            // verify the customer exists
            if (verifyCustomer($conn, $district_id))
            {
                // verify the category exists
                if (verifyCaseloadCategory($conn, $category_id))
                {
                    // get all cases for the customer where the student is attending the district and being billed
                    $getCasesByDistrict = mysqli_prepare($conn, "SELECT c.* FROM cases c
                                                                JOIN caseloads cl ON c.caseload_id=cl.id
                                                                JOIN caseload_categories cc ON cl.category_id=cc.id
                                                                WHERE c.period_id=? AND c.classroom_id=? AND ((c.district_attending=? AND c.bill_to=2) OR (c.residency=? AND c.bill_to=1)) AND cc.id=?");
                    mysqli_stmt_bind_param($getCasesByDistrict, "iiiii", $period_id, $classroom_id, $district_id, $district_id, $category_id);
                    if (mysqli_stmt_execute($getCasesByDistrict))
                    {
                        $getCasesByDistrictResults = mysqli_stmt_get_result($getCasesByDistrict);
                        if (mysqli_num_rows($getCasesByDistrictResults) > 0) // cases exist; continue
                        {
                            while ($case = mysqli_fetch_array($getCasesByDistrictResults))
                            {
                                // store case data locally
                                $case_id = $case["id"];
                                $case_days = $case["membership_days"];

                                // calculate the FTE - round to nearest whole quarter // TODO - in future, allow custom FTE
                                $case_fte = (floor(($case_days / 180) * 4) / 4);

                                // add the case FTEs to the total units
                                $total_units += $case_fte;
                            }
                        }
                    }
                }
            }
        }

        // return the total units
        return $total_units;
    }

    /** function to get the total units of service for a caseload category for a specific district */
    function getDistrictCostsTotalByCategory($conn, $district_id, $category_id, $period_id)
    {
        // initialize total cost
        $total_cost = 0;

        // verify the period exists
        if (verifyPeriod($conn, $period_id))
        {
            // verify the customer exists
            if (verifyCustomer($conn, $district_id))
            {
                // verify the category exists
                if (verifyCaseloadCategory($conn, $category_id))
                {
                    // get category settings
                    $category_settings = getCaseloadCategorySettings($conn, $category_id);

                    ///////////////////////////////////////////////////////////////////////////////////
                    // 
                    //  classroom-based caseload
                    //
                    ///////////////////////////////////////////////////////////////////////////////////
                    if ($category_settings["is_classroom"] == 1)
                    {
                        // get a list of classroom for the category and the service tied to each classroom
                        $getClassrooms = mysqli_prepare($conn, "SELECT id, service_id FROM caseload_classrooms WHERE category_id=? ORDER BY id ASC");
                        mysqli_stmt_bind_param($getClassrooms, "i", $category_id);
                        if (mysqli_stmt_execute($getClassrooms))
                        {
                            $getClassroomsResults = mysqli_stmt_get_result($getClassrooms);
                            if (mysqli_num_rows($getClassroomsResults) > 0)
                            {
                                // for each classroom connected to the category, get the projected invoice cost
                                while ($classroom = mysqli_fetch_array($getClassroomsResults))
                                {
                                    // store classroom details
                                    $classroom_id = $classroom["id"];
                                    $service_id = $classroom["service_id"];

                                    // get the total units for the classroom
                                    $total_units = getDistrictUnitsTotalByClassroom($conn, $district_id, $category_id, $classroom_id, $period_id);

                                    // initialize classroom cost
                                    $classroom_cost = 0;

                                    // get service details
                                    $service = getServiceDetails($conn, $service_id);
                                    if (is_array($service)) // service exists; continue
                                    {                
                                        // store service details locally
                                        $service_cost_type = $service["cost_type"];
                                        $service_round_costs = $service["round_costs"];

                                        // get the estimated cost of the service
                                        $classroom_cost = getInvoiceCost($conn, $service_id, $district_id, $period_id, $service_cost_type, $service_round_costs, $total_units);

                                        // add classroom cost to total cost
                                        $total_cost += $classroom_cost;
                                    }
                                }
                            }
                        }
                    }
                    ///////////////////////////////////////////////////////////////////////////////////
                    // 
                    //  unit-based caseload
                    //
                    ///////////////////////////////////////////////////////////////////////////////////
                    else if ($category_settings["uos_enabled"] == 1)
                    {
                        // get the total units provided
                        $total_units = getDistrictUnitsTotalByCategory($conn, $district_id, $category_id, $period_id);

                        // get the service ID for the category
                        $service_id = getCaseloadCategoryService($conn, $category_id);

                        // get service details
                        $service = getServiceDetails($conn, $service_id);
                        if (is_array($service)) // service exists; continue
                        {                
                            // store service details locally
                            $service_cost_type = $service["cost_type"];
                            $service_round_costs = $service["round_costs"];

                            // get the estimated cost of the service
                            $total_cost = getInvoiceCost($conn, $service_id, $district_id, $period_id, $service_cost_type, $service_round_costs, $total_units);
                        }
                    }
                }
            }
        }

        // return the total cost
        return $total_cost;
    }

    /** function to get the total units of service for a classroom for a specific district */
    function getDistrictCostsTotalByClassroom($conn, $district_id, $category_id, $classroom_id, $period_id)
    {
        // initialize total cost
        $total_cost = 0;

        // verify the period exists
        if (verifyPeriod($conn, $period_id))
        {
            // verify the customer exists
            if (verifyCustomer($conn, $district_id))
            {
                // verify the category exists
                if (verifyCaseloadCategory($conn, $category_id))
                {
                    // get the total units for the classroom
                    $total_units = getDistrictUnitsTotalByClassroom($conn, $district_id, $category_id, $classroom_id, $period_id);

                    // initialize classroom cost
                    $classroom_cost = 0;

                    // get the service tied to the classroom
                    $service_id = getCaseloadClassroomService($conn, $classroom_id);

                    // get service details
                    $service = getServiceDetails($conn, $service_id);
                    if (is_array($service)) // service exists; continue
                    {                
                        // store service details locally
                        $service_cost_type = $service["cost_type"];
                        $service_round_costs = $service["round_costs"];

                        // get the estimated cost of the service
                        $classroom_cost = getInvoiceCost($conn, $service_id, $district_id, $period_id, $service_cost_type, $service_round_costs, $total_units);

                        // add classroom cost to total cost
                        $total_cost += $classroom_cost;
                    }
                }
            }
        }

        // return the total cost
        return $total_cost;
    }

    // function to get the service connected to a caseload category
    function getCaseloadCategoryService($conn, $category_id)
    {
        // initialize service ID
        $service_id = null;
        
        // verify the category exists
        if (verifyCaseloadCategory($conn, $category_id))
        {
            // get the service
            $getService = mysqli_prepare($conn, "SELECT service_id FROM caseload_categories WHERE id=?");
            mysqli_stmt_bind_param($getService, "i", $category_id);
            if (mysqli_stmt_execute($getService))
            {
                $getServiceResult = mysqli_stmt_get_result($getService);
                if (mysqli_num_rows($getServiceResult) > 0)
                {
                    $service_id = mysqli_fetch_array($getServiceResult)["service_id"];
                }
            }
        }

        // return the service ID
        return $service_id;
    }

    // function to get the service connected to a caseload category
    function getCaseloadClassroomService($conn, $classroom_id)
    {
        // initialize service ID
        $service_id = null;
        
        // get the service
        $getService = mysqli_prepare($conn, "SELECT service_id FROM caseload_classrooms WHERE id=?");
        mysqli_stmt_bind_param($getService, "i", $classroom_id);
        if (mysqli_stmt_execute($getService))
        {
            $getServiceResult = mysqli_stmt_get_result($getService);
            if (mysqli_num_rows($getServiceResult) > 0)
            {
                $service_id = mysqli_fetch_array($getServiceResult)["service_id"];
            }
        }

        // return the service ID
        return $service_id;
    }

    /** function to get the cost of a single unit of service */
    function getServiceCost($conn, $service_id, $period_id, $units)
    {
        // initialize service cost
        $service_cost = 0;

        // get the cost of the service based on the service ID provided
        $getServiceCostType = mysqli_prepare($conn, "SELECT cost_type FROM services WHERE id=?");
        mysqli_stmt_bind_param($getServiceCostType, "s", $service_id);
        if (mysqli_stmt_execute($getServiceCostType))
        {
            $results = mysqli_stmt_get_result($getServiceCostType);
            if (mysqli_num_rows($results) > 0) // cost type found 
            {
                $serviceDetails = mysqli_fetch_array($results);
                $costType = $serviceDetails["cost_type"];

                // if cost type if fixed (0)
                if ($costType == 0)
                {
                    $getServiceCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND cost_type=? AND period_id=?");
                    mysqli_stmt_bind_param($getServiceCost, "sii", $service_id, $costType, $period_id);
                    if (mysqli_stmt_execute($getServiceCost))
                    {
                        $result = mysqli_stmt_get_result($getServiceCost);
                        if (mysqli_num_rows($result) > 0)
                        {
                            $service_cost = mysqli_fetch_array($result)["cost"];
                        }
                    }
                }
                // if cost type is variable (1)
                else if ($costType == 1)
                {
                    $getServiceCost = mysqli_prepare($conn, "SELECT cost FROM costs WHERE service_id=? AND cost_type=? AND period_id=? AND ((min_quantity<=? AND max_quantity>=?) OR (min_quantity<=? AND max_quantity=-1)) ORDER BY variable_order ASC");
                    mysqli_stmt_bind_param($getServiceCost, "siiddd", $service_id, $costType, $period_id, $units, $units, $units);
                    if (mysqli_stmt_execute($getServiceCost))
                    {
                        $result = mysqli_stmt_get_result($getServiceCost);
                        if (mysqli_num_rows($result) > 0)
                        {
                            $service_cost = mysqli_fetch_array($result)["cost"];
                        }
                    }
                }
            }
        }

        // return the service cost
        return $service_cost;
    }

    /** function to check if the caseload is locked */
    function isCaseloadLocked($conn, $caseload_id)
    {
        // initialize locked variable to locked (true)
        $locked = true;

        // verify caseload exists
        if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
        {
            // get the category ID based on the caseload
            $category_id = getCaseloadCategory($conn, $caseload_id);

            // verify category exists
            if (verifyCaseloadCategory($conn, $category_id))
            {
                $getLocked = mysqli_prepare($conn, "SELECT locked FROM caseload_categories WHERE id=?");
                mysqli_stmt_bind_param($getLocked, "i", $category_id);
                if (mysqli_stmt_execute($getLocked))
                {
                    $getLockedResult = mysqli_stmt_get_result($getLocked);
                    if (mysqli_num_rows($getLockedResult) > 0)
                    {
                        $locked_int = mysqli_fetch_array($getLockedResult)["locked"];
                        if ($locked_int == 1) { // category is locked
                            $locked = true; 
                        } else { // category is unlocked
                            $locked = false;
                        }
                    }
                }
            }
        }
        // demo caseloads
        else if ($caseload_id < 0)
        {
            // get category ID based on caseload ID
            $category_id = abs($caseload_id);
            
            // verify category exists
            if (verifyCaseloadCategory($conn, $category_id)) // category exists; return false, unlocked
            {
                $locked = false;
            }
        }

        // return locked status (true = locked; false = unlocked)
        return $locked;
    }

    /** function to get an employee's email address */
    function getEmployeeEmail($conn, $employee_id)
    {
        $email = "";
        $getEmail = mysqli_prepare($conn, "SELECT email FROM employees WHERE id=?");
        mysqli_stmt_bind_param($getEmail, "i", $employee_id);
        if (mysqli_stmt_execute($getEmail))
        {
            $getEmailResult = mysqli_stmt_get_result($getEmail);
            if (mysqli_num_rows($getEmailResult) > 0)
            {
                $email = mysqli_fetch_array($getEmailResult)["email"];
            }
        }
        return $email;
    }

    /** function to get the label for a notification */
    function getNotificationLabel($conn, $notificaton_id)
    {
        $label = "";
        $getLabel = mysqli_prepare($conn, "SELECT type FROM email_types WHERE id=?");
        mysqli_stmt_bind_param($getLabel, "i", $notificaton_id);
        if (mysqli_stmt_execute($getLabel))
        {
            $getLabelResult = mysqli_stmt_get_result($getLabel);
            if (mysqli_num_rows($getLabelResult) > 0)
            {
                $label = mysqli_fetch_array($getLabelResult)["type"];
            }
        }
        return $label;
    }

    /** function to get a case's assistant */
    function getCaseAssistant($conn, $case_id)
    {
        $assistant_id = null;
        $getAssistant = mysqli_prepare($conn, "SELECT assistant_id FROM cases WHERE id=?");
        mysqli_stmt_bind_param($getAssistant, "i", $assistant_id);
        if (mysqli_stmt_execute($getAssistant))
        {
            $getAssistantResult = mysqli_stmt_get_result($getAssistant);
            if (mysqli_num_rows($getAssistantResult) > 0)
            {
                $assistant_id = mysqli_fetch_assoc($getAssistantResult)["assistant_id"];
            }
        }
        return $assistant_id;
    }
?>