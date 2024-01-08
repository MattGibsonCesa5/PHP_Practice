<?php
    // get the config
    require_once(__DIR__."/../includes/config.php");
    require_once(__DIR__."/../getSettings.php");

    // set timezone
    date_default_timezone_set("America/Chicago");

    // initialize current time
    $now = date("H:i:00");
    $day = date("N"); // 1 = Monday; 7 = Sunday
    $month = date("n");
    $day_of_month = date("j");

    // connect to the database
    $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

    // clean up the automation log nightly
    if ($now == "00:00:00")
    {
        $dateToClear = date("Y-m-d H:i:00");
        $clearLog = mysqli_prepare($conn, "DELETE FROM automation_log WHERE time<? AND message LIKE 'Looking for automation jobs set to%'");
        mysqli_stmt_bind_param($clearLog, "s", $dateToClear);
        if (mysqli_stmt_execute($clearLog))
        {
            $log_msg = "Cleaning the automation log...";
            $log = mysqli_prepare($conn, "INSERT INTO automation_log (message) VALUES (?)");
            mysqli_stmt_bind_param($log, "s", $log_msg);
            mysqli_stmt_execute($log);
        }
    }

    // log looking for jobs
    $log_msg = "Looking for automation jobs set to $now on day $day";
    $log = mysqli_prepare($conn, "INSERT INTO automation_log (message) VALUES (?)");
    mysqli_stmt_bind_param($log, "s", $log_msg);
    mysqli_stmt_execute($log);

    // jobs to run at midnight
    if ($now == "00:00:00")
    {
        // if the day is the first of the month, take a snapshot
        if ($day_of_month == 1)
        {
            // for each quarter, take a snapshot
            for ($q = 1; $q <= 4; $q++)
            {
                // log snapshot
                $log_msg = "Taking monthly snapshot at $now on $month/$day_of_month...";
                $log = mysqli_prepare($conn, "INSERT INTO automation_log (message) VALUES (?)");
                mysqli_stmt_bind_param($log, "s", $log_msg);
                mysqli_stmt_execute($log);

                // snapshot the quarter
                if (snapshotQuarter($conn, $GLOBAL_SETTINGS["active_period"], $quarter, $month, 1)) {
                    // log snapshot
                    $log_msg = "Successfully took a snapshot of Q$q for the period of ID ".$GLOBAL_SETTINGS["active_period"]." via automation.";
                    $log = mysqli_prepare($conn, "INSERT INTO automation_log (message) VALUES (?)");
                    mysqli_stmt_bind_param($log, "s", $log_msg);
                    mysqli_stmt_execute($log);
                } else {
                    // log snapshot
                    $log_msg = "Failed to take a snapshot of Q$q for the period of ID ".$GLOBAL_SETTINGS["active_period"]." via automation.";
                    $log = mysqli_prepare($conn, "INSERT INTO automation_log (message) VALUES (?)");
                    mysqli_stmt_bind_param($log, "s", $log_msg);
                    mysqli_stmt_execute($log);
                }
            }
        }
    }

    // check for enabled automation tasks
    $getTasks = mysqli_prepare($conn, "SELECT * FROM automation WHERE runtime=? AND enabled=1");
    mysqli_stmt_bind_param($getTasks, "s", $now);
    if (mysqli_stmt_execute($getTasks))
    {
        $getTasksResults = mysqli_stmt_get_result($getTasks);
        if (mysqli_num_rows($getTasksResults) > 0) // tasks found; run tasks
        {
            // log looking for jobs
            $log_msg = "Automation jobs found for $now on day $day";
            $log = mysqli_prepare($conn, "INSERT INTO automation_log (message) VALUES (?)");
            mysqli_stmt_bind_param($log, "s", $log_msg);
            mysqli_stmt_execute($log);

            while ($task = mysqli_fetch_array($getTasksResults))
            {
                // store task details locally
                $setting = $task["setting"];
                $cycle = $task["cycle"];
                $sun = $task["sunday"];
                $mon = $task["monday"];
                $tue = $task["tuesday"];
                $wed = $task["wednesday"];
                $thu = $task["thursday"];
                $fri = $task["friday"];
                $sat = $task["saturday"];

                ///////////////////////////////////////////////////////////////////////////////////
                //
                //  AUTO EMPLOYEES UPLOAD
                //
                ///////////////////////////////////////////////////////////////////////////////////
                if ($setting = "autoEmployeeUpload")
                {
                    // run job only if set to run today
                    if (($day == 1 && $mon == 1) ||
                        ($day == 2 && $tue == 1) ||
                        ($day == 3 && $wed == 1) ||
                        ($day == 4 && $thu == 1) ||
                        ($day == 5 && $fri == 1) ||
                        ($day == 6 && $sat == 1) ||
                        ($day == 7 && $sun == 1) ||
                        $cycle == $daily
                    ) {
                        // build command to run job job
                        $cmd = "php ".__DIR__."/auto_processEmployeesUpload.php 1";

                        // log looking for jobs
                        $log_msg = "Running autoEmployeeUpload at $now on day $day, using command: $cmd";
                        $log = mysqli_prepare($conn, "INSERT INTO automation_log (message) VALUES (?)");
                        mysqli_stmt_bind_param($log, "s", $log_msg);
                        mysqli_stmt_execute($log);

                        // run job
                        echo shell_exec($cmd);
                    }
                }
            }
        }
    }

    // disconnect from the database
    mysqli_close($conn);
?>