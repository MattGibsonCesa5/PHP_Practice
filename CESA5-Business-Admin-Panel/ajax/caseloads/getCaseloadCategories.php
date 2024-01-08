<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold the categories
        $categories = [];

        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            $getCategories = mysqli_query($conn, "SELECT * FROM caseload_categories ORDER BY name ASC");
            if (mysqli_num_rows($getCategories) > 0)
            {
                while ($category = mysqli_fetch_array($getCategories))
                {
                    // store category details locally
                    $category_id = $category["id"];
                    $category_name = $category["name"];
                    $service_id = $category["service_id"];

                    // build the category name display
                    $category_name_display = "<div class='float-start'>".$category_name."</div>";
                    if ($category["locked"] == 1) { // category is locked
                        $category_name_display .= "<div class='badge bg-danger float-end mx-1'>
                            <i class='fa-solid fa-lock'></i>
                        </div>"; 
                    } else { // category is unlocked 
                        $category_name_display .= "<div class='badge bg-success float-end mx-1'>
                            <i class='fa-solid fa-lock-open'></i>
                        </div>"; 
                    }
                    if ($category["is_classroom"] == 1) { // category is a classroom
                        $category_name_display .= "<div class='badge bg-secondary float-end mx-1' title='This caseload category is specified as a classroom.'>
                            <i class='fa-solid fa-school'></i>
                        </div>"; 
                    }

                    // get the number of therapists assigned to this category
                    $therapist_count = 0;
                    $getTherapistCount = mysqli_prepare($conn, "SELECT id FROM caseloads WHERE category_id=?");
                    mysqli_stmt_bind_param($getTherapistCount, "i", $category_id);
                    if (mysqli_stmt_execute($getTherapistCount))
                    {
                        $getTherapistCountResult = mysqli_stmt_get_result($getTherapistCount);
                        $therapist_count = mysqli_num_rows($getTherapistCountResult);
                    }

                    // get the number of students within the active period assigned to this category
                    $student_count = 0;
                    $getStudentCount = mysqli_prepare($conn, "SELECT DISTINCT c.student_id FROM cases c
                                                            JOIN caseloads cl ON c.caseload_id=cl.id
                                                            WHERE cl.category_id=? AND c.period_id=?");
                    mysqli_stmt_bind_param($getStudentCount, "ii", $category_id, $GLOBAL_SETTINGS["active_period"]);
                    if (mysqli_stmt_execute($getStudentCount))
                    {
                        $getStudentCountResult = mysqli_stmt_get_result($getStudentCount);
                        $student_count = mysqli_num_rows($getStudentCountResult);
                    }

                    // build the subcategories display
                    $subcat_counter = 0;
                    $subcategories_display = "";
                    $getSubcategories = mysqli_prepare($conn, "SELECT id, name FROM caseload_subcategories WHERE category_id=?");
                    mysqli_stmt_bind_param($getSubcategories, "i", $category_id);
                    if (mysqli_stmt_execute($getSubcategories))
                    {
                        $getSubcategoriesResults = mysqli_stmt_get_result($getSubcategories);
                        if (($subcategory_count = mysqli_num_rows($getSubcategoriesResults)) > 0)
                        {
                            while ($subcategory = mysqli_fetch_array($getSubcategoriesResults))
                            {
                                // store subcategory details locally
                                $subcategory_id = $subcategory["id"];
                                $subcategory_name = $subcategory["name"];

                                // append subcategory to string
                                if ($subcat_counter == $subcategory_count - 1) { $subcategories_display .= $subcategory_name; }
                                else { $subcategories_display .= $subcategory_name . ", "; }

                                // increment counter
                                $subcat_counter++;
                            }
                        }
                    }

                    // get the name of the service
                    $service_name = getServiceName($conn, $service_id);

                    // build the actions column
                    $actions = "<div class='d-flex justify-content-end'>
                        <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditCategoryModal(".$category_id.");' title='Edit Case'><i class='fa-solid fa-pencil'></i></button>
                    </div>";

                    // build the temporary category array, then add the master listing
                    $temp = [];
                    $temp["name"] = $category_name_display;
                    $temp["therapist_count"] = $therapist_count;
                    $temp["student_count"] = $student_count;
                    $temp["subcategories"] = $subcategories_display;
                    $temp["service_id"] = $service_id;
                    $temp["service_name"] = $service_name;
                    $temp["actions"] = $actions;
                    $categories[] = $temp;
                }
            }
        }

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $categories;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>