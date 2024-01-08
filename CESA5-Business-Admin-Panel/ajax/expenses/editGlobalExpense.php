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

        if (checkUserPermission($conn, "EDIT_EMPLOYEE_EXPENSES"))
        {
            // get the expense ID from POST
            if (isset($_POST["expense"]) && $_POST["expense"] <> "") { $expense = $_POST["expense"]; } else { $expense = null; }
            if (isset($_POST["value"]) && $_POST["value"] <> "") { $value = $_POST["value"]; } else { $value = null; }
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }

            if ($expense != null && $value != null)
            {
                // validate the expense
                if ($expense == "health-single") { $DB_expense = "health_single"; }
                else if ($expense == "health-family") { $DB_expense = "health_family"; }
                else if ($expense == "dental-single") { $DB_expense = "dental_single"; }
                else if ($expense == "dental-family") { $DB_expense = "dental_family"; }
                else if ($expense == "wrs-rate") { $DB_expense = "wrs_rate"; }
                else if ($expense == "FICA-rate") { $DB_expense = "FICA"; }
                else if ($expense == "LTD-rate") { $DB_expense = "LTD"; }
                else if ($expense == "life-rate") { $DB_expense = "life"; }
                else if ($expense == "agency-indirect") { $DB_expense = "agency_indirect"; }
                else if ($expense == "grant-rate") { $DB_expense = "grant_rate"; }
                else if ($expense == "dpi_grant-rate") { $DB_expense = "dpi_grant_rate"; }
                else if ($expense == "supervision-aidable") { $DB_expense = "aidable_supervision"; }
                else if ($expense == "supervision-nonaidable") { $DB_expense = "nonaidable_supervision"; }
                else { $DB_expense = false; }

                if ($DB_expense != false) // a valid database expense was found
                {
                    // create the code field
                    $DB_code = $DB_expense."_code";

                    // attempt to modify the expense
                    $editExpense = mysqli_prepare($conn, "UPDATE global_expenses SET `$DB_expense`=?, `$DB_code`=? WHERE period_id=?");
                    mysqli_stmt_bind_param($editExpense, "dsi", $value, $code, $GLOBAL_SETTINGS["active_period"]);
                    if (mysqli_stmt_execute($editExpense)) { echo 1; } // successful edit
                    else { echo 2; } // failed edit
                }
                else { echo 2; } // failed edit
            }
            else { echo 2; } // failed edit
        }
        else { echo 2; } // failed edit
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>