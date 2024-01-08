<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize variable to store the report data to return
        $report = [];

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

        // verify user permissions
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // get the category from POST
            if (isset($_POST["category"]) && $_POST["category"] <> "") { $category_id = $_POST["category"]; } else { $category_id = null; }

            // ensure both required search parameters are set
            if ($period != null && $category_id != null)
            {
                // verify the period exists; if it exists, store the period ID
                if ($period_id = getPeriodID($conn, $period)) 
                {
                    // verify the caseload category exists and is valid
                    if (verifyCaseloadCategory($conn, $category_id))
                    {
                        // get category settings
                        $category_settings = getCaseloadCategorySettings($conn, $category_id);

                        // create the table to be displayed
                        if ($category_settings["is_classroom"] == 1) { ?>
                            <table id="UOSQuarterlyBilling_district" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th style="text-align: center !important;">District</th>
                                        <th style="text-align: center !important;">Location</th>
                                        <th style="text-align: center !important;">Student</th>
                                        <th style="text-align: center !important;">Membership Days</th>
                                    </tr>
                                </thead>

                                <tfoot>
                                    <tr>
                                        <th colspan="3"></th>
                                        <th class="py-1" id="district-sum-days"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php } else if ($category_settings["uos_enabled"] == 1) { ?>
                            <table id="UOSQuarterlyBilling_district" class="report_table w-100">
                                <thead>
                                    <tr>
                                        <th style="text-align: center !important;">District</th>
                                        <th style="text-align: center !important;">Student</th>
                                        <th style="text-align: center !important;">Therapist</th>
                                        <th style="text-align: center !important;">Units Of Service Billed</th>
                                    </tr>
                                </thead>

                                <tfoot>
                                    <tr>
                                        <th colspan="3"></th>
                                        <th class="py-1" id="district-sum-units"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>