<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get required additional files
            include("../../includes/config.php");
            include("../../includes/functions.php");
            
            // initialize an array to store expenses
            $total_revenues = [];

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get all periods
            $getPeriods = mysqli_query($conn, "SELECT id, name FROM periods");
            if (mysqli_num_rows($getPeriods) > 0) // periods found; continue
            {
                while ($period = mysqli_fetch_array($getPeriods))
                {
                    // store the period details locally
                    $period_id = $period["id"];
                    $period_name = $period["name"];

                    $service_revenues = getServiceRevenues($conn, $period_id);
                    $other_service_revenues = getOtherServiceRevenues($conn, $period_id);
                    $other_revenues = getOtherRevenues($conn, $period_id);

                    // create the array to store data
                    $period_array = [$period_name, $service_revenues, $other_service_revenues, $other_revenues, ""];
                    $total_revenues[] = $period_array;
                }
            }

            // disconnect from the database
            mysqli_close($conn);

            echo json_encode($total_revenues);
        }
    }
?>