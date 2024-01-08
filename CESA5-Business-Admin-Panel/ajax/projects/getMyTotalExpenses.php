<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the total cost
        $total_expenses = 0;

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECTS_ASSIGNED"))
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period)) 
            {
                // get a list of all projects
                $getProjects = mysqli_prepare($conn, "SELECT p.code FROM projects p 
                                                    JOIN departments d ON p.department_id=d.id
                                                    WHERE d.director_id=? OR d.secondary_director_id=?
                                                    ORDER BY p.code ASC");
                mysqli_stmt_bind_param($getProjects, "ii", $_SESSION["id"], $_SESSION["id"]);
                if (mysqli_stmt_execute($getProjects))
                {
                    $getProjectsResults = mysqli_stmt_get_result($getProjects);
                    if (mysqli_num_rows($getProjectsResults) > 0) // projects found; continue
                    {
                        // for each project; get the project's total expenses
                        while ($project = mysqli_fetch_array($getProjectsResults))
                        {
                            // store the project's code locally
                            $code = $project["code"];

                            // add the project's total expenses to the global total
                            $total_expenses += getProjectsTotalExpenses($conn, $code, $period_id);
                        }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // send back the total expenses
        echo $total_expenses;
    }
?>