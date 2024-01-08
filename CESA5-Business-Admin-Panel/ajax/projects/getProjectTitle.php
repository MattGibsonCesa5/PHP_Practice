<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    { 
        // initialize variables
        $title = "";

        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
        {
            // get the project code from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }

            // get and create the project title
            if ($code != null)
            {
                $getProjectName = mysqli_prepare($conn, "SELECT name FROM projects WHERE code=?");
                mysqli_stmt_bind_param($getProjectName, "i", $code);
                if (mysqli_stmt_execute($getProjectName))
                {
                    $getProjectNameResult = mysqli_stmt_get_result($getProjectName);
                    if (mysqli_num_rows($getProjectNameResult) > 0) // project exists
                    {
                        $name = mysqli_fetch_array($getProjectNameResult)["name"];
                        $title = $code . " - " . $name;
                    }
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);

        // echo the title to be printed
        echo $title;
    }
?>