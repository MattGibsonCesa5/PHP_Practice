<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]))
        {
            // include config
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the parameters from POST
            if (isset($_POST["dark_mode"])) { $dark_mode = $_POST["dark_mode"]; } else { $dark_mode = 0; }
            if (isset($_POST["page_length"])) { $page_length = $_POST["page_length"]; } else { $page_length = 10; }

            // check to see if user has settings account already
            $checkSettings = mysqli_prepare($conn, "SELECT user_id FROM user_settings WHERE user_id=?");
            mysqli_stmt_bind_param($checkSettings, "i", $_SESSION["id"]);
            if (mysqli_stmt_execute($checkSettings))
            {
                $checkSettingsResult = mysqli_stmt_get_result($checkSettings);
                if (mysqli_num_rows($checkSettingsResult) > 0) // user has settings saved; update entry
                {
                    $updateProfile = mysqli_prepare($conn, "UPDATE user_settings SET dark_mode=?, page_length=? WHERE user_id=?");
                    mysqli_stmt_bind_param($updateProfile, "iii", $dark_mode, $page_length, $_SESSION["id"]);
                    if (mysqli_stmt_execute($updateProfile)) { echo "<span class=\"log-success\">Successfully</span> saved your settings."; }
                    else { echo "<span class=\"log-fail\">Failed</span> to update your profile."; }
                }
                else // user does not have settings saved; insert new entry
                {
                    $updateProfile = mysqli_prepare($conn, "INSERT INTO user_settings (user_id, dark_mode, page_length) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($updateProfile, "iii", $_SESSION["id"], $dark_mode, $page_length);
                    if (mysqli_stmt_execute($updateProfile)) { echo "<span class=\"log-success\">Successfully</span> saved your settings."; }
                    else { echo "<span class=\"log-fail\">Failed</span> to update your profile."; }
                }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to update your profile."; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>