<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // include config
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // initialize array to store all users
        $users = [];

        // get notificaton ID from POST
        if (isset($_POST["notification_id"])) { $notification_id = $_POST["notification_id"]; } else { $notification_id = null; }

        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            if ($notification_id != null)
            {
                // get all active users
                $getUsers = mysqli_query($conn, "SELECT u.id, u.lname, u.fname, u.email FROM users u
                                                    WHERE u.status=1
                                                    ORDER BY u.lname ASC, u.fname ASC");
                if (mysqli_num_rows($getUsers) > 0)
                {
                    while ($user = mysqli_fetch_array($getUsers))
                    {
                        // store user details locall 
                        $user_id = $user["id"];
                        $lname = $user["lname"];
                        $fname = $user["fname"];
                        $email = $user["email"];

                        // initialize notification settings
                        $subscribed = $frequency = $enrolled = 0;

                        // get notification settings for the user
                        $getNotificationSettings = mysqli_prepare($conn, "SELECT subscribed, frequency, enrolled FROM email_recipients WHERE user_id=? AND type_id=? LIMIT 1");
                        mysqli_stmt_bind_param($getNotificationSettings, "ii", $user_id, $notification_id);
                        if (mysqli_stmt_execute($getNotificationSettings))
                        {
                            $getNotificationSettingsResults = mysqli_stmt_get_result($getNotificationSettings);
                            if (mysqli_num_rows($getNotificationSettingsResults) > 0)
                            {
                                // store notification settings locally
                                $notification_settings = mysqli_fetch_array($getNotificationSettingsResults);
                                $subscribed = $notification_settings["subscribed"];
                                $frequency = $notification_settings["frequency"];
                                $enrolled = $notification_settings["enrolled"];
                            }
                        }

                        // build temporary array to return
                        $temp["user_id"] = $user_id;
                        $temp["lname"] = $lname;
                        $temp["fname"] = $fname;
                        $temp["email"] = $email;
                        $temp["subscribed"] = $subscribed;
                        $temp["frequency"] = $frequency;
                        $temp["enrolled"] = $enrolled;
                        $users[] = $temp;
                    }
                }
            }
        }

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $users;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>