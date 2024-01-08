<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");
            include("../../getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the active period label
            $active_period_label = getActivePeriodLabel($conn);

            // get the current agency indirect rate
            $agency_indirect = 0;
            $getIndirect = mysqli_prepare($conn, "SELECT agency_indirect FROM global_expenses WHERE period_id=?");
            mysqli_stmt_bind_param($getIndirect, "i", $GLOBAL_SETTINGS["active_period"]);
            if (mysqli_stmt_execute($getIndirect))
            {
                $getIndirectResult = mysqli_stmt_get_result($getIndirect);
                if (mysqli_num_rows($getIndirectResult) > 0) // indirect rate found
                {
                    $agency_indirect = mysqli_fetch_array($getIndirectResult)["agency_indirect"];
                }
            }

            // get expenses
            $total_expenses = 0;
            $project_expenses = getTotalProjectExpenses($conn, $GLOBAL_SETTINGS["active_period"]);
            $employee_expenses = getEmployeeExpenses($conn, $GLOBAL_SETTINGS["active_period"]);
            $total_expenses = $project_expenses + $employee_expenses;

            // initialize variables
            $total_project_overhead = $total_project_nonoverhead = 0; // initialize total overhead and nonoverhead to 0
            $overhead_project_expenses = $other_project_expenses = 0; // initialize variables for overhead and non-overhead expenses
            $total_project_expenses = $total_project_supervision = $total_project_indirect = 0; // initialize variables to store project expenses
            $total_project_salaries = $total_project_benefits = 0; // initialize variable ot store total budgeted salaries and benefits; assume 0

            // for each project; get the total expenses
            $getProjects = mysqli_query($conn, "SELECT code FROM projects ORDER BY code ASC");
            if (mysqli_num_rows($getProjects) > 0) // projects found
            {
                while ($project = mysqli_fetch_array($getProjects))
                {
                    $total_project_salaries += getProjectSalary($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $total_project_benefits += getProjectBenefits($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $total_project_expenses += getProjectExpenses($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $total_project_supervision += getProjectSupervisionCosts($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $total_project_indirect += getProjectIndirectCosts($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $total_project_overhead += getProjectOverhead($conn, $GLOBAL_SETTINGS["active_period"], $project["code"], $GLOBAL_SETTINGS["overhead_costs_fund"]);
                    $total_project_nonoverhead += getProjectNonoverhead($conn, $GLOBAL_SETTINGS["active_period"], $project["code"], $GLOBAL_SETTINGS["overhead_costs_fund"]);
                }
            }

            // calculate the non-indirect costs
            $total_nonindirect_expenses = $total_project_salaries + $total_project_benefits + $total_project_expenses + $total_project_supervision;

            // calculate the actual indirect rate
            $calculated_indirect = $total_project_indirect / $total_nonindirect_expenses;

            // calculate the actual indirect rate based on overhead costs
            $calculated_overhead_indirect = $total_project_overhead / $total_project_nonoverhead;

            // initialize variables
            $total_indirect_project_expenses = $total_indirect_other_project_expenses = 0; // initialize variables for overhead and non-overhead expenses
            $total_indirect_project_expenses = $total_indirect_project_supervision = $total_indirect_project_indirect = 0; // initialize variables to store project expenses
            $total_indirect_project_salaries = $total_indirect_project_benefits = 0; // initialize variable ot store total budgeted salaries and benefits; assume 0
            $agency_indirect_project_expenses = $agency_indirect_other_project_expenses = 0; // initialize variables for overhead and non-overhead expenses
            $agency_indirect_project_expenses = $agency_indirect_project_supervision = $agency_indirect_project_indirect = 0; // initialize variables to store project expenses
            $agency_indirect_project_salaries = $agency_indirect_project_benefits = 0; // initialize variable ot store total budgeted salaries and benefits; assume 0
            $grant_indirect_project_expenses = $grant_indirect_other_project_expenses = 0; // initialize variables for overhead and non-overhead expenses
            $grant_indirect_project_expenses = $grant_indirect_project_supervision = $grant_indirect_project_indirect = 0; // initialize variables to store project expenses
            $grant_indirect_project_salaries = $grant_indirect_project_benefits = 0; // initialize variable ot store total budgeted salaries and benefits; assume 0
            $dpi_grant_indirect_project_expenses = $dpi_grant_indirect_other_project_expenses = 0; // initialize variables for overhead and non-overhead expenses
            $dpi_grant_indirect_project_expenses = $dpi_grant_indirect_project_supervision = $dpi_grant_indirect_project_indirect = 0; // initialize variables to store project expenses
            $dpi_grant_indirect_project_salaries = $dpi_grant_indirect_project_benefits = 0; // initialize variable ot store total budgeted salaries and benefits; assume 0

            // for each project; get the total agency indirect expenses
            $getAgencyIndirectProjects = mysqli_query($conn, "SELECT code FROM projects WHERE indirect_costs=1 ORDER BY code ASC");
            if (mysqli_num_rows($getAgencyIndirectProjects) > 0) // projects found
            {
                while ($project = mysqli_fetch_array($getAgencyIndirectProjects))
                {
                    $agency_indirect_project_salaries += getProjectSalary($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $agency_indirect_project_benefits += getProjectBenefits($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $agency_indirect_project_expenses += getProjectExpenses($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $agency_indirect_project_supervision += getProjectSupervisionCosts($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $agency_indirect_project_indirect += getProjectIndirectCosts($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                }
            }

            // for each project; get the total grant indirect rate expenses
            $getGrantIndirectProjects = mysqli_query($conn, "SELECT code FROM projects WHERE indirect_costs=2 ORDER BY code ASC");
            if (mysqli_num_rows($getGrantIndirectProjects) > 0) // projects found
            {
                while ($project = mysqli_fetch_array($getGrantIndirectProjects))
                {
                    $grant_indirect_project_salaries += getProjectSalary($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $grant_indirect_project_benefits += getProjectBenefits($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $grant_indirect_project_expenses += getProjectExpenses($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $grant_indirect_project_supervision += getProjectSupervisionCosts($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $grant_indirect_project_indirect += getProjectIndirectCosts($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                }
            }

            // for each project; get the total grant indirect rate expenses
            $getDPIGrantIndirectProjects = mysqli_query($conn, "SELECT code FROM projects WHERE indirect_costs=3 ORDER BY code ASC");
            if (mysqli_num_rows($getDPIGrantIndirectProjects) > 0) // projects found
            {
                while ($project = mysqli_fetch_array($getDPIGrantIndirectProjects))
                {
                    $dpi_grant_indirect_project_salaries += getProjectSalary($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $dpi_grant_indirect_project_benefits += getProjectBenefits($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $dpi_grant_indirect_project_expenses += getProjectExpenses($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $dpi_grant_indirect_project_supervision += getProjectSupervisionCosts($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                    $dpi_grant_indirect_project_indirect += getProjectIndirectCosts($conn, $GLOBAL_SETTINGS["active_period"], $project["code"]);
                }
            }

            // calculate totals
            $total_indirect_project_salaries = $agency_indirect_project_salaries + $grant_indirect_project_salaries + $dpi_grant_indirect_project_salaries;
            $total_indirect_project_benefits = $agency_indirect_project_benefits + $grant_indirect_project_benefits + $dpi_grant_indirect_project_benefits;
            $total_indirect_project_expenses = $agency_indirect_project_expenses + $grant_indirect_project_expenses + $dpi_grant_indirect_project_expenses;
            $total_indirect_project_supervision = $agency_indirect_project_supervision + $grant_indirect_project_supervision + $dpi_grant_indirect_project_supervision;
            $total_indirect_project_indirect = $agency_indirect_project_indirect + $grant_indirect_project_indirect + $dpi_grant_indirect_project_indirect;

            // calculate the non-indirect costs
            $total_indirect_nonindirect_expenses = $total_indirect_project_salaries + $total_indirect_project_benefits + $total_indirect_project_expenses + $total_indirect_project_supervision;

            // calculate the actual indirect rate
            $indirect_calculated_indirect = $total_indirect_project_indirect / $total_indirect_nonindirect_expenses;

            ?>
                <div class="modal fade" tabindex="-1" role="dialog" id="expensesBreakdownModal" data-bs-backdrop="static" aria-labelledby="expensesBreakdownModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="expensesBreakdownModalLabel"><?php echo $active_period_label; ?> Expenses</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row align-items-center">
                                    <div class="col-12 p-1">
                                        <table class="report_table report_table_border w-100">
                                            <thead>
                                                <tr style="visibility: hidden !important; height: 0px !important;">
                                                    <th style="width: 60% !important; padding: 0px !important; border: 0px !important; outline: 0px !important;"></th>
                                                    <th style="width: 20% !important; padding: 0px !important; border: 0px !important; outline: 0px !important;"></th>
                                                    <th style="width: 20% !important; padding: 0px !important; border: 0px !important; outline: 0px !important;"></th>
                                                </tr>

                                                <tr>
                                                    <th class="text-center p-1" colspan="3"><h3 class="m-0">Expenses</h3></th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr>
                                                    <td class="text-center" colspan="3"><b>Project Expenses</b></td>
                                                </tr>

                                                <tr>
                                                    <td><b>Project Added Expenses</b></td>
                                                    <td class="text-end"></td>
                                                    <td class="text-end"><?php echo printDollar($total_project_expenses); ?></td>
                                                </tr>

                                                <tr>
                                                    <td><b>Project Supervision Costs</b></td>
                                                    <td class="text-end"></td>
                                                    <td class="text-end"><?php echo printDollar($total_project_supervision); ?></td>
                                                </tr>

                                                <tr>
                                                    <td><b>Project Indirect Costs</b></td>
                                                    <td class="text-end"></td>
                                                    <td class="text-end"><?php echo printDollar($total_project_indirect); ?></td>
                                                </tr>

                                                <tr>
                                                    <td class="text-center"><i>Agency Indirect</i></td>
                                                    <td class="text-center"><?php echo printDollar($agency_indirect_project_indirect); ?></td>
                                                    <td class="text-end"></td>
                                                </tr>

                                                <tr>
                                                    <td class="text-center"><i>Grant Indirect</i></td>
                                                    <td class="text-center"><?php echo printDollar($grant_indirect_project_indirect); ?></td>
                                                    <td class="text-end"></td>
                                                </tr>

                                                <tr>
                                                    <td class="text-center"><i>DPI Grant Indirect</i></td>
                                                    <td class="text-center"><?php echo printDollar($dpi_grant_indirect_project_indirect); ?></td>
                                                    <td class="text-end"></td>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td class="text-end"></td>
                                                    <td class="text-end"><b><?php echo printDollar($project_expenses); ?></b></td>
                                                </tr>

                                                <!-- spacer -->
                                                <tr><th colspan="3"></th></tr>

                                                <tr>
                                                    <td class="text-center" colspan="3"><b>Employee Expenses</b></td>
                                                </tr>

                                                <tr>
                                                    <td><b>Employee Budgeted Salaries</b></td>
                                                    <td class="text-end"></td>
                                                    <td class="text-end"><?php echo printDollar($total_project_salaries); ?></td>
                                                </tr>

                                                <tr>
                                                    <td><b>Employee Budgeted Benefits</b></td>
                                                    <td class="text-end"></td>
                                                    <td class="text-end"><?php echo printDollar($total_project_benefits); ?></td>
                                                </tr>

                                                <tr>
                                                    <td></td>
                                                    <td class="text-end"></td>
                                                    <td class="text-end"><b><?php echo printDollar($employee_expenses); ?></b></td>
                                                </tr>
                                            </tbody>
                                            
                                            <tfoot>
                                                <th colspan="2"></th>
                                                <th class="text-end"><?php echo printDollar($total_expenses); ?></th>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <div class="row align-items-center">
                                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6 p-1">
                                        <table class="report_table report_table_border w-100">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" colspan="2">Indirect Rate (<?php echo $agency_indirect * 100; ?>%)</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr>
                                                    <td>Indirect Costs</td>
                                                    <td class="text-end"><?php echo printDollar($total_project_indirect); ?></td>
                                                </tr>

                                                <tr>
                                                    <td>Other Costs</td>
                                                    <td class="text-end"><?php echo printDollar($total_nonindirect_expenses); ?></td>
                                                </tr>

                                                <tr>
                                                    <td>Calculated Indirect Rate</td>
                                                    <td class="text-end"><b><?php echo round($calculated_indirect * 100, 2); ?>%</b></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6 p-1">
                                        <table class="report_table report_table_border w-100">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" colspan="2">Indirect Rate (<?php echo $agency_indirect * 100; ?>%)</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr>
                                                    <td>Overhead Costs (<?php echo $GLOBAL_SETTINGS["overhead_costs_fund"]; ?>)</td>
                                                    <td class="text-end"><?php echo printDollar($total_project_overhead); ?></td>
                                                </tr>

                                                <tr>
                                                    <td>Non-Overhead Costs</td>
                                                    <td class="text-end"><?php echo printDollar($total_project_nonoverhead); ?></td>
                                                </tr>

                                                <tr>
                                                    <td>Calculated Indirect Rate</td>
                                                    <td class="text-end"><b><?php echo round($calculated_overhead_indirect * 100, 2); ?>%</b></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 col-xxl-12 p-1">
                                        <table class="report_table report_table_border w-100">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" colspan="2">New Indirect Rate</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr>
                                                    <td>Indirect Costs</td>
                                                    <td class="text-end"><?php echo printDollar($total_indirect_project_indirect); ?></td>
                                                </tr>

                                                <tr>
                                                    <td>Other Costs</td>
                                                    <td class="text-end"><?php echo printDollar($total_indirect_nonindirect_expenses); ?></td>
                                                </tr>

                                                <tr>
                                                    <td>Calculated Indirect Rate</td>
                                                    <td class="text-end"><b><?php echo round($indirect_calculated_indirect * 100, 2); ?>%</b></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>