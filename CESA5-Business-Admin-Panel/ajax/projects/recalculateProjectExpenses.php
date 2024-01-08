<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "BUDGET_PROJECTS_ALL") || checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED"))
        {
            // get parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($code != null && $period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    if (verifyProject($conn, $code)) // verify the project exists
                    {
                        if (verifyUserProject($conn, $_SESSION["id"], $code)) // user has been verified to make changes to this project
                        {
                            // run the function to recalculate the project's expenses
                            recalculateAutomatedExpenses($conn, $code, $period_id);
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to recalculate the project's expenses. The user is not verified to make changes to this project.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to recalculate the project's expenses. The project code was invalid.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to recalculte the project's expenses. The period selected was invalid.<br>"; }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>