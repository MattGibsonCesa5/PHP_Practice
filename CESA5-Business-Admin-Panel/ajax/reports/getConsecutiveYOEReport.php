<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // initialize the array of data to send
            $masterData = [];

            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");
            include("../../getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get a list of all employees and their compensation
            $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, e.most_recent_hire_date, e.most_recent_end_date, ec.experience_adjustment, ec.active, t.name AS title FROM employees e
                                                    LEFT JOIN employee_compensation ec ON e.id=ec.employee_id
                                                    LEFT JOIN employee_titles t ON ec.title_id=t.id
                                                    WHERE ec.period_id=? AND ec.active=1");
            mysqli_stmt_bind_param($getEmployees, "i", $GLOBAL_SETTINGS["active_period"]);
            if (mysqli_stmt_execute($getEmployees))
            {
                $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                if (mysqli_num_rows($getEmployeesResults) > 0)
                {
                    while ($employee = mysqli_fetch_array($getEmployeesResults))
                    {
                        // store employee data locally
                        $id = $employee["id"];
                        $lname = $employee["lname"];
                        $fname = $employee["fname"];
                        $hire = $employee["most_recent_hire_date"];
                        $end = $employee["most_recent_end_date"];
                        $adj = $employee["experience_adjustment"];
                        $title = $employee["title"];

                        // get the hire year
                        if (isset($hire)) 
                        { 
                            $hire_year = date("Y", strtotime($hire));

                            // get current year
                            $year = date("Y");

                            // calc difference between years
                            $consecutive_years = ($year - $hire_year) + $adj;

                            // build tempoary array for the employee to be displayed 
                            $temp = [];
                            $temp["id"] = $id;
                            $temp["name"] = $lname.", ".$fname;
                            $temp["title"] = $title;
                            $temp["hire"] = date("n/j/Y", strtotime($hire));
                            $temp["adj"] = $adj;
                            $temp["yoe"] = $consecutive_years;

                            // add employee to the master array
                            $masterData[] = $temp;
                        }
                    }
                }
            }

            // send data to be printed
            $fullData = [];
            $fullData["draw"] = 1;
            $fullData["data"] = $masterData;
            echo json_encode($fullData);

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>