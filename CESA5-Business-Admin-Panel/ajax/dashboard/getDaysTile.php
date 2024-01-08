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
            // get total contract days of active employees
            $total_contract_days = getTotalContractDays($conn, $GLOBAL_SETTINGS["active_period"]);
            
            // get total budgeted days of all employees (both active and inactive)
            $total_budgeted_days = getTotalBudgetedDays($conn, $GLOBAL_SETTINGS["active_period"]);

            // calculate the total amount of days misbudgeted
            $total_misbudgeted_days = $total_contract_days - $total_budgeted_days;

            // calculate the percentage of budgeted days
            if ($total_contract_days != 0) { $budgeted_days_percent = (($total_budgeted_days / $total_contract_days) * 100); }
            else { $budgeted_days_percent = "100"; }

            // build the tile
            $tile_content = "<p class='card-text m-0'>Total Contract Days: ".number_format($total_contract_days)."</p>";
            $tile_content .= "<p class='card-text m-0'>Total Budgeted Days: ".number_format($total_budgeted_days)."</p>";
            if ($total_misbudgeted_days > 0) { $tile_content .= "<p class='card-text m-0'>Total Days Underbudgeted: ".number_format(abs($total_misbudgeted_days))."</p>"; }
            else if ($total_misbudgeted_days < 0) { $tile_content .= "<p class='card-text m-0'>Total Days Overbudgeted: ".number_format(abs($total_misbudgeted_days))."</p>"; }

            // build the data to be returned
            $data["percent"] = $budgeted_days_percent;
            $data["content"] = $tile_content;
        }

        // send data to be printed
        echo json_encode($data);

        // disconnect from the database
        mysqli_close($conn);
    }
?>