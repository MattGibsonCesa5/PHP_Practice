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

        if (checkUserPermission($conn, "DASHBOARD_SHOW_EXPENSES_TILE"))
        {
            // get the active period's total expenses
            $active_expenses = 0; // initialize to 0
            $active_expenses = getPeriodExpenses($conn, $GLOBAL_SETTINGS["active_period"]);

            // only get comparison data if a comparison period is set
            if ($GLOBAL_SETTINGS["comparison_period"] != 0 && ($GLOBAL_SETTINGS["active_period"] != $GLOBAL_SETTINGS["comparison_period"]))
            {
                // get the comparison period's total expenses
                $comp_expenses = getPeriodExpenses($conn, $GLOBAL_SETTINGS["comparison_period"]);

                // calc % difference between active and comp expenses
                $expenses_growth = 0;
                if ($comp_expenses != 0) { $expenses_growth = (($active_expenses - $comp_expenses) / ($comp_expenses)) * 100; }

                // get comp period label
                $comp_label = getCompPeriodLabel($conn);
            }
            
            ?>
                <h2 class="d-flex justify-content-between align-items-center">
                    <?php if ($active_expenses != null) { echo printDollar($active_expenses); } else { echo "$0.00"; } ?>
                    <button type="button" class="btn btn-danger" onclick="getExpensesBreakdownModal();" title="View expenses breakdown" id="expenses-breakdown-btn"><i class="fa-solid fa-plus fa-xl"></i></button>
                </h2>
                <?php if ($GLOBAL_SETTINGS["comparison_period"] != 0 && ($GLOBAL_SETTINGS["active_period"] != $GLOBAL_SETTINGS["comparison_period"])) { // only display comparison data if set ?>
                    <p class="card-text"><?php if ($expenses_growth >= 0) { echo "Up"; } else { echo "Down"; } ?> <?php echo number_format($expenses_growth, 2)."%"; ?> from <?php echo $comp_label; ?>.</p>
                <?php } ?>
            <?php
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>