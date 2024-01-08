<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "BUDGET_PROJECTS_ALL") || checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED"))
        {
            // get the parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // verify project exists
                if (verifyProject($conn, $code))
                {
                    // get the project's fund and function codes
                    $fund_code = $function_code = null; 
                    $staff_location = 0;
                    $getCodes = mysqli_prepare($conn, "SELECT fund_code, function_code, staff_location FROM projects WHERE code=?");
                    mysqli_stmt_bind_param($getCodes, "s", $code);
                    if (mysqli_stmt_execute($getCodes))
                    {
                        $getCodesResult = mysqli_stmt_get_result($getCodes);
                        if (mysqli_num_rows($getCodesResult) > 0)
                        {
                            $codes = mysqli_fetch_array($getCodesResult);
                            $fund_code = $codes["fund_code"];
                            $function_code = $codes["function_code"];
                            $staff_location = $codes["staff_location"];
                        }
                    }

                    ?>
                        <div class="modal fade" tabindex="-1" role="dialog" id="addEmployeeToProjectModal" data-bs-backdrop="static" aria-labelledby="addEmployeeToProjectModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="addEmployeeToProjectModalLabel">Add Employee To Project</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <!-- Disclaimer -->
                                        <p>
                                            When selecting an employee to add to the project, we will display their days remaining to be fully budgeted, as well as how many days are in their contract.
                                            If the number of days remaining is negative, this means the employee has already been budgeted more days than they are expected.
                                            We will not prevent the addition of an employee who has been overbudgeted; however, these errors should be fixed at some point.
                                            To view employees who have been misbudgeted, view the <a class="template-link" href="days_misbudgeted.php" target="_blank">Misbudgeted Employees</a> report.
                                        </p>

                                        <div class="row d-flex justify-content-between my-3">
                                            <div class="col-9">
                                                <label class="fw-bold" for="add-employee_to_project-employee"><span class="required-field">*</span> Employee</label>
                                                <select class="form-select w-100" id="add-employee_to_project-employee" name="add-employee_to_project-employee" required>
                                                    <option></option>
                                                    <?php
                                                        if (checkUserPermission($conn, "BUDGET_PROJECTS_ALL")) // admin list - create a dropdown of all active employees
                                                        { 
                                                            $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.contract_days FROM employees e 
                                                                                                    JOIN employee_compensation ec ON e.id=ec.employee_id
                                                                                                    WHERE ec.active=1 AND ec.period_id=? 
                                                                                                    ORDER BY e.lname ASC, e.fname ASC"); 
                                                            mysqli_stmt_bind_param($getEmployees, "i", $period_id);
                                                        }
                                                        else if (checkUserPermission($conn, "BUDGET_PROJECTS_ASSIGNED")) // director list - create a dropdown of all active employees in their department(s)
                                                        { 
                                                            $getEmployees = mysqli_prepare($conn, "SELECT e.id, e.fname, e.lname, ec.contract_days FROM employees e
                                                                                                    JOIN employee_compensation ec ON e.id=ec.employee_id
                                                                                                    JOIN department_members dm ON e.id=dm.employee_id 
                                                                                                    JOIN departments d ON d.id=dm.department_id 
                                                                                                    WHERE ec.active=1 AND ec.period_id=? AND ((d.director_id=? OR d.secondary_director_id=?) OR e.global=1) 
                                                                                                    ORDER BY e.lname ASC, e.fname ASC"); 
                                                            mysqli_stmt_bind_param($getEmployees, "iii", $period_id, $_SESSION["id"], $_SESSION["id"]);
                                                        }
                                                        
                                                        if (mysqli_stmt_execute($getEmployees))
                                                        {
                                                            $getEmployeesResults = mysqli_stmt_get_result($getEmployees);
                                                            if (mysqli_num_rows($getEmployeesResults) > 0) // employees found
                                                            {
                                                                while ($employee = mysqli_fetch_array($getEmployeesResults))
                                                                {
                                                                    // store employee details locally
                                                                    $id = $employee["id"];
                                                                    $fname = $employee["fname"];
                                                                    $lname = $employee["lname"];
                                                                    $name = $lname . ", " . $fname;
                                                                    $days = $employee["contract_days"];
                                                                    
                                                                    // get the number of days the employee has already been budgeted
                                                                    $budgeted_days = getBudgetedDays($conn, $id, $period_id);

                                                                    // calculate the days remaining an employee has to be fully budgeted
                                                                    $days_remaining = $days - $budgeted_days;

                                                                    // create the dropdown option
                                                                    echo "<option value=".$id.">".$name." (".$days_remaining."/".$days.")</option>";
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-3">
                                                <label class="fw-bold" for="add-employee_to_project-days"><span class="required-field">*</span> Days In Project</label>
                                                <input type="number" min="0" max="365" class="form-control w-100" id="add-employee_to_project-days" name="add-employee_to_project-days" required>
                                            </div>
                                        </div>

                                        <div class="row d-flex justify-content-between my-3">
                                            <div class="col-12">
                                                <h5 class="text-center fw-bold">WUFAR Codes</h5>
                                            </div>
                                            <div class="col-3">
                                                <label class="fw-bold" for="add-employee_to_project-fund"><span class="required-field">*</span> Fund</label>
                                                <input type="number" min="10" max="99" value="<?php echo $fund_code; ?>" class="form-control w-100" id="add-employee_to_project-fund" name="add-employee_to_project-fund" required>
                                            </div>
                                            <div class="col-3">
                                                <label class="fw-bold" for="add-employee_to_project-loc"><span class="required-field">*</span> Location</label>
                                                <input type="number" min="100" max="999" value="999" class="form-control w-100" id="add-employee_to_project-loc" name="add-employee_to_project-loc" required>
                                            </div>
                                            <div class="col-3">
                                                <label class="fw-bold" for="add-employee_to_project-obj"><span class="required-field">*</span> Object</label>
                                                <input type="number" min="100" max="999" value="100" class="form-control w-100" id="add-employee_to_project-obj" name="add-employee_to_project-obj" required>
                                            </div>
                                            <div class="col-3">
                                                <label class="fw-bold" for="add-employee_to_project-func"><span class="required-field">*</span> Function</label>
                                                <input type="number" min="100000" max="999999" value="<?php echo $function_code; ?>" class="form-control w-100" id="add-employee_to_project-func" name="add-employee_to_project-func" required>
                                            </div>
                                        </div>

                                        <?php if ($staff_location == 1 || $staff_location == 2) { ?>
                                            <div class="row d-flex justify-content-between my-3">
                                                <div class="col-12">
                                                    <label class="fw-bold" for="add-employee_to_project-staff_location">Staff Location</label>
                                                    <?php if ($staff_location == 1) { ?>
                                                        <select class="form-select" id="add-employee_to_project-staff_location">
                                                            <option></option>
                                                            <?php
                                                                // get customers to populate options
                                                                $getCustomers = mysqli_query($conn, "SELECT id, name FROM customers ORDER BY name ASC");
                                                                if (mysqli_num_rows($getCustomers) > 0)
                                                                {
                                                                    while ($customer = mysqli_fetch_array($getCustomers))
                                                                    {
                                                                        // store customer details locally
                                                                        $customer_id = $customer["id"];
                                                                        $customer_name = $customer["name"];

                                                                        // build option
                                                                        echo "<option id='".$customer_id."'>".$customer_name."</option>";
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    <?php } else if ($staff_location == 2) { ?>
                                                        <select class="form-select" id="add-employee_to_project-staff_location">
                                                            <option></option>
                                                            <?php
                                                                // get categories/classrooms to populate options
                                                                $getCategories = mysqli_query($conn, "SELECT id, name FROM caseload_categories WHERE is_classroom=1 ORDER BY name ASC");
                                                                if (mysqli_num_rows($getCategories) > 0)
                                                                {
                                                                    while ($category = mysqli_fetch_array($getCategories))
                                                                    {
                                                                        // store category details locally
                                                                        $category_id = $category["id"];
                                                                        $category_name = $category["name"];

                                                                        // create option group for the category
                                                                        ?>
                                                                            <optgroup label="<?php echo $category_name; ?>">
                                                                            <?php
                                                                                // get classrooms to populate options
                                                                                $getClassrooms = mysqli_prepare($conn, "SELECT id, name FROM caseload_classrooms WHERE category_id=? ORDER BY name ASC");
                                                                                mysqli_stmt_bind_param($getClassrooms, "i", $category_id);
                                                                                if (mysqli_stmt_execute($getClassrooms))
                                                                                {
                                                                                    $getClassroomsResult = mysqli_stmt_get_result($getClassrooms);
                                                                                    if (mysqli_num_rows($getClassroomsResult) > 0)
                                                                                    {
                                                                                        while ($classroom = mysqli_fetch_array($getClassroomsResult))
                                                                                        {
                                                                                            // store classroom details locally
                                                                                            $classroom_id = $classroom["id"];
                                                                                            $classroom_name = $classroom["name"];

                                                                                            // build option 
                                                                                            echo "<option value='".$classroom_id."'>".$classroom_name."</option>";
                                                                                        }
                                                                                    }
                                                                                }
                                                                            ?>
                                                                            </optgroup>
                                                                        <?php
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } else { ?>
                                            <input type="hidden" id="add-employee_to_project-staff_location" value="0" aria-hidden="true">
                                        <?php } ?>

                                        <!-- Required Field Indicator -->
                                        <div class="row justify-content-center">
                                            <div class="text-center fst-italic">
                                                <span class="required-field">*</span> indicates a required field
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" onclick="addEmployeeToProject();"><i class="fa-solid fa-plus"></i> Add Employee</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>