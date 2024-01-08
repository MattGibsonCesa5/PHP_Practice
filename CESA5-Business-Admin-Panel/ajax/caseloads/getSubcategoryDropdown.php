<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {            
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_THERAPISTS") && checkUserPermission($conn, "ADD_THERAPISTS"))
        {
            // get the period from POST
            if (isset($_POST["category_id"]) && $_POST["category_id"] <> "") { $category_id = $_POST["category_id"]; } else { $category_id = null; }

            if ($category_id != null && verifyCaseloadCategory($conn, $category_id))
            {
                // create the default option for no subcategory
                echo "<option></option>";
                
                // get a list of all subcategories for the category provided
                $getSubcategories = mysqli_prepare($conn, "SELECT id, name FROM caseload_subcategories WHERE category_id=?");
                mysqli_stmt_bind_param($getSubcategories, "i", $category_id);
                if (mysqli_stmt_execute($getSubcategories))
                {
                    $getSubcategoriesResults = mysqli_stmt_get_result($getSubcategories);
                    if (mysqli_num_rows($getSubcategoriesResults) > 0)
                    {
                        while ($subcategory = mysqli_fetch_array($getSubcategoriesResults))
                        {
                            // store subcategory details locally
                            $subcategory_name = $subcategory["name"];
                            $subcategory_id = $subcategory["id"];

                            // create the dropdown option
                            echo "<option value='".$subcategory_id."'>".$subcategory_name."</option>";
                        }
                    }
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>