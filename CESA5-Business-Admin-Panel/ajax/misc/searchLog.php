<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // include config
            include("../../includes/config.php");
            include("../../includes/functions.php");

            // set the timezone
            $DB_Timezone = HOST_TIMEZONE;
            date_default_timezone_set("America/Chicago");

            // initialize variable
            $log = [];

            // get search filters from POST
            if (isset($_POST["user_id"]) && $_POST["user_id"] <> "") { $user_id = $_POST["user_id"]; } else { $user_id = null; }
            if (isset($_POST["start"]) && $_POST["start"] <> "") 
            { 
                // convert date to DB date format
                $start = $end = date("Y-m-d H:i:s", strtotime($_POST["start"])); 
            } 
            else { $start = null; }
            if (isset($_POST["end"]) && $_POST["end"] <> "") 
            { 
                // convert date to DB date format
                $end = date("Y-m-d H:i:s", strtotime($_POST["end"])); 
            } 
            else { $end = null; }
            if (isset($_POST["records"]) && is_numeric($_POST["records"])) { $records = $_POST["records"]; } else { $records = null; }

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize search filters
            $user_filter = "";
            $start_filter = "";
            $end_filter = "";
            $limit_filter = "";

            // create search filters based on what filters were provided
            if ($user_id != null) { $user_filter = "user_id = ?"; }
            if ($start != null) { $start_filter = "time >= ?"; }
            if ($end != null) { $end_filter = "time <= ?"; }
            if ($records != null && is_numeric($records)) { $limit_filter = "LIMIT ?"; }

            // create the query
            $first = true;
            $last = false;
            $query = "SELECT * FROM log";
            if ($user_filter <> "") 
            { 
                if ($first === true) 
                { 
                    $query .= " WHERE"; 
                    $first = false;
                }
                $query .= " $user_filter"; 
                $last = true;
            }
            if ($start_filter <> "")
            {
                if ($first === true) 
                { 
                    $query .= " WHERE"; 
                    $first = false;
                }
                if ($last === true) { $query .= " AND"; }
                $query .= " $start_filter";
                $last = true;
            }
            if ($end_filter <> "")
            {
                if ($first === true) 
                { 
                    $query .= " WHERE"; 
                    $first = false;
                }
                if ($last === true) { $query .= " AND"; }
                $query .= " $end_filter";
                $last = true;
            }
            $query .= " ORDER BY id DESC, time DESC";
            if ($records != null && is_numeric($records)) { $query .= " $limit_filter"; }

            // prepare the query
            $getLog = mysqli_prepare($conn, $query);

            // build the string to store parameter types
            $types = "";
            if ($user_filter <> "") { $types .= "i"; }
            if ($start_filter <> "") { $types .= "s"; }
            if ($end_filter <> "") { $types .= "s"; }
            if ($limit_filter <> "") { $types .= "i"; }

            // build the array to store variables to bind
            $bindVars = [];
            if ($user_filter <> "") { $bindVars[] = $user_id; }
            if ($start_filter <> "") { $bindVars[] = $start; }
            if ($end_filter <> "") { $bindVars[] = $end; }
            if ($limit_filter <> "") { $bindVars[] = $records; }

            // bind the parameters
            if ($user_filter <> "" || $start_filter <> "" || $end_filter <> "" || $limit_filter <> "")
            {
                mysqli_stmt_bind_param($getLog, $types, ...$bindVars);
            }

            // execute query to get the log
            if (mysqli_stmt_execute($getLog))
            {
                $getLogResults = mysqli_stmt_get_result($getLog);
                if (mysqli_num_rows($getLogResults) > 0)
                {
                    while ($entry = mysqli_fetch_array($getLogResults))
                    {
                        // initialize temporary array to store current log entry results
                        $temp = [];

                        // reset all variables
                        $log_user = $log_time = $log_msg = $user_email = $user_name = $user_role = "";

                        // store log fields locally
                        $log_record = $entry["id"];
                        $log_user = $entry["user_id"];
                        $log_time = $entry["time"];
                        $log_msg = $entry["message"];

                        // get user details based on user ID
                        if ($log_user == 0) // user was a super admin 
                        { 
                            $user_email = SUPER_LOGIN_EMAIL; 
                            $user_name = "SUPER ADMIN";
                            $user_role = "SUPER ADMIN";
                        }
                        else if ($log_user == -2) // automation
                        {
                            $user_email = "-"; 
                            $user_name = "AUTOMATION";
                            $user_role = "-";
                        }
                        else // user was not a super admin, get additional user information
                        {
                            $getUserInfo = mysqli_prepare($conn, "SELECT u.id, u.email, r.name AS role FROM users u 
                                                                    JOIN roles r ON u.role_id=r.id
                                                                    WHERE u.id=?");
                            mysqli_stmt_bind_param($getUserInfo, "i", $log_user);
                            if (mysqli_stmt_execute($getUserInfo))
                            {
                                $getUserInfoResults = mysqli_stmt_get_result($getUserInfo);
                                if (mysqli_num_rows($getUserInfoResults) > 0) // user info found
                                {
                                    // store user info locally
                                    $user_info = mysqli_fetch_array($getUserInfoResults);
                                    $user_email = $user_info["email"];
                                    $user_name = getUserDisplayName($conn, $log_user);
                                    $user_role = $user_info["role"];
                                }
                                else
                                {
                                    $user_email = "Unknown";
                                    $user_name = "Unknown";
                                    $user_role = "Unknown";
                                }
                            }
                            else
                            {
                                $user_email = "Unknown";
                                $user_name = "Unknown";
                                $user_role = "Unknown";
                            }
                        }

                        // build the temporary array
                        $temp["record"] = $log_record;
                        $temp["time"] = date_convert($log_time, $DB_Timezone, "America/Chicago", "n/j/Y g:i:s A");
                        $temp["user_id"] = $log_user;
                        $temp["user_name"] = $user_name;
                        $temp["user_email"] = $user_email;
                        $temp["user_role"] = $user_role;
                        $temp["message"] = $log_msg;
                        $log[] = $temp;
                    }

                    // print the table
                    ?>
                        <table id="log" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th>Record</th>
                                    <th>Time</th>
                                    <th>User ID</th>
                                    <th>User Name</th>
                                    <th>User Email</th>
                                    <th>User Role</th>
                                    <th>Log Message</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                    for ($x = 0; $x < count($log); $x++)
                                    {
                                        ?>
                                            <tr>
                                                <td><?php echo $log[$x]["record"]; ?></td>
                                                <td><span class='d-none' aria-hidden='true'><?php echo strtotime($log[$x]["time"]); ?></span><?php echo date("n/j/Y g:i:s A", strtotime($log[$x]["time"])); ?></td>
                                                <td><?php echo $log[$x]["user_id"]; ?></td>
                                                <td><?php echo $log[$x]["user_name"]; ?></td> 
                                                <td><?php echo $log[$x]["user_email"]; ?></td>
                                                <td><?php echo $log[$x]["user_role"]; ?></td>
                                                <td><?php echo $log[$x]["message"]; ?></td>
                                            </tr>
                                        <?php
                                    }
                                ?>
                            </tbody>
                        </table>
                        <?php createTableFooter("log"); ?>
                    <?php
                }
                else 
                { 
                    ?>
                        <p class="text-center">No log entries found for the provided search filters.</p>
                    <?php
                }
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>