<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold caseloads to be displayed
        $caseloads = [];
        
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period was set
            if ($period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    // store if the period is editable
                    $is_editable = isPeriodEditable($conn, $period_id);

                    // store user permissions for managing caseloads
                    $can_user_add = checkUserPermission($conn, "ADD_THERAPISTS");
                    $can_user_remove = checkUserPermission($conn, "REMOVE_THERAPISTS");
                    $can_user_view_all = checkUserPermission($conn, "VIEW_CASELOADS_ALL");
                    $can_user_view_assigned = checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED");

                    // get the caseloads
                    $getCaseloads = mysqli_prepare($conn, "SELECT cl.id AS caseload_id, cc.id AS category_id, cc.name AS caseload_category, u.id AS therapist_id, u.fname, u.lname, ec.title_id, cs.status, cl.subcategory_id, cs.status FROM caseloads cl 
                                                            JOIN users u ON cl.employee_id=u.id
                                                            LEFT JOIN employees e ON u.email=e.email
                                                            LEFT JOIN employee_compensation ec ON e.id=ec.employee_id
                                                            LEFT JOIN caseload_categories cc ON cl.category_id=cc.id
                                                            LEFT JOIN caseloads_status cs ON cl.id=cs.caseload_id
                                                            WHERE (ec.period_id=? OR ec.period_id IS NULL) AND cs.period_id=?");
                    mysqli_stmt_bind_param($getCaseloads, "ii", $period_id, $period_id);
                    if (mysqli_stmt_execute($getCaseloads))
                    {
                        $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                        if (mysqli_num_rows($getCaseloadsResults) > 0) // caseloads exist; continue
                        {
                            while ($caseload = mysqli_fetch_array($getCaseloadsResults))
                            {
                                // store student data locally
                                $caseload_id = $caseload["caseload_id"];
                                $status = $caseload["status"];

                                // build the caseload display name
                                $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                                // get the number of cases this therapist has
                                $caseload_count = 0;
                                $getCaseloadCount = mysqli_prepare($conn, "SELECT COUNT(id) AS caseload_count FROM cases WHERE caseload_id=? AND period_id=?");
                                mysqli_stmt_bind_param($getCaseloadCount, "ii", $caseload_id, $period_id);
                                if (mysqli_stmt_execute($getCaseloadCount))
                                {
                                    $getCaseloadCountResult = mysqli_stmt_get_result($getCaseloadCount);
                                    if (mysqli_num_rows($getCaseloadCountResult) > 0)
                                    {
                                        $caseload_count = mysqli_fetch_array($getCaseloadCountResult)["caseload_count"];
                                    }
                                }

                                // build the name and status column
                                $name_div = ""; // initialize div
                                $name_div .= "<div class='my-1'>
                                    <span class='text-nowrap float-start'>$caseload_name</span>";
                                    if ($status == 1) { $name_div .= "<div class='active-div text-center px-3 py-1 float-end'>Active</div>"; }
                                    else { $name_div .= "<div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div>"; } 
                                $name_div .= "</div>";


                                // build the temporary array of data
                                $temp = [];
                                $temp["is_checked"] = "";
                                $temp["caseload_id"] = $caseload_id;
                                $temp["caseload_name"] = $name_div;
                                $temp["caseload_count"] = $caseload_count;

                                // add the temporary array to the master list
                                $caseloads[] = $temp;
                            }
                        }
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