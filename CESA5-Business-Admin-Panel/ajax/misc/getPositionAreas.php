<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        include("../../includes/config.php");
        include("../../includes/functions.php");

        if (isset($_POST["position"]) && $_POST["position"] <> "") { $position = $_POST["position"]; } else { $position = null; }

        echo $position; 
        echo "<option></option>";

        if ($position != null)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            $areas = getPositionAreas($conn, $position);
            for ($a = 0; $a < count($areas); $a++)
            {
                echo "<option value='".$areas[$a]["area_code"]."'>".$areas[$a]["area_code"]." - ".$areas[$a]["area_name"]."</option>";
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>