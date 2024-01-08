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

            // initialize the total cost sum
            $total_services_cost = $total_otherServices_cost = $total_combined_cost = 0;

            ?>
                <div class="modal fade" tabindex="-1" role="dialog" id="quarterlyCostBreakdownModal" data-bs-backdrop="static" aria-labelledby="quarterlyCostBreakdownModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="quarterlyCostBreakdownModalLabel"><?php echo $active_period_label; ?> Quarterly Revenues</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <table class="report_table w-100" id="quarterlyCostsBreakdownTable">
                                    <thead>
                                        <tr>
                                            <th colspan="2" class="text-center">Quarter</th>
                                            <th colspan="3" class="text-center">Revenues From Services</th>
                                        </tr>

                                        <tr>
                                            <th class="text-center">#</th>
                                            <th class="text-center">Label</th>
                                            <th class="text-center">Services</th>
                                            <th class="text-center">Other Services</th>
                                            <th class="text-center">Total</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php
                                            // get the total quarterly cost of each quarter
                                            $quarterlyCosts = [];
                                            $quarterlyCosts["Q1"] = $quarterlyCosts["Q2"] = $quarterlyCosts["Q3"] = $quarterlyCosts["Q4"] = 0;
                                            for ($q = 1; $q <= 4; $q++)
                                            {
                                                // get quarterly costs for services
                                                $getQuarterlyCost = mysqli_prepare($conn, "SELECT SUM(cost) AS quarterly_cost FROM quarterly_costs WHERE quarter=? AND period_id=?");
                                                mysqli_stmt_bind_param($getQuarterlyCost, "ii", $q, $GLOBAL_SETTINGS["active_period"]);
                                                if (mysqli_stmt_execute($getQuarterlyCost))
                                                {
                                                    $getQuarterlyCostResult = mysqli_stmt_get_result($getQuarterlyCost);
                                                    if (mysqli_num_rows($getQuarterlyCostResult) > 0)
                                                    {
                                                        // store the cost locally
                                                        $quarterly_cost = mysqli_fetch_array($getQuarterlyCostResult)["quarterly_cost"];

                                                        // add the quarter's cost to the global totals
                                                        $total_services_cost += $quarterly_cost;
                                                        $total_combined_cost += $quarterly_cost;
                                                        
                                                        // store the total quarterly cost in the array
                                                        if (isset($quarterly_cost) && is_numeric($quarterly_cost)) { $quarterlyCosts["Q$q"] = $quarterly_cost; }
                                                    }
                                                }
                                                
                                                // get quarterly costs for other services
                                                $otherQuarterlyCosts = [];
                                                $otherQuarterlyCosts["Q1"] = $otherQuarterlyCosts["Q2"] = $otherQuarterlyCosts["Q3"] = $otherQuarterlyCosts["Q4"] = 0;
                                                $getOtherQuarterlyCost = mysqli_prepare($conn, "SELECT SUM(cost) AS quarterly_cost FROM other_quarterly_costs WHERE quarter=? AND period_id=?");
                                                mysqli_stmt_bind_param($getOtherQuarterlyCost, "ii", $q, $GLOBAL_SETTINGS["active_period"]);
                                                if (mysqli_stmt_execute($getOtherQuarterlyCost))
                                                {
                                                    $getOtherQuarterlyCostResult = mysqli_stmt_get_result($getOtherQuarterlyCost);
                                                    if (mysqli_num_rows($getOtherQuarterlyCostResult) > 0)
                                                    {
                                                        // store the cost locally
                                                        $other_quarterly_cost = mysqli_fetch_array($getOtherQuarterlyCostResult)["quarterly_cost"];

                                                        // add the quarter's cost to the global totals
                                                        $total_otherServices_cost += $other_quarterly_cost;
                                                        $total_combined_cost += $other_quarterly_cost;
                                                        
                                                        // store the total quarterly cost in the array
                                                        if (isset($other_quarterly_cost) && is_numeric($other_quarterly_cost)) { $otherQuarterlyCosts["Q$q"] = $other_quarterly_cost; }
                                                    }
                                                }

                                                // calculate the quarter's total cost
                                                $quarter_total_cost = $quarterly_cost + $other_quarterly_cost;
                                                
                                                // get the quarter's label
                                                $quarter_label = "Quarter $q";
                                                $getQuarterLabel = mysqli_prepare($conn, "SELECT label FROM quarters WHERE quarter=? AND period_id=?");
                                                mysqli_stmt_bind_param($getQuarterLabel, "ii", $q, $GLOBAL_SETTINGS["active_period"]);
                                                if (mysqli_stmt_execute($getQuarterLabel))
                                                {
                                                    $getQuarterLabelResult = mysqli_stmt_get_result($getQuarterLabel);
                                                    if (mysqli_num_rows($getQuarterLabelResult) > 0) // label found
                                                    {
                                                        $quarter_label = mysqli_fetch_array($getQuarterLabelResult)["label"];
                                                    }
                                                }

                                                // display the table row
                                                echo "<tr>
                                                    <td class='text-center'>$q</td>
                                                    <td class='text-center'>$quarter_label</td>
                                                    <td class='text-end'>".printDollar($quarterlyCosts["Q$q"], 2)."</td>
                                                    <td class='text-end'>".printDollar($otherQuarterlyCosts["Q$q"], 2)."</td>
                                                    <td class='text-end fw-bold'>".printDollar($quarter_total_cost, 2)."</td>
                                                </tr>";
                                            }
                                        ?>
                                    </tbody>

                                    <tfoot>
                                        <th></th>
                                        <th></th>
                                        <th class="text-end"><?php echo printDollar($total_services_cost); ?></th>
                                        <th class="text-end"><?php echo printDollar($total_otherServices_cost); ?></th>
                                        <th class="text-end fw-bolder"><?php echo printDollar($total_combined_cost); ?></th>
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