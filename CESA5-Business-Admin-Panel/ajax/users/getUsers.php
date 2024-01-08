<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // initialize variables
        $users = [];

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        ///////////////////////////////////////////////////////////////////////////////////////////
        //
        //  Admin View
        //
        ///////////////////////////////////////////////////////////////////////////////////////////
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get all users (excluding the super user)
            $getUsers = mysqli_query($conn, "SELECT u.*, r.name AS role FROM users u
                                            LEFT JOIN roles r ON u.role_id=r.id
                                            WHERE status!=2");
            while ($user = mysqli_fetch_array($getUsers))
            {
                // store user info locally
                $id = $user["id"];
                $fname = $user["fname"];
                $lname = $user["lname"];
                $email = $user["email"];
                $role_id = $user["role_id"];
                $status = $user["status"];
                $role = $user["role"];

                if (!isset($role) || trim($role) == "") {
                    $role = "<span class='missing-field'>Missing</span>";
                }

                // get when the user last logged in
                $last_login = "<span class='d-none' aria-hidden='true'>0</span><span class='missing-field'>Unknown</span>";
                $getLastLogin = mysqli_prepare($conn, "SELECT timestamp FROM logins WHERE user_id=? AND status=1 ORDER BY timestamp DESC LIMIT 1");
                mysqli_stmt_bind_param($getLastLogin, "i", $id);
                if (mysqli_stmt_execute($getLastLogin))
                {
                    $getLastLoginResult = mysqli_stmt_get_result($getLastLogin);
                    if (mysqli_num_rows($getLastLoginResult) > 0) // last login was found
                    {
                        $timestamp = mysqli_fetch_array($getLastLoginResult)["timestamp"];
                        $DB_Timezone = HOST_TIMEZONE;
                        $last_login = "<span class='d-none' aria-hidden='true'>".strtotime($timestamp)."</span>".date_convert($timestamp, $DB_Timezone, "America/Chicago", "n/j/Y g:i A");
                    } else {
                        $last_login = "<span class='d-none' aria-hidden='true'>0</span><span class='missing-field'>Never</span>";
                    }
                }

                // check to see if the user is an employee in the system based on matching email address and name 
                $is_employee = "<span class='log-fail'>No</span>";
                $title = "N/A";
                $checkEmployee = mysqli_prepare($conn, "SELECT id FROM employees WHERE lname=? AND fname=? AND email=?");
                mysqli_stmt_bind_param($checkEmployee, "sss", $lname, $fname, $email);
                if (mysqli_stmt_execute($checkEmployee))
                {
                    $checkEmployeeResult = mysqli_stmt_get_result($checkEmployee);
                    if (mysqli_num_rows($checkEmployeeResult) > 0)
                    {
                        $employee_id = mysqli_fetch_assoc($checkEmployeeResult)["id"];
                        $is_employee = "<span class='log-success'>Yes</span>";
                        
                        // attempt to get the employee's title
                        $getTitle = mysqli_prepare($conn, "SELECT t.name AS title FROM employee_compensation ec
                                                            JOIN periods p ON ec.period_id=p.id
                                                            LEFT JOIN employee_titles t ON ec.title_id=t.id
                                                            WHERE p.active=1 AND ec.employee_id=?");
                        mysqli_stmt_bind_param($getTitle, "i", $employee_id);
                        if (mysqli_stmt_execute($getTitle))
                        {
                            $getTitleResult = mysqli_stmt_get_result($getTitle);
                            if (mysqli_num_rows($getTitleResult) > 0)
                            {
                                $title = mysqli_fetch_assoc($getTitleResult)["title"];
                            } else {
                                $title = "<span class='missing-field'>Missing</span>";
                            }
                        }
                        
                    }
                }
                
                // build the actions column
                $actions = "<div class='d-flex justify-content-end'>
                    <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditUserModal(".$id.");' title='Edit user.'>
                        <i class='fa-solid fa-pencil'></i>
                    </button>

                    <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getMasqueradeModal(".$id.");' title='Login as user...'>
                        <i class='fa-solid fa-user-secret'></i>
                    </button>

                    <button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteUserModal(".$id.");' title='Delete user.'>
                        <i class='fa-solid fa-trash-can'></i>
                    </button>
                </div>";

                // build the temporary array
                $temp = [];
                $temp["status"] = $status;
                $temp["id"] = $id;
                $temp["fname"] = $fname;
                $temp["lname"] = $lname;
                $temp["email"] = $email;
                $temp["role"] = $role;
                $temp["is_employee"] = $is_employee;
                $temp["title"] = $title;
                $temp["last_login"] = $last_login;
                $temp["actions"] = $actions;
                $temp["export_status"] = $status;
                $users[] = $temp;
            }
        }
        ///////////////////////////////////////////////////////////////////////////////////////////
        //
        //  District View
        //
        ///////////////////////////////////////////////////////////////////////////////////////////
        else if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1 && ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor"))
        {
            // verify district/customer exists
            if (verifyCustomer($conn, $_SESSION["district"]["id"]))
            {
                // get all users (excluding the super user)
                $getUsers = mysqli_prepare($conn, "SELECT u.*, r.name AS role FROM users u
                                                LEFT JOIN roles r ON u.role_id=r.id
                                                WHERE u.customer_id=? AND status!=2");
                mysqli_stmt_bind_param($getUsers, "i", $_SESSION["district"]["id"]);
                if (mysqli_stmt_execute($getUsers))
                {
                    $getUsersResults = mysqli_stmt_get_result($getUsers);
                    while ($user = mysqli_fetch_array($getUsersResults))
                    {
                        // store user info locally
                        $id = $user["id"];
                        $fname = $user["fname"];
                        $lname = $user["lname"];
                        $email = $user["email"];
                        $role_id = $user["role_id"];
                        $status = $user["status"];
                        $role = $user["role"];

                        if (!isset($role) || trim($role) == "") {
                            $role = "<span class='missing-field'>Missing</span>";
                        }

                        // get when the user last logged in
                        $last_login = "<span class='d-none' aria-hidden='true'>0</span><span class='missing-field'>Unknown</span>";
                        $getLastLogin = mysqli_prepare($conn, "SELECT timestamp FROM logins WHERE user_id=? AND status=1 ORDER BY timestamp DESC LIMIT 1");
                        mysqli_stmt_bind_param($getLastLogin, "i", $id);
                        if (mysqli_stmt_execute($getLastLogin))
                        {
                            $getLastLoginResult = mysqli_stmt_get_result($getLastLogin);
                            if (mysqli_num_rows($getLastLoginResult) > 0) // last login was found
                            {
                                $timestamp = mysqli_fetch_array($getLastLoginResult)["timestamp"];
                                $DB_Timezone = HOST_TIMEZONE;
                                $last_login = "<span class='d-none' aria-hidden='true'>".strtotime($timestamp)."</span>".date_convert($timestamp, $DB_Timezone, "America/Chicago", "n/j/Y g:i A");
                            } else {
                                $last_login = "<span class='d-none' aria-hidden='true'>0</span><span class='missing-field'>Never</span>";
                            }
                        }

                        // build the status column
                        $status_display = "";
                        if ($status == 0) { $status_display = "<span class=\"badge bg-danger px-3 py-2\">Inactive</span>"; }
                        else if ($status == 1) { $status_display = "<span class=\"badge bg-success px-3 py-2\">Active</span>"; }

                        // build the actions column
                        // if user is the district admin, do not add any actions
                        if ($role == "District Administrator") { 
                            $actions = "<div class='d-flex justify-content-end'>
                                <button class='btn btn-primary btn-sm mx-1' type='button' disabled>
                                    <i class='fa-solid fa-pencil'></i>
                                </button>

                                <button class='btn btn-danger btn-sm mx-1' type='button' disabled>
                                    <i class='fa-solid fa-trash-can'></i>
                                </button>
                            </div>";
                        } else {
                            $actions = "<div class='d-flex justify-content-end'>
                                <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditUserModal(".$id.");' title='Edit user.'>
                                    <i class='fa-solid fa-pencil'></i>
                                </button>

                                <button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteUserModal(".$id.");' title='Delete user.'>
                                    <i class='fa-solid fa-trash-can'></i>
                                </button>
                            </div>";
                        }

                        // build the temporary array
                        $temp = [];
                        $temp["fname"] = $fname;
                        $temp["lname"] = $lname;
                        $temp["email"] = $email;
                        $temp["role"] = $role;
                        $temp["status"] = $status_display;
                        $temp["last_login"] = $last_login;
                        $temp["actions"] = $actions;
                        $users[] = $temp;
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $users;
        echo json_encode($fullData);
    }
?>