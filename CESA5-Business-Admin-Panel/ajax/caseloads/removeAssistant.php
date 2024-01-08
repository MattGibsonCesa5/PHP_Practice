<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "REMOVE_THERAPISTS"))
        {
            // get the caseload ID from POST
            if (isset($_POST["assistant_id"]) && $_POST["assistant_id"] <> "") { $assistant_id = $_POST["assistant_id"]; } else { $assistant_id = null; }

            // verify assistant ID is valid
            if (verifyAssistant($conn, $assistant_id))
            {
                // get the assistants name and category
                $name = getAssistantName($conn, $assistant_id);
                $category = getAssistantCategory($conn, $assistant_id);

                // attempt to remove the assistant
                $removeAssistant = mysqli_prepare($conn, "DELETE FROM caseload_assistants WHERE id=?");
                mysqli_stmt_bind_param($removeAssistant, "i", $assistant_id);
                if (mysqli_stmt_execute($removeAssistant)) 
                { 
                    echo "<span class=\"log-success\">Successfully</span> removed $name as a designated assistant for $category caseloads.<br><br>Attempting to remove the assistant from all cases...<br>";
                    
                    $removeFromCases = mysqli_prepare($conn, "UPDATE cases SET assistant_id=NULL WHERE assistant_id=?");
                    mysqli_stmt_bind_param($removeFromCases, "i", $assistant_id);
                    if (mysqli_stmt_execute($removeFromCases)) { echo "<span class=\"log-success\">Successfully</span> removed the assistant from all cases!<br>"; }
                    else { echo "<span class=\"log-fail\">Failed</span> to remove the assistant from all cases. An unexpected error has occurred!<br>"; }

                    // log therapist addition
                    $message = "Successfully removed $name as a designated assistant for $category caseloads.";
                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                    mysqli_stmt_execute($log);
                }
                else { echo "<span class=\"log-fail\">Failed</span> to remove the assistant. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to remove the employee as an assistant as the assistant was not found!<br>"; }
        }
        else { echo "Your account does not have permission to complete this action.<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>