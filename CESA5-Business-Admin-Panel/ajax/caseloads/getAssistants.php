<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold assistants to be displayed
        $assistants = [];
        
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // store user permissions for managing assistants
            $can_user_remove = checkUserPermission($conn, "REMOVE_THERAPISTS");

            // get the assistants
            $getAssistants = mysqli_prepare($conn, "SELECT a.*, e.fname, e.lname, ec.title_id, ec.active FROM caseload_assistants a 
                                                    JOIN employees e ON a.employee_id=e.id
                                                    LEFT JOIN employee_compensation ec ON e.id=ec.employee_id
                                                    WHERE ec.period_id=?");
            mysqli_stmt_bind_param($getAssistants, "i", $GLOBAL_SETTINGS["active_period"]);
            if (mysqli_stmt_execute($getAssistants))
            {
                $getAssistantsResults = mysqli_stmt_get_result($getAssistants);
                if (mysqli_num_rows($getAssistantsResults) > 0) // assistants exist; continue
                {
                    while ($assistant = mysqli_fetch_array($getAssistantsResults))
                    {
                        // store student data locally
                        $assistant_id = $assistant["id"];
                        $employee_id = $assistant["employee_id"];
                        $category_id = $assistant["category_id"];
                        $fname = $assistant["fname"];
                        $lname = $assistant["lname"];
                        $status = $assistant["active"];
                        $title = getTitleName($conn, $assistant["title_id"]);

                        // build the therapist display name
                        $name = $lname.", ".$fname;

                        // build the name and status column
                        $name_div = ""; // initialize div
                        $name_div .= "<div class='my-1'>
                            <span class='text-nowrap float-start'>$name</span>";
                            if ($status == 1) { $name_div .= "<div class='active-div text-center px-3 py-1 float-end'>Active</div>"; }
                            else { $name_div .= "<div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div>"; } 
                        $name_div .= "</div>";

                        // get caseload category name
                        $caseload_category = getCaseloadCategoryName($conn, $category_id);

                        // get the number of cases this assistant is assigned to
                        $caseload_count = 0;
                        $getCaseloadCount = mysqli_prepare($conn, "SELECT COUNT(id) AS caseload_count FROM cases WHERE assistant_id=? AND period_id=?");
                        mysqli_stmt_bind_param($getCaseloadCount, "ii", $assistant_id, $GLOBAL_SETTINGS["active_period"]);
                        if (mysqli_stmt_execute($getCaseloadCount))
                        {
                            $getCaseloadCountResult = mysqli_stmt_get_result($getCaseloadCount);
                            if (mysqli_num_rows($getCaseloadCountResult) > 0)
                            {
                                $caseload_count = mysqli_fetch_array($getCaseloadCountResult)["caseload_count"];
                            }
                        }

                        // build the actions column
                        $actions = "<div class='d-flex justify-content-end'>";
                            if ($can_user_remove === true) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getRemoveAssistantModal(".$assistant_id.");'><i class='fa-solid fa-trash-can'></i></button>"; }
                        $actions .= "</div>";

                        // build the temporary array of data
                        $temp = [];
                        $temp["assistant_id"] = $assistant_id;
                        $temp["employee_id"] = $employee_id;
                        $temp["name"] = $name_div;
                        $temp["title"] = $title;
                        $temp["caseload_category"] = $caseload_category;
                        $temp["caseload_count"] = $caseload_count;
                        $temp["actions"] = $actions;

                        // add the temporary array to the master list
                        $assistants[] = $temp;
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $assistants;
        echo json_encode($fullData);
    }
?>