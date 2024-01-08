<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_REPORT_SALARY_PROJECTION_ALL") || checkUserPermission($conn, "VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"))
        {
            // get the employee ID from POST
            if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }

            if ($employee_id != null)
            {
                // get the salary projection rate from the database
                $salary_projection_rate = 1; // assume projection rate is 0% (1)
                $getSalaryProjectionRate = mysqli_query($conn, "SELECT salary_projection_rate FROM settings WHERE id=1");
                if (mysqli_num_rows($getSalaryProjectionRate) > 0)
                {
                    $salary_projection_rate_value = mysqli_fetch_array($getSalaryProjectionRate)["salary_projection_rate"];
                    $salary_projection_rate = (($salary_projection_rate_value / 100) + 1);
                }

                $getEmployee = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.yearly_rate, ec.contract_days, ec.assignment_position, ec.sub_assignment, ec.experience, ec.highest_degree
                                                        FROM employees e
                                                        JOIN employee_compensation ec ON e.id=ec.employee_id
                                                        WHERE ec.period_id=? AND e.id=?");
                mysqli_stmt_bind_param($getEmployee, "ii", $GLOBAL_SETTINGS["active_period"], $employee_id);
                if (mysqli_stmt_execute($getEmployee))
                {
                    $getEmployeeResult = mysqli_stmt_get_result($getEmployee);
                    if (mysqli_num_rows($getEmployeeResult) > 0) // employee found
                    {
                        while ($employee = mysqli_fetch_array($getEmployeeResult))
                        {
                            // store the employee details locally
                            $id = $employee["id"];
                            $fname = $employee["fname"];
                            $lname = $employee["lname"];
                            $salary = $employee["yearly_rate"];
                            $days = $employee["contract_days"];
                            $position = $employee["assignment_position"];
                            $area = $employee["sub_assignment"];
                            $experience = $employee["experience"];
                            $degree = $employee["highest_degree"];

                            // calculate the employee's daily salary
                            $daily_salary = 0;
                            if ($days > 0) { $daily_salary = $salary / $days; }

                            // get all DPI reported employees, whose salary is greater than 0, with matching details
                            $dpi_array = [];
                            $cummulative_contract_days = $cummulative_projected_salary = $avg_contract_days = $avg_projected_salary = $avg_daily_salary = $dpi_employee_count = 0; // initialize variables
                            $getDPIEmployees = mysqli_prepare($conn, "SELECT total_salary, total_experience, contract_days FROM dpi_employees WHERE assignment_position=? AND assignment_area=? AND contract_high_degree=? AND total_salary>0");
                            mysqli_stmt_bind_param($getDPIEmployees, "sss", $position, $area, $degree);
                            if (mysqli_stmt_execute($getDPIEmployees))
                            {
                                $getDPIEmployeesResults = mysqli_stmt_get_result($getDPIEmployees);
                                if (mysqli_num_rows($getDPIEmployeesResults) > 0) // employees with same parameters found
                                {
                                    while ($dpi_employee = mysqli_fetch_array($getDPIEmployeesResults))
                                    {
                                        // store the total salary of the DPI employee locally
                                        $dpi_employee_salary = $dpi_employee["total_salary"];
                                        $dpi_employee_total_experience = $dpi_employee["total_experience"];
                                        $dpi_employee_contract_days = $dpi_employee["contract_days"];

                                        if (($experience >= 16 && $dpi_employee_total_experience >= 16) || ($experience == $dpi_employee_total_experience))
                                        {
                                            // increase the DPI employee salary by our set salary projection rate
                                            $dpi_employee_projected_salary = ($dpi_employee_salary * $salary_projection_rate);

                                            /// add the contract days to the cummulative total
                                            $cummulative_contract_days += $dpi_employee_contract_days;

                                            // add the projected salary to the cummulative total
                                            $cummulative_projected_salary += $dpi_employee_projected_salary;
                                            
                                            // increment counter
                                            $dpi_employee_count++;
                                        }
                                    }
                                }
                            }

                            if ($dpi_employee_count > 0) 
                            { 
                                $avg_projected_salary = $cummulative_projected_salary / $dpi_employee_count; 
                                $avg_contract_days = $cummulative_contract_days / $dpi_employee_count;
                                if ($avg_contract_days > 0) { $avg_daily_salary = ($avg_projected_salary / $avg_contract_days); }
                            }

                            // if our employee's rate is less than average, calculate % we need to increase and add the employee to the report
                            $match_yearly_rate = $match_daily_rate = $match_inbetween = 0;
                            // calculate yearly salary rate increase/decrease
                            if ($salary > 0) // if the employee's salary is greater than 0 (prevents divide by 0 error)
                            {
                                // calculate the % we need to increase the employee's salary to match the average
                                $match_yearly_rate = ((($avg_projected_salary - $salary) / $salary) * 100);
                            }
                            // calculate daily salary rate increase/decrease
                            if ($daily_salary > 0) // if the employee's daily salary is greater than 0 (prevents divide by 0 error)
                            {
                                // calculate the % we need to increase the employee's salary to match the average
                                $match_daily_rate = ((($avg_daily_salary - $daily_salary) / $daily_salary) * 100);
                            }
                            // calculate the inbetween rate increase/decrease
                            $match_inbetween = (($match_yearly_rate + $match_daily_rate) / 2);

                            ?>
                                <div class="modal fade" tabindex="-1" role="dialog" id="infoModal" data-bs-backdrop="static" aria-labelledby="infoModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header primary-modal-header">
                                                <h5 class="modal-title primary-modal-title" id="infoModalLabel">Additional Info</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="form-row">
                                                    <div class="col-12 text-center">
                                                        <h3>Employee Information</h3>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- ID -->
                                                    <div class="form-group col-2">
                                                        <label class="text-center w-100" for="id">ID</label>
                                                        <input type="text" class="form-control text-center w-100" id="id" name="id" value="<?php echo $id; ?>" readonly>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>
                                                    
                                                    <!-- First Name -->
                                                    <div class="form-group col-4">
                                                        <label class="text-center w-100" for="fname">First Name</label>
                                                        <input type="text" class="form-control text-center w-100" id="fname" name="fname" value="<?php echo $fname; ?>"  readonly>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>

                                                    <!-- Last Name -->
                                                    <div class="form-group col-4">
                                                        <label class="text-center w-100" for="lname">Last Name</label>
                                                        <input type="text" class="form-control text-center w-100" id="lname" name="lname" value="<?php echo $lname; ?>"  readonly>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- Annual Salary -->
                                                    <div class="form-group col-4">
                                                        <label class="text-center w-100" for="salary-yearly">Salary (Yearly)</label>
                                                        <div class="input-group w-100 h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" id="salary-yearly" name="salary-yearly" value="<?php echo number_format($salary, 2); ?>"  readonly>
                                                        </div>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>
                                                    
                                                    <!-- Daily Salary -->
                                                    <div class="form-group col-4">
                                                        <label class="text-center w-100" for="salary-daily">Salary (Daily)</label>
                                                        <div class="input-group w-100 h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" id="salary-daily" name="salary-daily" value="<?php echo number_format($daily_salary, 2); ?>"  readonly>
                                                        </div>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>

                                                    <!-- Contract Days -->
                                                    <div class="form-group col-2">
                                                        <label class="text-center w-100" for="days">Days</label>
                                                        <input type="text" class="form-control text-center w-100" id="days" name="days" value="<?php echo $days; ?>"  readonly>
                                                    </div>
                                                </div>

                                                <div class="card text-white bg-primary mb-3" style="max-width: 540px;">
                                                    <div class="row g-0">
                                                       <div class="col-9">
                                                            <div class="card-body">
                                                                <h6 class="card-title my-1"><?php echo $position; ?></h5>
                                                                <h6 class="card-title my-1"><?php echo $area; ?></h5>
                                                                <?php if ($experience >= 16) { ?>
                                                                    <h6 class="card-title my-1">16+ years of total experience</h5>
                                                                <?php } else { ?>
                                                                    <h6 class="card-title my-1"><?php echo $experience; ?> years of total experience</h5>
                                                                <?php } ?>
                                                                <h6 class="card-title my-1"><?php echo $degree; ?></h5>
                                                            </div>
                                                        </div> 

                                                        <div class="col-3 text-center m-auto">
                                                            <h4 class="card-title m-0"><?php echo $dpi_employee_count; ?></h4>
                                                            <p class="card-text lh-1"><small class="text-muted">matching employees</small></p>
                                                        </div>
                                                    </div>
                                                </div> 
                                                
                                                <div class="form-row">
                                                    <div class="col-12 text-center">
                                                        <h3>DPI Unweighted Averages</h3>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <!-- DPI Average Annual Salary -->
                                                    <div class="form-group col-4">
                                                        <label class="text-center w-100" for="salary-yearly">Salary (Yearly)</label>
                                                        <div class="input-group w-100 h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" id="salary-yearly" name="salary-yearly" value="<?php echo number_format($avg_projected_salary, 2); ?>"  readonly>
                                                        </div>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>
                                                    
                                                    <!-- DPI Average Daily Salary -->
                                                    <div class="form-group col-4">
                                                        <label class="text-center w-100" for="salary-daily">Salary (Daily)</label>
                                                        <div class="input-group w-100 h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" id="salary-daily" name="salary-daily" value="<?php echo number_format($avg_daily_salary, 2); ?>"  readonly>
                                                        </div>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>

                                                    <!-- DPI Average Contract Days -->
                                                    <div class="form-group col-2">
                                                        <label class="text-center w-100" for="days">Days</label>
                                                        <input type="text" class="form-control text-center w-100" id="days" name="days" value="<?php echo round($avg_contract_days, 2); ?>"  readonly>
                                                    </div>
                                                </div>

                                                <div class="form-row">
                                                    <div class="col-12 text-center">
                                                        <h3>Rate Change Suggestions</h3>
                                                    </div>
                                                </div>

                                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                                    <div class="form-group col-3">
                                                        <label class="text-center w-100" for="salary-yearly">Match Yearly</label>
                                                        <div class="input-group w-100 h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control text-center" id="salary-yearly" name="salary-yearly" value="<?php echo round($match_yearly_rate, 2); ?>"  readonly>
                                                        </div>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>
                                                    
                                                    <div class="form-group col-3">
                                                        <label class="text-center w-100" for="salary-daily">Match Daily</label>
                                                        <div class="input-group w-100 h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control text-center" id="salary-daily" name="salary-daily" value="<?php echo round($match_daily_rate, 2); ?>"  readonly>
                                                        </div>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="form-group col-1 p-0"></div>

                                                    <div class="form-group col-3 p-0">
                                                        <label class="text-center w-100" for="days">Inbetween</label>
                                                        <div class="input-group w-100 h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-percent"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control text-center" id="days" name="days" value="<?php echo round($match_inbetween, 2); ?>"  readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                        }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>