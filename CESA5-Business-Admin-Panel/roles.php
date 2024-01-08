<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>
                <div class="report">
                    <!-- Page Header -->
                    <div class="table-header p-0">
                        <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3">
                            <!-- Filters-->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <div class="row px-3">
                                    <div class="col-3 ps-2 py-0">
                                        <div class="dropdown float-start">
                                            <button class="btn btn-primary" type="button" id="filtersMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                            <div class="dropdown-menu filters-menu px-2" aria-labelledby="filtersMenu" style="width: 288px;">
                                                <!-- Search Table -->
                                                <div class="row mx-0 mt-0 mb-2">
                                                    <div class="input-group h-auto p-0">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text h-100" id="nav-search-icon">
                                                                <label for="search-all"><i class="fa-solid fa-magnifying-glass"></i></label>
                                                            </span>
                                                        </div>
                                                        <input class="form-control" type="text" placeholder="Search table" id="search-all" name="search-all" autocomplete="off">
                                                    </div>
                                                </div>

                                                <!-- Clear Filters -->
                                                <div class="row m-0">
                                                    <button class="btn btn-secondary w-100" id="clearFilters"><i class="fa-solid fa-xmark"></i> Clear Filters</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Page Header -->
                            <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-8 col-xxl-8 p-0">
                                <h2 class="m-0">Roles</h2>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <div class="dropdown float-end">
                                    <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        Manage Roles
                                    </button>
                                    <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addRoleModal">Add Role</button></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row report-body m-0">
                        <table id="roles" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2">Role</th>
                                    <th class="text-center py-1 px-2">Role Users</th>
                                    <th class="text-center py-1 px-2">Actions</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("roles", "BAP_Roles_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add Role Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addRoleModal" data-bs-backdrop="static" aria-labelledby="addRoleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addRoleModalLabel">Add Role</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body px-4">
                                <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                    <!-- Role Name -->
                                    <div class="form-group col-12">
                                        <label for="add-role_name"><span class="required-field">*</span> Role Name:</label>
                                        <input class="form-control w-100" id="add-role_name" name="add-role_name" required>
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
                                                        <input class="form-check-input" type="checkbox" id="view-employees-all">
                                                        <label class="form-check-label" for="view-employees-all">View All Employees</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-employees-assigned">
                                                        <label class="form-check-label" for="view-employees-assigned">View Assigned Employees</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-employees">
                                                        <label class="form-check-label" for="add-employees">Add Employees</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-employees">
                                                        <label class="form-check-label" for="edit-employees">Edit Employees</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-employees">
                                                        <label class="form-check-label" for="delete-employees">Delete Employees</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-departments-all">
                                                        <label class="form-check-label" for="view-departments-all">View All Departments</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-departments-assigned">
                                                        <label class="form-check-label" for="view-departments-assigned">View Assigned Departments</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-departments">
                                                        <label class="form-check-label" for="add-departments">Add Departments</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-departments">
                                                        <label class="form-check-label" for="edit-departments">Edit Departments</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-departments">
                                                        <label class="form-check-label" for="delete-departments">Delete Departments</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-project_expenses">
                                                        <label class="form-check-label" for="view-project_expenses">View Project Expenses</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-project_expenses">
                                                        <label class="form-check-label" for="add-project_expenses">Add Project Expenses</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-project_expenses">
                                                        <label class="form-check-label" for="edit-project_expenses">Edit Project Expenses</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-project_expenses">
                                                        <label class="form-check-label" for="delete-project_expenses">Delete Project Expenses</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-employee_expenses">
                                                        <label class="form-check-label" for="view-employee_expenses">View Employee Expenses</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-employee_expenses">
                                                        <label class="form-check-label" for="edit-employee_expenses">Edit Employee Expenses</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-services-all">
                                                        <label class="form-check-label" for="view-services-all">View All Services</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-services-assigned">
                                                        <label class="form-check-label" for="view-services-assigned">View Assigned Services</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-services">
                                                        <label class="form-check-label" for="add-services">Add Services</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-services">
                                                        <label class="form-check-label" for="edit-services">Edit Services</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-services">
                                                        <label class="form-check-label" for="delete-services">Delete Services</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-invoices-all">
                                                        <label class="form-check-label" for="view-invoices-all">View All Invoices</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-invoices-assigned">
                                                        <label class="form-check-label" for="view-invoices-assigned">View Assigned Invoices</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-invoices">
                                                        <label class="form-check-label" for="add-invoices">Add Invoices</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-invoices">
                                                        <label class="form-check-label" for="edit-invoices">Edit Invoices</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-invoices">
                                                        <label class="form-check-label" for="delete-invoices">Delete Invoices</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-other_services">
                                                        <label class="form-check-label" for="view-other_services">View Other Services</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-other_services">
                                                        <label class="form-check-label" for="add-other_services">Add Other Services</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-other_services">
                                                        <label class="form-check-label" for="edit-other_services">Edit Other Services</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-other_services">
                                                        <label class="form-check-label" for="delete-other_services">Delete Other Services</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="invoice-other_services">
                                                        <label class="form-check-label" for="invoice-other_services">Invoice Other Services</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-other_revenues-all">
                                                        <label class="form-check-label" for="view-other_revenues-all">View All Other Revenues</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-other_revenues-assigned">
                                                        <label class="form-check-label" for="view-other_revenues-assigned">View Assigned Other Revenues</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-other_revenues">
                                                        <label class="form-check-label" for="add-other_revenues">Add Other Revenues</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-other_revenues">
                                                        <label class="form-check-label" for="edit-other_revenues">Edit Other Revenues</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-other_revenues">
                                                        <label class="form-check-label" for="delete-other_revenues">Delete Other Revenues</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-projects-all">
                                                        <label class="form-check-label" for="view-projects-all">View All Projects</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-projects-assigned">
                                                        <label class="form-check-label" for="view-projects-assigned">View Assigned Projects</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-projects">
                                                        <label class="form-check-label" for="add-projects">Add Projects</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-projects">
                                                        <label class="form-check-label" for="edit-projects">Edit Projects</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-projects">
                                                        <label class="form-check-label" for="delete-projects">Delete Projects</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-project_budgets-all">
                                                        <label class="form-check-label" for="view-project_budgets-all">View All Project Budgets</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-project_budgets-assigned">
                                                        <label class="form-check-label" for="view-project_budgets-assigned">View Assigned Project Budgets</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="budget-project_budgets-all">
                                                        <label class="form-check-label" for="budget-project_budgets-all">Budget All Projects</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="budget-project_budgets-assigned">
                                                        <label class="form-check-label" for="budget-project_budgets-assigned">Budget Assigned Projects</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-customers">
                                                        <label class="form-check-label" for="view-customers">View Customers</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-customers">
                                                        <label class="form-check-label" for="add-customers">Add Customers</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-customers">
                                                        <label class="form-check-label" for="edit-customers">Edit Customers</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-customers">
                                                        <label class="form-check-label" for="delete-customers">Delete Customers</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-customer_groups">
                                                        <label class="form-check-label" for="view-customer_groups">View Customer Groups</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-customer_groups">
                                                        <label class="form-check-label" for="add-customer_groups">Add Customer Groups</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-customer_groups">
                                                        <label class="form-check-label" for="edit-customer_groups">Edit Customer Groups</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-customer_groups">
                                                        <label class="form-check-label" for="delete-customer_groups">Delete Customer Groups</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-caseloads-students-all">
                                                        <label class="form-check-label" for="view-caseloads-students-all">View All Students</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-caseloads-students-assigned">
                                                        <label class="form-check-label" for="view-caseloads-students-assigned">View Assigned Students</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-caseloads-students">
                                                        <label class="form-check-label" for="add-caseloads-students">Add Students</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-caseloads-students">
                                                        <label class="form-check-label" for="edit-caseloads-students">Edit Students</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-caseloads-students">
                                                        <label class="form-check-label" for="delete-caseloads-students">Delete Students</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-caseloads-therapists">
                                                        <label class="form-check-label" for="view-caseloads-therapists">View Therapists</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-caseloads-therapists">
                                                        <label class="form-check-label" for="add-caseloads-therapists">Add Therapists</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="remove-caseloads-therapists">
                                                        <label class="form-check-label" for="remove-caseloads-therapists">Remove Therapists</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-caseloads-all">
                                                        <label class="form-check-label" for="view-caseloads-all">View All Caseloads</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-caseloads-assigned">
                                                        <label class="form-check-label" for="view-caseloads-assigned">View Assigned Caseloads</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="add-caseloads">
                                                        <label class="form-check-label" for="add-caseloads">Add Caseloads</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit-caseloads">
                                                        <label class="form-check-label" for="edit-caseloads">Edit Caseloads</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete-caseloads">
                                                        <label class="form-check-label" for="delete-caseloads">Delete Caseloads</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="transfer-caseloads">
                                                        <label class="form-check-label" for="transfer-caseloads">Transfer Caseloads</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-salary_comparison-state">
                                                        <label class="form-check-label" for="view-salary_comparison-state">View State Salary Comparison</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-salary_comparison-internal-all">
                                                        <label class="form-check-label" for="view-salary_comparison-internal-all">View All Internal Salary Comparison</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-salary_comparison-internal-assigned">
                                                        <label class="form-check-label" for="view-salary_comparison-internal-assigned">View Assigned Internal Salary Comparison</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-raise_projection">
                                                        <label class="form-check-label" for="view-raise_projection">View Raise Projection</label>
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
                                                        <input class="form-check-input" type="checkbox" id="show-revenues">
                                                        <label class="form-check-label" for="show-revenues">Show Revenues</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="show-expenses">
                                                        <label class="form-check-label" for="show-expenses">Show Expenses</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="show-net">
                                                        <label class="form-check-label" for="show-net">Show Net Income</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="show-employees">
                                                        <label class="form-check-label" for="show-employees">Show Employees</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="show-contract_days">
                                                        <label class="form-check-label" for="show-contract_days">Show Contract Days</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="show-budget_errors-all">
                                                        <label class="form-check-label" for="show-budget_errors-all">Show All Budget Errors</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="show-budget_errors-assigned">
                                                        <label class="form-check-label" for="show-budget_errors-assigned">Show Assigned Budget Errors</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="show-caseloads-all">
                                                        <label class="form-check-label" for="show-caseloads-all">Show All Caseloads</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="show-maintenance_mode">
                                                        <label class="form-check-label" for="show-maintenance_mode">Show Maintenance Mode</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-service_contracts">
                                                        <label class="form-check-label" for="view-service_contracts">View Service Contracts</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-quarterly_invoices">
                                                        <label class="form-check-label" for="view-quarterly_invoices">View Quarterly Invoices</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="create-service_contracts">
                                                        <label class="form-check-label" for="create-service_contracts">Create Service Contracts</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="create-quarterly_invoices">
                                                        <label class="form-check-label" for="create-quarterly_invoices">Create Quarterly Invoices</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="build-service_contracts">
                                                        <label class="form-check-label" for="build-service_contracts">Build Service Contracts</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="build-quarterly_invoices">
                                                        <label class="form-check-label" for="build-quarterly_invoices">Build Quarterly Invoices</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="export_invoices">
                                                        <label class="form-check-label" for="export_invoices">Export Invoices</label>
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
                                                        <input class="form-check-input" type="checkbox" id="view-misbudgeted_employees-all">
                                                        <label class="form-check-label" for="view-misbudgeted_employees-all">View All Misbudgeted Employees</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-misbudgeted_employees-assigned">
                                                        <label class="form-check-label" for="view-misbudgeted_employees-assigned">View Assigned Misbudgeted Employees</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-budgeted_inactive-all">
                                                        <label class="form-check-label" for="view-budgeted_inactive-all">View All Budgeted Inactive Employees</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-budgeted_inactive-assigned">
                                                        <label class="form-check-label" for="view-budgeted_inactive-assigned">View Assigned Budgeted Inactive Employees</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-test_employees-all">
                                                        <label class="form-check-label" for="view-test_employees-all">View All Test Employees Employees</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-test_employees-assigned">
                                                        <label class="form-check-label" for="view-test_employees-assigned">View Assigned Test Employees Employees</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-salary_projection-all">
                                                        <label class="form-check-label" for="view-salary_projection-all">View All Employees Salary Projection</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-salary_projection-assigned">
                                                        <label class="form-check-label" for="view-salary_projection-assigned">View Assigned Employees Salary Projection</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-employee_changes-all">
                                                        <label class="form-check-label" for="view-employee_changes-all">View All Employees Changes</label>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="view-employee_changes-assigned">
                                                        <label class="form-check-label" for="view-employee_changes-assigned">View Assigned Employees Changes</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addRole();"><i class="fa-solid fa-plus"></i> Add Role</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Role Modal -->

                <div id="edit-role-modal-div"></div>
                <div id="view-role_users-modal-div"></div>
                <!--
                    END MODALS
                -->

                <script>
                    var roles = $("#roles").DataTable({
                        ajax: {
                            url: "ajax/roles/getRoles.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        paging: true,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "role_name", orderable: true, width: "20%", className: "text-center" },
                            { data: "role_users", orderable: true, width: "15%", className: "text-center" },
                            { data: "actions", orderable: true, width: "65%", className: "text-center" },
                        ],
                        dom: 'rt',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        rowCallback: function (row, data, index)
                        {
                            // initialize page selection dropdown
                            updatePageSelection("roles");

                            // for roles that are default generated, bold the role name
                            if (data["default_generated"] == 1) { $("td:eq(0)", row).addClass("fw-bold"); }
                        },
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        roles.search($(this).val()).draw();
                    });

                    /** function to add a new role */
                    function addRole()
                    {
                        // initialize teh string of data to send
                        let sendString = "";

                        // get group details
                        let role_name = document.getElementById("add-role_name").value;
                        sendString += "role_name="+role_name;

                        // get employee permissions
                        if ($("#view-employees-all").is(":checked")) { sendString += "&view_employees_all=1"; }
                        if ($("#view-employees-assigned").is(":checked")) { sendString += "&view_employees_assigned=1"; }
                        if ($("#add-employees").is(":checked")) { sendString += "&add_employees=1"; }
                        if ($("#edit-employees").is(":checked")) { sendString += "&edit_employees=1"; }
                        if ($("#delete-employees").is(":checked")) { sendString += "&delete_employees=1"; }

                        // get department permissions
                        if ($("#view-departments-all").is(":checked")) { sendString += "&view_departments_all=1"; }
                        if ($("#view-departments-assigned").is(":checked")) { sendString += "&view_departments_assigned=1"; }
                        if ($("#add-departments").is(":checked")) { sendString += "&add_departments=1"; }
                        if ($("#edit-departments").is(":checked")) { sendString += "&edit_departments=1"; }
                        if ($("#delete-departments").is(":checked")) { sendString += "&delete_departments=1"; }

                        // get project expenses permissions
                        if ($("#view-project_expenses").is(":checked")) { sendString += "&view_project_expenses=1"; }
                        if ($("#add-project_expenses").is(":checked")) { sendString += "&add_project_expenses=1"; }
                        if ($("#edit-project_expenses").is(":checked")) { sendString += "&edit_project_expenses=1"; }
                        if ($("#delete-project_expenses").is(":checked")) { sendString += "&delete_project_expenses=1"; }

                        // get employee expenses permissions
                        if ($("#view-employee_expenses").is(":checked")) { sendString += "&view_employee_expenses=1"; }
                        if ($("#edit-employee_expenses").is(":checked")) { sendString += "&edit_employee_expenses=1"; }

                        // get services permissions
                        if ($("#view-services-all").is(":checked")) { sendString += "&view_services_all=1"; }
                        if ($("#view-services-assigned").is(":checked")) { sendString += "&view_services_assigned=1"; }
                        if ($("#add-services").is(":checked")) { sendString += "&add_services=1"; }
                        if ($("#edit-services").is(":checked")) { sendString += "&edit_services=1"; }
                        if ($("#delete-services").is(":checked")) { sendString += "&delete_services=1"; }

                        // get invoices permissions
                        if ($("#view-invoices-all").is(":checked")) { sendString += "&view_invoices_all=1"; }
                        if ($("#view-invoices-assigned").is(":checked")) { sendString += "&view_invoices_assigned=1"; }
                        if ($("#add-invoices").is(":checked")) { sendString += "&add_invoices=1"; }
                        if ($("#edit-invoices").is(":checked")) { sendString += "&edit_invoices=1"; }
                        if ($("#delete-invoices").is(":checked")) { sendString += "&delete_invoices=1"; }

                        // get other services permissions
                        if ($("#view-other_services").is(":checked")) { sendString += "&view_other_services=1"; }
                        if ($("#add-other_services").is(":checked")) { sendString += "&add_other_services=1"; }
                        if ($("#edit-other_services").is(":checked")) { sendString += "&edit_other_services=1"; }
                        if ($("#delete-other_services").is(":checked")) { sendString += "&delete_other_services=1"; }
                        if ($("#invoice-other_services").is(":checked")) { sendString += "&invoice_other_services=1"; }

                        // get revenues permissions
                        if ($("#view-other_revenues-all").is(":checked")) { sendString += "&view_revenues_all=1"; }
                        if ($("#view-other_revenues-assigned").is(":checked")) { sendString += "&view_revenues_assigned=1"; }
                        if ($("#add-other_revenues").is(":checked")) { sendString += "&add_revenues=1"; }
                        if ($("#edit-other_revenues").is(":checked")) { sendString += "&edit_revenues=1"; }
                        if ($("#delete-other_revenues").is(":checked")) { sendString += "&delete_revenues=1"; }

                        // get manage projects permissions
                        if ($("#view-projects-all").is(":checked")) { sendString += "&view_projects_all=1"; }
                        if ($("#view-projects-assigned").is(":checked")) { sendString += "&view_projects_assigned=1"; }
                        if ($("#add-projects").is(":checked")) { sendString += "&add_projects=1"; }
                        if ($("#edit-projects").is(":checked")) { sendString += "&edit_projects=1"; }
                        if ($("#delete-projects").is(":checked")) { sendString += "&delete_projects=1"; }

                        // get manage projects permissions
                        if ($("#view-project_budgets-all").is(":checked")) { sendString += "&view_project_budgets_all=1"; }
                        if ($("#view-project_budgets-assigned").is(":checked")) { sendString += "&view_project_budgets_assigned=1"; }
                        if ($("#budget-project_budgets-all").is(":checked")) { sendString += "&budget_projects_all=1"; }
                        if ($("#budget-project_budgets-assigned").is(":checked")) { sendString += "&budget_projects_assigned=1"; }

                        // get manage customers permissions
                        if ($("#view-customers").is(":checked")) { sendString += "&view_customers=1"; }
                        if ($("#add-customers").is(":checked")) { sendString += "&add_customers=1"; }
                        if ($("#edit-customers").is(":checked")) { sendString += "&edit_customers=1"; }
                        if ($("#delete-customers").is(":checked")) { sendString += "&delete_customers=1"; }

                        // get customer groups permissions
                        if ($("#view-customer_groups").is(":checked")) { sendString += "&view_customer_groups=1"; }
                        if ($("#add-customer_groups").is(":checked")) { sendString += "&add_customer_groups=1"; }
                        if ($("#edit-customer_groups").is(":checked")) { sendString += "&edit_customer_groups=1"; }
                        if ($("#delete-customer_groups").is(":checked")) { sendString += "&delete_customer_groups=1"; }

                        // get caseload students permissions
                        if ($("#view-caseloads-students-all").is(":checked")) { sendString += "&view_students_all=1"; }
                        if ($("#view-caseloads-students-assigned").is(":checked")) { sendString += "&view_students_assigned=1"; }
                        if ($("#add-caseloads-students").is(":checked")) { sendString += "&add_students=1"; }
                        if ($("#edit-caseloads-students").is(":checked")) { sendString += "&edit_students=1"; }
                        if ($("#delete-caseloads-students").is(":checked")) { sendString += "&delete_students=1"; }

                        // get caseload therapists permissions
                        if ($("#view-caseloads-therapists").is(":checked")) { sendString += "&view_therapists=1"; }
                        if ($("#add-caseloads-therapists").is(":checked")) { sendString += "&add_therapists=1"; }
                        if ($("#remove-caseloads-therapists").is(":checked")) { sendString += "&remove_therapists=1"; }

                        // get manage caseloads permissions
                        if ($("#view-caseloads-all").is(":checked")) { sendString += "&view_caseloads_all=1"; }
                        if ($("#view-caseloads-assigned").is(":checked")) { sendString += "&view_caseloads_assigned=1"; }
                        if ($("#add-caseloads").is(":checked")) { sendString += "&add_caseloads=1"; }
                        if ($("#edit-caseloads").is(":checked")) { sendString += "&edit_caseloads=1"; }
                        if ($("#delete-caseloads").is(":checked")) { sendString += "&delete_caseloads=1"; }
                        if ($("#transfer-caseloads").is(":checked")) { sendString += "&transfer_caseloads=1"; }

                        // get salary comparison permissions
                        if ($("#view-salary_comparison-state").is(":checked")) { sendString += "&view_salary_comparison_state=1"; }
                        if ($("#view-salary_comparison-internal-all").is(":checked")) { sendString += "&view_salary_comparison_internal_all=1"; }
                        if ($("#view-salary_comparison-internal-assigned").is(":checked")) { sendString += "&view_salary_comparison_internal_assigned=1"; }
                        if ($("#view-raise_projection").is(":checked")) { sendString += "&view_raise_projection=1"; }

                        // get dashboard tiles permissions
                        if ($("#show-revenues").is(":checked")) { sendString += "&dashboard_show_revenues=1"; }
                        if ($("#show-expenses").is(":checked")) { sendString += "&dashboard_show_expenses=1"; }
                        if ($("#show-net").is(":checked")) { sendString += "&dashboard_show_net=1"; }
                        if ($("#show-employees").is(":checked")) { sendString += "&dashboard_show_employees=1"; }
                        if ($("#show-contract_days").is(":checked")) { sendString += "&dashboard_show_contract_days=1"; }
                        if ($("#show-budget_errors-all").is(":checked")) { sendString += "&dashboard_budget_errors_all=1"; }
                        if ($("#show-budget_errors-assigned").is(":checked")) { sendString += "&dashboard_show_budget_errors_assigned=1"; }
                        if ($("#show-maintenance_mode").is(":checked")) { sendString += "&dashboard_show_maintenance_mode=1"; }
                        if ($("#show-caseloads-all").is(":checked")) { sendString += "&dashboard_show_caseloads_all=1"; }

                        // get contract permissions
                        if ($("#view-service_contracts").is(":checked")) { sendString += "&view_service_contracts=1"; }
                        if ($("#view-quarterly_invoices").is(":checked")) { sendString += "&view_quarterly_invoices=1"; }
                        if ($("#create-service_contracts").is(":checked")) { sendString += "&create_service_contracts=1"; }
                        if ($("#create-quarterly_invoices").is(":checked")) { sendString += "&create_quarterly_invoices=1"; }
                        if ($("#build-service_contracts").is(":checked")) { sendString += "&build_service_contracts=1"; }
                        if ($("#build-quarterly_invoices").is(":checked")) { sendString += "&build_quarterly_invoices=1"; }
                        if ($("#export_invoices").is(":checked")) { sendString += "&export_invoices=1"; }

                        // get reports permissions
                        if ($("#view-misbudgeted_employees-all").is(":checked")) { sendString += "&reports_view_misbudgeted_employees_all=1"; }
                        if ($("#view-misbudgeted_employees-assigned").is(":checked")) { sendString += "&reports_view_misbudgeted_employees_assigned=1"; }
                        if ($("#view-budgeted_inactive-all").is(":checked")) { sendString += "&reports_view_budgeted_inactive_all=1"; }
                        if ($("#view-budgeted_inactive-assigned").is(":checked")) { sendString += "&reports_view_budgeted_inactive_assigned=1"; }
                        if ($("#view-test_employees-all").is(":checked")) { sendString += "&reports_view_test_employees_all=1"; }
                        if ($("#view-test_employees-assigned").is(":checked")) { sendString += "&reports_view_test_employees_assigned=1"; }
                        if ($("#view-salary_projection-all").is(":checked")) { sendString += "&reports_view_salary_projection_all=1"; }
                        if ($("#view-salary_projection-assigned").is(":checked")) { sendString += "&reports_view_salary_projection_assigned=1"; }
                        if ($("#view-employee_changes-all").is(":checked")) { sendString += "&reports_view_employee_changes_all=1"; }
                        if ($("#view-employee_changes-assigned").is(":checked")) { sendString += "&reports_view_employee_changes_assigned=1"; }

                        // send the data to process the add role request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/roles/addRole.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Role Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addRoleModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to edit an existing role */
                    function editRole(role_id)
                    {
                        // initialize teh string of data to send
                        let sendString = "";

                        // get group details
                        let role_name = document.getElementById("edit-role_name").value;
                        sendString += "role_id="+role_id+"&role_name="+role_name;

                        // get employee permissions
                        if ($("#ER-view-employees-all").is(":checked")) { sendString += "&view_employees_all=1"; }
                        if ($("#ER-view-employees-assigned").is(":checked")) { sendString += "&view_employees_assigned=1"; }
                        if ($("#ER-add-employees").is(":checked")) { sendString += "&add_employees=1"; }
                        if ($("#ER-edit-employees").is(":checked")) { sendString += "&edit_employees=1"; }
                        if ($("#ER-delete-employees").is(":checked")) { sendString += "&delete_employees=1"; }

                        // get department permissions
                        if ($("#ER-view-departments-all").is(":checked")) { sendString += "&view_departments_all=1"; }
                        if ($("#ER-view-departments-assigned").is(":checked")) { sendString += "&view_departments_assigned=1"; }
                        if ($("#ER-add-departments").is(":checked")) { sendString += "&add_departments=1"; }
                        if ($("#ER-edit-departments").is(":checked")) { sendString += "&edit_departments=1"; }
                        if ($("#ER-delete-departments").is(":checked")) { sendString += "&delete_departments=1"; }

                        // get project expenses permissions
                        if ($("#ER-view-project_expenses").is(":checked")) { sendString += "&view_project_expenses=1"; }
                        if ($("#ER-add-project_expenses").is(":checked")) { sendString += "&add_project_expenses=1"; }
                        if ($("#ER-edit-project_expenses").is(":checked")) { sendString += "&edit_project_expenses=1"; }
                        if ($("#ER-delete-project_expenses").is(":checked")) { sendString += "&delete_project_expenses=1"; }

                        // get employee expenses permissions
                        if ($("#ER-view-employee_expenses").is(":checked")) { sendString += "&view_employee_expenses=1"; }
                        if ($("#ER-edit-employee_expenses").is(":checked")) { sendString += "&edit_employee_expenses=1"; }

                        // get services permissions
                        if ($("#ER-view-services-all").is(":checked")) { sendString += "&view_services_all=1"; }
                        if ($("#ER-view-services-assigned").is(":checked")) { sendString += "&view_services_assigned=1"; }
                        if ($("#ER-add-services").is(":checked")) { sendString += "&add_services=1"; }
                        if ($("#ER-edit-services").is(":checked")) { sendString += "&edit_services=1"; }
                        if ($("#ER-delete-services").is(":checked")) { sendString += "&delete_services=1"; }

                        // get invoices permissions
                        if ($("#ER-view-invoices-all").is(":checked")) { sendString += "&view_invoices_all=1"; }
                        if ($("#ER-view-invoices-assigned").is(":checked")) { sendString += "&view_invoices_assigned=1"; }
                        if ($("#ER-add-invoices").is(":checked")) { sendString += "&add_invoices=1"; }
                        if ($("#ER-edit-invoices").is(":checked")) { sendString += "&edit_invoices=1"; }
                        if ($("#ER-delete-invoices").is(":checked")) { sendString += "&delete_invoices=1"; }

                        // get other services permissions
                        if ($("#ER-view-other_services").is(":checked")) { sendString += "&view_other_services=1"; }
                        if ($("#ER-add-other_services").is(":checked")) { sendString += "&add_other_services=1"; }
                        if ($("#ER-edit-other_services").is(":checked")) { sendString += "&edit_other_services=1"; }
                        if ($("#ER-delete-other_services").is(":checked")) { sendString += "&delete_other_services=1"; }
                        if ($("#ER-invoice-other_services").is(":checked")) { sendString += "&invoice_other_services=1"; }

                        // get revenues permissions
                        if ($("#ER-view-other_revenues-all").is(":checked")) { sendString += "&view_revenues_all=1"; }
                        if ($("#ER-view-other_revenues-assigned").is(":checked")) { sendString += "&view_revenues_assigned=1"; }
                        if ($("#ER-add-other_revenues").is(":checked")) { sendString += "&add_revenues=1"; }
                        if ($("#ER-edit-other_revenues").is(":checked")) { sendString += "&edit_revenues=1"; }
                        if ($("#ER-delete-other_revenues").is(":checked")) { sendString += "&delete_revenues=1"; }

                        // get manage projects permissions
                        if ($("#ER-view-projects-all").is(":checked")) { sendString += "&view_projects_all=1"; }
                        if ($("#ER-view-projects-assigned").is(":checked")) { sendString += "&view_projects_assigned=1"; }
                        if ($("#ER-add-projects").is(":checked")) { sendString += "&add_projects=1"; }
                        if ($("#ER-edit-projects").is(":checked")) { sendString += "&edit_projects=1"; }
                        if ($("#ER-delete-projects").is(":checked")) { sendString += "&delete_projects=1"; }

                        // get manage projects permissions
                        if ($("#ER-view-project_budgets-all").is(":checked")) { sendString += "&view_project_budgets_all=1"; }
                        if ($("#ER-view-project_budgets-assigned").is(":checked")) { sendString += "&view_project_budgets_assigned=1"; }
                        if ($("#ER-budget-project_budgets-all").is(":checked")) { sendString += "&budget_projects_all=1"; }
                        if ($("#ER-budget-project_budgets-assigned").is(":checked")) { sendString += "&budget_projects_assigned=1"; }

                        // get manage customers permissions
                        if ($("#ER-view-customers").is(":checked")) { sendString += "&view_customers=1"; }
                        if ($("#ER-add-customers").is(":checked")) { sendString += "&add_customers=1"; }
                        if ($("#ER-edit-customers").is(":checked")) { sendString += "&edit_customers=1"; }
                        if ($("#ER-delete-customers").is(":checked")) { sendString += "&delete_customers=1"; }

                        // get customer groups permissions
                        if ($("#ER-view-customer_groups").is(":checked")) { sendString += "&view_customer_groups=1"; }
                        if ($("#ER-add-customer_groups").is(":checked")) { sendString += "&add_customer_groups=1"; }
                        if ($("#ER-edit-customer_groups").is(":checked")) { sendString += "&edit_customer_groups=1"; }
                        if ($("#ER-delete-customer_groups").is(":checked")) { sendString += "&delete_customer_groups=1"; }

                        // get caseload students permissions
                        if ($("#ER-view-caseloads-students-all").is(":checked")) { sendString += "&view_students_all=1"; }
                        if ($("#ER-view-caseloads-students-assigned").is(":checked")) { sendString += "&view_students_assigned=1"; }
                        if ($("#ER-add-caseloads-students").is(":checked")) { sendString += "&add_students=1"; }
                        if ($("#ER-edit-caseloads-students").is(":checked")) { sendString += "&edit_students=1"; }
                        if ($("#ER-delete-caseloads-students").is(":checked")) { sendString += "&delete_students=1"; }

                        // get caseload therapists permissions
                        if ($("#ER-view-caseloads-therapists").is(":checked")) { sendString += "&view_therapists=1"; }
                        if ($("#ER-add-caseloads-therapists").is(":checked")) { sendString += "&add_therapists=1"; }
                        if ($("#ER-remove-caseloads-therapists").is(":checked")) { sendString += "&remove_therapists=1"; }

                        // get manage caseloads permissions
                        if ($("#ER-view-caseloads-all").is(":checked")) { sendString += "&view_caseloads_all=1"; }
                        if ($("#ER-view-caseloads-assigned").is(":checked")) { sendString += "&view_caseloads_assigned=1"; }
                        if ($("#ER-add-caseloads").is(":checked")) { sendString += "&add_caseloads=1"; }
                        if ($("#ER-edit-caseloads").is(":checked")) { sendString += "&edit_caseloads=1"; }
                        if ($("#ER-delete-caseloads").is(":checked")) { sendString += "&delete_caseloads=1"; }
                        if ($("#ER-transfer-caseloads").is(":checked")) { sendString += "&transfer_caseloads=1"; }

                        // get salary comparison permissions
                        if ($("#ER-view-salary_comparison-state").is(":checked")) { sendString += "&view_salary_comparison_state=1"; }
                        if ($("#ER-view-salary_comparison-internal-all").is(":checked")) { sendString += "&view_salary_comparison_internal_all=1"; }
                        if ($("#ER-view-salary_comparison-internal-assigned").is(":checked")) { sendString += "&view_salary_comparison_internal_assigned=1"; }
                        if ($("#ER-view-raise_projection").is(":checked")) { sendString += "&view_raise_projection=1"; }

                        // get dashboard tiles permissions
                        if ($("#ER-show-revenues").is(":checked")) { sendString += "&dashboard_show_revenues=1"; }
                        if ($("#ER-show-expenses").is(":checked")) { sendString += "&dashboard_show_expenses=1"; }
                        if ($("#ER-show-net").is(":checked")) { sendString += "&dashboard_show_net=1"; }
                        if ($("#ER-show-employees").is(":checked")) { sendString += "&dashboard_show_employees=1"; }
                        if ($("#ER-show-contract_days").is(":checked")) { sendString += "&dashboard_show_contract_days=1"; }
                        if ($("#ER-show-budget_errors-all").is(":checked")) { sendString += "&dashboard_budget_errors_all=1"; }
                        if ($("#ER-show-budget_errors-assigned").is(":checked")) { sendString += "&dashboard_show_budget_errors_assigned=1"; }
                        if ($("#ER-show-maintenance_mode").is(":checked")) { sendString += "&dashboard_show_maintenance_mode=1"; }
                        if ($("#ER-show-caseloads-all").is(":checked")) { sendString += "&dashboard_show_caseloads_all=1"; }

                        // get contract permissions
                        if ($("#ER-view-service_contracts").is(":checked")) { sendString += "&view_service_contracts=1"; }
                        if ($("#ER-view-quarterly_invoices").is(":checked")) { sendString += "&view_quarterly_invoices=1"; }
                        if ($("#ER-create-service_contracts").is(":checked")) { sendString += "&create_service_contracts=1"; }
                        if ($("#ER-create-quarterly_invoices").is(":checked")) { sendString += "&create_quarterly_invoices=1"; }
                        if ($("#ER-build-service_contracts").is(":checked")) { sendString += "&build_service_contracts=1"; }
                        if ($("#ER-build-quarterly_invoices").is(":checked")) { sendString += "&build_quarterly_invoices=1"; }
                        if ($("#ER-export_invoices").is(":checked")) { sendString += "&export_invoices=1"; }

                        // get reports permissions
                        if ($("#ER-view-misbudgeted_employees-all").is(":checked")) { sendString += "&reports_view_misbudgeted_employees_all=1"; }
                        if ($("#ER-view-misbudgeted_employees-assigned").is(":checked")) { sendString += "&reports_view_misbudgeted_employees_assigned=1"; }
                        if ($("#ER-view-budgeted_inactive-all").is(":checked")) { sendString += "&reports_view_budgeted_inactive_all=1"; }
                        if ($("#ER-view-budgeted_inactive-assigned").is(":checked")) { sendString += "&reports_view_budgeted_inactive_assigned=1"; }
                        if ($("#ER-view-test_employees-all").is(":checked")) { sendString += "&reports_view_test_employees_all=1"; }
                        if ($("#ER-view-test_employees-assigned").is(":checked")) { sendString += "&reports_view_test_employees_assigned=1"; }
                        if ($("#ER-view-salary_projection-all").is(":checked")) { sendString += "&reports_view_salary_projection_all=1"; }
                        if ($("#ER-view-salary_projection-assigned").is(":checked")) { sendString += "&reports_view_salary_projection_assigned=1"; }
                        if ($("#ER-view-employee_changes-all").is(":checked")) { sendString += "&reports_view_employee_changes_all=1"; }
                        if ($("#ER-view-employee_changes-assigned").is(":checked")) { sendString += "&reports_view_employee_changes_assigned=1"; }

                        // send the data to process the add role request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/roles/editRole.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Role Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editRoleModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the modal to edit an existing role */
                    function getEditRoleModal(role_id)
                    {
                        // send the data to create the edit employee modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/roles/getEditRoleModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the edit employee modal
                                document.getElementById("edit-role-modal-div").innerHTML = this.responseText;
                                $("#editRoleModal").modal("show");
                            }
                        };
                        xmlhttp.send("role_id="+role_id);
                    }

                    /** function to get the modal to view the users assigned to the role */
                    function getViewRoleUsersModal(role_id)
                    {
                        // send the data to create the edit employee modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/roles/getViewRoleUsersModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // initialize the view role users table                  
                                $(document).ready(function () {
                                    var role_users = $("#role_users").DataTable({
                                        autoWidth: false,
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        columns: [
                                            { data: "id", orderable: true, width: "10%", className: "text-center" },
                                            { data: "lname", orderable: true, width: "25%", className: "text-center" },
                                            { data: "fname", orderable: true, width: "25%", className: "text-center" },
                                            { data: "email", orderable: true, width: "40%", className: "text-center" },
                                        ],
                                        dom: 'rt',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        order: [
                                            [ 1, "asc" ],
                                            [ 2, "asc" ]
                                        ],
                                        rowCallback: function (row, data, index)
                                        {
                                            // initialize page selection dropdown
                                            updatePageSelection("role_users");
                                        },
                                    });
                                });

                                // display the edit employee modal
                                document.getElementById("view-role_users-modal-div").innerHTML = this.responseText;
                                $("#viewRoleUsersModal").modal("show");
                            }
                        };
                        xmlhttp.send("role_id="+role_id);
                    }
                </script>
            <?php 

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>