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

        if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) || checkUserPermission($conn, "VIEW_EMPLOYEES_ASSIGNED"))
        {
            // get the employee ID from POST
            if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }

            // verify the employee exists
            if (checkExistingEmployee($conn, $employee_id))
            {
                // verify the director has access to the employee
                if (verifyUserEmployee($conn, $_SESSION["id"], $employee_id))
                {
                    // get additional fields from POST
                    if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }
                    if (isset($_POST["new_days"]) && is_numeric($_POST["new_days"])) { $new_days = $_POST["new_days"]; } else { $new_days = 0; }
                    if (isset($_POST["comment"]) && $_POST["comment"] <> "") { $comment = trim($_POST["comment"]); } else { $comment = null; }

                    // verify the period exists
                    if (verifyPeriod($conn, $period_id))
                    {
                        // get the employee's current contract days and salary
                        $days = getEmployeeContractDays($conn, $employee_id, $period_id);
                        $current_salary = getEmployeeSalary($conn, $employee_id, $period_id);

                        // calculate the employee's daily rate
                        $daily_rate = 0;
                        if ($days > 0) { $daily_rate = $current_salary / $days; }

                        // calculate the employee's new estimated yearly salary
                        $estimated_salary = $daily_rate * $new_days;

                        // request the change
                        $requestChange = mysqli_prepare($conn, "INSERT INTO employee_compensation_change_requests (employee_id, period_id, current_contract_days, new_contract_days, current_yearly_salary, new_yearly_salary, reason, requested_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        mysqli_stmt_bind_param($requestChange, "iiiiddsi", $employee_id, $period_id, $days, $new_days, $current_salary, $estimated_salary, $comment, $_SESSION["id"]);
                        if (mysqli_stmt_execute($requestChange))
                        {
                            // get teh employee's name
                            $employee_name = getEmployeeDisplayName($conn, $employee_id);

                            // log employee change request
                            echo "<span class=\"log-success\">Successfully</span> submitted the request to change $employee_name's details.<br>";
                            $message = "Successfully submitted an employee change request for $employee_name (ID: $employee_id) for the period with ID of $period_id.";
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
                                    $body .= $myName." has submitted an employee compensation change request for ".$employee_name." on ".$timestamp.".<br>
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
                                        $mail->Subject = "Employee Compensation Change Requested";
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
                        else { echo "<span class=\"log-fail\">Failed</span> to request the employee change. An unexpected error has occurred! Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to request the employee change. The fiscal period selected does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to request the employee change. You do not have permission to request changes for this employee!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to request the employee change. The employee selected does not exist!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to request the employee change. You do not have permission to request changes for this employee!<br>"; }
    }
?>