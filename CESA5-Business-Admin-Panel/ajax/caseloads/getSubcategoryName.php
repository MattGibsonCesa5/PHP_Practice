<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        include("../../includes/config.php");
        include("../../includes/functions.php");

        $subcategory_name = "";

        if (isset($_POST["caseload_id"]) && $_POST["caseload_id"] <> "") { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id = null; }

        if ($caseload_id != null)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            $subcategory_id = getCaseloadSubcategory($conn, $caseload_id);
            $subcategory_name = getCaseloadSubcategoryName($conn, $subcategory_id); 

            // disconnect from the database
            mysqli_close($conn);
        }

        echo $subcategory_name;
    }
?>