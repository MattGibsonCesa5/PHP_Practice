<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize variables
        $revenues = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_REVENUES_ALL") || checkUserPermission($conn, "VIEW_REVENUES_ASSIGNED"))
        {
            // store if the user can manage revenues locally
            $can_user_edit = checkUserPermission($conn, "EDIT_REVENUES");
            $can_user_delete = checkUserPermission($conn, "DELETE_REVENUES");

            // get the period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period_id = getPeriodID($conn, $period))
            {
                // build and prepare the query to get revenues based on the user's permissions
                if (checkUserPermission($conn, "VIEW_REVENUES_ALL"))
                {
                    $getRevenues = mysqli_prepare($conn, "SELECT * FROM revenues WHERE period_id=?");
                    mysqli_stmt_bind_param($getRevenues, "i", $period_id);
                }
                else if (checkUserPermission($conn, "VIEW_REVENUES_ASSIGNED"))
                {
                    $getRevenues = mysqli_prepare($conn, "SELECT r.* FROM revenues r 
                                                            JOIN projects p ON r.project_code=p.code
                                                            JOIN departments d ON p.department_id=d.id
                                                            WHERE period_id=? AND (d.director_id=? OR d.secondary_director_id=?)");
                    mysqli_stmt_bind_param($getRevenues, "iii", $period_id, $_SESSION["id"], $_SESSION["id"]);
                }

                // execute the query to get revenues based on user permissions
                if (mysqli_stmt_execute($getRevenues))
                {
                    $getRevenuesResults = mysqli_stmt_get_result($getRevenues);
                    if (mysqli_num_rows($getRevenuesResults) > 0) // revenues found; continue
                    {
                        while ($revenue = mysqli_fetch_array($getRevenuesResults))
                        {
                            // store revenue details locally
                            $revenue_id = $revenue["id"];
                            $name = $revenue["name"];
                            $desc = $revenue["description"];
                            $date = date("n/j/Y", strtotime($revenue["date"]));
                            $fund = $revenue["fund_code"];
                            $loc = $revenue["location_code"];
                            $src = $revenue["source_code"];
                            $func = $revenue["function_code"];
                            $proj = $revenue["project_code"];
                            $cost = $revenue["total_cost"];
                            $quantity = $revenue["quantity"];
                            $period_id = $revenue["period_id"];

                            // initialize the temporary array to store data to send
                            $temp = [];
                            
                            // build the period columns
                            $period_name = "";
                            $getPeriodDetails = mysqli_prepare($conn, "SELECT name, active, editable FROM periods WHERE id=?");
                            mysqli_stmt_bind_param($getPeriodDetails, "i", $period_id);
                            if (mysqli_stmt_execute($getPeriodDetails))
                            {
                                $getPeriodDetailsResult = mysqli_stmt_get_result($getPeriodDetails);
                                if (mysqli_num_rows($getPeriodDetailsResult) > 0)
                                {
                                    $periodDetails = mysqli_fetch_array($getPeriodDetailsResult);
                                    $period_name = $periodDetails["name"];
                                    $is_active = $periodDetails["active"];
                                    $is_editable = $periodDetails["editable"];
                                }
                            }
                            $temp["period_id"] = $period_id;
                            $temp["period_name"] = $period_name;

                            // build the revenue details columns
                            $temp["revenue_id"] = $revenue_id;
                            $temp["name"] = $name;
                            $temp["description"] = $desc;
                            $temp["date"] = $date;
                            $temp["revenue"] = printDollar($cost);
                            $temp["quantity"] = $quantity;
                            $temp["calc_revenue"] = $cost;

                            // build the WUFAR codes columns
                            $temp["fund"] = $fund;
                            $temp["loc"] = $loc;
                            $temp["src"] = $src;
                            $temp["func"] = $func;
                            if (isset($proj) && $proj <> "") { $temp["proj"] = $proj; } else { $temp["proj"] = "<span class='missing-field'>Missing</span>"; }

                            // build the actions column
                            $actions = "<div class='d-flex justify-content-end'>";
                                if ($is_editable == 1 && $can_user_edit === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditRevenueModal(".$revenue_id.");'><i class='fa-solid fa-pencil'></i></button>"; }
                                if ($is_editable == 1 && $can_user_delete === true) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteRevenueModal(".$revenue_id.");'><i class='fa-solid fa-trash-can'></i></button>"; }
                            $actions .= "</div>";
                            $temp["actions"] = $actions;

                            $revenues[] = $temp;
                        }
                    }
                }
            } 
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $revenues;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>