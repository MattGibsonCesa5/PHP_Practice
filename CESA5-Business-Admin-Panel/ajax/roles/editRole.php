<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // include additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");
            
            // get parameters from POST
            // role details
            if (isset($_POST["role_id"]) && trim($_POST["role_id"])) { $role_id = trim($_POST["role_id"]); } else { $role_id = null; }
            if (isset($_POST["role_name"]) && trim($_POST["role_name"])) { $role_name = trim($_POST["role_name"]); } else { $role_name = null; }
            // employees
            if (isset($_POST["view_employees_all"]) && is_numeric($_POST["view_employees_all"])) { $view_employees_all = $_POST["view_employees_all"]; } else { $view_employees_all = 0; }
            if (isset($_POST["view_employees_assigned"]) && is_numeric($_POST["view_employees_assigned"])) { $view_employees_assigned = $_POST["view_employees_assigned"]; } else { $view_employees_assigned = 0; }
            if (isset($_POST["add_employees"]) && is_numeric($_POST["add_employees"])) { $add_employees = $_POST["add_employees"]; } else { $add_employees = 0; }
            if (isset($_POST["edit_employees"]) && is_numeric($_POST["edit_employees"])) { $edit_employees = $_POST["edit_employees"]; } else { $edit_employees = 0; }
            if (isset($_POST["delete_employees"]) && is_numeric($_POST["delete_employees"])) { $delete_employees = $_POST["delete_employees"]; } else { $delete_employees = 0; }
            // departments
            if (isset($_POST["view_departments_all"]) && is_numeric($_POST["view_departments_all"])) { $view_departments_all = $_POST["view_departments_all"]; } else { $view_departments_all = 0; }
            if (isset($_POST["view_departments_assigned"]) && is_numeric($_POST["view_departments_assigned"])) { $view_departments_assigned = $_POST["view_departments_assigned"]; } else { $view_departments_assigned = 0; }
            if (isset($_POST["add_departments"]) && is_numeric($_POST["add_departments"])) { $add_departments = $_POST["add_departments"]; } else { $add_departments = 0; }
            if (isset($_POST["edit_departments"]) && is_numeric($_POST["edit_departments"])) { $edit_departments = $_POST["edit_departments"]; } else { $edit_departments = 0; }
            if (isset($_POST["delete_departments"]) && is_numeric($_POST["delete_departments"])) { $delete_departments = $_POST["delete_departments"]; } else { $delete_departments = 0; }
            // project expenses
            if (isset($_POST["view_project_expenses"]) && is_numeric($_POST["view_project_expenses"])) { $view_project_expenses = $_POST["view_project_expenses"]; } else { $view_project_expenses = 0; }
            if (isset($_POST["add_project_expenses"]) && is_numeric($_POST["add_project_expenses"])) { $add_project_expenses = $_POST["add_project_expenses"]; } else { $add_project_expenses = 0; }
            if (isset($_POST["edit_project_expenses"]) && is_numeric($_POST["edit_project_expenses"])) { $edit_project_expenses = $_POST["edit_project_expenses"]; } else { $edit_project_expenses = 0; }
            if (isset($_POST["delete_project_expenses"]) && is_numeric($_POST["delete_project_expenses"])) { $delete_project_expenses = $_POST["delete_project_expenses"]; } else { $delete_project_expenses = 0; }
            // employee expenses
            if (isset($_POST["view_employee_expenses"]) && is_numeric($_POST["view_employee_expenses"])) { $view_employee_expenses = $_POST["view_employee_expenses"]; } else { $view_employee_expenses = 0; }
            if (isset($_POST["edit_employee_expenses"]) && is_numeric($_POST["edit_employee_expenses"])) { $edit_employee_expenses = $_POST["edit_employee_expenses"]; } else { $edit_employee_expenses = 0; }
            // manage services
            if (isset($_POST["view_services_all"]) && is_numeric($_POST["view_services_all"])) { $view_services_all = $_POST["view_services_all"]; } else { $view_services_all = 0; }
            if (isset($_POST["view_services_assigned"]) && is_numeric($_POST["view_services_assigned"])) { $view_services_assigned = $_POST["view_services_assigned"]; } else { $view_services_assigned = 0; }
            if (isset($_POST["add_services"]) && is_numeric($_POST["add_services"])) { $add_services = $_POST["add_services"]; } else { $add_services = 0; }
            if (isset($_POST["edit_services"]) && is_numeric($_POST["edit_services"])) { $edit_services = $_POST["edit_services"]; } else { $edit_services = 0; }
            if (isset($_POST["delete_services"]) && is_numeric($_POST["delete_services"])) { $delete_services = $_POST["delete_services"]; } else { $delete_services = 0; }
            // provide services (invoices)
            if (isset($_POST["view_invoices_all"]) && is_numeric($_POST["view_invoices_all"])) { $view_invoices_all = $_POST["view_invoices_all"]; } else { $view_invoices_all = 0; }
            if (isset($_POST["view_invoices_assigned"]) && is_numeric($_POST["view_invoices_assigned"])) { $view_invoices_assigned = $_POST["view_invoices_assigned"]; } else { $view_invoices_assigned = 0; }
            if (isset($_POST["add_invoices"]) && is_numeric($_POST["add_invoices"])) { $add_invoices = $_POST["add_invoices"]; } else { $add_invoices = 0; }
            if (isset($_POST["edit_invoices"]) && is_numeric($_POST["edit_invoices"])) { $edit_invoices = $_POST["edit_invoices"]; } else { $edit_invoices = 0; }
            if (isset($_POST["delete_invoices"]) && is_numeric($_POST["delete_invoices"])) { $delete_invoices = $_POST["delete_invoices"]; } else { $delete_invoices = 0; }
            // other services
            if (isset($_POST["view_other_services"]) && is_numeric($_POST["view_other_services"])) { $view_other_services = $_POST["view_other_services"]; } else { $view_other_services = 0; }
            if (isset($_POST["add_other_services"]) && is_numeric($_POST["add_other_services"])) { $add_other_services = $_POST["add_other_services"]; } else { $add_other_services = 0; }
            if (isset($_POST["edit_other_services"]) && is_numeric($_POST["edit_other_services"])) { $edit_other_services = $_POST["edit_other_services"]; } else { $edit_other_services = 0; }
            if (isset($_POST["delete_other_services"]) && is_numeric($_POST["delete_other_services"])) { $delete_other_services = $_POST["delete_other_services"]; } else { $delete_other_services = 0; }
            if (isset($_POST["invoice_other_services"]) && is_numeric($_POST["invoice_other_services"])) { $invoice_other_services = $_POST["invoice_other_services"]; } else { $invoice_other_services = 0; }
            // other revenues
            if (isset($_POST["view_revenues_all"]) && is_numeric($_POST["view_revenues_all"])) { $view_revenues_all = $_POST["view_revenues_all"]; } else { $view_revenues_all = 0; }
            if (isset($_POST["view_revenues_assigned"]) && is_numeric($_POST["view_revenues_assigned"])) { $view_revenues_assigned = $_POST["view_revenues_assigned"]; } else { $view_revenues_assigned = 0; }
            if (isset($_POST["add_revenues"]) && is_numeric($_POST["add_revenues"])) { $add_revenues = $_POST["add_revenues"]; } else { $add_revenues = 0; }
            if (isset($_POST["edit_revenues"]) && is_numeric($_POST["edit_revenues"])) { $edit_revenues = $_POST["edit_revenues"]; } else { $edit_revenues = 0; }
            if (isset($_POST["delete_revenues"]) && is_numeric($_POST["delete_revenues"])) { $delete_revenues = $_POST["delete_revenues"]; } else { $delete_revenues = 0; }
            // manage projects
            if (isset($_POST["view_projects_all"]) && is_numeric($_POST["view_projects_all"])) { $view_projects_all = $_POST["view_projects_all"]; } else { $view_projects_all = 0; }
            if (isset($_POST["view_projects_assigned"]) && is_numeric($_POST["view_projects_assigned"])) { $view_projects_assigned = $_POST["view_projects_assigned"]; } else { $view_projects_assigned = 0; }
            if (isset($_POST["add_projects"]) && is_numeric($_POST["add_projects"])) { $add_projects = $_POST["add_projects"]; } else { $add_projects = 0; }
            if (isset($_POST["edit_projects"]) && is_numeric($_POST["edit_projects"])) { $edit_projects = $_POST["edit_projects"]; } else { $edit_projects = 0; }
            if (isset($_POST["delete_projects"]) && is_numeric($_POST["delete_projects"])) { $delete_projects = $_POST["delete_projects"]; } else { $delete_projects = 0; }
            // manage projects
            if (isset($_POST["view_project_budgets_all"]) && is_numeric($_POST["view_project_budgets_all"])) { $view_project_budgets_all = $_POST["view_project_budgets_all"]; } else { $view_project_budgets_all = 0; }
            if (isset($_POST["view_project_budgets_assigned"]) && is_numeric($_POST["view_project_budgets_assigned"])) { $view_project_budgets_assigned = $_POST["view_project_budgets_assigned"]; } else { $view_project_budgets_assigned = 0; }
            if (isset($_POST["budget_projects_all"]) && is_numeric($_POST["budget_projects_all"])) { $budget_projects_all = $_POST["budget_projects_all"]; } else { $budget_projects_all = 0; }
            if (isset($_POST["budget_projects_assigned"]) && is_numeric($_POST["budget_projects_assigned"])) { $budget_projects_assigned = $_POST["budget_projects_assigned"]; } else { $budget_projects_assigned = 0; }
            // manage customers
            if (isset($_POST["view_customers"]) && is_numeric($_POST["view_customers"])) { $view_customers = $_POST["view_customers"]; } else { $view_customers = 0; }
            if (isset($_POST["add_customers"]) && is_numeric($_POST["add_customers"])) { $add_customers = $_POST["add_customers"]; } else { $add_customers = 0; }
            if (isset($_POST["edit_customers"]) && is_numeric($_POST["edit_customers"])) { $edit_customers = $_POST["edit_customers"]; } else { $edit_customers = 0; }
            if (isset($_POST["delete_customers"]) && is_numeric($_POST["delete_customers"])) { $delete_customers = $_POST["delete_customers"]; } else { $delete_customers = 0; }
            // customer groups
            if (isset($_POST["view_customer_groups"]) && is_numeric($_POST["view_customer_groups"])) { $view_customer_groups = $_POST["view_customer_groups"]; } else { $view_customer_groups = 0; }
            if (isset($_POST["add_customer_groups"]) && is_numeric($_POST["add_customer_groups"])) { $add_customer_groups = $_POST["add_customer_groups"]; } else { $add_customer_groups = 0; }
            if (isset($_POST["edit_customer_groups"]) && is_numeric($_POST["edit_customer_groups"])) { $edit_customer_groups = $_POST["edit_customer_groups"]; } else { $edit_customer_groups = 0; }
            if (isset($_POST["delete_customer_groups"]) && is_numeric($_POST["delete_customer_groups"])) { $delete_customer_groups = $_POST["delete_customer_groups"]; } else { $delete_customer_groups = 0; }
            // caseloads - students
            if (isset($_POST["view_students_all"]) && is_numeric($_POST["view_students_all"])) { $view_students_all = $_POST["view_students_all"]; } else { $view_students_all = 0; }
            if (isset($_POST["view_students_assigned"]) && is_numeric($_POST["view_students_assigned"])) { $view_students_assigned = $_POST["view_students_assigned"]; } else { $view_students_assigned = 0; }
            if (isset($_POST["add_students"]) && is_numeric($_POST["add_students"])) { $add_students = $_POST["add_students"]; } else { $add_students = 0; }
            if (isset($_POST["edit_students"]) && is_numeric($_POST["edit_students"])) { $edit_students = $_POST["edit_students"]; } else { $edit_students = 0; }
            if (isset($_POST["delete_students"]) && is_numeric($_POST["delete_students"])) { $delete_students = $_POST["delete_students"]; } else { $delete_students = 0; }
            // caseloads - students
            if (isset($_POST["view_therapists"]) && is_numeric($_POST["view_therapists"])) { $view_therapists = $_POST["view_therapists"]; } else { $view_therapists = 0; }
            if (isset($_POST["add_therapists"]) && is_numeric($_POST["add_therapists"])) { $add_therapists = $_POST["add_therapists"]; } else { $add_therapists = 0; }
            if (isset($_POST["remove_therapists"]) && is_numeric($_POST["remove_therapists"])) { $remove_therapists = $_POST["remove_therapists"]; } else { $remove_therapists = 0; }
            // caseloads - manage
            if (isset($_POST["view_caseloads_all"]) && is_numeric($_POST["view_caseloads_all"])) { $view_caseloads_all = $_POST["view_caseloads_all"]; } else { $view_caseloads_all = 0; }
            if (isset($_POST["view_caseloads_assigned"]) && is_numeric($_POST["view_caseloads_assigned"])) { $view_caseloads_assigned = $_POST["view_caseloads_assigned"]; } else { $view_caseloads_assigned = 0; }
            if (isset($_POST["add_caseloads"]) && is_numeric($_POST["add_caseloads"])) { $add_caseloads = $_POST["add_caseloads"]; } else { $add_caseloads = 0; }
            if (isset($_POST["edit_caseloads"]) && is_numeric($_POST["edit_caseloads"])) { $edit_caseloads = $_POST["edit_caseloads"]; } else { $edit_caseloads = 0; }
            if (isset($_POST["delete_caseloads"]) && is_numeric($_POST["delete_caseloads"])) { $delete_caseloads = $_POST["delete_caseloads"]; } else { $delete_caseloads = 0; }
            if (isset($_POST["transfer_caseloads"]) && is_numeric($_POST["transfer_caseloads"])) { $transfer_caseloads = $_POST["transfer_caseloads"]; } else { $transfer_caseloads = 0; }
            // salary comparison
            if (isset($_POST["view_salary_comparison_state"]) && is_numeric($_POST["view_salary_comparison_state"])) { $view_salary_comparison_state = $_POST["view_salary_comparison_state"]; } else { $view_salary_comparison_state = 0; }
            if (isset($_POST["view_salary_comparison_internal_all"]) && is_numeric($_POST["view_salary_comparison_internal_all"])) { $view_salary_comparison_internal_all = $_POST["view_salary_comparison_internal_all"]; } else { $view_salary_comparison_internal_all = 0; }
            if (isset($_POST["view_salary_comparison_internal_assigned"]) && is_numeric($_POST["view_salary_comparison_internal_assigned"])) { $view_salary_comparison_internal_assigned = $_POST["view_salary_comparison_internal_assigned"]; } else { $view_salary_comparison_internal_assigned = 0; }
            if (isset($_POST["view_raise_projection"]) && is_numeric($_POST["view_raise_projection"])) { $view_raise_projection = $_POST["view_raise_projection"]; } else { $view_raise_projection = 0; }
            // dashboard tiles
            if (isset($_POST["dashboard_show_revenues"]) && is_numeric($_POST["dashboard_show_revenues"])) { $dashboard_show_revenues = $_POST["dashboard_show_revenues"]; } else { $dashboard_show_revenues = 0; }
            if (isset($_POST["dashboard_show_expenses"]) && is_numeric($_POST["dashboard_show_expenses"])) { $dashboard_show_expenses = $_POST["dashboard_show_expenses"]; } else { $dashboard_show_expenses = 0; }
            if (isset($_POST["dashboard_show_net"]) && is_numeric($_POST["dashboard_show_net"])) { $dashboard_show_net = $_POST["dashboard_show_net"]; } else { $dashboard_show_net = 0; }
            if (isset($_POST["dashboard_show_employees"]) && is_numeric($_POST["dashboard_show_employees"])) { $dashboard_show_employees = $_POST["dashboard_show_employees"]; } else { $dashboard_show_employees = 0; }
            if (isset($_POST["dashboard_show_contract_days"]) && is_numeric($_POST["dashboard_show_contract_days"])) { $dashboard_show_contract_days = $_POST["dashboard_show_contract_days"]; } else { $dashboard_show_contract_days = 0; }
            if (isset($_POST["dashboard_show_budget_errors_all"]) && is_numeric($_POST["dashboard_show_budget_errors_all"])) { $dashboard_show_budget_errors_all = $_POST["dashboard_show_budget_errors_all"]; } else { $dashboard_show_budget_errors_all = 0; }
            if (isset($_POST["dashboard_show_budget_errors_assigned"]) && is_numeric($_POST["dashboard_show_budget_errors_assigned"])) { $dashboard_show_budget_errors_assigned = $_POST["dashboard_show_budget_errors_assigned"]; } else { $dashboard_show_budget_errors_assigned = 0; }
            if (isset($_POST["dashboard_show_maintenance_mode"]) && is_numeric($_POST["dashboard_show_maintenance_mode"])) { $dashboard_show_maintenance_mode = $_POST["dashboard_show_maintenance_mode"]; } else { $dashboard_show_maintenance_mode = 0; }
            if (isset($_POST["dashboard_show_caseloads_all"]) && is_numeric($_POST["dashboard_show_caseloads_all"])) { $dashboard_show_caseloads_all = $_POST["dashboard_show_caseloads_all"]; } else { $dashboard_show_caseloads_all = 0; }
            // contracts
            if (isset($_POST["view_service_contracts"]) && is_numeric($_POST["view_service_contracts"])) { $view_service_contracts = $_POST["view_service_contracts"]; } else { $view_service_contracts = 0; }
            if (isset($_POST["view_quarterly_invoices"]) && is_numeric($_POST["view_quarterly_invoices"])) { $view_quarterly_invoices = $_POST["view_quarterly_invoices"]; } else { $view_quarterly_invoices = 0; }
            if (isset($_POST["create_service_contracts"]) && is_numeric($_POST["create_service_contracts"])) { $create_service_contracts = $_POST["create_service_contracts"]; } else { $create_service_contracts = 0; }
            if (isset($_POST["create_quarterly_invoices"]) && is_numeric($_POST["create_quarterly_invoices"])) { $create_quarterly_invoices = $_POST["create_quarterly_invoices"]; } else { $create_quarterly_invoices = 0; }
            if (isset($_POST["build_service_contracts"]) && is_numeric($_POST["build_service_contracts"])) { $build_service_contracts = $_POST["build_service_contracts"]; } else { $build_service_contracts = 0; }
            if (isset($_POST["build_quarterly_invoices"]) && is_numeric($_POST["build_quarterly_invoices"])) { $build_quarterly_invoices = $_POST["build_quarterly_invoices"]; } else { $build_quarterly_invoices = 0; }
            if (isset($_POST["export_invoices"]) && is_numeric($_POST["export_invoices"])) { $export_invoices = $_POST["export_invoices"]; } else { $export_invoices = 0; }
            // reports
            if (isset($_POST["reports_view_misbudgeted_employees_all"]) && is_numeric($_POST["reports_view_misbudgeted_employees_all"])) { $reports_view_misbudgeted_employees_all = $_POST["reports_view_misbudgeted_employees_all"]; } else { $reports_view_misbudgeted_employees_all = 0; }
            if (isset($_POST["reports_view_misbudgeted_employees_assigned"]) && is_numeric($_POST["reports_view_misbudgeted_employees_assigned"])) { $reports_view_misbudgeted_employees_assigned = $_POST["reports_view_misbudgeted_employees_assigned"]; } else { $reports_view_misbudgeted_employees_assigned = 0; }
            if (isset($_POST["reports_view_budgeted_inactive_all"]) && is_numeric($_POST["reports_view_budgeted_inactive_all"])) { $reports_view_budgeted_inactive_all = $_POST["reports_view_budgeted_inactive_all"]; } else { $reports_view_budgeted_inactive_all = 0; }
            if (isset($_POST["reports_view_budgeted_inactive_assigned"]) && is_numeric($_POST["reports_view_budgeted_inactive_assigned"])) { $reports_view_budgeted_inactive_assigned = $_POST["reports_view_budgeted_inactive_assigned"]; } else { $reports_view_budgeted_inactive_assigned = 0; }
            if (isset($_POST["reports_view_test_employees_all"]) && is_numeric($_POST["reports_view_test_employees_all"])) { $reports_view_test_employees_all = $_POST["reports_view_test_employees_all"]; } else { $reports_view_test_employees_all = 0; }
            if (isset($_POST["reports_view_test_employees_assigned"]) && is_numeric($_POST["reports_view_test_employees_assigned"])) { $reports_view_test_employees_assigned = $_POST["reports_view_test_employees_assigned"]; } else { $reports_view_test_employees_assigned = 0; }
            if (isset($_POST["reports_view_salary_projection_all"]) && is_numeric($_POST["reports_view_salary_projection_all"])) { $reports_view_salary_projection_all = $_POST["reports_view_salary_projection_all"]; } else { $reports_view_salary_projection_all = 0; }
            if (isset($_POST["reports_view_salary_projection_assigned"]) && is_numeric($_POST["reports_view_salary_projection_assigned"])) { $reports_view_salary_projection_assigned = $_POST["reports_view_salary_projection_assigned"]; } else { $reports_view_salary_projection_assigned = 0; }
            if (isset($_POST["reports_view_employee_changes_all"]) && is_numeric($_POST["reports_view_employee_changes_all"])) { $reports_view_employee_changes_all = $_POST["reports_view_employee_changes_all"]; } else { $reports_view_employee_changes_all = 0; }
            if (isset($_POST["reports_view_employee_changes_assigned"]) && is_numeric($_POST["reports_view_employee_changes_assigned"])) { $reports_view_employee_changes_assigned = $_POST["reports_view_employee_changes_assigned"]; } else { $reports_view_employee_changes_assigned = 0; }

            if ($role_id != null)
            {
                if ($role_name != null && trim($role_name) <> "")
                {
                    // connect to the database
                    $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                    // attempt to create the new permissions group
                    try
                    {
                        // verify the role exists before attempting to edit the role
                        if (verifyRole($conn, $role_id))
                        {
                            // only allow edits to the role if it was not generated by default
                            if (!isRoleDefaultGenerated($conn, $role_id))
                            {
                                $editRole = mysqli_prepare($conn, "UPDATE roles SET name=? WHERE id=?");
                                mysqli_stmt_bind_param($editRole, "si", $role_name, $role_id);
                                if (mysqli_stmt_execute($editRole)) // successfully edited the role; set role permissions
                                {
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Employees
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_employees_all == 1) { setPermission($conn, $role_id, "VIEW_EMPLOYEES_ALL"); } else { removePermission($conn, $role_id, "VIEW_EMPLOYEES_ALL"); }
                                    if ($view_employees_assigned == 1 && $view_employees_all != 1) { setPermission($conn, $role_id, "VIEW_EMPLOYEES_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_EMPLOYEES_ASSIGNED"); }
                                    if ($add_employees == 1) { setPermission($conn, $role_id, "ADD_EMPLOYEES"); } else { removePermission($conn, $role_id, "ADD_EMPLOYEES"); }
                                    if ($edit_employees  == 1) { setPermission($conn, $role_id, "EDIT_EMPLOYEES"); } else { removePermission($conn, $role_id, "EDIT_EMPLOYEES"); }
                                    if ($delete_employees == 1) { setPermission($conn, $role_id, "DELETE_EMPLOYEES"); } else { removePermission($conn, $role_id, "DELETE_EMPLOYEES"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Departments
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_departments_all == 1) { setPermission($conn, $role_id, "VIEW_DEPARTMENTS_ALL"); } else { removePermission($conn, $role_id, "VIEW_DEPARTMENTS_ALL"); }
                                    if ($view_departments_assigned == 1 && $view_departments_all != 1) { setPermission($conn, $role_id, "VIEW_DEPARTMENTS_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_DEPARTMENTS_ASSIGNED"); }
                                    if ($add_departments == 1) { setPermission($conn, $role_id, "ADD_DEPARTMENTS"); } else { removePermission($conn, $role_id, "ADD_DEPARTMENTS"); }
                                    if ($edit_departments  == 1) { setPermission($conn, $role_id, "EDIT_DEPARTMENTS"); } else { removePermission($conn, $role_id, "EDIT_DEPARTMENTS"); }
                                    if ($delete_departments == 1) { setPermission($conn, $role_id, "DELETE_DEPARTMENTS"); } else { removePermission($conn, $role_id, "DELETE_DEPARTMENTS"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Project Expenses
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_project_expenses == 1) { setPermission($conn, $role_id, "VIEW_PROJECT_EXPENSES"); } else { removePermission($conn, $role_id, "VIEW_PROJECT_EXPENSES"); }
                                    if ($add_project_expenses == 1) { setPermission($conn, $role_id, "ADD_PROJECT_EXPENSES"); } else { removePermission($conn, $role_id, "ADD_PROJECT_EXPENSES"); }
                                    if ($edit_project_expenses  == 1) { setPermission($conn, $role_id, "EDIT_PROJECT_EXPENSES"); } else { removePermission($conn, $role_id, "EDIT_PROJECT_EXPENSES"); }
                                    if ($delete_project_expenses == 1) { setPermission($conn, $role_id, "DELETE_PROJECT_EXPENSES"); } else { removePermission($conn, $role_id, "DELETE_PROJECT_EXPENSES"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Employee Expenses
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_employee_expenses == 1) { setPermission($conn, $role_id, "VIEW_EMPLOYEE_EXPENSES"); } else { removePermission($conn, $role_id, "VIEW_EMPLOYEE_EXPENSES"); }
                                    if ($edit_employee_expenses == 1) { setPermission($conn, $role_id, "EDIT_EMPLOYEE_EXPENSES"); } else { removePermission($conn, $role_id, "EDIT_EMPLOYEE_EXPENSES"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Manage Services
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_services_all == 1) { setPermission($conn, $role_id, "VIEW_SERVICES_ALL"); } else { removePermission($conn, $role_id, "VIEW_SERVICES_ALL"); }
                                    if ($view_services_assigned == 1 && $view_services_all != 1) { setPermission($conn, $role_id, "VIEW_SERVICES_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_SERVICES_ASSIGNED"); }
                                    if ($add_services == 1) { setPermission($conn, $role_id, "ADD_SERVICES"); } else { removePermission($conn, $role_id, "ADD_SERVICES"); }
                                    if ($edit_services  == 1) { setPermission($conn, $role_id, "EDIT_SERVICES"); } else { removePermission($conn, $role_id, "EDIT_SERVICES"); }
                                    if ($delete_services == 1) { setPermission($conn, $role_id, "DELETE_SERVICES"); } else { removePermission($conn, $role_id, "DELETE_SERVICES"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Provide Services (Invoices)
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_invoices_all == 1) { setPermission($conn, $role_id, "VIEW_INVOICES_ALL"); } else { removePermission($conn, $role_id, "VIEW_INVOICES_ALL"); }
                                    if ($view_invoices_assigned == 1 && $view_invoices_all != 1) { setPermission($conn, $role_id, "VIEW_INVOICES_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_INVOICES_ASSIGNED"); }
                                    if ($add_invoices == 1) { setPermission($conn, $role_id, "ADD_INVOICES"); } else { removePermission($conn, $role_id, "ADD_INVOICES"); }
                                    if ($edit_invoices  == 1) { setPermission($conn, $role_id, "EDIT_INVOICES"); } else { removePermission($conn, $role_id, "EDIT_INVOICES"); }
                                    if ($delete_invoices == 1) { setPermission($conn, $role_id, "DELETE_INVOICES"); } else { removePermission($conn, $role_id, "DELETE_INVOICES"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Other Services
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_other_services == 1) { setPermission($conn, $role_id, "VIEW_OTHER_SERVICES"); } else { removePermission($conn, $role_id, "VIEW_OTHER_SERVICES"); }
                                    if ($add_other_services == 1) { setPermission($conn, $role_id, "ADD_OTHER_SERVICES"); } else { removePermission($conn, $role_id, "ADD_OTHER_SERVICES"); }
                                    if ($edit_other_services == 1) { setPermission($conn, $role_id, "EDIT_OTHER_SERVICES"); } else { removePermission($conn, $role_id, "EDIT_OTHER_SERVICES"); }
                                    if ($delete_other_services == 1) { setPermission($conn, $role_id, "DELETE_OTHER_SERVICES"); } else { removePermission($conn, $role_id, "DELETE_OTHER_SERVICES"); }
                                    if ($invoice_other_services == 1) { setPermission($conn, $role_id, "INVOICE_OTHER_SERVICES"); } else { removePermission($conn, $role_id, "INVOICE_OTHER_SERVICES"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Other Revenues
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_revenues_all == 1) { setPermission($conn, $role_id, "VIEW_REVENUES_ALL"); } else { removePermission($conn, $role_id, "VIEW_REVENUES_ALL"); }
                                    if ($view_revenues_assigned == 1 && $view_revenues_all != 1) { setPermission($conn, $role_id, "VIEW_REVENUES_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_REVENUES_ASSIGNED"); }
                                    if ($add_revenues == 1) { setPermission($conn, $role_id, "ADD_REVENUES"); } else { removePermission($conn, $role_id, "ADD_REVENUES"); }
                                    if ($edit_revenues  == 1) { setPermission($conn, $role_id, "EDIT_REVENUES"); } else { removePermission($conn, $role_id, "EDIT_REVENUES"); }
                                    if ($delete_revenues == 1) { setPermission($conn, $role_id, "DELETE_REVENUES"); } else { removePermission($conn, $role_id, "DELETE_REVENUES"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Manage Projects
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_projects_all == 1) { setPermission($conn, $role_id, "VIEW_PROJECTS_ALL"); } else { removePermission($conn, $role_id, "VIEW_PROJECTS_ALL"); }
                                    if ($view_projects_assigned == 1 && $view_projects_all != 1) { setPermission($conn, $role_id, "VIEW_PROJECTS_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_PROJECTS_ASSIGNED"); }
                                    if ($add_projects == 1) { setPermission($conn, $role_id, "ADD_PROJECTS"); } else { removePermission($conn, $role_id, "ADD_PROJECTS"); }
                                    if ($edit_projects  == 1) { setPermission($conn, $role_id, "EDIT_PROJECTS"); } else { removePermission($conn, $role_id, "EDIT_PROJECTS"); }
                                    if ($delete_projects == 1) { setPermission($conn, $role_id, "DELETE_PROJECTS"); } else { removePermission($conn, $role_id, "DELETE_PROJECTS"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Budget Projects
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_project_budgets_all == 1) { setPermission($conn, $role_id, "VIEW_PROJECT_BUDGETS_ALL"); } else { removePermission($conn, $role_id, "VIEW_PROJECT_BUDGETS_ALL"); }
                                    if ($view_project_budgets_assigned == 1 && $view_project_budgets_all != 1) { setPermission($conn, $role_id, "VIEW_PROJECT_BUDGETS_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_PROJECT_BUDGETS_ASSIGNED"); }
                                    if ($budget_projects_all == 1) { setPermission($conn, $role_id, "BUDGET_PROJECTS_ALL"); } else { removePermission($conn, $role_id, "BUDGET_PROJECTS_ALL"); }
                                    if ($budget_projects_assigned == 1) { setPermission($conn, $role_id, "BUDGET_PROJECTS_ASSIGNED"); } else { removePermission($conn, $role_id, "BUDGET_PROJECTS_ASSIGNED"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Manage Customers
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_customers == 1) { setPermission($conn, $role_id, "VIEW_CUSTOMERS"); } else { removePermission($conn, $role_id, "VIEW_CUSTOMERS"); }
                                    if ($add_customers == 1) { setPermission($conn, $role_id, "ADD_CUSTOMERS"); } else { removePermission($conn, $role_id, "ADD_CUSTOMERS"); }
                                    if ($edit_customers  == 1) { setPermission($conn, $role_id, "EDIT_CUSTOMERS"); } else { removePermission($conn, $role_id, "EDIT_CUSTOMERS"); }
                                    if ($delete_customers == 1) { setPermission($conn, $role_id, "DELETE_CUSTOMERS"); } else { removePermission($conn, $role_id, "DELETE_CUSTOMERS"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Customer Groups
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_customer_groups == 1) { setPermission($conn, $role_id, "VIEW_CUSTOMER_GROUPS"); } else { removePermission($conn, $role_id, "VIEW_CUSTOMER_GROUPS"); }
                                    if ($add_customer_groups == 1) { setPermission($conn, $role_id, "ADD_CUSTOMER_GROUPS"); } else { removePermission($conn, $role_id, "ADD_CUSTOMER_GROUPS"); }
                                    if ($edit_customer_groups  == 1) { setPermission($conn, $role_id, "EDIT_CUSTOMER_GROUPS"); } else { removePermission($conn, $role_id, "EDIT_CUSTOMER_GROUPS"); }
                                    if ($delete_customer_groups == 1) { setPermission($conn, $role_id, "DELETE_CUSTOMER_GROUPS"); } else { removePermission($conn, $role_id, "DELETE_CUSTOMER_GROUPS"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Caseloads - Students
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_students_all == 1) { setPermission($conn, $role_id, "VIEW_STUDENTS_ALL"); } else { removePermission($conn, $role_id, "VIEW_STUDENTS_ALL"); }
                                    if ($view_students_assigned == 1 && $view_students_all != 1) { setPermission($conn, $role_id, "VIEW_STUDENTS_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_STUDENTS_ASSIGNED"); }
                                    if ($add_students == 1) { setPermission($conn, $role_id, "ADD_STUDENTS"); } else { removePermission($conn, $role_id, "ADD_STUDENTS"); }
                                    if ($edit_students  == 1) { setPermission($conn, $role_id, "EDIT_STUDENTS"); } else { removePermission($conn, $role_id, "EDIT_STUDENTS"); }
                                    if ($delete_students == 1) { setPermission($conn, $role_id, "DELETE_STUDENTS"); } else { removePermission($conn, $role_id, "DELETE_STUDENTS"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Caseloads - Therapists
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_therapists == 1) { setPermission($conn, $role_id, "VIEW_THERAPISTS"); } else { removePermission($conn, $role_id, "VIEW_THERAPISTS"); }
                                    if ($add_therapists == 1) { setPermission($conn, $role_id, "ADD_THERAPISTS"); } else { removePermission($conn, $role_id, "ADD_THERAPISTS"); }
                                    if ($remove_therapists == 1) { setPermission($conn, $role_id, "REMOVE_THERAPISTS"); } else { removePermission($conn, $role_id, "REMOVE_THERAPISTS"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Caseloads - Managements
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_caseloads_all == 1) { setPermission($conn, $role_id, "VIEW_CASELOADS_ALL"); } else { removePermission($conn, $role_id, "VIEW_CASELOADS_ALL"); }
                                    if ($view_caseloads_assigned == 1 && $view_caseloads_all != 1) { setPermission($conn, $role_id, "VIEW_CASELOADS_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_CASELOADS_ASSIGNED"); }
                                    if ($add_caseloads == 1) { setPermission($conn, $role_id, "ADD_CASELOADS"); } else { removePermission($conn, $role_id, "ADD_CASELOADS"); }
                                    if ($edit_caseloads  == 1) { setPermission($conn, $role_id, "EDIT_CASELOADS"); } else { removePermission($conn, $role_id, "EDIT_CASELOADS"); }
                                    if ($delete_caseloads == 1) { setPermission($conn, $role_id, "DELETE_CASELOADS"); } else { removePermission($conn, $role_id, "DELETE_CASELOADS"); }
                                    if ($transfer_caseloads == 1) { setPermission($conn, $role_id, "TRANSFER_CASELOADS"); } else { removePermission($conn, $role_id, "TRANSFER_CASELOADS"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Salary Comparison
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_salary_comparison_state == 1) { setPermission($conn, $role_id, "VIEW_SALARY_COMPARISON_STATE"); } else { removePermission($conn, $role_id, "VIEW_SALARY_COMPARISON_STATE"); }
                                    if ($view_salary_comparison_internal_all == 1) { setPermission($conn, $role_id, "VIEW_SALARY_COMPARISON_INTERNAL_ALL"); } else { removePermission($conn, $role_id, "VIEW_SALARY_COMPARISON_INTERNAL_ALL"); }
                                    if ($view_salary_comparison_internal_assigned == 1) { setPermission($conn, $role_id, "VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"); }
                                    if ($view_raise_projection == 1) { setPermission($conn, $role_id, "VIEW_RAISE_PROJECTION"); } else { removePermission($conn, $role_id, "VIEW_RAISE_PROJECTION"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Dashboard Tiles
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($dashboard_show_revenues == 1) { setPermission($conn, $role_id, "DASHBOARD_SHOW_REVENUES_TILE"); } else { removePermission($conn, $role_id, "DASHBOARD_SHOW_REVENUES_TILE"); }
                                    if ($dashboard_show_expenses == 1) { setPermission($conn, $role_id, "DASHBOARD_SHOW_EXPENSES_TILE"); } else { removePermission($conn, $role_id, "DASHBOARD_SHOW_EXPENSES_TILE"); }
                                    if ($dashboard_show_net == 1) { setPermission($conn, $role_id, "DASHBOARD_SHOW_NET_TILE"); } else { removePermission($conn, $role_id, "DASHBOARD_SHOW_NET_TILE"); }
                                    if ($dashboard_show_employees == 1) { setPermission($conn, $role_id, "DASHBOARD_SHOW_EMPLOYEES_TILE "); } else { removePermission($conn, $role_id, "DASHBOARD_SHOW_EMPLOYEES_TILE "); }
                                    if ($dashboard_show_contract_days == 1) { setPermission($conn, $role_id, "DASHBOARD_SHOW_CONTRACT_DAYS_TILE"); } else { removePermission($conn, $role_id, "DASHBOARD_SHOW_CONTRACT_DAYS_TILE"); }
                                    if ($dashboard_show_budget_errors_all == 1) { setPermission($conn, $role_id, "DASHBOARD_SHOW_BUDGET_ERRORS_ALL_TILE"); } else { removePermission($conn, $role_id, "DASHBOARD_SHOW_BUDGET_ERRORS_ALL_TILE"); }
                                    if ($dashboard_show_budget_errors_assigned == 1) { setPermission($conn, $role_id, "DASHBOARD_SHOW_BUDGET_ERRORS_ASSIGNED_TILE"); } else { removePermission($conn, $role_id, "DASHBOARD_SHOW_BUDGET_ERRORS_ASSIGNED_TILE"); }
                                    if ($dashboard_show_maintenance_mode == 1) { setPermission($conn, $role_id, "DASHBOARD_SHOW_MAINTENANCE_MODE_TILE"); } else { removePermission($conn, $role_id, "DASHBOARD_SHOW_MAINTENANCE_MODE_TILE"); }
                                    if ($dashboard_show_caseloads_all == 1) { setPermission($conn, $role_id, "DASHBOARD_SHOW_CASELOADS_ALL_TILE"); } else { removePermission($conn, $role_id, "DASHBOARD_SHOW_CASELOADS_ALL_TILE"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Contracts
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($view_service_contracts == 1) { setPermission($conn, $role_id, "VIEW_SERVICE_CONTRACTS"); } else { removePermission($conn, $role_id, "VIEW_SERVICE_CONTRACTS"); }
                                    if ($view_quarterly_invoices == 1) { setPermission($conn, $role_id, "VIEW_QUARTERLY_INVOICES"); } else { removePermission($conn, $role_id, "VIEW_QUARTERLY_INVOICES"); }
                                    if ($create_service_contracts == 1) { setPermission($conn, $role_id, "CREATE_SERVICE_CONTRACTS"); } else { removePermission($conn, $role_id, "CREATE_SERVICE_CONTRACTS"); }
                                    if ($create_quarterly_invoices == 1) { setPermission($conn, $role_id, "CREATE_QUARTERLY_INVOICES"); } else { removePermission($conn, $role_id, "CREATE_QUARTERLY_INVOICES"); }
                                    if ($build_service_contracts == 1) { setPermission($conn, $role_id, "BUILD_SERVICE_CONTRACTS"); } else { removePermission($conn, $role_id, "BUILD_SERVICE_CONTRACTS"); }
                                    if ($build_quarterly_invoices == 1) { setPermission($conn, $role_id, "BUILD_QUARTERLY_INVOICES"); } else { removePermission($conn, $role_id, "BUILD_QUARTERLY_INVOICES"); }
                                    if ($export_invoices == 1) { setPermission($conn, $role_id, "EXPORT_INVOICES"); } else { removePermission($conn, $role_id, "EXPORT_INVOICES"); }

                                    ///////////////////////////////////////////////////////////////////////////////////
                                    //
                                    //  Reports
                                    //
                                    ///////////////////////////////////////////////////////////////////////////////////
                                    if ($reports_view_misbudgeted_employees_all == 1) { setPermission($conn, $role_id, "VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL"); } else { removePermission($conn, $role_id, "VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL"); }
                                    if ($reports_view_misbudgeted_employees_assigned == 1 && $reports_view_misbudgeted_employees_all != 1) { setPermission($conn, $role_id, "VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"); }
                                    if ($reports_view_budgeted_inactive_all == 1) { setPermission($conn, $role_id, "VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL"); } else { removePermission($conn, $role_id, "VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL"); }
                                    if ($reports_view_test_employees_assigned == 1 && $reports_view_budgeted_inactive_all != 1) { setPermission($conn, $role_id, "VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED"); }
                                    if ($reports_view_test_employees_all == 1) { setPermission($conn, $role_id, "VIEW_REPORT_TEST_EMPLOYEES_ALL"); } else { removePermission($conn, $role_id, "VIEW_REPORT_TEST_EMPLOYEES_ALL"); }
                                    if ($reports_view_budgeted_inactive_assigned == 1 && $reports_view_test_employees_all != 1) { setPermission($conn, $role_id, "VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"); }
                                    if ($reports_view_salary_projection_all == 1) { setPermission($conn, $role_id, "VIEW_REPORT_SALARY_PROJECTION_ALL"); } else { removePermission($conn, $role_id, "VIEW_REPORT_SALARY_PROJECTION_ALL"); }
                                    if ($reports_view_salary_projection_assigned == 1 && $reports_view_salary_projection_all != 1) { setPermission($conn, $role_id, "VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"); }
                                    if ($reports_view_employee_changes_all == 1) { setPermission($conn, $role_id, "VIEW_REPORT_EMPLOYEE_CHANGES_ALL"); } else { removePermission($conn, $role_id, "VIEW_REPORT_EMPLOYEE_CHANGES_ALL"); }
                                    if ($reports_view_employee_changes_assigned == 1 && $reports_view_employee_changes_all != 1) { setPermission($conn, $role_id, "VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"); } else { removePermission($conn, $role_id, "VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"); }
                                
                                    echo "<span class=\"log-success\">Successfully</span> edited the role.<br>";
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the role. An unexpected error has occurred! Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the role. The selected role was generated by default and cannot be modified!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the role. The selected role does not exist!?<br>"; }
                    }
                    catch (Exception $e)
                    {
                        echo "<span class=\"log-fail\">Failed</span> to edit the role. An unexpected error has occurred! Please try again later.<br>";
                    }

                    // disconnect from the database
                    mysqli_close($conn);
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the role. You must provide the role with a name!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the role. An unexpected error has occurred! Please try again later.<br>"; }
        }
    }
?>