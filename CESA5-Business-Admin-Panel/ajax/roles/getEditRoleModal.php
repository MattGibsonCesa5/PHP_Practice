<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // include additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");
            
            // get role ID from POST
            if (isset($_POST["role_id"]) && trim($_POST["role_id"])) { $role_id = trim($_POST["role_id"]); } else { $role_id = null; }

            if ($role_id != null)
            {
                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                if (verifyRole($conn, $role_id))
                {
                    // initialize variables
                    $role_name = "";
                    $role_permissions = [];

                    // get role details
                    $getRoleDetails = mysqli_prepare($conn, "SELECT * FROM roles WHERE id=?");
                    mysqli_stmt_bind_param($getRoleDetails, "i", $role_id);
                    if (mysqli_stmt_execute($getRoleDetails))
                    {
                        $getRoleDetailsResult = mysqli_stmt_get_result($getRoleDetails);
                        if (mysqli_num_rows($getRoleDetailsResult) > 0) // role exists
                        {
                            // store role details locally
                            $role_details = mysqli_fetch_array($getRoleDetailsResult);
                            $role_name = $role_details["name"];
                        }
                    }

                    // get all permissions assigned to this role
                    $getRolePermissions = mysqli_prepare($conn, "SELECT p.name FROM role_permissions rp JOIN permissions p ON rp.permission_id=p.id WHERE rp.role_id=?");
                    mysqli_stmt_bind_param($getRolePermissions, "i", $role_id);
                    if (mysqli_stmt_execute($getRolePermissions))
                    {
                        $getRolePermissionsResults = mysqli_stmt_get_result($getRolePermissions);
                        if (mysqli_num_rows($getRolePermissionsResults) > 0) // permissions found; continue
                        {
                            while ($permission = mysqli_fetch_array($getRolePermissionsResults))
                            {
                                // store permission name locally
                                $permission_name = $permission["name"];
                                $role_permissions[$permission_name] = 1;
                            }
                        }
                    }

                    // build the edit role modal
                    ?>
                        <!-- Edit Role Modal -->
                        <div class="modal fade" tabindex="-1" role="dialog" id="editRoleModal" data-bs-backdrop="static" aria-labelledby="editRoleModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="editRoleModalLabel">
                                            <?php if (isRoleDefaultGenerated($conn, $role_id)) { ?>
                                                View Role
                                            <?php } else { ?>
                                                Edit Role
                                            <?php } ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body px-4">
                                        <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                            <!-- Role Name -->
                                            <div class="form-group col-12">
                                                <label for="edit-role_name"><span class="required-field">*</span> Role Name:</label>
                                                <input class="form-control w-100" id="edit-role_name" name="edit-role_name" value="<?php echo $role_name; ?>" <?php if (isRoleDefaultGenerated($conn, $role_id)) { echo "disabled readonly"; } ?> required>
                                            </div>
                                        </div>

                                        <div class="row justify-content-center mb-3">
                                            <!-- Employees -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Employees</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-employees-all" <?php if (isset($role_permissions["VIEW_EMPLOYEES_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-employees-all">View All Employees</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-employees-assigned" <?php if (isset($role_permissions["VIEW_EMPLOYEES_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-employees-assigned">View Assigned Employees</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-employees" <?php if (isset($role_permissions["ADD_EMPLOYEES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-employees">Add Employees</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-employees" <?php if (isset($role_permissions["EDIT_EMPLOYEES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-employees">Edit Employees</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-employees" <?php if (isset($role_permissions["DELETE_EMPLOYEES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-employees">Delete Employees</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Departments -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Departments</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-departments-all" <?php if (isset($role_permissions["VIEW_DEPARTMENTS_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-departments-all">View All Departments</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-departments-assigned" <?php if (isset($role_permissions["VIEW_DEPARTMENTS_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-departments-assigned">View Assigned Departments</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-departments" <?php if (isset($role_permissions["ADD_DEPARTMENTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-departments">Add Departments</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-departments" <?php if (isset($role_permissions["EDIT_DEPARTMENTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-departments">Edit Departments</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-departments" <?php if (isset($role_permissions["DELETE_DEPARTMENTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-departments">Delete Departments</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Project Expenses -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Project Expenses</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-project_expenses" <?php if (isset($role_permissions["VIEW_PROJECT_EXPENSES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-project_expenses">View Project Expenses</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-project_expenses" <?php if (isset($role_permissions["ADD_PROJECT_EXPENSES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-project_expenses">Add Project Expenses</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-project_expenses" <?php if (isset($role_permissions["EDIT_PROJECT_EXPENSES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-project_expenses">Edit Project Expenses</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-project_expenses" <?php if (isset($role_permissions["DELETE_PROJECT_EXPENSES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-project_expenses">Delete Project Expenses</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Employee Expenses -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Employee Expenses</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-employee_expenses" <?php if (isset($role_permissions["VIEW_EMPLOYEE_EXPENSES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-employee_expenses">View Employee Expenses</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-employee_expenses" <?php if (isset($role_permissions["EDIT_EMPLOYEE_EXPENSES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-employee_expenses">Edit Employee Expenses</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Manage Services -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Manage Services</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-services-all" <?php if (isset($role_permissions["VIEW_SERVICES_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-services-all">View All Services</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-services-assigned" <?php if (isset($role_permissions["VIEW_SERVICES_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-services-assigned">View Assigned Services</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-services" <?php if (isset($role_permissions["ADD_SERVICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-services">Add Services</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-services" <?php if (isset($role_permissions["EDIT_SERVICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-services">Edit Services</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-services" <?php if (isset($role_permissions["DELETE_SERVICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-services">Delete Services</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Provide Services (Invoices) -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Manage Invoices</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-invoices-all" <?php if (isset($role_permissions["VIEW_INVOICES_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-invoices-all">View All Invoices</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-invoices-assigned" <?php if (isset($role_permissions["VIEW_INVOICES_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-invoices-assigned">View Assigned Invoices</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-invoices" <?php if (isset($role_permissions["ADD_INVOICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-invoices">Add Invoices</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-invoices" <?php if (isset($role_permissions["EDIT_INVOICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-invoices">Edit Invoices</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-invoices" <?php if (isset($role_permissions["DELETE_INVOICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-invoices">Delete Invoices</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Other Services -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Other Services</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-other_services" <?php if (isset($role_permissions["VIEW_OTHER_SERVICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-other_services">View Other Services</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-other_services" <?php if (isset($role_permissions["ADD_OTHER_SERVICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-other_services">Add Other Services</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-other_services" <?php if (isset($role_permissions["EDIT_OTHER_SERVICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-other_services">Edit Other Services</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-other_services" <?php if (isset($role_permissions["DELETE_OTHER_SERVICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-other_services">Delete Other Services</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-invoice-other_services" <?php if (isset($role_permissions["INVOICE_OTHER_SERVICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-invoice-other_services">Invoice Other Services</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Other Revenues -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Other Revenues</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-other_revenues-all" <?php if (isset($role_permissions["VIEW_REVENUES_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-other_revenues-all">View All Other Revenues</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-other_revenues-assigned" <?php if (isset($role_permissions["VIEW_REVENUES_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-other_revenues-assigned">View Assigned Other Revenues</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-other_revenues" <?php if (isset($role_permissions["ADD_REVENUES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-other_revenues">Add Other Revenues</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-other_revenues" <?php if (isset($role_permissions["EDIT_REVENUES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-other_revenues">Edit Other Revenues</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-other_revenues" <?php if (isset($role_permissions["DELETE_REVENUES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-other_revenues">Delete Other Revenues</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Manage Projects -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Manage Projects</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-projects-all" <?php if (isset($role_permissions["VIEW_PROJECTS_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-projects-all">View All Projects</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-projects-assigned" <?php if (isset($role_permissions["VIEW_PROJECTS_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-projects-assigned">View Assigned Projects</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-projects" <?php if (isset($role_permissions["ADD_PROJECTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-projects">Add Projects</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-projects" <?php if (isset($role_permissions["EDIT_PROJECTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-projects">Edit Projects</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-projects" <?php if (isset($role_permissions["DELETE_PROJECTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-projects">Delete Projects</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Budget Projects -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Budget Projects</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-project_budgets-all" <?php if (isset($role_permissions["VIEW_PROJECT_BUDGETS_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-project_budgets-all">View All Project Budgets</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-project_budgets-assigned" <?php if (isset($role_permissions["VIEW_PROJECT_BUDGETS_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-project_budgets-assigned">View Assigned Project Budgets</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-budget-project_budgets-all" <?php if (isset($role_permissions["BUDGET_PROJECTS_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-budget-project_budgets-all">Budget All Projects</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-budget-project_budgets-assigned" <?php if (isset($role_permissions["BUDGET_PROJECTS_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-budget-project_budgets-assigned">Budget Assigned Projects</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Manage Customers -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Manage Customers</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-customers" <?php if (isset($role_permissions["VIEW_CUSTOMERS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-customers">View Customers</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-customers" <?php if (isset($role_permissions["ADD_CUSTOMERS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-customers">Add Customers</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-customers" <?php if (isset($role_permissions["EDIT_CUSTOMERS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-customers">Edit Customers</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-customers" <?php if (isset($role_permissions["DELETE_CUSTOMERS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-customers">Delete Customers</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Customer Groups -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Customer Groups</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-customer_groups" <?php if (isset($role_permissions["VIEW_CUSTOMER_GROUPS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-customer_groups">View Customer Groups</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-customer_groups" <?php if (isset($role_permissions["ADD_CUSTOMER_GROUPS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-customer_groups">Add Customer Groups</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-customer_groups" <?php if (isset($role_permissions["EDIT_CUSTOMER_GROUPS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-customer_groups">Edit Customer Groups</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-customer_groups" <?php if (isset($role_permissions["DELETE_CUSTOMER_GROUPS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-customer_groups">Delete Customer Groups</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Caseloads - Students -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Caseloads - Students</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-caseloads-students-all" <?php if (isset($role_permissions["VIEW_STUDENTS_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-caseloads-students-all">View All Students</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-caseloads-students-assigned" <?php if (isset($role_permissions["VIEW_STUDENTS_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-caseloads-students-assigned">View Assigned Students</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-caseloads-students" <?php if (isset($role_permissions["ADD_STUDENTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-caseloads-students">Add Students</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-caseloads-students" <?php if (isset($role_permissions["EDIT_STUDENTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-caseloads-students">Edit Students</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-caseloads-students" <?php if (isset($role_permissions["DELETE_STUDENTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-caseloads-students">Delete Students</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Caseloads - Therapists -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Caseloads - Therapists</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-caseloads-therapists" <?php if (isset($role_permissions["VIEW_THERAPISTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-caseloads-therapists">View Therapists</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-caseloads-therapists" <?php if (isset($role_permissions["ADD_THERAPISTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-caseloads-therapists">Add Therapists</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-remove-caseloads-therapists" <?php if (isset($role_permissions["REMOVE_THERAPISTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-remove-caseloads-therapists">Remove Therapists</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Manage Caseloads -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Manage Caseloads</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-caseloads-all" <?php if (isset($role_permissions["VIEW_CASELOADS_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-caseloads-all">View All Caseloads</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-caseloads-assigned" <?php if (isset($role_permissions["VIEW_CASELOADS_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-caseloads-assigned">View Assigned Caseloads</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-add-caseloads" <?php if (isset($role_permissions["ADD_CASELOADS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-add-caseloads">Add Caseloads</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-edit-caseloads" <?php if (isset($role_permissions["EDIT_CASELOADS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-edit-caseloads">Edit Caseloads</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-delete-caseloads" <?php if (isset($role_permissions["DELETE_CASELOADS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-delete-caseloads">Delete Caseloads</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-transfer-caseloads" <?php if (isset($role_permissions["TRANSFER_CASELOADS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-transfer-caseloads">Transfer Caseloads</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Salary Comparison -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Salary Comparison</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-salary_comparison-state" <?php if (isset($role_permissions["VIEW_SALARY_COMPARISON_STATE"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-salary_comparison-state">View State Salary Comparison</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-salary_comparison-internal-all" <?php if (isset($role_permissions["VIEW_SALARY_COMPARISON_INTERNAL_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-salary_comparison-internal-all">View All Internal Salary Comparison</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-salary_comparison-internal-assigned" <?php if (isset($role_permissions["VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-salary_comparison-internal-assigned">View Assigned Internal Salary Comparison</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-raise_projection" <?php if (isset($role_permissions["VIEW_RAISE_PROJECTION"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-raise_projection">View Raise Projection</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Dashboard Tiles -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Dashboard Tiles</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-show-revenues" <?php if (isset($role_permissions["DASHBOARD_SHOW_REVENUES_TILE"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-show-revenues">Show Revenues</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-show-expenses" <?php if (isset($role_permissions["DASHBOARD_SHOW_EXPENSES_TILE"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-show-expenses">Show Expenses</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-show-net" <?php if (isset($role_permissions["DASHBOARD_SHOW_NET_TILE"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-show-net">Show Net Income</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-show-employees" <?php if (isset($role_permissions["DASHBOARD_SHOW_EMPLOYEES_TILE"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-show-employees">Show Employees</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-show-contract_days" <?php if (isset($role_permissions["DASHBOARD_SHOW_CONTRACT_DAYS_TILE"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-show-contract_days">Show Contract Days</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-show-budget_errors-all" <?php if (isset($role_permissions["DASHBOARD_SHOW_BUDGET_ERRORS_ALL_TILE"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-show-budget_errors-all">Show All Budget Errors</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-show-budget_errors-assigned" <?php if (isset($role_permissions["DASHBOARD_SHOW_BUDGET_ERRORS_ASSIGNED_TILE"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-show-budget_errors-assigned">Show Assigned Budget Errors</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-show-caseloads-all" <?php if (isset($role_permissions["DASHBOARD_SHOW_CASELOADS_ALL_TILE"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-show-caseloads-all">Show All Caseloads</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-show-maintenance_mode" <?php if (isset($role_permissions["DASHBOARD_SHOW_MAINTENANCE_MODE_TILE"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-show-maintenance_mode">Show Maintenance Mode</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Contracts -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Contracts</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-service_contracts" <?php if (isset($role_permissions["VIEW_SERVICE_CONTRACTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-service_contracts">View Service Contracts</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-quarterly_invoices" <?php if (isset($role_permissions["VIEW_QUARTERLY_INVOICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-quarterly_invoices">View Quarterly Invoices</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-create-service_contracts" <?php if (isset($role_permissions["CREATE_SERVICE_CONTRACTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-create-service_contracts">Create Service Contracts</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-create-quarterly_invoices" <?php if (isset($role_permissions["CREATE_QUARTERLY_INVOICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-create-quarterly_invoices">Create Quarterly Invoices</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-build-service_contracts" <?php if (isset($role_permissions["BUILD_SERVICE_CONTRACTS"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-build-service_contracts">Build Service Contracts</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-build-quarterly_invoices" <?php if (isset($role_permissions["BUILD_QUARTERLY_INVOICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-build-quarterly_invoices">Build Quarterly Invoices</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-export_invoices" <?php if (isset($role_permissions["EXPORT_INVOICES"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-export_invoices">Export Invoices</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Reports -->
                                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-2 p-1">
                                                <div class="card role-card h-100">
                                                    <div class="card-body p-2">
                                                        <h5 class="card-title">Reports</h5>
                                                        <div class="card-text" style="font-size: 14px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-misbudgeted_employees-all" <?php if (isset($role_permissions["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-misbudgeted_employees-all">View All Misbudgeted Employees</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-misbudgeted_employees-assigned" <?php if (isset($role_permissions["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-misbudgeted_employees-assigned">View Assigned Misbudgeted Employees</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-budgeted_inactive-all" <?php if (isset($role_permissions["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-budgeted_inactive-all">View All Budgeted Inactive Employees</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-budgeted_inactive-assigned" <?php if (isset($role_permissions["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-budgeted_inactive-assigned">View Assigned Budgeted Inactive Employees</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-test_employees-all" <?php if (isset($role_permissions["VIEW_REPORT_TEST_EMPLOYEES_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-test_employees-all">View All Test Employees Employees</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-test_employees-assigned" <?php if (isset($role_permissions["VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-test_employees-assigned">View Assigned Test Employees Employees</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-salary_projection-all" <?php if (isset($role_permissions["VIEW_REPORT_SALARY_PROJECTION_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-salary_projection-all">View All Employees Salary Projection</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-salary_projection-assigned" <?php if (isset($role_permissions["VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-salary_projection-assigned">View Assigned Employees Salary Projection</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-employee_changes-all" <?php if (isset($role_permissions["VIEW_REPORT_EMPLOYEE_CHANGES_ALL"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-employee_changes-all">View All Employees Changes</label>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="ER-view-employee_changes-assigned" <?php if (isset($role_permissions["VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"])) { echo "checked"; } ?>>
                                                                <label class="form-check-label" for="ER-view-employee_changes-assigned">View Assigned Employees Changes</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <?php if (!isRoleDefaultGenerated($conn, $role_id)) { // only allow roles not generated by default allowed to be saved ?>
                                        <button type="button" class="btn btn-primary" onclick="editRole(<?php echo $role_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Role</button>
                                        <?php } ?>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Edit Role Modal -->
                    <?php
                }

                // disconnect from the database
                mysqli_close($conn);
            }
        }
    }
?>