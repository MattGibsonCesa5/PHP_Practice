<?php
    include("header.php");
    include("getSettings.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && ($_SESSION["role"] == 1 || $_SESSION["role"] == 4))
        {
            ?>
                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8"><h1 class="upload-status-header">Recalculate Automated Expenses Status</h1></div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8 upload-status-report">
                    <?php
                        // connect to the database
                        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                        // for each project; recaluate their automated expenses
                        $getProjects = mysqli_query($conn, "SELECT code, name FROM projects WHERE status=1");
                        if (mysqli_num_rows($getProjects) > 0) // projects exists; continue
                        {
                            while ($project = mysqli_fetch_array($getProjects))
                            {
                                // store project details locally
                                $code = $project["code"];
                                $name = $project["name"];

                                // recalculate the projects automated expenses
                                recalculateAutomatedExpenses($conn, $code, $GLOBAL_SETTINGS["active_period"]);
                                
                                // log status to screen
                                echo "Recalculated automated expenses for $code - $name<br>";
                            }
                        }

                        // log action
                        $message = "Successfully mass recalculated automated expenses. ";
                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                        mysqli_stmt_execute($log);

                        // disconnect from the database
                        mysqli_close($conn);
                    ?>
                    </div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center justify-content-center mt-3">
                    <div class="col-3"><button class="btn btn-primary w-100" onclick="goToManageProjects();">Return To Manage Projects</button></div>
                </div>

                <script>function goToManageProjects() { window.location.href = "projects_manage.php"; }</script>
            <?php
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
?>