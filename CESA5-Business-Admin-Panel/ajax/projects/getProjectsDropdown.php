<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && ($_SESSION["role"] == 1 || $_SESSION["role"] == 2))
        {
            // get additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");

            // get the period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null)
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                // if the period is valid, create the modal
                if ($period_id = getPeriodID($conn, $period))
                {
                    // create a dropdown of all active projects to view the budget for depending on the user type
                    // -- if the user is an admin, show all active projects
                    // -- if the user is a director, show only active projects that are assigned to the director
                    if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL")) // admin projects listing; get all projects
                    {
                        $getProjects = mysqli_prepare($conn, "SELECT p.code, p.name FROM projects p 
                                                            JOIN projects_status ps ON p.code=ps.code
                                                            WHERE ps.status=1 AND ps.period_id=?");
                        mysqli_stmt_bind_param($getProjects, "i", $period_id);
                        if (mysqli_stmt_execute($getProjects))
                        {
                            $getProjectsResults = mysqli_stmt_get_result($getProjects);
                            if (mysqli_num_rows($getProjectsResults) > 0) // projects found
                            {
                                while ($project = mysqli_fetch_array($getProjectsResults))
                                {
                                    $code = $project["code"];
                                    $name = $project["name"];
                                    echo "<option value=".$code.">".$code." - ".$name."</option>";
                                }
                            }
                        }
                    }
                    else if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED")) // director projects listing; get projects assigned to director's department(s)
                    {
                        $getProjects = mysqli_prepare($conn, "SELECT p.code, p.name FROM projects p 
                                                                JOIN projects_status ps ON p.code=ps.code
                                                                JOIN departments d ON p.department_id=d.id 
                                                                WHERE (d.director_id=? OR d.secondary_director_id=?) AND ps.status=1 AND ps.period_id=?");
                        mysqli_stmt_bind_param($getProjects, "iii", $_SESSION["id"], $_SESSION["id"], $period_id);
                        if (mysqli_stmt_execute($getProjects))
                        {
                            $getProjectsResults = mysqli_stmt_get_result($getProjects);
                            if (mysqli_num_rows($getProjectsResults) > 0) // projects found
                            {
                                while ($project = mysqli_fetch_array($getProjectsResults))
                                {
                                    $code = $project["code"];
                                    $name = $project["name"];
                                    echo "<option value=".$code.">".$code." - ".$name."</option>";
                                }
                            }
                        }
                    }
                }

                // disconnect from the database
                mysqli_close($conn);
            }
        }
    }
?>