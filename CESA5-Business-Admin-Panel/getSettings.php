<?php    
    $GLOBAL_SETTINGS = [];

    // connect to the database
    $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

    // get the active period
    $getActivePeriod = mysqli_query($conn, "SELECT id FROM periods WHERE active=1");
    $GLOBAL_SETTINGS["active_period"] = mysqli_fetch_array($getActivePeriod)["id"];

    // get the comparison period
    $getComparisonPeriod = mysqli_query($conn, "SELECT id FROM periods WHERE comparison=1");
    if (mysqli_num_rows($getComparisonPeriod) > 0) { $GLOBAL_SETTINGS["comparison_period"] = mysqli_fetch_array($getComparisonPeriod)["id"]; }
    else { $GLOBAL_SETTINGS["comparison_period"] = 0; }

    // get additinal global settings
    $getSettings = mysqli_query($conn, "SELECT * FROM settings WHERE id=1");
    if (mysqli_num_rows($getSettings) > 0) // settings found; set GLOBAL_SETTINGS to settings found
    {
        $getSettingsResult = mysqli_fetch_array($getSettings);
        $GLOBAL_SETTINGS["maintenance_mode"] = $getSettingsResult["maintenance_mode"];
        $GLOBAL_SETTINGS["hours_per_workday"] = $getSettingsResult["hours_per_workday"];
        $GLOBAL_SETTINGS["FTE_days"] = $getSettingsResult["FTE_days"];
        $GLOBAL_SETTINGS["overhead_costs_fund"] = $getSettingsResult["overhead_costs_fund"];
        $GLOBAL_SETTINGS["inactivity_timeout"] = $getSettingsResult["inactivity_timeout"];
        $GLOBAL_SETTINGS["grant_indirect_rate"] = $getSettingsResult["grant_indirect_rate"];
        $GLOBAL_SETTINGS["service_contracts_gid"] = $getSettingsResult["service_contracts_gid"];
        $GLOBAL_SETTINGS["quarterly_invoices_gid"] = $getSettingsResult["quarterly_invoices_gid"];
        $GLOBAL_SETTINGS["caseloads_billing_gid"] = "";
        $GLOBAL_SETTINGS["caseloads_units_warning"] = $getSettingsResult["caseloads_units_warning"];
    }
    else // settings not found; set GLOBAL_SETTINGS to default values
    {
        $GLOBAL_SETTINGS["maintenance_mode"] = 1;
        $GLOBAL_SETTINGS["hours_per_workday"] = 7.5;
        $GLOBAL_SETTINGS["FTE_days"] = 190;
        $GLOBAL_SETTINGS["overhead_costs_fund"] = "25 E";
        $GLOBAL_SETTINGS["inactivity_timeout"] = 15;
        $GLOBAL_SETTINGS["grant_indirect_rate"] = 0.05;
        $GLOBAL_SETTINGS["service_contracts_gid"] = "";
        $GLOBAL_SETTINGS["quarterly_invoices_gid"] = "";
        $GLOBAL_SETTINGS["caseloads_billing_gid"] = "";
        $GLOBAL_SETTINGS["caseloads_units_warning"] = 200;
    }

    // get caseload categories
    $CASELOAD_CATEGORIES = [];
    $getCaseloadCategories = mysqli_query($conn, "SELECT * FROM caseload_categories ORDER BY name ASC");
    if (mysqli_num_rows($getCaseloadCategories) > 0)
    {
        while ($category = mysqli_fetch_assoc($getCaseloadCategories))
        {
            if (isset($category))
            {
                $CASELOAD_CATEGORIES[] = $category;
            }
        }
    }

    // disconnect from the database
    mysqli_close($conn);
?>