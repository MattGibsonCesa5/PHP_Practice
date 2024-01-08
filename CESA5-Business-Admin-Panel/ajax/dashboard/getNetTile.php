<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store data
        $data = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DASHBOARD_SHOW_NET_TILE"))
        {
            // get the active period's total revenues
            $active_revenues = getPeriodRevenues($conn, $GLOBAL_SETTINGS["active_period"]);

            // get the active period's total expenses
            $active_expenses = getPeriodExpenses($conn, $GLOBAL_SETTINGS["active_period"]);

            // calculate net income
            $net_income = $active_revenues - $active_expenses;

            // only get comparison data if a comparison period is set
            if ($GLOBAL_SETTINGS["comparison_period"] != 0 && ($GLOBAL_SETTINGS["active_period"] != $GLOBAL_SETTINGS["comparison_period"]))
            {
                // get the comparison period's total revenues
                $comp_revenues = getPeriodRevenues($conn, $GLOBAL_SETTINGS["comparison_period"]);

                // get the comparison period's total expenses
                $comp_expenses = getPeriodExpenses($conn, $GLOBAL_SETTINGS["comparison_period"]);

                // calc % difference between active and comp net income
                $comp_net = $comp_revenues - $comp_expenses;
                $net_growth = 0;
                if ($comp_net != 0) { $net_growth = (($net_income - $comp_net) / ($comp_net)) * 100; }

                // get comp period label
                $comp_label = getCompPeriodLabel($conn);
            }
            
            // build the tile
            $tile_content = "<h2 class='d-flex justify-content-between align-items-center'>";
                // add the income/loss amount
                if ($net_income != null) { $tile_content .= printDollar($net_income); } else { $tile_content .= "$0.00"; }

                // build button for net breakdown
                if ($net_income > 0) { $tile_content .= "<button type='button' class='btn btn-success' onclick='getNetBreakdownModal();'' title='View income breakdown' id='net-breakdown-btn'><i class='fa-solid fa-plus fa-xl'></i></button>"; }
                else if ($net_income < 0) { $tile_content .= "<button type='button' class='btn btn-danger' onclick='getNetBreakdownModal();'' title='View income breakdown' id='net-breakdown-btn'><i class='fa-solid fa-plus fa-xl'></i></button>"; }
                else { $tile_content .= "<button type='button' class='btn btn-secondary' onclick='getNetBreakdownModal();'' title='View income breakdown' id='net-breakdown-btn'><i class='fa-solid fa-plus fa-xl'></i></button>"; }
            $tile_content .= "</h2>";
            // add comparison period data if set
            if ($GLOBAL_SETTINGS["comparison_period"] != 0 && ($GLOBAL_SETTINGS["active_period"] != $GLOBAL_SETTINGS["comparison_period"]))
            {
                if ($net_growth >= 0) { $tile_content .= "<p class='card-text'>Up ".number_format($net_growth, 2)."% from ".$comp_label."</p>"; }
                else { $tile_content .= "<p class='card-text'>Down ".number_format($net_growth, 2)."% from ".$comp_label."</p>"; }
            }

            // build the data to be returned
            $data["net"] = $net_income;
            $data["content"] = $tile_content;
        }

        // send data to be printed
        echo json_encode($data);

        // disconnect from the database
        mysqli_close($conn);
    }
?>