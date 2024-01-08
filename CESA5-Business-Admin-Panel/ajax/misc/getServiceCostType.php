<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && ($_SESSION["role"] == 1 || $_SESSION["role"] == 2 || $_SESSION["role"] == 4))
        {
            include("../../includes/config.php");
            include("../../includes/functions.php");

            // get service ID from POST
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = trim($_POST["service_id"]); } else { $service_id = null; }

            if ($service_id != null)
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                // get the service cost type
                $getCostType = mysqli_prepare($conn, "SELECT cost_type FROM services WHERE id=?");
                mysqli_stmt_bind_param($getCostType, "s", $service_id);
                if (mysqli_stmt_execute($getCostType))
                {
                    $getCostTypeResult = mysqli_stmt_get_result($getCostType);
                    if (mysqli_num_rows($getCostTypeResult) > 0) // service exists; continue
                    {
                        $cost_type = mysqli_fetch_array($getCostTypeResult)["cost_type"];
                        echo $cost_type;
                    }
                }

                // disconnect from the database
                mysqli_close($conn);
            }
        }
    }
?>