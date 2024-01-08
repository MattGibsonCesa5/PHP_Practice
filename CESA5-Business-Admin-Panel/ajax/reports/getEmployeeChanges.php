<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // initialize the array to store data
        $employeeChanges = [];

        if (checkUserPermission($conn, "VIEW_REPORT_EMPLOYEE_CHANGES_ALL") || checkUserPermission($conn, "VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"))
        {
            // store additional user permissions locally
            $can_user_edit = checkUserPermission($conn, "EDIT_EMPLOYEES");

            // build and prepare the query to get employee changes based on the user's permissions
            if (checkUserPermission($conn, "VIEW_REPORT_EMPLOYEE_CHANGES_ALL"))
            {
                // get all employee changes
                $getChanges = mysqli_prepare($conn, "SELECT c.id AS change_id, c.employee_id, c.from_period_id, c.to_period_id, c.field_changed, c.notes, c.change_user_id, c.timestamp, e.fname, e.lname FROM employee_changes c
                                                    JOIN employees e ON c.employee_id=e.id");
            }
            else if (checkUserPermission($conn, "VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"))
            {
                // get only assigned employee changes
                $getChanges = mysqli_prepare($conn, "SELECT c.id AS change_id, c.employee_id, c.from_period_id, c.to_period_id, c.field_changed, c.notes, c.change_user_id, c.timestamp, e.fname, e.lname FROM employee_changes c
                                                    JOIN employees e ON c.employee_id=e.id
                                                    JOIN department_members dm ON e.id=dm.employee_id
                                                    JOIN departments d ON dm.department_id=d.id
                                                    WHERE d.director_id=? OR d.secondary_director_id=?");
                mysqli_stmt_bind_param($getChanges, "ii", $_SESSION["id"], $_SESSION["id"]);
            }

            // execute the query to get employee changes
            if (mysqli_stmt_execute($getChanges))
            {
                $getChangesResults = mysqli_stmt_get_result($getChanges);
                if (mysqli_num_rows($getChangesResults) > 0) // changes found; build report
                {
                    while ($change = mysqli_fetch_array($getChangesResults))
                    {
                        // store change details locally
                        $change_id = $change["change_id"];
                        $employee_id = $change["employee_id"];
                        $from = $change["from_period_id"];
                        $to = $change["to_period_id"];
                        $field = $change["field_changed"];
                        $notes = $change["notes"];
                        $fname = $change["fname"];
                        $lname = $change["lname"];
                        $change_user = $change["change_user_id"];
                        $timestamp = $change["timestamp"];

                        // get the period labels
                        $from_details = getPeriodDetails($conn, $from);
                        $to_details = getPeriodDetails($conn, $to);
                        $from_label = $from_details["name"];
                        $to_label = $to_details["name"];

                        // get change user details
                        $change_user_name = getUserDisplayName($conn, $change_user);

                        // convert date of change
                        $date = date("n/j/Y", strtotime($timestamp));

                        // build the ID / status column
                        $id_div = ""; // initialize div
                        if (isEmployeeActive($conn, $employee_id, $GLOBAL_SETTINGS["active_period"])) { $id_div .= "<div class='my-1'><span class='text-nowrap'>$employee_id</span><div class='active-div text-center px-3 py-1 float-end'>Active</div></div>"; }
                        else { $id_div .= "<div class='my-1'><span class='text-nowrap'>$employee_id</span><div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div></div>"; } 

                        // build the changed from column
                        $changed_from = $from_label." <i class='fa-solid fa-arrow-right'></i> ".$to_label;

                        // build the changed by column
                        if ($change_user_name <> "" && $change_user_name != null) { $changed_by = "Changed by $change_user_name on $date"; }
                        else { $changed_by = "Changed by <i>unknown</i> on $date"; }

                        // build the actions column
                        $actions = "";
                        if ($can_user_edit === true)
                        {
                            $actions = "<div class='row justify-content-center'>
                                <div class='col-12 col-sm-12 col-md-12 col-lg-12 col-xl-6 col-xxl-6 p-1'><button class='btn btn-primary w-100' type='button' onclick='getDeleteMarkedChangeModal(".$change_id.");'><i class='fa-solid fa-trash-can'></i></button></div>
                            </div>";
                        }
                        
                        // build the temporary array to store the changes
                        $temp = [];
                        $temp["id"] = $id_div;
                        $temp["fname"] = $fname;
                        $temp["lname"] = $lname;
                        $temp["field_changed"] = $field;
                        $temp["changed_from"] = $changed_from;
                        $temp["notes"] = $notes;
                        $temp["changed_by"] = $changed_by;
                        $temp["actions"] = $actions;
                        $employeeChanges[] = $temp;
                    }
                }
            }
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $employeeChanges;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>