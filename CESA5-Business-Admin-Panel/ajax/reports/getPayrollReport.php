<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // initialize the array of data to send
            $masterData = [];

            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // get a list of all employees and their compensation
                $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.yearly_rate AS salary, ec.contract_days, ec.number_of_pays, ec.active FROM employees e
                                                        LEFT JOIN employee_compensation ec ON e.id=ec.employee_id
                                                        WHERE ec.period_id=?");
                mysqli_stmt_bind_param($getEmployees, "i", $period_id);
                if (mysqli_stmt_execute($getEmployees))
                {
                    $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                    if (mysqli_num_rows($getEmployeesResults) > 0)
                    {
                        while ($employee = mysqli_fetch_array($getEmployeesResults))
                        {
                            // store employee data locally
                            $id = $employee["id"];
                            $lname = $employee["lname"];
                            $fname = $employee["fname"];
                            $salary = $employee["salary"];
                            $days = $employee["contract_days"];
                            $num_of_pays = $employee["number_of_pays"];
                            $status = $employee["active"];

                            // build the ID / status column
                            $id_div = ""; // initialize div
                            if ($status == 1) { $id_div .= "<div class='my-1'><span class='text-nowrap'>$id</span><div class='active-div text-center px-3 py-1 float-end'>Active</div></div>"; }
                            else { $id_div .= "<div class='my-1'><span class='text-nowrap'>$id</span><div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div></div>"; } 

                            // calculate the per pay gross
                            $per_pay_gross = 0; // initialize per pay gross variable to 0
                            if ($num_of_pays > 0) { $per_pay_gross = $salary / $num_of_pays; }
                            else { $per_pay_gross = 0; }

                            // build status export column
                            $export_status = "";
                            if ($status == 1) { $export_status = "Active"; } else { $export_status = "Inactive"; }

                            // build tempoary array for the employee to be displayed 
                            $temp = [];
                            $temp["id"] = $id_div;
                            $temp["name"] = $lname.", ".$fname;
                            $temp["salary"] = printDollar($salary);
                            $temp["days"] = $days;
                            $temp["num_of_pays"] = $num_of_pays;
                            $temp["status"] = $export_status;
                            $temp["per_pay_gross"] = printDollar($per_pay_gross);
                            $temp["calc_per_pay_gross"] = $per_pay_gross;
                            $temp["export_id"] = $id;

                            // add employee to the master array
                            $masterData[] = $temp;
                        }
                    }
                }
            }

            // send data to be printed
            $fullData = [];
            $fullData["draw"] = 1;
            $fullData["data"] = $masterData;
            echo json_encode($fullData);

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>