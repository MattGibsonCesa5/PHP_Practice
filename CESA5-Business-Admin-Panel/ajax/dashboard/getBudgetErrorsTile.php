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

        if (checkUserPermission($conn, "DASHBOARD_SHOW_BUDGET_ERRORS_ALL_TILE") || checkUserPermission($conn, "DASHBOARD_SHOW_BUDGET_ERRORS_ASSIGNED_TILE"))
        {
            // get the number of employees who have been misbudgeted
            $misbudgeted_employees_count = getMisbudgetedEmployeesCount($conn, $GLOBAL_SETTINGS["active_period"], $_SESSION["id"]);

            // build the tile content
            $tile_content = "<h5 class='m-0'>".$misbudgeted_employees_count." misbudgeted staff</h5>";
            $tile_content .= "<p class='m-0'><a href='days_misbudgeted.php'>View Report</a></p>";

            // build return array
            $data["content"] = $tile_content;
            $data["count"] = $misbudgeted_employees_count;
        }

        // send data to be printed
        echo json_encode($data);

        // disconnect from the database
        mysqli_close($conn);
    }
?>