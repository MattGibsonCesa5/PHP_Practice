<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the string to store the project directors
        $directors_string = "";

        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
        {
            // get the parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }

            if ($code != null)
            {
                $getProjectDirectors = mysqli_prepare($conn, "SELECT u.id FROM projects p
                                                            JOIN departments d ON p.department_id=d.id
                                                            JOIN users u ON d.director_id=u.id OR d.secondary_director_id=u.id
                                                            WHERE p.code=?");
                mysqli_stmt_bind_param($getProjectDirectors, "s", $code);
                if (mysqli_stmt_execute($getProjectDirectors))
                {
                    $getProjectDirectorsResults = mysqli_stmt_get_result($getProjectDirectors);
                    $numOfDirectors = mysqli_num_rows($getProjectDirectorsResults);
                    if ($numOfDirectors > 0) // directors found
                    {
                        // initialize the variable to store the current director we are on
                        $director_count = 1;
                        while ($director = mysqli_fetch_array($getProjectDirectorsResults))
                        {
                            // store the director's ID locally
                            $director_id = $director["id"];

                            // get the director's display name
                            $director_name = getUserDisplayName($conn, $director_id);

                            // add director name to string
                            $directors_string .= $director_name;

                            // add comma to separate directors
                            if ($numOfDirectors > 1 && $director_count < $numOfDirectors) { $directors_string .= ", "; }

                            // increment director count
                            $director_count++;
                        }
                    }
                }
            }
        } 
        
        // echo the directors string to return
        echo $directors_string;

        // disconnect from the database
        mysqli_close($conn);
    }
?>