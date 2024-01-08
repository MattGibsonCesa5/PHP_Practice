<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to queue
        $queue = [];

        // verify user is an admin
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // override server settings
            ini_set("max_execution_time", 1800); // cap to 30 minutes
            ini_set("memory_limit", "1024M"); // cap to 1024 MB (1 GB)

            // include additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");
            include("../../getSettings.php");
            
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get sync queue
            $getQueue = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, e.email, e.phone, e.original_hire_date, e.original_end_date, e.updated FROM employees e WHERE e.queued=1");
            if (mysqli_stmt_execute($getQueue))
            {
                $getQueueResult = mysqli_stmt_get_result($getQueue);
                if (mysqli_num_rows($getQueueResult) > 0) // there are employee changes queued to sync
                {
                    while ($employee = mysqli_fetch_array($getQueueResult))
                    {
                        // store employee details locally
                        $employee_id = $employee["id"];
                        $fname = $employee["fname"];
                        $lname = $employee["lname"];
                        $email = $employee["email"];
                        $phone = $employee["phone"];
                        $original_hire_date = $employee["original_hire_date"];
                        $original_end_date = $employee["original_end_date"];
                        $sync_time = $employee["updated"];
                        
                        // handle contract date validation
                        if (isset($original_hire_date) && $original_hire_date != null) { $original_hire_date = date("m/d/Y", strtotime($original_hire_date)); } else { $original_hire_date = ""; }
                        if (isset($original_end_date) && $original_end_date != null) { $original_end_date = date("m/d/Y", strtotime($original_end_date)); } else { $original_end_date = ""; }
                        
                        // build the actions column
                        $actions = "<div class='d-flex justify-content-end'>
                            <button class='btn btn-success btn-sm mx-1' id='btn-action-success-new-".$employee_id."' onclick='syncNew(".$employee_id.", 1);'>
                                <i class='fa-solid fa-check'></i>
                            </button>

                            <button class='btn btn-danger btn-sm mx-1' id='btn-action-danger-new-".$employee_id."' onclick='syncNew(".$employee_id.", 0);'>
                                <i class='fa-solid fa-xmark'></i>
                            </button>
                        </div>";

                        // build the temporary array
                        $temp = [];
                        $temp["id"] = $employee_id;
                        $temp["lname"] = $lname;
                        $temp["fname"] = $fname;
                        $temp["email"] = $email;
                        $temp["phone"] = $phone;
                        $temp["hire_date"] = $original_hire_date;
                        $temp["end_date"] = $original_end_date;
                        $temp["sync_time"] = date("m/d/Y H:i:s", strtotime($sync_time));
                        $temp["actions"] = $actions;

                        $queue[] = $temp;
                    }
                }
            }
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $queue;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>