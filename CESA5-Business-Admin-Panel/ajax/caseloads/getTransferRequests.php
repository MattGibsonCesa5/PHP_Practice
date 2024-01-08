<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold the caseload transfer requests to be displayed
        $transferRequests = [];

        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period)) 
            {
                // get all the transfer requests for the current active period
                $getTransferRequests = mysqli_prepare($conn, "SELECT tr.*, c.caseload_id AS current_caseload_id, c.residency, c.district_attending, c.school_attending, c.start_date, c.end_date, s.fname, s.lname FROM caseload_transfers tr
                                                            JOIN cases c ON tr.case_id=c.id
                                                            JOIN caseload_students s ON c.student_id=s.id
                                                            WHERE c.period_id=?");
                mysqli_stmt_bind_param($getTransferRequests, "i", $period_id);
                if (mysqli_stmt_execute($getTransferRequests))
                {
                    $getTransferRequestsResults = mysqli_stmt_get_result($getTransferRequests);
                    if (mysqli_num_rows($getTransferRequestsResults) > 0) // transfer requests found
                    {
                        while ($request = mysqli_fetch_array($getTransferRequestsResults))
                        {
                            // store data locally
                            $request_id = $request["id"];
                            $case_id = $request["case_id"];
                            $current_caseload = $request["current_caseload_id"];
                            $new_caseload = $request["new_caseload_id"];
                            $student_fname = $request["fname"];
                            $student_lname = $request["lname"];
                            $request_comments = $request["comments"];
                            $iep_completed = $request["iep_completed"];
                            $requested_by = $request["requested_by"];
                            $requested_at = $request["requested_at"];
                            $accepted_by = $request["accepted_by"];
                            $accepted_at = $request["accepted_at"];
                            $transfer_status = $request["transfer_status"];

                            // get the display name of the user who submitted and/or accepted the transfer request
                            $requested_username = getUserDisplayName($conn, $requested_by);
                            $accepted_username = getUserDisplayName($conn, $accepted_by);

                            // get the caseload display names
                            $current_caseload_name = getCaseloadDisplayName($conn, $current_caseload);
                            $new_caseload_name = getCaseloadDisplayName($conn, $new_caseload);
                            if ($new_caseload_name == "") { $new_caseload_name = "?"; }

                            // build the student name to be displayed
                            $student_name = $student_lname.", ".$student_fname;

                            // build the IEP completion display
                            $display_iep_completed = "";
                            if ($iep_completed == 1) { $display_iep_completed = "Yes"; }
                            else { $display_iep_completed = "No"; }

                            // convert the dates to readable format
                            $DB_Timezone = HOST_TIMEZONE;
                            $display_requested_at = date_convert($requested_at, $DB_Timezone, "America/Chicago", "n/j/Y g:i A");
                            if (isset($accepted_at)) { $display_accepted_at = date_convert($accepted_at, $DB_Timezone, "America/Chicago", "n/j/Y"); } else { $display_accepted_at = null; }

                            // build the request details column
                            $request_details = $requested_username."<br>".$display_requested_at;

                            // build the transfer status column
                            $display_transfer_status = "";
                            if ($transfer_status == 1) { $display_transfer_status = "<div class='active-div text-center px-3 py-1'>Transferred</div>"; }
                            else if ($transfer_status == 2) { $display_transfer_status = "<div class='inactive-div text-center px-3 py-1'>Rejected</div>"; }
                            else { $display_transfer_status = "<div class='pending-div text-center px-3 py-1'>Pending</div>"; }
                            // add the user who accepted/rejected the request
                            if ($transfer_status == 1 || $transfer_status == 2)
                            {
                                if ($accepted_username <> "" && $display_accepted_at <> "") 
                                {
                                    $display_transfer_status .= "<div class='my-1'>
                                        $accepted_username<br>
                                        $display_accepted_at
                                    </div>";
                                }
                            }

                            // build the actions column
                            $actions = "<div class='d-flex justify-content-end'>";
                                if ($transfer_status != 1 && $transfer_status != 2) 
                                { 
                                    $actions .= "<button class='btn btn-danger btn-sm mx-1' title='Transfer caseload to a different therapist.' onclick='getTransferCaseloadModal($case_id, $request_id);'>
                                        <i class='fa-solid fa-right-left'></i>
                                    </button>

                                    <button class='btn btn-danger btn-sm mx-1' title='Reject the caseload transfer.' onclick='getRejectTransferCaseloadModal($request_id);'>
                                        <i class='fa-solid fa-xmark'></i>
                                    </button>";
                                }
                            $actions .= "</div>";

                            // build the filter status column
                            $filter_status = "";
                            if ($transfer_status == 1) { $filter_status = "Transferred"; }
                            else if ($transfer_status == 2) { $filter_status = "Rejected"; }
                            else { $filter_status = "Pending"; }

                            // build temporary array of data
                            $temp = [];
                            $temp["current_caseload"] = $current_caseload_name;
                            $temp["new_caseload"] = $new_caseload_name;
                            $temp["student"] = $student_name;
                            $temp["IEP_status"] = $display_iep_completed;
                            $temp["comments"] = $request_comments;
                            $temp["request_details"] = $request_details;
                            $temp["status"] = $display_transfer_status;
                            $temp["actions"] = $actions;
                            $temp["filter_status"] = $filter_status;
                            $transferRequests[] = $temp;
                        }
                    }
                }
            }
        }

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $transferRequests;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>