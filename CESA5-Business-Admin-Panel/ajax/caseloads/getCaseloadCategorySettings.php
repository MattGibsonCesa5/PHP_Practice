<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // get the category from POST
        if (isset($_POST["category"]) && $_POST["category"] <> "") { $category_id = $_POST["category"]; } else { $category_id = null; }

        // initialize array to store caseload settings
        $settings = [];

        // ensure the category was sent
        if ($category_id != null)
        {
            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // verify the category exists
            if (verifyCaseloadCategory($conn, $category_id))
            {
                // get the settings for the caseload category
                $getSettings = mysqli_prepare($conn, "SELECT * FROM caseload_categories WHERE id=?");
                mysqli_stmt_bind_param($getSettings, "i", $category_id);
                if (mysqli_stmt_execute($getSettings))
                {
                    $getSettingsResults = mysqli_stmt_get_result($getSettings);
                    if (mysqli_num_rows($getSettingsResults) > 0)
                    {
                        $settings = mysqli_fetch_array($getSettingsResults);
                    }
                }
            }

            // disconnect from the database
            mysqli_close($conn);
        }

        // return the array of settings 
        echo json_encode($settings);
    }
?>