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

        // get period name from POST
        if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

        // verify the period was set
        if ($period != null && $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
        {
            // store if the period is editable
            $is_editable = isPeriodEditable($conn, $period_id);

            // store user permissions for managing caseloads
            $can_user_add = checkUserPermission($conn, "ADD_THERAPISTS");
            $can_user_remove = checkUserPermission($conn, "REMOVE_THERAPISTS");
            $can_user_view_all = checkUserPermission($conn, "VIEW_CASELOADS_ALL");
            $can_user_view_assigned = checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED");

            ///////////////////////////////////////////////////////////////////////////////////////////
            //
            //  VIEW ALL CASELOADS
            //
            ///////////////////////////////////////////////////////////////////////////////////////////
            if (checkUserPermission($conn, "VIEW_THERAPISTS") && checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
            {
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
                            $therapist_id = $caseload["therapist_id"];
                            $category_id = $caseload["category_id"];
                            $fname = $caseload["fname"];
                            $lname = $caseload["lname"];
                            $status = $caseload["status"];
                            $title = getTitleName($conn, $caseload["title_id"]);
                            $caseload_category = $caseload["caseload_category"];
                            $subcategory_id = $caseload["subcategory_id"];

                            // build the therapist display name
                            $name = $lname.", ".$fname;

                            // get the subcategory name
                            $subcategory_name = getCaseloadSubcategoryName($conn, $subcategory_id);

                            // build teh category display
                            $category_display = "";
                            $category_display .= "<p class='m-0'>".$caseload_category."</p>";
                            if ($subcategory_name <> "") { $category_display .= "<p class='m-0 fst-italic'>".$subcategory_name."</p>"; }

                            // get the number of cases this therapist has
                            $caseload_count = 0;
                            $getCaseloadCount = mysqli_prepare($conn, "SELECT COUNT(id) AS caseload_count FROM cases WHERE caseload_id=? AND period_id=? AND active=1");
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
                                <form class='w-100' method='POST' action='caseload.php'>
                                    <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                    <input type='hidden' id='therapist_id' name='therapist_id' value='".$therapist_id."' aria-hidden='true'>
                                    <input type='hidden' id='category_id' name='category_id' value='".$category_id."' aria-hidden='true'>
                                    <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                    <button class='btn btn-therapist_caseload w-100' type='submit'>
                                        <span class='text-nowrap float-start'>$name</span>";
                                        if ($status == 1) { $name_div .= "<div class='active-div text-center px-3 py-1 float-end'>Active</div>"; }
                                        else { $name_div .= "<div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div>"; } 
                                    $name_div .= "</button>
                                </form>
                            </div>";

                            // build the actions column
                            $actions = "<div class='d-flex justify-content-end'>";
                                if ($can_user_view_all === true || $can_user_view_assigned === true) 
                                { 
                                    $actions .= "<div>
                                        <form method='POST' action='caseload.php'>
                                            <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                            <input type='hidden' id='therapist_id' name='therapist_id' value='".$therapist_id."' aria-hidden='true'>
                                            <input type='hidden' id='category_id' name='category_id' value='".$category_id."' aria-hidden='true'>
                                            <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                            <button class='btn btn-primary btn-sm mx-1' type='submit' title='View Caseload'>
                                                <i class='fa-solid fa-eye'></i>
                                            </button>
                                        </form>
                                    </div>"; 
                                }
                                if ($can_user_add === true && $is_editable === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditCaseloadModal(".$caseload_id.");' title='Edit Caseload'><i class='fa-solid fa-pencil'></i></button>"; }
                                if ($can_user_add === true && $is_editable === true) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getTransferCasesModal(".$caseload_id.");' title='Transfer Caseload'><i class='fa-solid fa-right-left'></i></button>"; }
                                if ($_SESSION["role"] == 1) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteCaseloadModal(".$caseload_id.");' title='Delete Caseload'><i class='fa-solid fa-trash-can'></i></button>"; }
                            $actions .= "</div>";

                            // build the status column to be filtered by
                            $filter_status = "";
                            if ($status == 1) { $filter_status = "Active"; }
                            else { $filter_status = "Inactive"; }

                            // calculate total units for the caseload
                            $caseload_units = getCaseloadUnits($conn, $caseload_id, $period_id);

                            // build the temporary array of data
                            $temp = [];
                            $temp["order"] = 1;
                            $temp["caseload_id"] = $caseload_id;
                            $temp["therapist_id"] = $therapist_id;
                            $temp["name"] = $name_div;
                            $temp["title"] = $title;
                            $temp["caseload_category"] = $category_display;
                            $temp["caseload_count"] = $caseload_count;
                            $temp["caseload_units"] = number_format($caseload_units);
                            $temp["caseload_status"] = $filter_status;
                            $temp["actions"] = $actions;
                            $temp["export_name"] = $name;
                            if ($status == 1) { $temp["export_status"] = "Active"; } else { $temp["export_status"] = "Inactive"; }

                            // add the temporary array to the master list
                            $caseloads[] = $temp;
                        }
                    }
                }

                // get DEMO caseloads
                $getCategories = mysqli_query($conn, "SELECT id, name FROM caseload_categories ORDER BY name ASC");
                if (mysqli_num_rows($getCategories) > 0)
                {
                    while ($category = mysqli_fetch_array($getCategories))
                    {
                        // store category details locally
                        $category_id = $category["id"];
                        $category_name = $category["name"];

                        // build demo caseload name
                        $demo_caseload_id = $category_id * -1;
                        $demo_caseload_name = "<i class=\"fa-solid fa-helmet-safety\"></i> ".$category_name;
                        $demo_title = "<i>DEMO CASELOAD</i>";

                        // build the name div
                        $name_div = ""; // initialize div
                        $name_div .= "<div class='my-1'>
                            <form class='w-100' method='POST' action='caseload.php'>
                                <input type='hidden' id='caseload_id' name='caseload_id' value='".$demo_caseload_id."' aria-hidden='true'>
                                <input type='hidden' id='category_id' name='category_id' value='".$category_id."' aria-hidden='true'>
                                <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                <button class='btn btn-therapist_caseload w-100' type='submit'>
                                    <span class='text-nowrap float-start'>$demo_caseload_name</span>
                                    <div class='demo-div text-center px-3 py-1 float-end'>Demo</div>
                                </button>
                            </form>
                        </div>";

                        // build the actions column
                        $actions = "<div class='d-flex justify-content-end'>
                            <form method='POST' action='caseload.php'>
                                <input type='hidden' id='caseload_id' name='caseload_id' value='".$demo_caseload_id."' aria-hidden='true'>
                                <input type='hidden' id='category_id' name='category_id' value='".$category_id."' aria-hidden='true'>
                                <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                <button class='btn btn-primary btn-sm mx-1' type='submit' title='View Caseload'>
                                    <i class='fa-solid fa-eye'></i>
                                </button>
                            </form>
                        </div>"; 

                        // build the DEMO caseload
                        $temp = [];
                        $temp["order"] = 2;
                        $temp["caseload_id"] = $demo_caseload_id;
                        $temp["name"] = $name_div;
                        $temp["title"] = $demo_title;
                        $temp["caseload_category"] = $category_name;
                        $temp["caseload_count"] = 0;
                        $temp["caseload_units"] = 0;
                        $temp["caseload_status"] = "Active";
                        $temp["actions"] = $actions;
                        $temp["export_name"] = $name;
                        if ($status == 1) { $temp["export_status"] = "Active"; } else { $temp["export_status"] = "Inactive"; }

                        // add the DEMO caseload to the master list
                        $caseloads[] = $temp;
                    }
                }
            }
            ///////////////////////////////////////////////////////////////////////////////////////////
            //
            //  VIEW MY CASELOADS
            //
            ///////////////////////////////////////////////////////////////////////////////////////////
            if (checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED"))
            {
                ///////////////////////////////////////////////////////////////////////////////////////////
                //
                //  THERAPISTS
                //
                ///////////////////////////////////////////////////////////////////////////////////////////
                if (!verifyCoordinator($conn, $_SESSION["id"]))
                {
                    // get the caseloads
                    $getMyCaseloads = mysqli_prepare($conn, "SELECT cl.id AS caseload_id, cs.status, cs.status FROM caseloads cl 
                                                            LEFT JOIN caseloads_status cs ON cl.id=cs.caseload_id
                                                            WHERE cs.period_id=? AND cl.employee_id=? AND cs.status=1");
                    mysqli_stmt_bind_param($getMyCaseloads, "ii", $period_id, $_SESSION["id"]);
                    if (mysqli_stmt_execute($getMyCaseloads))
                    {
                        $getMyCaseloadsResults = mysqli_stmt_get_result($getMyCaseloads);
                        if (mysqli_num_rows($getMyCaseloadsResults) > 0) // caseloads exist; continue
                        {
                            while ($caseload = mysqli_fetch_array($getMyCaseloadsResults))
                            {
                                // store caseload data locally
                                $caseload_id = $caseload["caseload_id"];

                                // build the caseload display name
                                $name = getCaseloadDisplayName($conn, $caseload_id);

                                // get the number of cases this therapist has
                                $caseload_count = 0;
                                $getCaseloadCount = mysqli_prepare($conn, "SELECT COUNT(id) AS caseload_count FROM cases WHERE caseload_id=? AND period_id=? AND active=1");
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
                                $name_div = "<div class='my-1'>
                                    <form class='w-100' method='POST' action='caseload.php'>
                                        <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                        <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                        <button class='btn btn-therapist_caseload w-100' type='submit'>
                                            <span class='text-nowrap float-start'>$name</span>
                                        </button>
                                    </form>
                                </div>";

                                // calculate total units for the caseload
                                $caseload_units = getCaseloadUnits($conn, $caseload_id, $period_id);

                                // build the temporary array of data
                                $temp = [];
                                $temp["order"] = 1;
                                $temp["caseload_id"] = $caseload_id;
                                $temp["name"] = $name_div;
                                $temp["caseload_count"] = $caseload_count;
                                $temp["caseload_units"] = number_format($caseload_units);

                                // add the temporary array to the master list
                                $caseloads[] = $temp;
                            }
                        }
                    }

                    // get DEMO caseloads
                    $getCategories = mysqli_prepare($conn, "SELECT cc.id, cc.name FROM caseload_categories cc 
                                                            JOIN caseloads cl ON cc.id=cl.category_id
                                                            WHERE cl.employee_id=?
                                                            ORDER BY cc.name ASC");
                    mysqli_stmt_bind_param($getCategories, "i", $_SESSION["id"]);
                    if (mysqli_stmt_execute($getCategories))
                    {
                        $getCategoriesResults = mysqli_stmt_get_result($getCategories);
                        if (mysqli_num_rows($getCategoriesResults) > 0)
                        {
                            while ($category = mysqli_fetch_array($getCategoriesResults))
                            {
                                // store category details locally
                                $category_id = $category["id"];
                                $category_name = $category["name"];

                                // build demo caseload name
                                $demo_caseload_id = $category_id * -1;
                                $demo_caseload_name = "<i class=\"fa-solid fa-helmet-safety\"></i> ".$category_name;
                                $demo_title = "<i>DEMO CASELOAD</i>";

                                // build the name div
                                $name_div = ""; // initialize div
                                $name_div .= "<div class='my-1'>
                                    <form class='w-100' method='POST' action='caseload.php'>
                                        <input type='hidden' id='caseload_id' name='caseload_id' value='".$demo_caseload_id."' aria-hidden='true'>
                                        <input type='hidden' id='category_id' name='category_id' value='".$category_id."' aria-hidden='true'>
                                        <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                        <button class='btn btn-therapist_caseload w-100' type='submit'>
                                            <span class='text-nowrap float-start'>$demo_caseload_name</span>
                                            <div class='demo-div text-center px-3 py-1 float-end'>Demo</div>
                                        </button>
                                    </form>
                                </div>";

                                // build the actions column
                                $actions = "<div class='d-flex justify-content-end'>
                                    <form method='POST' action='caseload.php'>
                                        <input type='hidden' id='caseload_id' name='caseload_id' value='".$demo_caseload_id."' aria-hidden='true'>
                                        <input type='hidden' id='category_id' name='category_id' value='".$category_id."' aria-hidden='true'>
                                        <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                        <button class='btn btn-primary btn-sm mx-1' type='submit' title='View Caseload'>
                                            <i class='fa-solid fa-eye'></i>
                                        </button>
                                    </form>
                                </div>"; 

                                // build the DEMO caseload
                                $temp = [];
                                $temp["order"] = 2;
                                $temp["caseload_id"] = $demo_caseload_id;
                                $temp["name"] = $name_div;
                                $temp["title"] = $demo_title;
                                $temp["caseload_category"] = $category_name;
                                $temp["caseload_count"] = 0;
                                $temp["caseload_units"] = 0;
                                $temp["caseload_status"] = "Active";
                                $temp["actions"] = $actions;

                                // add the DEMO caseload to the master list
                                $caseloads[] = $temp;
                            }
                        }
                    }
                }
                ///////////////////////////////////////////////////////////////////////////////////////////
                //
                //  COORDINATORS
                //
                ///////////////////////////////////////////////////////////////////////////////////////////
                else
                {
                    // get the caseloads assigned to the coordinator
                    $getMyCaseloads = mysqli_prepare($conn, "SELECT cl.id AS caseload_id, cs.status, cs.status FROM caseloads cl 
                                                            LEFT JOIN caseloads_status cs ON cl.id=cs.caseload_id
                                                            LEFT JOIN caseload_coordinators_assignments ca ON cl.id=ca.caseload_id
                                                            WHERE cs.period_id=? AND cs.status=1 AND (ca.user_id=? OR cl.employee_id=?)");
                    mysqli_stmt_bind_param($getMyCaseloads, "iii", $period_id, $_SESSION["id"], $_SESSION["id"]);
                    if (mysqli_stmt_execute($getMyCaseloads))
                    {
                        $getMyCaseloadsResults = mysqli_stmt_get_result($getMyCaseloads);
                        if (mysqli_num_rows($getMyCaseloadsResults) > 0) // caseloads exist; continue
                        {
                            while ($caseload = mysqli_fetch_array($getMyCaseloadsResults))
                            {
                                // store caseload data locally
                                $caseload_id = $caseload["caseload_id"];

                                // build the caseload display name
                                $name = getCaseloadDisplayName($conn, $caseload_id);

                                // get the number of cases this therapist has
                                $caseload_count = 0;
                                $getCaseloadCount = mysqli_prepare($conn, "SELECT COUNT(id) AS caseload_count FROM cases WHERE caseload_id=? AND period_id=? AND active=1");
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
                                $name_div = "<div class='my-1'>
                                    <form class='w-100' method='POST' action='caseload.php'>
                                        <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                        <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                        <button class='btn btn-therapist_caseload w-100' type='submit'>
                                            <span class='text-nowrap float-start'>$name</span>
                                        </button>
                                    </form>
                                </div>";

                                // calculate total units for the caseload
                                $caseload_units = getCaseloadUnits($conn, $caseload_id, $period_id);

                                // build the temporary array of data
                                $temp = [];
                                $temp["order"] = 1;
                                $temp["caseload_id"] = $caseload_id;
                                $temp["name"] = $name_div;
                                $temp["caseload_count"] = $caseload_count;
                                $temp["caseload_units"] = number_format($caseload_units);

                                // add the temporary array to the master list
                                $caseloads[] = $temp;
                            }
                        }
                    }

                    // get DEMO caseloads
                    $getCategories = mysqli_prepare($conn, "SELECT DISTINCT cc.id, cc.name FROM caseload_categories cc
                                                            JOIN caseloads cl ON cc.id=cl.category_id
                                                            LEFT JOIN caseload_coordinators_assignments ca ON cl.id=ca.caseload_id
                                                            WHERE ca.user_id=?
                                                            ORDER BY cc.name ASC");
                    mysqli_stmt_bind_param($getCategories, "i", $_SESSION["id"]);
                    if (mysqli_stmt_execute($getCategories))
                    {
                        $getCategoriesResults = mysqli_stmt_get_result($getCategories);
                        if (mysqli_num_rows($getCategoriesResults) > 0)
                        {
                            while ($category = mysqli_fetch_array($getCategoriesResults))
                            {
                                // store category details locally
                                $category_id = $category["id"];
                                $category_name = $category["name"];

                                // build demo caseload name
                                $demo_caseload_id = $category_id * -1;
                                $demo_caseload_name = "<i class=\"fa-solid fa-helmet-safety\"></i> ".$category_name;
                                $demo_title = "<i>DEMO CASELOAD</i>";

                                // build the name div
                                $name_div = ""; // initialize div
                                $name_div .= "<div class='my-1'>
                                    <form class='w-100' method='POST' action='caseload.php'>
                                        <input type='hidden' id='caseload_id' name='caseload_id' value='".$demo_caseload_id."' aria-hidden='true'>
                                        <input type='hidden' id='category_id' name='category_id' value='".$category_id."' aria-hidden='true'>
                                        <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                        <button class='btn btn-therapist_caseload w-100' type='submit'>
                                            <span class='text-nowrap float-start'>$demo_caseload_name</span>
                                            <div class='demo-div text-center px-3 py-1 float-end'>Demo</div>
                                        </button>
                                    </form>
                                </div>";

                                // build the actions column
                                $actions = "<div class='d-flex justify-content-end'>
                                    <form method='POST' action='caseload.php'>
                                        <input type='hidden' id='caseload_id' name='caseload_id' value='".$demo_caseload_id."' aria-hidden='true'>
                                        <input type='hidden' id='category_id' name='category_id' value='".$category_id."' aria-hidden='true'>
                                        <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                        <button class='btn btn-primary btn-sm mx-1' type='submit' title='View Caseload'>
                                            <i class='fa-solid fa-eye'></i>
                                        </button>
                                    </form>
                                </div>"; 

                                // build the DEMO caseload
                                $temp = [];
                                $temp["order"] = 2;
                                $temp["caseload_id"] = $demo_caseload_id;
                                $temp["name"] = $name_div;
                                $temp["title"] = $demo_title;
                                $temp["caseload_category"] = $category_name;
                                $temp["caseload_count"] = 0;
                                $temp["caseload_units"] = 0;
                                $temp["caseload_status"] = "Active";
                                $temp["actions"] = $actions;

                                // add the DEMO caseload to the master list
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