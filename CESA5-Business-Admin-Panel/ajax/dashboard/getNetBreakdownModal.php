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

            // get revenues
            $total_revenues = 0;
            $service_revenues = getServiceRevenues($conn, $GLOBAL_SETTINGS["active_period"]);
            $other_service_revenues = getOtherServiceRevenues($conn, $GLOBAL_SETTINGS["active_period"]);
            $other_revenues = getOtherRevenues($conn, $GLOBAL_SETTINGS["active_period"]);
            $total_revenues = $service_revenues + $other_service_revenues + $other_revenues;

            // get expenses
            $total_expenses = 0;
            $project_expenses = getTotalProjectExpenses($conn, $GLOBAL_SETTINGS["active_period"]);
            $employee_expenses = getEmployeeExpenses($conn, $GLOBAL_SETTINGS["active_period"]);
            $total_expenses = $project_expenses + $employee_expenses;

            // calculate the net income
            $net_income = $total_revenues - $total_expenses;

            ?>
                <div class="modal fade" tabindex="-1" role="dialog" id="netBreakdownModal" data-bs-backdrop="static" aria-labelledby="netBreakdownModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="netBreakdownModalLabel"><?php echo $active_period_label; ?> Net Income</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="w-100 text-center breakdown-net-profit p-2">
                                    <h3 class="m-0"><?php echo printDollar($net_income); ?></h3>
                                </div>

                                <hr>

                                <table class="report_table w-100" id="netBreakdownTable-revenues">
                                    <thead>
                                        <tr>
                                            <th class="text-center p-1" colspan="2"><h3 class="m-0">Revenues</h3></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <!-- Services -->
                                        <tr>
                                            <td class="text-center">Services</td>
                                            <td class="text-end"><?php echo printDollar($service_revenues); ?></td>
                                        </tr>

                                        <!-- Other Services -->
                                        <tr>
                                            <td class="text-center">Other Services</td>
                                            <td class="text-end"><?php echo printDollar($other_service_revenues); ?></td>
                                        </tr>

                                        <!-- Other Revenues -->
                                        <tr>
                                            <td class="text-center">Other Revenues</td>
                                            <td class="text-end"><?php echo printDollar($other_revenues); ?></td>
                                        </tr>
                                    </tbody>
                                    
                                    <tfoot>
                                        <th></th>
                                        <th class="text-end"><?php echo printDollar($total_revenues); ?></th>
                                    </tfoot>
                                </table>

                                <hr>

                                <table class="report_table w-100" id="netBreakdownTable-expenses">
                                    <thead>
                                        <tr>
                                            <th class="text-center p-1" colspan="2"><h3 class="m-0">Expenses</h3></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <!-- Project Expenses -->
                                        <tr>
                                            <td class="text-center">Project Expenses</td>
                                            <td class="text-end"><?php echo printDollar($project_expenses); ?></td>
                                        </tr>

                                        <!-- Employee Expenses -->
                                        <tr>
                                            <td class="text-center">Employee Expenses</td>
                                            <td class="text-end"><?php echo printDollar($employee_expenses); ?></td>
                                        </tr>
                                    </tbody>
                                    
                                    <tfoot>
                                        <th></th>
                                        <th class="text-end"><?php echo printDollar($total_expenses); ?></th>
                                    </tfoot>
                                </table>

                                <hr>

                                
                                <table class="report_table w-100" id="netBreakdownTable-net">
                                    <thead>
                                        <tr>
                                            <th class="text-center p-1" colspan="2"><h3 class="m-0">Net Income</h3></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <!-- Revenues -->
                                        <tr>
                                            <td class="text-center">Revenues</td>
                                            <td class="text-end"><?php echo printDollar($total_revenues); ?></td>
                                        </tr>

                                        <!-- Expenses -->
                                        <tr>
                                            <td class="text-center">Expenses</td>
                                            <td class="text-end"><?php echo printDollar($total_expenses, true); ?></td>
                                        </tr>
                                    </tbody>
                                    
                                    <tfoot>
                                        <th></th>
                                        <th class="text-end"><?php echo printDollar($net_income); ?></th>
                                    </tfoot>
                                </table>
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