<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // initialize array to store employees
        $employees = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_SALARY_COMPARISON_INTERNAL_ALL") || checkUserPermission($conn, "VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"))
        {
            // get the required POST parameters 
            if (isset($_POST["title"]) && $_POST["title"] <> "") { $title = trim($_POST["title"]); } else { $title = null; }
            if (isset($_POST["dept"]) && $_POST["dept"] <> "") { $dept = trim($_POST["dept"]); } else { $dept = null; }

            // get employees list based on user role and parameters provided
            if (checkUserPermission($conn, "VIEW_SALARY_COMPARISON_INTERNAL_ALL"))
            {
                if ($title == null && $dept == null) // no title or department set
                {
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.title_id, ec.yearly_rate, ec.contract_days, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.assignment_position, ec.sub_assignment, ec.experience, ec.highest_degree, d.name AS department_name, ec.active FROM employees e
                                                JOIN employee_compensation ec ON e.id=ec.employee_id
                                                JOIN department_members dm ON e.id=dm.employee_id
                                                JOIN departments d ON dm.department_id=d.id
                                                WHERE ec.period_id=? AND dm.is_primary=1
                                                ORDER BY ec.experience ASC");
                    mysqli_stmt_bind_param($getEmployees, "i", $GLOBAL_SETTINGS["active_period"]);
                }
                else if ($title == null && $dept != null) // no title set; department set
                {
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.title_id, ec.yearly_rate, ec.contract_days, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.assignment_position, ec.sub_assignment, ec.experience, ec.highest_degree, d.name AS department_name, ec.active FROM employees e
                                        JOIN employee_compensation ec ON e.id=ec.employee_id
                                        JOIN department_members dm ON e.id=dm.employee_id
                                        JOIN departments d ON dm.department_id=d.id
                                        WHERE ec.period_id=? AND d.name=? AND dm.is_primary=1
                                        ORDER BY ec.experience ASC");
                    mysqli_stmt_bind_param($getEmployees, "is", $GLOBAL_SETTINGS["active_period"], $dept);
                }
                else if ($title != null && $dept == null) // title set; no department set
                {
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.yearly_rate, ec.contract_days, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.assignment_position, ec.sub_assignment, ec.experience, ec.highest_degree, d.name AS department_name, ec.active FROM employees e
                                                JOIN employee_compensation ec ON e.id=ec.employee_id
                                                JOIN department_members dm ON e.id=dm.employee_id
                                                JOIN departments d ON dm.department_id=d.id
                                                WHERE ec.period_id=? AND dm.is_primary=1 AND ec.title_id=?
                                                ORDER BY ec.experience ASC");
                    mysqli_stmt_bind_param($getEmployees, "ii", $GLOBAL_SETTINGS["active_period"], $title);
                }
                else if ($title != null && $dept != null) // both title and department set
                {
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.yearly_rate, ec.contract_days, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.assignment_position, ec.sub_assignment, ec.experience, ec.highest_degree, d.name AS department_name, ec.active FROM employees e
                                                JOIN employee_compensation ec ON e.id=ec.employee_id
                                                JOIN department_members dm ON e.id=dm.employee_id
                                                JOIN departments d ON dm.department_id=d.id
                                                WHERE ec.period_id=? AND d.name=? AND dm.is_primary=1 AND ec.title_id=?
                                                ORDER BY ec.experience ASC");
                    mysqli_stmt_bind_param($getEmployees, "isi", $GLOBAL_SETTINGS["active_period"], $dept, $title);
                }
            }
            else if (checkUserPermission($conn, "VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"))
            {
                if ($title == null && $dept == null) // no title or department set
                {
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec..title_id, ec.yearly_rate, ec.contract_days, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.assignment_position, ec.sub_assignment, ec.experience, ec.highest_degree, d.name AS department_name, ec.active FROM employees e
                                                JOIN employee_compensation ec ON e.id=ec.employee_id
                                                JOIN department_members dm ON e.id=dm.employee_id
                                                JOIN departments d ON dm.department_id=d.id
                                                WHERE ec.period_id=? AND dm.is_primary=1 AND (d.director_id=? OR d.secondary_director_id=?)
                                                ORDER BY ec.experience ASC");
                    mysqli_stmt_bind_param($getEmployees, "iii", $GLOBAL_SETTINGS["active_period"], $_SESSION["id"], $_SESSION["id"]);
                }
                else if ($title == null && $dept != null) // no title set; department set
                {
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.title_id, ec.yearly_rate, ec.contract_days, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.assignment_position, ec.sub_assignment, ec.experience, ec.highest_degree, d.name AS department_name, ec.active FROM employees e
                                        JOIN employee_compensation ec ON e.id=ec.employee_id
                                        JOIN department_members dm ON e.id=dm.employee_id
                                        JOIN departments d ON dm.department_id=d.id
                                        WHERE ec.period_id=? AND d.name=? AND dm.is_primary=1 AND (d.director_id=? OR d.secondary_director_id=?)
                                        ORDER BY ec.experience ASC");
                    mysqli_stmt_bind_param($getEmployees, "isii", $GLOBAL_SETTINGS["active_period"], $dept, $_SESSION["id"], $_SESSION["id"]);
                }
                else if ($title != null && $dept == null) // title set; no department set
                {
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.yearly_rate, ec.contract_days, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.assignment_position, ec.sub_assignment, ec.experience, ec.highest_degree, d.name AS department_name, ec.active FROM employees e
                                                JOIN employee_compensation ec ON e.id=ec.employee_id
                                                JOIN department_members dm ON e.id=dm.employee_id
                                                JOIN departments d ON dm.department_id=d.id
                                                WHERE ec.period_id=? AND dm.is_primary=1 AND ec.title_id=? AND (d.director_id=? OR d.secondary_director_id=?)
                                                ORDER BY ec.experience ASC");
                    mysqli_stmt_bind_param($getEmployees, "iiii", $GLOBAL_SETTINGS["active_period"], $title, $_SESSION["id"], $_SESSION["id"]);
                }
                else if ($title != null && $dept != null) // both title and department set
                {
                    $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.yearly_rate, ec.contract_days, ec.health_insurance, ec.dental_insurance, ec.wrs_eligible, ec.assignment_position, ec.sub_assignment, ec.experience, ec.highest_degree, d.name AS department_name, ec.active FROM employees e
                                                JOIN employee_compensation ec ON e.id=ec.employee_id
                                                JOIN department_members dm ON e.id=dm.employee_id
                                                JOIN departments d ON dm.department_id=d.id
                                                WHERE ec.period_id=? AND d.name=? AND dm.is_primary=1 AND ec.title_id=? AND (d.director_id=? OR d.secondary_director_id=?)
                                                ORDER BY ec.experience ASC");
                    mysqli_stmt_bind_param($getEmployees, "isiii", $GLOBAL_SETTINGS["active_period"], $dept, $title, $_SESSION["id"], $_SESSION["id"]);
                }
            }

            // execute the prepared query
            if (mysqli_stmt_execute($getEmployees))
            {
                $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                if (mysqli_num_rows($getEmployeesResults) > 0) // employees found
                {
                    while ($employee = mysqli_fetch_array($getEmployeesResults))
                    {
                        $employees[] = $employee;
                    }
                }
            }

            ?>
                <!-- CESA Salary Breakdown Table -->
                <table class="report_table w-100" id="internal-salaries-breakdown">
                    <thead>
                        <tr>
                            <th class="text-center p-1">Employee ID</th>
                            <th class="text-center p-1">First Name</th>
                            <th class="text-center p-1">Last Name</th>
                            <th class="text-center p-1">Primary Department</th>
                            <th class="text-center p-1">DPI Assignment</th>
                            <th class="text-center p-1">Years Of Total Experience</th>
                            <th class="text-center p-1">Contract Days</th>
                            <th class="text-center p-1">Yearly Rate</th>
                            <th class="text-center p-1">Daily Rate</th>
                            <th class="text-center p-1">Hourly Rate</th>
                            <th class="text-center p-1">Health Insurance</th>
                            <th class="text-center p-1">Dental Insurance</th>
                            <th class="text-center p-1">WRS Eligible</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php 
                            for ($e = 0; $e < count($employees); $e++) 
                            { 
                                // store the employee contract days
                                $contract_days = $employees[$e]["contract_days"];

                                // store the yearly rate
                                $yearly_rate = $employees[$e]["yearly_rate"];

                                // calculate the daily rate
                                if ($contract_days > 0) { $daily_rate = $yearly_rate / $contract_days; }
                                else { $daily_rate = 0; }

                                // calculate the hourly rate
                                $hourly_rate = $daily_rate / $GLOBAL_SETTINGS["hours_per_workday"];

                                // store DPI position and area locally
                                $position = $employees[$e]["assignment_position"];
                                $area = $employees[$e]["sub_assignment"];

                                // build the dpi assignment div
                                $dpi_assignment = "";
                                if ((isset($position) && $position <> "") && (isset($area) && $area <> ""))
                                {
                                    $dpi_assignment = "<div class='card text-white bg-secondary w-100 m-0'>
                                        <div class='row g-0'>
                                            <div class='col-12'>
                                                <div class='card-body px-2 py-0'>
                                                    <h5 class='card-title my-1'>$position</h5>
                                                    <h6 class='my-1'>$area</h6>
                                                </div>
                                            </div> 
                                        </div>
                                    </div>";
                                }

                                // build the ID / status column
                                $id = $employees[$e]["id"];
                                $active = $employees[$e]["active"];
                                $id_div = ""; // initialize div
                                if ($active == 1) { $id_div = "<div class='d-none' aria-hidden='true'>$id</div><div class='active-div text-center p-1 my-1'>Active</div><div class='my-1'>$id</div>"; }
                                else { $id_div = "<div class='d-none' aria-hidden='true'>$id</div><div class='inactive-div text-center p-1'>Inactive</div><div class='my-1'>$id</div>"; } 

                                ?>
                                    <tr>
                                        <td><?php echo $id_div; ?></td>
                                        <td><?php echo $employees[$e]["fname"]; ?></td>
                                        <td><?php echo $employees[$e]["lname"]; ?></td>
                                        <td><?php echo $employees[$e]["department_name"]; ?></td>
                                        <td><?php echo $dpi_assignment; ?></td>
                                        <td class="text-center"><?php echo $employees[$e]["experience"]; ?></td>
                                        <td class="text-center"><?php echo $employees[$e]["contract_days"]; ?></td>
                                        <td class="text-end"><?php echo printDollar($yearly_rate, 2); ?></td>
                                        <td class="text-end"><?php echo printDollar($daily_rate, 2); ?></td>
                                        <td class="text-end"><?php echo printDollar($hourly_rate, 2); ?></td>
                                        <td class="text-center"><?php if ($employees[$e]["health_insurance"] == 1) { echo "Family"; } else if ($employees[$e]["health_insurance"] == 2) { echo "Single"; } else { echo "None"; } ?></td>
                                        <td class="text-center"><?php if ($employees[$e]["dental_insurance"] == 1) { echo "Family"; } else if ($employees[$e]["dental_insurance"] == 2) { echo "Single"; } else { echo "None"; } ?></td>
                                        <td class="text-center"><?php if ($employees[$e]["wrs_eligible"] == 1) { echo "Yes"; } else { echo "No"; } ?></td>
                                    </tr>
                                <?php 
                            } 
                        ?>
                    </tbody>
                </table>
            <?php
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>