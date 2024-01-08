<?php
    session_start();

    // initalize array to store all automation settings
    $AUTOMATION_SETTINGS = [
        "autoEmployeeUpload"
    ];

    $AUTOMATION_SETTINGS_CYCLES = array(
        "autoEmployeeUpload" => [
            "daily",
            "weekly",
            "custom"
        ]
    );

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the automation setting
            if (isset($_POST["setting"]) && trim($_POST["setting"]) <> "") { $setting = trim($_POST["setting"]); } else { $setting = null; }

            if ($setting != null && in_array($setting, $AUTOMATION_SETTINGS)) // automation setting is set; continue
            {
                // get the value from POST for the automation setting's status
                if (isset($_POST["enabled"]) && is_numeric($_POST["enabled"])) { $enabled = $_POST["enabled"]; } else { $enabled = 0; }

                // disable the automation setting for all cycles
                $disableAutomation = mysqli_prepare($conn, "UPDATE automation SET enabled=0 WHERE setting=?");
                mysqli_stmt_bind_param($disableAutomation, "i", $setting);
                if (mysqli_stmt_execute($disableAutomation)) { }
                else { }

                if ($enabled == 1) // enable automation setting
                {
                    // get the cycle setting
                    if (isset($_POST["cycle"]) && trim($_POST["cycle"]) <> "") { $cycle = trim($_POST["cycle"]); } else { $cycle = null; }

                    if ($cycle != null && in_array($cycle, $AUTOMATION_SETTINGS_CYCLES[$setting]))
                    {
                        // get the runtime from POST
                        if (isset($_POST["runtime"]) && trim($_POST["runtime"]) <> "") { $runtime = trim($_POST["runtime"]); } else { $runtime = null; }

                        if ($runtime != null)
                        {
                            // convert runtime to proper database format
                            $runtime = date("H:i:00", strtotime($runtime));

                            // get the days to run automatoin on
                            if (isset($_POST["days"]) && trim($_POST["days"]) <> "") { $days = json_decode($_POST["days"]); } else { $days = null; }

                            if (is_array($days))
                            {
                                // initialize variables to store if we should run on that day or not; assume not running on day (0)
                                $sun = $mon = $tue = $wed = $thu = $fri = $sat = 0;

                                // check to see what days we are running automation on
                                if (in_array("sun", $days)) { $sun = 1; }
                                if (in_array("mon", $days)) { $mon = 1; }
                                if (in_array("tue", $days)) { $tue = 1; }
                                if (in_array("wed", $days)) { $wed = 1; }
                                if (in_array("thu", $days)) { $thu = 1; }
                                if (in_array("fri", $days)) { $fri = 1; }
                                if (in_array("sat", $days)) { $sat = 1; }

                                // check to see if automaton is already saved for this setting
                                $checkAutomation = mysqli_prepare($conn, "SELECT id FROM automation WHERE setting=? AND cycle=?");
                                mysqli_stmt_bind_param($checkAutomation, "ss", $setting, $cycle);
                                if (mysqli_stmt_execute($checkAutomation))
                                {
                                    $checkAutomationResult = mysqli_stmt_get_result($checkAutomation);
                                    if (mysqli_num_rows($checkAutomationResult) > 0) // automation setting exists; update existing entry
                                    {
                                        // store the current automation ID
                                        $automation_id = mysqli_fetch_array($checkAutomationResult)["id"];

                                        // update existing automation entry
                                        $updateAutomation = mysqli_prepare($conn, "UPDATE automation SET cycle=?, runtime=?, sunday=?, monday=?, tuesday=?, wednesday=?, thursday=?, friday=?, saturday=?, enabled=1 WHERE id=?");
                                        mysqli_stmt_bind_param($updateAutomation, "ssiiiiiiii", $cycle, $runtime, $sun, $mon, $tue, $wed, $thu, $fri, $sat, $automation_id);
                                        if (mysqli_stmt_execute($updateAutomation)) { echo "Saved edit."; }
                                        else { echo "<span class=\"log-fail\">Failed</span> edit."; }
                                    }
                                    else // automation setting does not exist; insert new record
                                    {
                                        // add new automation entry
                                        $addAutomation = mysqli_prepare($conn, "INSERT INTO automation (setting, cycle, runtime, sunday, monday, tuesday, wednesday, thursday, friday, saturday, enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                                        mysqli_stmt_bind_param($addAutomation, "sssiiiiiii", $setting, $cycle, $runtime, $sun, $mon, $tue, $wed, $thu, $fri, $sat);
                                        if (mysqli_stmt_execute($addAutomation)) { echo "Saved"; }
                                        else { echo "<span class=\"log-fail\">Failed</span> to save"; }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>