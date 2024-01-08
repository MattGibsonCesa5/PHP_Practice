<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get a list of all periods
            $periods = [];
            $getPeriods = mysqli_query($conn, "SELECT * FROM periods");
            while ($period = mysqli_fetch_array($getPeriods)) 
            { 
                // initialize temporary array to store period data
                $temp = [];

                // build the name column
                $name_col = "";
                if ($period["active"] == 1) { $name_col .= $period["name"]."<span class='badge bg-success rounded-pill text-center fw-normal float-end mx-1 px-3 py-1'>Active</span>"; }
                else { $name_col .= $period["name"]."<span class='badge bg-danger rounded-pill text-center fw-normal float-end mx-1 px-3 py-1'>Inactive</span>"; } 

                // get quarterly data for the period
                $display_quarters = [];
                $getQuarters = mysqli_prepare($conn, "SELECT quarter, label, locked FROM quarters WHERE period_id=? ORDER BY quarter ASC");
                mysqli_stmt_bind_param($getQuarters, "i", $period["id"]);
                if (mysqli_stmt_execute($getQuarters))
                {
                    $getQuartersResults = mysqli_stmt_get_result($getQuarters);
                    if (mysqli_num_rows($getQuartersResults) > 0) // quarters found
                    {
                        while ($quarter = mysqli_fetch_array($getQuartersResults))
                        {
                            // store quarter details locally
                            $q = $quarter["quarter"];
                            $label = $quarter["label"];
                            $locked = $quarter["locked"];

                            // build the quarter's display
                            $display_quarter = "<div class=\"d-flex justify-content-evenly align-items-center\">";
                                $display_quarter .= $label;
                                if ($locked == 1) { $display_quarter .= "<span class=\"badge bg-danger rounded-pill float-end mx-1\"><i class=\"fa-solid fa-lock\"></i></span>"; } else { $display_quarter .= "<span class=\"badge bg-success rounded-pill float-end mx-1\"><i class=\"fa-solid fa-lock-open\"></i></span>"; }
                                $display_quarter .= "<button class=\"btn btn-secondary btn-sm rounded-pill float-end mx-1\" type=\"button\" onclick=\"takeSnapshot(".$period["id"].",".$q.");\"><i class=\"fa-solid fa-camera\"></i></button>";
                            $display_quarter .= "</div>";
                            $display_quarters[$q] = $display_quarter;
                        }
                    }
                }

                // build the comparison column
                $display_comparison = "";
                if ($period["comparison"] == 1) { $display_comparison = "<span class=\"badge bg-success rounded-pill mx-1\"><i class=\"fa-solid fa-check\"></i></span>"; } else { $display_comparison = "<span class=\"badge bg-danger rounded-pill mx-1\"><i class=\"fa-solid fa-xmark\"></i></span>"; }

                // build the next period column
                $display_next = "";
                if ($period["next"] == 1) { $display_next = "<span class=\"badge bg-success rounded-pill mx-1\"><i class=\"fa-solid fa-check\"></i></span>"; } else { $display_next = "<span class=\"badge bg-danger rounded-pill mx-1\"><i class=\"fa-solid fa-xmark\"></i></span>"; }

                // build the editable column
                $display_editable = "";
                if ($period["editable"] == 1) { $display_editable = "<span class=\"badge bg-success rounded-pill mx-1\"><i class=\"fa-solid fa-check\"></i></span>"; } else { $display_editable = "<span class=\"badge bg-danger rounded-pill mx-1\"><i class=\"fa-solid fa-xmark\"></i></span>"; }

                // create the actions column
                $actions = "<div class='d-flex justify-content-end'>
                    <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditPeriodModal(".$period["id"].");'><i class='fa-solid fa-pencil'></i></button>
                    <button class='btn btn-primary btn-sm mx-1' type='button' onclick='getDeletePeriodModal(".$period["id"].");'><i class='fa-solid fa-trash-can'></i></button>
                </div>";

                // build the temporary array to return
                $temp["id"] = $period["id"];
                $temp["name"] = $name_col;
                $temp["q1"] = $display_quarters[1];
                $temp["q2"] = $display_quarters[2];
                $temp["q3"] = $display_quarters[3];
                $temp["q4"] = $display_quarters[4];
                $temp["fiscal_start"] = date("n/j/Y", strtotime($period["start_date"]));
                $temp["fiscal_end"] = date("n/j/Y", strtotime($period["end_date"]));
                $temp["school_start"] = date("n/j/Y", strtotime($period["caseload_term_start"]));
                $temp["school_end"] = date("n/j/Y", strtotime($period["caseload_term_end"]));
                $temp["active"] = $period["active"];
                $temp["comparison"] = $display_comparison;
                $temp["editable"] = $display_editable;
                $temp["next"] = $display_next;
                $temp["actions"] = $actions;
                $periods[] = $temp;
            }

            // disconnect from the database
            mysqli_close($conn);

            // send data to be printed
            $fullData = [];
            $fullData["draw"] = 1;
            $fullData["data"] = $periods;
            echo json_encode($fullData);
        }
    }
?>