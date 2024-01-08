<?php
    session_start();
    
    require '../../vendor/autoload.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ASSIGNED"))
        {
            // get the request ID from POST
            if (isset($_POST["request_id"]) && $_POST["request_id"] <> "") { $request_id = $_POST["request_id"]; } else { $request_id = null; }
            if (isset($_POST["new_days"]) && is_numeric($_POST["new_days"])) { $new_days = $_POST["new_days"]; } else { $new_days = 0; }
            if (isset($_POST["comment"]) && $_POST["comment"] <> "") { $comment = trim($_POST["comment"]); } else { $comment = null; }

            if ($request_id != null)
            {
                // get current request details
                $getRequestDetails = mysqli_prepare($conn, "SELECT employee_id, period_id FROM employee_compensation_change_requests WHERE id=?");
                mysqli_stmt_bind_param($getRequestDetails, "i", $request_id);
                if (mysqli_stmt_execute($getRequestDetails))
                {
                    $getRequestDetailsResults = mysqli_stmt_get_result($getRequestDetails);
                    if (mysqli_num_rows($getRequestDetailsResults) > 0)
                    {
                        // store request details locally
                        $request_details = mysqli_fetch_array($getRequestDetailsResults);
                        $employee_id = $request_details["employee_id"];
                        $period_id = $request_details["period_id"];

                        // get the employee's current contract days and salary
                        $days = getEmployeeContractDays($conn, $employee_id, $period_id);
                        $current_salary = getEmployeeSalary($conn, $employee_id, $period_id);

                        // calculate the employee's daily rate
                        $daily_rate = 0;
                        if ($days > 0) { $daily_rate = $current_salary / $days; }

                        // calculate the employee's new estimated yearly salary
                        $estimated_salary = $daily_rate * $new_days;

                        // edit the change request
                        $editChange = mysqli_prepare($conn, "UPDATE employee_compensation_change_requests SET new_contract_days=?, new_yearly_salary=?, reason=? WHERE id=?");
                        mysqli_stmt_bind_param($editChange, "idsi",$new_days, $estimated_salary, $comment, $request_id);
                        if (mysqli_stmt_execute($editChange))
                        {
                            // get teh employee's name
                            $employee_name = getEmployeeDisplayName($conn, $employee_id);

                            // log employee change request
                            echo "<span class=\"log-success\">Successfully</span> edited the change request for $employee_name's details.<br>";
                            $message = "Successfully edited the change request (request ID: $request_id) for $employee_name (employee ID: $employee_id).";
                            $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                            mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                            mysqli_stmt_execute($log);

                            // send users subscribed to notification an email
                            $getRecipients = mysqli_query($conn, "SELECT u.email, u.lname, u.fname FROM users u
                                                                    JOIN email_recipients er ON u.id=er.user_id
                                                                    JOIN email_types et ON er.type_id=et.id
                                                                    WHERE et.type='Employee Change Requests Submitted' AND er.subscribed=1 AND er.frequency=1 AND u.status=1");
                            if (mysqli_num_rows($getRecipients) > 0) // recipients found; begin sending emails
                            {
                                // get the user's display name
                                $myName = getUserDisplayName($conn, $_SESSION["id"]);

                                // get current time
                                date_default_timezone_set("America/Chicago");
                                $timestamp = date("n/j/Y g:ia");

                                // send email to each recipient
                                while ($recipient = mysqli_fetch_array($getRecipients))
                                {
                                    // store recipient details locally
                                    $email = $recipient["email"];
                                    $lname = $recipient["lname"];
                                    $fname = $recipient["fname"];
                                    $name = $fname." ".$lname;

                                    // build email body
                                    $body = "";
                                    $body .= $myName." has modified an existing employee compensation change request for ".$employee_name." on ".$timestamp.".<br>
                                    Please login to <a href=\"https://bap.cesa5.org\" target=\"_blank\">https://bap.cesa5.org</a> to view the change request.";

                                    try 
                                    {
                                        $mail = new PHPMailer(true);
                                        $mail->isSMTP();
                                        $mail->SMTPAuth = true;
                                        $mail->Host = "smtp.gmail.com";
                                        $mail->Username = NOREPLY_ADDRESS;
                                        $mail->Password = NOREPLY_PASSWORD;
                                        $mail->Port = 465;
                                        $mail->SMTPSecure = "ssl";

                                        $mail->setFrom(NOREPLY_ADDRESS, "CESA 5 - BAP");
                                        $mail->addAddress($email, $name);
                                        $mail->isHTML(true);
                                        $mail->Subject = "Employee Compensation Change Request Modified";
                                        $mail->Body = $body;
                                        $mail->AltBody = $body;
                                        try
                                        {
                                            $mail->send();
                                        }
                                        catch (Exception $e)
                                        {

                                        }
                                    }
                                    catch (Exception $e)
                                    {
                                        
                                    }
                                }
                            }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the employee change request. An unexpected error has occurred! Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the employee change request. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the employee change request. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the employee change request. An unexpected error has occurred! Please try again later.<br>"; }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>