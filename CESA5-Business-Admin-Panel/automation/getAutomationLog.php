<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // include config
            include("../includes/config.php");
            include("../includes/functions.php");

            // set the timezone
            $DB_Timezone = HOST_TIMEZONE;
            date_default_timezone_set("America/Chicago");

            // initialize variable
            $log = [];

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            $getLog = mysqli_query($conn, "SELECT * FROM automation_log ORDER BY time DESC");
            if (mysqli_num_rows($getLog) > 0)
            {
                while ($entry = mysqli_fetch_array($getLog))
                {
                    // initialize temporary array to store current log entry results
                    $temp = [];

                    // reset all variables
                    $log_record = $log_time = $log_msg = "";

                    // store log fields locally
                    $log_record = $entry["id"];
                    $log_time = $entry["time"];
                    $log_msg = $entry["message"];

                    // build the temporary array
                    $temp["record"] = $log_record;
                    $temp["time"] = date_convert($log_time, $DB_Timezone, "America/Chicago", "n/j/Y g:i:s A");
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
                                            <td><?php echo $log[$x]["message"]; ?></td>
                                        </tr>
                                    <?php
                                }
                            ?>
                        </tbody>
                    </table>
                    <?php createTableFooterV2("log"); ?>
                <?php
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>