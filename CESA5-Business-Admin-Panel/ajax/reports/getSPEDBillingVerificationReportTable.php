<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // build default user settings array
            $USER_SETTINGS = [];
            $USER_SETTINGS["dark_mode"] = 0;
            $USER_SETTINGS["page_length"] = 10;

            // get user's settings
            $getUserSettings = mysqli_prepare($conn, "SELECT * FROM user_settings WHERE user_id=?");
            mysqli_stmt_bind_param($getUserSettings, "i", $_SESSION["id"]);
            if (mysqli_stmt_execute($getUserSettings))
            {
                $getUserSettingsResult = mysqli_stmt_get_result($getUserSettings);
                if (mysqli_num_rows($getUserSettingsResult)) // user's settings found
                {
                    $USER_SETTINGS = mysqli_fetch_array($getUserSettingsResult);
                }
            }

            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["quarter"]) && is_numeric($_POST["quarter"])) { $quarter = $_POST["quarter"]; } else { $quarter = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // verify the quarter is valid
                if ($quarter != null && ($quarter >= 1 && $quarter <= 4))
                {
                    ?>
                        <table id="SPEDBillingVerification" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" colspan="2">Service</th>
                                    <th class="text-center py-1 px-2" colspan="2">Customer</th>
                                    <th class="text-center py-1 px-2" colspan="3">Unit Comparison</th>
                                    <th class="text-center py-1 px-2" colspan="3" style="text-align: center !important;">Q<?php echo $quarter; ?> Cost Comparison</th>
                                    <th class="text-center py-1 px-2" colspan="3" style="text-align: center !important;">Projected Annual Cost Comparison</th>
                                </tr>

                                <tr>
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Name</th>
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Name</th>
                                    <th class="text-center py-1 px-2">Billed</th>
                                    <th class="text-center py-1 px-2">Expected</th>
                                    <th class="text-center py-1 px-2">Difference</th>
                                    <th class="text-center py-1 px-2" style="text-align: center !important;">Billed</th>
                                    <th class="text-center py-1 px-2" style="text-align: center !important;">Expected</th>
                                    <th class="text-center py-1 px-2" style="text-align: center !important;">Difference</th>
                                    <th class="text-center py-1 px-2" style="text-align: center !important;">Billed</th>
                                    <th class="text-center py-1 px-2" style="text-align: center !important;">Expected</th>
                                    <th class="text-center py-1 px-2" style="text-align: center !important;">Difference</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("SPEDBillingVerification", "BAP_Report_SPEDBillingVerification_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    <?php
                }
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>