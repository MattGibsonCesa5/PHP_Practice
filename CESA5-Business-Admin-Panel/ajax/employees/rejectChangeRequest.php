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

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get the request ID from POST
            if (isset($_POST["request_id"]) && $_POST["request_id"] <> "") { $request_id = $_POST["request_id"]; } else { $request_id = null; }

            if ($request_id != null)
            {
                // get request details
                $getRequest = mysqli_prepare($conn, "SELECT employee_id, period_id, requested_by FROM employee_compensation_change_requests WHERE id=?");
                mysqli_stmt_bind_param($getRequest, "i", $request_id);
                if (mysqli_stmt_execute($getRequest))
                {
                    $getRequestResult = mysqli_stmt_get_result($getRequest);
                    if (mysqli_num_rows($getRequestResult) > 0) // request exists; continue
                    {
                        // get request details
                        $request = mysqli_fetch_array($getRequestResult);
                        $employee_id = $request["employee_id"];
                        $period_id = $request["period_id"];
                        $requester_id = $request["requested_by"];

                        // verify the employee exists
                        if (checkExistingEmployee($conn, $employee_id))
                        {
                            // get the employee's display name
                            $employee_name = getEmployeeDisplayName($conn, $employee_id);

                            // get the current time
                            $rejected_at = date("Y-m-d H:i:s");

                            // set the status of the change request to indicate rejection (2)
                            $rejectRequest = mysqli_prepare($conn, "UPDATE employee_compensation_change_requests SET status=2, accepted_by=?, accepted_at=? WHERE id=?");
                            mysqli_stmt_bind_param($rejectRequest, "isi", $_SESSION["id"], $rejected_at, $request_id);
                            if (mysqli_stmt_execute($rejectRequest)) 
                            { 
                                // log rejected change request 
                                echo "<span class=\"log-success\">Successfully</span> rejected the change request for $employee_name.<br>"; 
                                $message = "Successfully rejected the change request for $employee_name (ID: $employee_id), for the period with ID of $period_id, with request ID $request_id.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // send users subscribed to notification an email
                                $getRecipients = mysqli_query($conn, "SELECT u.id, u.email, u.lname, u.fname FROM users u
                                                                        JOIN email_recipients er ON u.id=er.user_id
                                                                        JOIN email_types et ON er.type_id=et.id
                                                                        WHERE et.type='Employee Change Requests Rejected' AND er.subscribed=1 AND er.frequency=1 AND u.status=1");
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
                                        $user_id = $recipient["id"];
                                        $email = $recipient["email"];
                                        $lname = $recipient["lname"];
                                        $fname = $recipient["fname"];
                                        $name = $fname." ".$lname;

                                        // only send the email if the requestor is a recipient
                                        if ($user_id == $requester_id)
                                        {
                                            // build email body
                                            $body = "";
                                            $body .= "An employee compensation change request you submitted for ".$employee_name." has been rejected.<br>";

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
                                                $mail->Subject = "Employee Compensation Change Request Rejected";
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
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to reject the change request for $employee_name. An unexpected error has occurred! Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to reject the change request. The employee does not exist!?<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to reject the change request. The request is no longer valid!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to reject the change request. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to reject the change request. The change request was invalid!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to reject the change request. Your account does not have permission to perform this action.<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>