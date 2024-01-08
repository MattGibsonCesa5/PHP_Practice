<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold caseloads to be displayed
        $caseloads = [];
        
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get the caseloads
            $getCaseloads = mysqli_prepare($conn, "SELECT cl.id AS caseload_id, cc.id AS category_id, cc.name AS caseload_category, u.id AS therapist_id, u.fname, u.lname, ec.title_id, cs.status, cl.subcategory_id, cs.status FROM caseloads cl 
                                                    JOIN users u ON cl.employee_id=u.id
                                                    LEFT JOIN employees e ON u.email=e.email
                                                    LEFT JOIN employee_compensation ec ON e.id=ec.employee_id
                                                    LEFT JOIN caseload_categories cc ON cl.category_id=cc.id
                                                    LEFT JOIN caseloads_status cs ON cl.id=cs.caseload_id
                                                    WHERE (ec.period_id=? OR ec.period_id IS NULL) AND cs.period_id=?");
            mysqli_stmt_bind_param($getCaseloads, "ii", $GLOBAL_SETTINGS["active_period"], $GLOBAL_SETTINGS["active_period"]);
            if (mysqli_stmt_execute($getCaseloads))
            {
                $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                if (mysqli_num_rows($getCaseloadsResults) > 0) // caseloads exist; continue
                {
                    while ($caseload = mysqli_fetch_array($getCaseloadsResults))
                    {
                        // store caseload data locally
                        $caseload_id = $caseload["caseload_id"];
                        $status = $caseload["status"];

                        // build the caseload display name
                        $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                        // build the name and status column
                        $name_div = ""; // initialize div
                        $name_div .= "<div class='my-1'>
                            <span class='text-nowrap float-start'>$caseload_name</span>";
                            if ($status == 1) { $name_div .= "<div class='active-div text-center px-3 py-1 float-end'>Active</div>"; }
                            else { $name_div .= "<div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div>"; } 
                        $name_div .= "</div>";

                        // build the temporary array of data
                        $temp = [];
                        $temp["checked"] = "";
                        $temp["caseload_id"] = $caseload_id;
                        $temp["caseload_name"] = $name_div;

                        // add the temporary array to the master list
                        $caseloads[] = $temp;
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $caseloads;
        echo json_encode($fullData);
    }
?>