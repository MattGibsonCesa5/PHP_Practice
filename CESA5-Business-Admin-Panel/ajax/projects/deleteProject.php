<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_PROJECTS"))
        {
            // get the project code from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }

            // delete the project
            $deleteProject = mysqli_prepare($conn, "DELETE FROM projects WHERE code=?");
            mysqli_stmt_bind_param($deleteProject, "s", $code);
            if (mysqli_stmt_execute($deleteProject)) // successfully deleted the project; delete other data associated with this project
            {
                echo "<span class=\"log-success\">Successfully</span> deleted the project.<br>";

                // remove all employees from the project for all periods
                $removeEmployees = mysqli_prepare($conn, "DELETE FROM project_employees WHERE project_code=?");
                mysqli_stmt_bind_param($removeEmployees, "s", $code);
                if (!mysqli_stmt_execute($removeEmployees)) { echo "<span class=\"log-fail\">Failed</span> to remove employees from the deleted project. This could lead to some data inaccuracies.<br>"; }

                // remvoe all expenses from the project for all periods
                $removeExpenses = mysqli_prepare($conn, "DELETE FROM project_expenses WHERE project_code=?");
                mysqli_stmt_bind_param($removeExpenses, "s", $code);
                if (!mysqli_stmt_execute($removeExpenses)) { echo "<span class=\"log-fail\">Failed</span> to remove expenses from the deleted project. This could lead to some data inaccuracies.<br>"; }

                // set project code to null for services assigned to this project
                $updateServices = mysqli_prepare($conn, "UPDATE services SET project_code=null WHERE project_code=?");
                mysqli_stmt_bind_param($updateServices, "s", $code);
                if (!mysqli_stmt_execute($updateServices)) { echo "<span class=\"log-fail\">Failed</span> to update services assigned to this project. This could lead to some future errors and inaccuracies.<br>"; }

                // set project code to null for invoices for "other services" assigned to this project
                $updateOtherServicesInvoices = mysqli_prepare($conn, "UPDATE services_other_provided SET project_code=null WHERE project_code=?");
                mysqli_stmt_bind_param($updateOtherServicesInvoices, "s", $code);
                if (!mysqli_stmt_execute($updateOtherServicesInvoices)) { echo "<span class=\"log-fail\">Failed</span> to update invoices for \"other services\" assigned to this project.<br>"; }

                // set project code to null for invoices for "other services" assigned to this project
                $updateRevenues = mysqli_prepare($conn, "UPDATE revenues SET project_code=null WHERE project_code=?");
                mysqli_stmt_bind_param($updateRevenues, "s", $code);
                if (!mysqli_stmt_execute($updateRevenues)) { echo "<span class=\"log-fail\">Failed</span> to remove this project from revenues assigned to this project.<br>"; }

                // log project deletion
                $message = "Successfully deleted the project with code $code.";
                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                mysqli_stmt_execute($log);
            }
            else { echo "<span class=\"log-fail\">Failed</span> to delete the project.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to delete the project. Your account does not have permission to delete projects!<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>