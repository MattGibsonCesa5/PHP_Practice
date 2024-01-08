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

        // verify the user has permission
        if (checkUserPermission($conn, "VIEW_EMPLOYEE_EXPENSES"))
        {
            // store user's permissions
            $can_user_edit = checkUserPermission($conn, "EDIT_EMPLOYEE_EXPENSES");

            // get the period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = trim($_POST["period"]); } else { $period = null; }

            // verify the period exists
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                if (verifyPeriod($conn, $period_id))
                {
                    // store if the period is editable
                    $is_editable = isPeriodEditable($conn, $period_id);

                    // get the grant project indirect rate
                    $grant_indirect_rate = getGrantIndirectRate($conn);
                    $dpi_grant_indirect_rate = getDPIGrantIndirectRate($conn, $period_id);

                    // pre-initialize all expenses
                    $health_single = 0.00;
                    $health_family = 0.00;
                    $dental_single = 0.00;
                    $dental_family = 0.00;
                    $wrs_rate = 0.00;
                    $FICA_rate = 0.00;
                    $LTD_rate = 0.00;
                    $life_rate = 0.00;
                    $agency_indirect = 0.00;
                    $grant_rate = 0.00;
                    $dpi_grant_rate = 0.00;
                    $aidable_supervision = 0.00;
                    $nonaidable_supervision = 0.00;

                    // get the current employee expenses for the given year
                    $getExpenses = mysqli_prepare($conn, "SELECT * FROM global_expenses WHERE period_id=?");
                    mysqli_stmt_bind_param($getExpenses, "i", $period_id);
                    if (mysqli_stmt_execute($getExpenses));
                    {
                        $getExpensesResult = mysqli_stmt_get_result($getExpenses);
                        if (mysqli_num_rows($getExpensesResult) > 0) // expenses for the current active period exist
                        {
                            $expenses = mysqli_fetch_array($getExpensesResult);

                            // get the costs of each expense
                            $health_single = $expenses["health_single"];
                            $health_family = $expenses["health_family"];
                            $dental_single = $expenses["dental_single"];
                            $dental_family = $expenses["dental_family"];
                            $wrs_rate = $expenses["wrs_rate"];
                            $FICA_rate = $expenses["FICA"];
                            $LTD_rate = $expenses["LTD"];
                            $life_rate = $expenses["life"];
                            $agency_indirect = $expenses["agency_indirect"];
                            $grant_rate = $expenses["grant_rate"];
                            $dpi_grant_rate = $expenses["dpi_grant_rate"];
                            $aidable_supervision = $expenses["aidable_supervision"];
                            $nonaidable_supervision = $expenses["nonaidable_supervision"];

                            // get the object code of each expense
                            $health_single_code = $expenses["health_single_code"];
                            $health_family_code = $expenses["health_family_code"];
                            $dental_single_code = $expenses["dental_single_code"];
                            $dental_family_code = $expenses["dental_family_code"];
                            $wrs_rate_code = $expenses["wrs_rate_code"];
                            $FICA_rate_code = $expenses["FICA_code"];
                            $LTD_rate_code = $expenses["LTD_code"];
                            $life_rate_code = $expenses["life_code"];
                            $agency_indirect_code = $expenses["agency_indirect_code"];
                            $grant_rate_code = $expenses["grant_rate_code"];
                            $dpi_grant_rate_code = $expenses["dpi_grant_rate_code"];
                            $aidable_supervision_code = $expenses["aidable_supervision_code"];
                            $nonaidable_supervision_code = $expenses["nonaidable_supervision_code"];
                        }
                    }

                    // initialize the counter of employees
                    $employees = 0;

                    // get a list of all employees to sum their compensation/benefits costs
                    $total_single_health = $total_family_health = $total_single_dental = $total_family_dental = $total_wrs = $total_FICA = $total_LTD = $total_life = 0;
                    $getEmployeesCompensation = mysqli_prepare($conn, "SELECT employee_id, contract_days, yearly_rate, health_insurance, dental_insurance, wrs_eligible FROM employee_compensation WHERE period_id=?");
                    mysqli_stmt_bind_param($getEmployeesCompensation, "i", $period_id);
                    if (mysqli_stmt_execute($getEmployeesCompensation))
                    {
                        $getEmployeesCompensationResults = mysqli_stmt_get_result($getEmployeesCompensation);
                        if (mysqli_num_rows($getEmployeesCompensationResults) > 0)
                        {
                            while ($employee = mysqli_fetch_array($getEmployeesCompensationResults))
                            {
                                // store the employees contract days and yearly rate locally
                                $days = $employee["contract_days"];
                                $rate = $employee["yearly_rate"];
                                $wrs = $employee["wrs_eligible"];
                                $health = $employee["health_insurance"];
                                $dental = $employee["dental_insurance"];

                                // calculate the percentage of benefits based on days
                                if ($days >= $GLOBAL_SETTINGS["FTE_days"]) { $FTE_Benefits_Percentage = 1; }
                                else 
                                { 
                                    if ($GLOBAL_SETTINGS["FTE_days"] > 0) { $FTE_Benefits_Percentage = ($days / $GLOBAL_SETTINGS["FTE_days"]); }
                                    else { $FTE_Benefits_Percentage = 0; }
                                }

                                // if percentage is <= 50%; set to 0
                                if ($FTE_Benefits_Percentage < 0.5) { $FTE_Benefits_Percentage = 0; }

                                // calculate the employees FICA contribution
                                $employee_FICA = $rate * $FICA_rate;

                                // calculate the employees WRS contribution
                                if ($wrs == 1) { $employee_wrs = $rate * $wrs_rate; }
                                else { $employee_wrs = 0; }

                                // calculate the employees health contribution
                                if ($health == 1) { $employee_health = ($health_family * $FTE_Benefits_Percentage); }
                                else if ($health == 2) { $employee_health = ($health_single * $FTE_Benefits_Percentage); }
                                else { $employee_health = 0; }

                                // calculate the employees dental contribution
                                if ($dental == 1) { $employee_dental = ($dental_family * $FTE_Benefits_Percentage); }
                                else if ($dental == 2) { $employee_dental = ($dental_single * $FTE_Benefits_Percentage); }
                                else { $employee_dental = 0; }

                                // calculate the employees LTD contribution
                                $employee_LTD = ($rate / 100) * ($LTD_rate * $FTE_Benefits_Percentage);

                                // calculate the employees life insurance contribution
                                $employee_life = (($rate / 1000) * ($life_rate * 12) * 0.2);

                                // add employee's benefit totals to the total costs
                                $total_FICA += $employee_FICA;
                                $total_wrs += $employee_wrs;
                                $total_LTD += $employee_LTD;
                                $total_life += $employee_life;
                                if ($health == 1) { $total_family_health += $employee_health; }
                                else if ($health == 2) { $total_single_health += $employee_health; }
                                if ($dental == 1) { $total_family_dental += $employee_dental; }
                                else if ($dental == 2) { $total_single_dental += $employee_dental; }
                            }
                        }
                    }

                    // calculate indirect costs
                    $total_indirect = $total_aidable = $total_nonaidable = 0;
                    $getProjects = mysqli_prepare($conn, "SELECT p.code, p.supervision_costs, p.indirect_costs FROM projects p
                                                        JOIN projects_status ps ON p.code=ps.code
                                                        WHERE ps.status=1 AND ps.period_id=?");
                    mysqli_stmt_bind_param($getProjects, "i", $period_id);
                    if (mysqli_stmt_execute($getProjects))
                    {
                        $getProjectsResults = mysqli_stmt_get_result($getProjects);
                        if (mysqli_num_rows($getProjectsResults) > 0)
                        {
                            while ($project = mysqli_fetch_array($getProjectsResults))
                            {
                                // store the project code locally
                                $code = $project["code"];
                                $supervision_costs = $project["supervision_costs"];
                                $indirect_costs = $project["indirect_costs"];

                                // get a list of the employees within the project and the sum of their total compensation
                                $total_compensation = 0;
                                $employees = getProjectEmployees($conn, $code, $period_id);
                                for ($e = 0; $e < count($employees); $e++)
                                {
                                    $total_compensation += getEmployeesTotalCompensation($conn, $code, $employees[$e], $period_id);
                                }

                                // get the total expenses of the current project
                                $total_expenses = 0;
                                $getTotalExpenses = mysqli_prepare($conn, "SELECT SUM(cost) AS total_expenses FROM project_expenses WHERE project_code=? AND period_id=? AND auto=0");
                                mysqli_stmt_bind_param($getTotalExpenses, "si", $code, $period_id);
                                if (mysqli_stmt_execute($getTotalExpenses))
                                {
                                    $getTotalExpensesResult = mysqli_stmt_get_result($getTotalExpenses);
                                    if (mysqli_num_rows($getTotalExpensesResult) > 0) { $total_expenses = mysqli_fetch_array($getTotalExpensesResult)["total_expenses"]; }
                                }

                                /* AIDABLE SUPERVISION */
                                $project_aidable_supervision = 0;
                                if ($supervision_costs == 1) { $project_aidable_supervision = $aidable_supervision * $total_compensation; }

                                /* NON-AIDABLE SUPERVISION */
                                $project_nonaidable_supervision = 0;
                                if ($supervision_costs == 1) { $project_nonaidable_supervision = $nonaidable_supervision * $total_compensation; }

                                $nonpersonnel_expenses = $total_expenses + $aidable_supervision + $nonaidable_supervision;
                                if ($indirect_costs == 1) { $indirect_NPE = $nonpersonnel_expenses * $agency_indirect; }
                                else if ($indirect_costs == 2) { $indirect_NPE = $nonpersonnel_expenses * $grant_indirect_rate; } 
                                else if ($indirect_costs == 3) { $indirect_NPE = $nonpersonnel_expenses * $dpi_grant_indirect_rate; } 
                
                                $personnel_expenses = $total_compensation;
                                if ($indirect_costs == 1) { $indirect_PE = $personnel_expenses * $agency_indirect; }
                                else if ($indirect_costs == 2) { $indirect_PE = $personnel_expenses * $grant_indirect_rate; } 
                                else if ($indirect_costs == 3) { $indirect_PE = $personnel_expenses * $dpi_grant_indirect_rate; } 
                
                                /* PROJECT INDIRECT */
                                if ($indirect_costs == 1 || $indirect_costs == 2 || $indirect_costs == 3) { $project_indirect = $indirect_PE + $indirect_NPE; }
                                else { $project_indirect = 0; }

                                // add project supervision costs to totals
                                $total_aidable += $project_aidable_supervision;
                                $total_nonaidable += $project_nonaidable_supervision;
                                $total_indirect += $project_indirect;
                            }
                        }
                    }

                    ?>
                        <!-- Global Employee Expenses Table -->
                        <table id="employeeExpensesTable" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Object Code</th>
                                    <th>Cost / Rate</th>
                                    <th><?php echo $period; ?> Totals</th>
                                    <th>Actions</th>
                                    <th>Calc Totals</th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- Health Insurance (Single) -->
                                <tr>
                                    <td><label for="health-single">Health Insurance (Single)</label></td>
                                    <td>The cost of health insurance coverage for the single plan.</td>
                                    <td><input class="form-control" type="text" id="health-single-code" name="health-single-code" value="<?php echo $health_single_code; ?>" onchange="modifiedExpense('health-single');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="health-single" name="health-single" value="<?php echo $health_single; ?>" min="0.00" onchange="modifiedExpense('health-single');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo printDollar($total_single_health); ?></td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-health-single" aria-label="Save data in row." onclick="saveExpense('health-single');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td><?php echo $total_single_health; ?></td>
                                </tr>

                                <!-- Health Insurance (Family) -->
                                <tr>
                                    <td><label for="health-family">Health Insurance (Family)</label></td>
                                    <td>The cost of health insurance coverage for the family plan.</td>
                                    <td><input class="form-control" type="text" id="health-family-code" name="health-family-code" value="<?php echo $health_family_code; ?>" onchange="modifiedExpense('health-family');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="health-family" name="health-family" value="<?php echo $health_family; ?>" min="0.00" onchange="modifiedExpense('health-family');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo printDollar($total_family_health); ?></td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-health-family" aria-label="Save data in row." onclick="saveExpense('health-family');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td><?php echo $total_family_health; ?></td>
                                </tr>

                                <!-- Dental Insurance (Single) -->
                                <tr>
                                    <td><label for="dental-single">Dental Insurance (Single)</label></td>
                                    <td>The cost of dental insurance coverage for the single plan.</td>
                                    <td><input class="form-control" type="text" id="dental-single-code" name="dental-single-code" value="<?php echo $dental_single_code; ?>" onchange="modifiedExpense('dental-single');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="dental-single" name="dental-single" value="<?php echo $dental_single; ?>" min="0.00" onchange="modifiedExpense('dental-single');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo printDollar($total_single_dental); ?></td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-dental-single" aria-label="Save data in row." onclick="saveExpense('dental-single');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td><?php echo $total_single_dental; ?></td>
                                </tr>

                                <!-- Dental Insurance (Family) -->
                                <tr>
                                    <td><label for="dental-family">Dental Insurance (Family)</label></td>
                                    <td>The cost of dental insurance coverage for the family plan.</td>
                                    <td><input class="form-control" type="text" id="dental-family-code" name="dental-family-code" value="<?php echo $dental_family_code; ?>" onchange="modifiedExpense('dental-family');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="dental-family" name="dental-family" value="<?php echo $dental_family; ?>" min="0.00" onchange="modifiedExpense('dental-family');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo printDollar($total_family_dental); ?></td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-dental-family" aria-label="Save data in row." onclick="saveExpense('dental-family');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td><?php echo $total_family_dental; ?></td>
                                </tr>

                                <!-- WRS -->
                                <tr>
                                    <td><label for="wrs-rate">WRS Retirement Benefit</label></td>
                                    <td>The percentage of income taken to aid to the employees WRS retirement fund.</td>
                                    <td><input class="form-control" type="text" id="wrs-rate-code" name="wrs-rate-code" value="<?php echo $wrs_rate_code; ?>" onchange="modifiedExpense('wrs-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="wrs-rate" name="wrs-rate" value="<?php echo $wrs_rate; ?>" min="0.00" step="0.01" onchange="modifiedExpense('wrs-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo printDollar($total_wrs); ?></td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-wrs-rate" aria-label="Save data in row." onclick="saveExpense('wrs-rate');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td><?php echo $total_wrs; ?></td>
                                </tr>

                                <!-- FICA -->
                                <tr>
                                    <td><label for="FICA-rate">FICA Contribution</label></td>
                                    <td>The percentage of income taken for social security and medicare.</td>
                                    <td><input class="form-control" type="text" id="FICA-rate-code" name="FICA-rate-code" value="<?php echo $FICA_rate_code; ?>" onchange="modifiedExpense('FICA-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="FICA-rate" name="FICA-rate" value="<?php echo $FICA_rate; ?>" min="0.00" step="0.01" onchange="modifiedExpense('FICA-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo printDollar($total_FICA); ?></td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-FICA-rate" aria-label="Save data in row." onclick="saveExpense('FICA-rate');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td><?php echo $total_FICA; ?></td>
                                </tr>

                                <!-- LTD -->
                                <tr>
                                    <td><label for="LTD-rate">Long-term Disability Insurance (LTD)</label></td>
                                    <td>The percentage of income taken for the LTD.</td>
                                    <td><input class="form-control" type="text" id="LTD-rate-code" name="LTD-rate-code" value="<?php echo $LTD_rate_code; ?>" onchange="modifiedExpense('LTD-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="LTD-rate" name="LTD-rate" value="<?php echo $LTD_rate; ?>" min="0.00" step="0.01" onchange="modifiedExpense('LTD-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo printDollar($total_LTD); ?></td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-LTD-rate" aria-label="Save data in row." onclick="saveExpense('LTD-rate');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td><?php echo $total_LTD; ?></td>
                                </tr>

                                <!-- Life Insurance Rate -->
                                <tr>
                                    <td><label for="life-rate">Life Insurance</label></td>
                                    <td>The life insurance rate.</td>
                                    <td><input class="form-control" type="text" id="life-rate-code" name="life-rate-code" value="<?php echo $life_rate_code; ?>" onchange="modifiedExpense('life-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="life-rate" name="LTD-rate" value="<?php echo $life_rate; ?>" min="0.00" step="0.01" onchange="modifiedExpense('life-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo printDollar($total_life); ?></td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-life-rate" aria-label="Save data in row." onclick="saveExpense('life-rate');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td><?php echo $total_life; ?></td>
                                </tr>

                                <!-- Agency Indirect Rate -->
                                <tr>
                                    <td><label for="agency-indirect">Agency Indirect</label></td>
                                    <td>The percentage of income taken as indirect agency costs.</td>
                                    <td><input class="form-control" type="text" id="agency-indirect-code" name="agency-indirect-code" value="<?php echo $agency_indirect_code; ?>" onchange="modifiedExpense('agency-indirect');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="agency-indirect" name="agency-indirect" value="<?php echo $agency_indirect; ?>" min="0.00" step="0.01" onchange="modifiedExpense('agency-indirect');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo printDollar($total_indirect); ?></td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-agency-indirect" aria-label="Save data in row." onclick="saveExpense('agency-indirect');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td><?php echo $total_indirect; ?></td>
                                </tr>

                                <!-- Grant Rate -->
                                <tr>
                                    <td><label for="grant-rate">Grant Rate</label></td>
                                    <td>The percentage of income taken grants.</td>
                                    <td><input class="form-control" type="text" id="grant-rate-code" name="grant-rate-code" value="<?php echo $grant_rate_code; ?>" onchange="modifiedExpense('grant-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="grant-rate" name="grant-rate" value="<?php echo $grant_rate; ?>" min="0.00" step="0.01" onchange="modifiedExpense('grant-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end">$0.00</td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-grant-rate" aria-label="Save data in row." onclick="saveExpense('grant-rate');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td>0</td>
                                </tr>

                                <!-- DPI Grant Rate -->
                                <tr>
                                    <td><label for="dpi_grant-rate">DPI Grant Rate</label></td>
                                    <td>The percentage of income taken grants.</td>
                                    <td><input class="form-control" type="text" id="dpi_grant-rate-code" name="dpi_grant-rate-code" value="<?php echo $dpi_grant_rate_code; ?>" onchange="modifiedExpense('dpi_grant-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="dpi_grant-rate" name="dpi_grant-rate" value="<?php echo $dpi_grant_rate; ?>" min="0.00" step="0.01" onchange="modifiedExpense('dpi_grant-rate');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end">-</td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-dpi_grant-rate" aria-label="Save data in row." onclick="saveExpense('dpi_grant-rate');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td>0</td>
                                </tr>

                                <!-- Aidable Supervision -->
                                <tr>
                                    <td><label for="supervision-aidable">Supervision (Aidable)</label></td>
                                    <td>The percentage of income taken for aidable supervision costs. Supervision costs are only calculated and added for projects that have supervision costs enabled.</td>
                                    <td><input class="form-control" type="text" id="supervision-aidable-code" name="supervision-aidable-code" value="<?php echo $aidable_supervision_code; ?>" onchange="modifiedExpense('supervision-aidable');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="supervision-aidable" name="supervision-aidable" value="<?php echo $aidable_supervision; ?>" min="0.00" step="0.01" onchange="modifiedExpense('supervision-aidable');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo printDollar($total_aidable); ?></td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-supervision-aidable" aria-label="Save data in row." onclick="saveExpense('supervision-aidable');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td><?php echo $total_aidable; ?></td>
                                </tr>

                                <!-- Non-Aidable Supervision -->
                                <tr>
                                    <td><label for="supervision-nonaidable">Supervision (Non-Aidable)</label></td>
                                    <td>The percentage of income taken for non-aidable supervision costs. Supervision costs are only calculated and added for projects that have supervision costs enabled.</td>
                                    <td><input class="form-control" type="text" id="supervision-nonaidable-code" name="supervision-nonaidable-code" value="<?php echo $nonaidable_supervision_code; ?>" onchange="modifiedExpense('supervision-nonaidable');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>></td>
                                    <td>
                                        <div class="input-group w-100 h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                            </div>
                                            <input class="form-control" type="number" id="supervision-nonaidable" name="supervision-nonaidable" value="<?php echo $nonaidable_supervision; ?>" min="0.00" step="0.01" onchange="modifiedExpense('supervision-nonaidable');" <?php if (!$can_user_edit == 1 || !$is_editable == 1) { echo "disabled readonly"; } ?>>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo printDollar($total_nonaidable); ?></td>
                                    <td><?php if ($can_user_edit == 1 && $is_editable == 1) { ?><button class="btn btn-secondary w-100" id="edit-supervision-nonaidable" aria-label="Save data in row." onclick="saveExpense('supervision-nonaidable');" disabled><i class="fa-solid fa-floppy-disk"></i></button><?php } ?></td>
                                    <td><?php echo $total_nonaidable; ?></td>
                                </tr>
                            </tbody>

                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end px-3 py-2">TOTAL:</th>
                                    <th class="text-end px-3 py-2" id="sum-all"></th> <!-- total expenses sum -->
                                    <th class="text-end px-3 py-2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    <?php
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>