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

        if (checkUserPermission($conn, "DASHBOARD_SHOW_CASELOADS_ALL_TILE"))
        {
            // get the number of active caseloads
            $total_active_therapists = getTherapistsWithStudentsCount($conn, $GLOBAL_SETTINGS["active_period"]);

            // get the number of students in caseloads
            $total_caseload_students = getStudentsInCaseloadsCount($conn, $GLOBAL_SETTINGS["active_period"]);

            // get the total units of service for caseloads
            $total_caseload_units = getTotalCaseloadUnits($conn, $GLOBAL_SETTINGS["active_period"]);
            
            ?>
                <p class="card-text m-0">Total Therapists w/ Students: <?php echo number_format($total_active_therapists); ?></p>
                <p class="card-text m-0">Total Students in Caseloads: <?php echo number_format($total_caseload_students); ?></p>
                <p class="card-text m-0">Total Units of Service: <?php echo number_format($total_caseload_units); ?></p>
            <?php
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>