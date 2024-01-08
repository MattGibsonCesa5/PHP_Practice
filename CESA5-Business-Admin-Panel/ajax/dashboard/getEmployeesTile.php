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

        if (checkUserPermission($conn, "DASHBOARD_SHOW_EMPLOYEES_TILE"))
        {
            // get total active employees count
            $employees_count = getTotalActiveEmployees($conn, $GLOBAL_SETTINGS["active_period"]);

            // get total inactive employees count
            $inactive_employees_count = getTotalInactiveEmployees($conn, $GLOBAL_SETTINGS["active_period"]);

            // get test employees count
            $test_employees_count = getTestEmployeesCount($conn, $GLOBAL_SETTINGS["active_period"]);
            $included_test_employees_count = getIncludedTestEmployeesCount($conn, $GLOBAL_SETTINGS["active_period"]);
            
            ?>
                <h2 class="m-0"><?php echo $employees_count; ?> Employees</h2>
                <?php if ($inactive_employees_count > 0) { ?>
                    <h6 class="fst-italic fw-normal m-0">+<?php echo $inactive_employees_count; ?> Inactive Employee<?php if ($inactive_employees_count > 1) { echo "s"; } ?></h6>
                <?php } ?>
                <?php if ($test_employees_count > 0) { ?>
                    <h6 class="fst-italic fw-normal m-0">+<?php echo $test_employees_count; ?> Test Employees (<?php echo $included_test_employees_count; ?> Included)</h6>
                <?php } ?>
            <?php
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>