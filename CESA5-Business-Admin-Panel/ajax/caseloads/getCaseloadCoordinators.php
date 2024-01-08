<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold the coordinators
        $coordinators = [];

        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            $getCoordinators = mysqli_query($conn, "SELECT * FROM caseload_coordinators");
            if (mysqli_num_rows($getCoordinators) > 0) // coordinators found
            {
                while ($coordinator = mysqli_fetch_array($getCoordinators))
                {
                    // store classroom details locally
                    $id = $coordinator["id"];
                    $user_id = $coordinator["user_id"];

                    // get the coordinator's name
                    $name = getUserDisplayName($conn, $user_id);

                    // build the column to show all caseloads the coordinator is assigned to
                    $assignments = "";
                    $getAssignments = mysqli_prepare($conn, "SELECT DISTINCT a.caseload_id FROM caseload_coordinators_assignments a 
                                                            JOIN caseloads c ON a.caseload_id=c.id
                                                            JOIN users u ON c.employee_id=u.id
                                                            WHERE a.user_id=?
                                                            ORDER BY u.fname ASC, u.lname ASC");
                    mysqli_stmt_bind_param($getAssignments, "i", $user_id);
                    if (mysqli_stmt_execute($getAssignments))
                    {
                        $getAssignmentsResults = mysqli_stmt_get_result($getAssignments);
                        if ($numOfAssigments = mysqli_num_rows($getAssignmentsResults) > 0)
                        {
                            while ($caseload = mysqli_fetch_array($getAssignmentsResults))
                            {
                                // store the caseload's ID locally
                                $caseload_id = $caseload["caseload_id"];

                                // get the name of the caseload
                                $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                                // build the name and status column
                                $assignments .= "<div class='my-1'>
                                    <form class='w-100' method='POST' action='caseload.php'>
                                        <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                        <input type='hidden' id='period_id' name='period_id' value='".$GLOBAL_SETTINGS["active_period"]."' aria-hidden='true'>
                                        <button class='btn btn-therapist_caseload w-100' type='submit'>
                                            <span class='text-nowrap float-start'>$caseload_name</span>
                                        </button>
                                    </form>
                                </div>";
                            }
                        }
                    }

                    // build the actions column
                    $actions = "<div class='d-flex justify-content-end'>
                        <!-- Edit Coordinator Modal -->
                        <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditCoordinatorModal(".$user_id.");'>
                            <i class='fa-solid fa-pencil'></i>
                        </button>
                    
                        <!-- Remove Coordinator Modal -->
                        <button class='btn btn-danger btn-sm mx-1' type='button' onclick='getRemoveCoordinatorModal(".$user_id.");'>
                            <i class='fa-solid fa-trash-can'></i>
                        </button>
                    </div>";

                    // build the temporary array of data
                    $temp = [];
                    $temp["name"] = $name;
                    $temp["caseloads"] = $assignments;
                    $temp["actions"] = $actions;

                    // add the temporary array to the master coordinators array
                    $coordinators[] = $temp;
                }
            }
        }

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $coordinators;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>