<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_REVENUES"))
        {
            // get the parameters from POST
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $id = $_POST["id"]; } else { $id = null; }
            if (isset($_POST["name"]) && $_POST["name"] <> "") { $name = $_POST["name"]; } else { $name = null; }
            if (isset($_POST["desc"]) && $_POST["desc"] <> "") { $desc = $_POST["desc"]; } else { $desc = null; }
            if (isset($_POST["date"]) && $_POST["date"] <> "") { $date = date("Y-m-d", strtotime($_POST["date"])); } else { $date = null; }
            if (isset($_POST["revenue"]) && $_POST["revenue"] <> "") { $revenue = $_POST["revenue"]; } else { $revenue = 0; }
            if (isset($_POST["quantity"]) && $_POST["quantity"] <> "") { $quantity = $_POST["quantity"]; } else { $quantity = 0; }
            if (isset($_POST["fund"]) && $_POST["fund"] <> "") { $fund = $_POST["fund"]; } else { $fund = null; }
            if (isset($_POST["loc"]) && $_POST["loc"] <> "") { $loc = $_POST["loc"]; } else { $loc = null; }
            if (isset($_POST["src"]) && $_POST["src"] <> "") { $src = $_POST["src"]; } else { $src = null; }
            if (isset($_POST["func"]) && $_POST["func"] <> "") { $func = $_POST["func"]; } else { $func = null; }
            if (isset($_POST["proj"]) && $_POST["proj"] <> "") { $proj = $_POST["proj"]; } else { $proj = null; }

            if ($id != null)
            {
                if ($name != null && $date != null && $fund != null && $loc != null && $src != null && $func != null && $proj != null)
                {
                    if ($revenue > 0)
                    {
                        if (verifyRevenue($conn, $id)) // verify the revenue exists
                        {
                            if (verifyProject($conn, $proj)) // verify the project exists
                            {
                                $editRevenue = mysqli_prepare($conn, "UPDATE revenues SET name=?, description=?, date=?, fund_code=?, location_code=?, source_code=?, function_code=?, project_code=?, total_cost=?, quantity=? WHERE id=?");
                                mysqli_stmt_bind_param($editRevenue, "ssssssssddi", $name, $desc, $date, $fund, $loc, $src, $func, $proj, $revenue, $quantity, $id);
                                if (mysqli_stmt_execute($editRevenue)) 
                                { 
                                    echo "<span class=\"log-success\">Successfully</span> edited the revenue.<br>"; 

                                    // log revenue edit
                                    $message = "Successfully edited the revenue with the ID of $id. The revenue is assigned to project $proj. ";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the revenue. An unexpected error has occurred. Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the revenue. The project selected does not exist!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the revenue. The revenue does not exist!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the revenue. The total revenue most be greater than $0.00!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the revenue. You must provide all the required fields!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the revenue. No revenue was selected.<br>"; }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>