<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // get selected period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period_id = $_POST["period"]; } else { $period_id = null; }
            
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get and verify the period
            if ($period_id != null && verifyPeriod($conn, $period_id))
            {
                // get the period label
                $period_details = getPeriodDetails($conn, $period_id);
                $period_label = $period_details["name"];

                // prepare and execute the query to add a year of experience to all employees
                $addYear = mysqli_prepare($conn, "UPDATE employee_compensation SET experience=experience+1 WHERE period_id=?");
                mysqli_stmt_bind_param($addYear, "i", $period_id);
                if (mysqli_stmt_execute($addYear)) 
                { 
                    echo "<span class=\"log-success\">Successfully</span> added a year of experience to all employees in $period_label.<br>"; 

                    // log adding year of experience
                    $message = "Successfully added a year of experience for all employees within the period with the ID of $period_id.";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);
                }
                else { echo "<span class=\"log-fail\">Failed</span> to add a year of experience to all employees in $period_label.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add a year of experience to all employees. The period selected is invalid.<br>"; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>