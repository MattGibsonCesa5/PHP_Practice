<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // include additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // initialize array to store titles 
        $titles = [];

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "ADD_EMPLOYEES")) 
        {
            $getTitles = mysqli_query($conn, "SELECT * FROM employee_titles ORDER BY name ASC");
            if (mysqli_num_rows($getTitles) > 0)
            {
                while ($title = mysqli_fetch_array($getTitles))
                {
                    // store title details locally
                    $title_id = $title["id"];
                    $name = $title["name"];

                    // get the number of employees who are assigned this title in the current active period
                    $count = 0;
                    $getCount = mysqli_prepare($conn, "SELECT id FROM employee_compensation WHERE title_id=? AND period_id=?");
                    mysqli_stmt_bind_param($getCount, "ii", $title_id, $GLOBAL_SETTINGS["active_period"]);
                    if (mysqli_stmt_execute($getCount))
                    {
                        $getCountResult = mysqli_stmt_get_result($getCount);
                        $count = mysqli_num_rows($getCountResult);
                    }

                    // build the actions column
                    $actions = "<div class='d-flex float-end'>
                        <div class='px-1'><button class='btn btn-primary' type='button' onclick='getEditTitleModal(".$title_id.");'><i class='fa-solid fa-pencil'></i></button></div>
                        <div class='px-1'><button class='btn btn-danger' type='button' onclick='getDeleteTitleModal(".$title_id.");'><i class='fa-solid fa-trash-can'></i></button></div>
                    </div>";

                    // build temporary array of data
                    $temp = [];
                    $temp["name"] = $name;
                    $temp["employees_count"] = $count;
                    $temp["actions"] = $actions;
                    
                    // add temporary array to master list
                    $titles[] = $temp;
                }
            }
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $titles;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>