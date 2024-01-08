<?php 
    include_once("header.php");

    if ((isset($_SESSION["status"]) && $_SESSION["status"] == 1) && isset($_SESSION["role"])) 
    { 
        ?>
            <!-- Page Specific Styling -->
            <style>
                .accordion-header, .accordion-button
                {
                    font-size: 20px !important;
                    font-weight: 500 !important;
                }

                <?php if (isset($USER_SETTINGS) && $USER_SETTINGS["dark_mode"] == 1) { ?>
                    .accordion-header, .accordion-button, .accordion-item
                    {
                        background-color: #1c1c1c !important;
                        color: #ffffff !important;
                    }
                <?php } ?>
            </style>

            <div class="container-fluid">
                <div class="container-xxl guide-container my-3 p-0">
                    <div class="guide-header p-2">
                        <h1 class="m-0">Guide & Documentation</h1>
                    </div>

                    <div class="accordion accordion-flush" id="accordionFlushExample">
                        <?php if ($_SESSION["role"] == 1 || $_SESSION["role"] == 2) { ?>
                            <!-- Employees -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="flush-headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                                        Employees
                                    </button>
                                </h2>
                                <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        <!-- Employees Sub-Accordion -->
                                        <div class="accordion accordion-flush" id="employeesSubAccordion">
                                            <!-- Employees List -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="employees-subflush-headingOne">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#employees-subflush-collapseOne" aria-expanded="false" aria-controls="employees-subflush-collapseOne">
                                                        Employees List
                                                    </button>
                                                </h3>
                                                <div id="employees-subflush-collapseOne" class="accordion-collapse collapse" aria-labelledby="employees-subflush-headingOne" data-bs-parent="#employeesSubAccordion">
                                                    <div class="accordion-body">
                                                        <?php if ($_SESSION["role"] == 1) { ?>
                                                            <h4>The Basics</h4>
                                                            <p>
                                                                The <a class="template-link" href="employees_list" target="_blank">Employees List</a> is the place to view and manage all employees. From the list page, as an admin, you can:
                                                                <ul>
                                                                    <li>Add Employees</li>
                                                                    <li>Edit Employees</li>
                                                                    <li>Delete Employees</li>
                                                                    <li>Upload Employees</li>
                                                                </ul>
                                                            </p>

                                                            <h4>Managing Employees</h4>
                                                            <p>
                                                                When adding and editing employees, you must fill out all of the required fields. The employee ID must be a unique identifier that no one else has within the system.
                                                            </p>

                                                            <p>
                                                                <b>Global employees</b> are employees who any director will be able to have access to, even if they are not within any of the director's departments. You can
                                                                set a global employee by selecting the "Global Employee" option.
                                                            </p>

                                                            <p>
                                                                Setting an employee's status is a crucial aspect within the BAP. Although, inactive employees may still be in some reports and calculations, there are other spots where
                                                                these employees will be inaccessible. Inactive employees will also be restricted from accessing the BAP. We <b>strongly encourage</b> admins to set an employee to inactive
                                                                rather than deleting the employee. This will maintain the employee's historical data and records.
                                                            </p>

                                                            <p>
                                                                As an admin, you can easily add a single of year of experience to all employees by clicking the "Add Experience" button in the header. You'll be asked which
                                                                period to add a year to. There is no way to revert this add, so proceed with caution.
                                                            </p>

                                                            <h4>Uploading Employees</h4>
                                                            <p>
                                                                To quickly add many employees, or even mass update employees, we recommend using our employee upload following the correct <a class="template-link" href="https://docs.google.com/spreadsheets/d/1wnwv8QqX0cExA4zl5zS1EG7-QIYuFQm8PhzbT-oNYpA/copy" target="_blank">BAP upload template.</a>
                                                                This upload must be in a .csv file format, and each required field must be provided. If the employee ID within the upload template matches an employee in the BAP system, we'll update that employee; otherwise, we'll add a new employee.
                                                                To quickly export employees to mass update, you can export the employees list directly into the correct upload template format.
                                                            </p>

                                                            <h4>Department of Public Instruction Fields</h4>
                                                            <p>
                                                                Each employee is required within the BAP to have three fields that must match a field provided by the Department of Public Instruction (DPI). These fields are the following:
                                                                <ul>
                                                                    <li><b>Assignment Postion:</b> which is a position code and position assignment directly from the DPI.</li>
                                                                    <li><b>Subcategory:</b> which is an area code and area assignment direcetly from the DPI.</li>
                                                                    <li><b>Highest Degree Obtained:</b> which must match one of the degree codes from the DPI.</li>
                                                                </ul>
                                                                These DPI related fields will help match an employee to other employees within Wisconsin, or even internally, when looking at salary comparisons.
                                                            </p>
                                                        <?php } else if ($_SESSION["role"] == 2) { ?>
                                                            <p>
                                                                The employees list displays a list of all employees who you as a director have access to via your departments.
                                                                You can view employee demographics, benefits and compensation, and basic job descriptors for your employees here.
                                                            </p>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Employees List -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="employees-subflush-headingTwo">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#employees-subflush-collapseTwo" aria-expanded="false" aria-controls="employees-subflush-collapseTwo">
                                                        Departments
                                                    </button>
                                                </h3>
                                                <div id="employees-subflush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="employees-subflush-headingTwo" data-bs-parent="#employeesSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>The Basics</h4>
                                                        <p>     
                                                            Departments are a way to break up employees into different groups. Each department can be assigned a director and secondary director. These directors will have access to 
                                                            only the employees within their departments.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Salary Comparison -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="employees-subflush-headingThree">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#employees-subflush-collapseThree" aria-expanded="false" aria-controls="employees-subflush-collapseThree">
                                                        Salary Comparison
                                                    </button>
                                                </h3>
                                                <div id="employees-subflush-collapseThree" class="accordion-collapse collapse" aria-labelledby="employees-subflush-headingThree" data-bs-parent="#employeesSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>State-wide Salary Comparison</h4>
                                                        <p>     
                                                            The state-wide salary calculator takes an in-depth look at all public staff reported to the Department of Public Instruction (DPI) within Wisconsin.
                                                            After providing a matching DPI assignment position and assignment area pairing, we'll generate an in-depth report that display all employees who match
                                                            that criteria within the state.
                                                        </p>

                                                        <?php if ($_SESSION["role"] == 1) { ?>
                                                            <p>
                                                                As an admin, you can upload both <a class="template-link" href="https://publicstaffreports.dpi.wi.gov/PubStaffReport/Public/PublicReport/AssignmentCodeList" target="_blank">DPI assignment positions</a> 
                                                                and <a class="template-link" href="https://publicstaffreports.dpi.wi.gov/PubStaffReport/Public/PublicReport/AllStaffReport" target="_blank">DPI staff</a> 
                                                                that come from the reports directly from the DPI.
                                                            </p>
                                                        <?php }?>

                                                        <h4>Internal Salary Comparison</h4>
                                                        <p> 
                                                            
                                                            When using the internal salary comparison tool, you can look at employees within a specific deparment that share the same internal title, and compare salaries.
                                                            <?php if ($_SESSION["role"] == 1) { ?>
                                                                As an admin, you can view all employees within the BAP system.
                                                            <?php } else if ($_SESSION["role"] == 2) { ?>
                                                                As a director, you'll only be able to view employees that are assigned to your department(s).
                                                            <?php } ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if ($_SESSION["role"] == 1) { ?>
                            <!-- Expenses -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="flush-headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false" aria-controls="flush-collapseThree">
                                        Expenses
                                    </button>
                                </h2>
                                <div id="flush-collapseThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        <!-- Expenses Sub-Accordion -->
                                        <div class="accordion accordion-flush" id="expensesSubAccordion">
                                            <!-- Project Expenses -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="exp-subflush-headingOne">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#exp-subflush-collapseOne" aria-expanded="false" aria-controls="exp-subflush-collapseOne">
                                                        Project Expenses
                                                    </button>
                                                </h3>
                                                <div id="exp-subflush-collapseOne" class="accordion-collapse collapse" aria-labelledby="exp-subflush-headingOne" data-bs-parent="#expensesSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>What are "Project Expenses"?</h4>
                                                        <p>
                                                            Project expenses are expenses that can be added on a per project basis. From the <a class="template-link" href="expenses_manage.php" target="_blank">Manage Expenses</a> page, you can manage project expenses.
                                                            These expenses are required to be given a location code and object code. When we add these expenses to a project, you'll be required to then provide a fund code and function code.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Global Expenses -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="exp-subflush-headingTwo">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#exp-subflush-collapseTwo" aria-expanded="false" aria-controls="exp-subflush-collapseTwo">
                                                        Global Expenses
                                                    </button>
                                                </h3>
                                                <div id="exp-subflush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="exp-subflush-headingTwo" data-bs-parent="#expensesSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>What are "Global Expenses"?</h4>
                                                        <p>
                                                            Global expenses are expenses that are calculated automatically in many cases. These include insurance costs, tax costs, and more. These expenses can be found on the
                                                            <a class="template-link" href="expenses_global.php" target="_blank">Employee Expenses</a> page. These costs are automatically calculated whenever an employee is added
                                                            to a project. Some of these expenses are calculated at a differnet rate depending on the FTE days setting and the employees contract days.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <!-- Services -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="flush-headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseFour" aria-expanded="false" aria-controls="flush-collapseFour">
                                    Services
                                </button>
                            </h2>
                            <div id="flush-collapseFour" class="accordion-collapse collapse" aria-labelledby="flush-headingFour" data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body">
                                    <!-- Services Sub-Accordion -->
                                    <div class="accordion accordion-flush" id="servicesSubAccordion">
                                        <!-- Services -->
                                        <div class="accordion-item">
                                            <h3 class="accordion-header" id="services-subflush-headingOne">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#services-subflush-collapseOne" aria-expanded="false" aria-controls="services-subflush-collapseOne">
                                                    Services
                                                </button>
                                            </h3>
                                            <div id="services-subflush-collapseOne" class="accordion-collapse collapse" aria-labelledby="services-subflush-headingOne" data-bs-parent="#servicesSubAccordion">
                                                <div class="accordion-body">
                                                    <?php if ($_SESSION["role"] == 1 || $_SESSION["role"] == 4) { ?>
                                                        <h4>Managing Services</h4>
                                                        <p>
                                                            Admins can create services that will be able to be provided to customers. These services can have a wide variety of cost types, such as:
                                                            <ul>
                                                                <li><b>Fixed Costs</b></li>
                                                                <li><b>Variable Costs</b></li>
                                                                <li><b>Membership Costs</b></li>
                                                                <li><b>Rate-based Costs</b></li>
                                                                <li><b>Group Rate Costs</b></li>
                                                                <li><b>Custom Costs</b></li>
                                                            </ul>
                                                        </p>

                                                        <h4>WUFAR Codes</h4>
                                                        <p>
                                                            Services are required to have the following WUFAR codes provided:
                                                            <ul>
                                                                <li>Fund Code</li>
                                                                <li>Source Code</li>
                                                                <li>Function Code</li>
                                                                <li>Project Code</li>
                                                            </ul>
                                                        </p>
                                                    <?php } ?>

                                                    <h4>Providing Services</h4>
                                                    <p>
                                                        When you provide a service to a customer, we will calculate the cost of the service depending on the cost type of the service.
                                                        When we create contracts and invoices, we will display these costs and quantity on the contracts and invoices based on the period selected.
                                                        By default when we provide a service, we initialize all the quarterly costs the same for all unlocked quarters; however, these quarterly costs
                                                        can be manually overriden as long as these total sum of the quarters equals the invoices initial cost. There is an option where you can set the
                                                        quarterly costs to sum to 0 on a per invoice basis. "Other Services" also follow these rules.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($_SESSION["role"] == 1 || $_SESSION["role"] == 4) { ?>
                                            <!-- Other Services -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="services-subflush-headingTwo">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#services-subflush-collapseTwo" aria-expanded="false" aria-controls="services-subflush-collapseTwo">
                                                        Other Services
                                                    </button>
                                                </h3>
                                                <div id="services-subflush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="services-subflush-headingTwo" data-bs-parent="#servicesSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>What are "Other Services"?</h4>
                                                        <p>
                                                            Other services are services that don't necessarily fit any of the regular services. Other services are customerized on a per invoice basis.
                                                            Each customer can only be provided a single other service once per period.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Other Revenues -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="services-subflush-headingThree">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#services-subflush-collapseThree" aria-expanded="false" aria-controls="services-subflush-collapseThree">
                                                        Other Revenues
                                                    </button>
                                                </h3>
                                                <div id="services-subflush-collapseThree" class="accordion-collapse collapse" aria-labelledby="services-subflush-headingThree" data-bs-parent="#servicesSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>What are "Other Revenues"?</h4>
                                                        <p>
                                                            Other revenues are revenues that we don't get from services that we provided. These could be grants, donations, etc., that have to be in the budget.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($_SESSION["role"] == 1 || $_SESSION["role"] == 2) { ?>
                            <!-- Projects -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="flush-headingFive">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseFive" aria-expanded="false" aria-controls="flush-collapseFive">
                                        Projects
                                    </button>
                                </h2>
                                <div id="flush-collapseFive" class="accordion-collapse collapse" aria-labelledby="flush-headingFive" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        <!-- Projects Sub-Accordion -->
                                        <div class="accordion accordion-flush" id="projectsSubAccordion">
                                            <!-- Manage Projects -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="projects-subflush-headingOne">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#projects-subflush-collapseOne" aria-expanded="false" aria-controls="projects-subflush-collapseOne">
                                                        Manage Projects
                                                    </button>
                                                </h3>
                                                <div id="projects-subflush-collapseOne" class="accordion-collapse collapse" aria-labelledby="projects-subflush-headingOne" data-bs-parent="#projectsSubAccordion">
                                                    <div class="accordion-body">
                                                        <div class="alert alert-warning text-center m-0" role="alert">
                                                            <b><i class="fa-solid fa-triangle-exclamation"></i> This section is currently under construction. <i class="fa-solid fa-helmet-safety"></i></b>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Budgeting Projects -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="projects-subflush-headingTwo">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#projects-subflush-collapseTwo" aria-expanded="false" aria-controls="projects-subflush-collapseTwo">
                                                        Budgeting Projects
                                                    </button>
                                                </h3>
                                                <div id="projects-subflush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="projects-subflush-headingTwo" data-bs-parent="#projectsSubAccordion">
                                                    <div class="accordion-body">
                                                        <div class="alert alert-warning text-center m-0" role="alert">
                                                            <b><i class="fa-solid fa-triangle-exclamation"></i> This section is currently under construction. <i class="fa-solid fa-helmet-safety"></i></b>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if ($_SESSION["role"] == 1 || $_SESSION["role"] == 4) { ?>
                            <!-- Customers -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="flush-headingSix">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseSix" aria-expanded="false" aria-controls="flush-collapseSix">
                                        Customers
                                    </button>
                                </h2>
                                <div id="flush-collapseSix" class="accordion-collapse collapse" aria-labelledby="flush-headingSix" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        <h4>Managing Customers</h4>
                                        <p>
                                            Customers are a crucial aspect when using and providing services. We provide a service to an existing customer within the system.
                                            Customers are allowed to have up to two contacts. In the future, we'll automatically email these contacts whenever the customer has
                                            new documents available to view; however, for now, these contacts are just stored.
                                        </p>

                                        <p>
                                            During the invoice creation process, we will print a customer invoice number on the quarterly invoices. This customer invoice number is typically
                                            connected to an invoice number in your financial software. You can mass upload these invoice numbers from the <a class="template-link" href="customers_manage.php" target="_blank">Manage Customers</a> page.
                                        </p>

                                        <h4>Customer Groups</h4>
                                        <p>
                                            Customers can be sorted into groups. These groups can then be used to mass invoice customers within the group. Membership-based services rely customer groups to determine
                                            each customers invoice cost. These costs will take the total number of members each customer has, and then take a percentage of the customers total from that total to determine the cost.
                                            Groups can also have different rate-based costs, allowing different costs for customers within a group and outside of a group.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if ($_SESSION["role"] == 1 || $_SESSION["role"] == 2) { ?>
                            <!-- Caseloads -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="flush-headingTen">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTen" aria-expanded="false" aria-controls="flush-headingTen">
                                        Caseloads
                                    </button>
                                </h2>
                                <div id="flush-collapseTen" class="accordion-collapse collapse" aria-labelledby="flush-headingTen" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        <!-- Caseloads Sub-Accordion -->
                                        <div class="accordion accordion-flush" id="caseloadsSubAccordion">
                                            <!-- Case Management -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="caseloads-subflush-headingOne">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#caseloads-subflush-collapseOne" aria-expanded="false" aria-controls="caseloads-subflush-collapseOne">
                                                        Caseloads Management
                                                    </button>
                                                </h3>
                                                <div id="caseloads-subflush-collapseOne" class="accordion-collapse collapse" aria-labelledby="caseloads-subflush-headingOne" data-bs-parent="#caseloadsSubAccordion">
                                                    <div class="accordion-body">
                                                        <div class="alert alert-warning text-center m-0" role="alert">
                                                            <b><i class="fa-solid fa-triangle-exclamation"></i> This section is currently under construction. <i class="fa-solid fa-helmet-safety"></i></b>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Student Management -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="caseloads-subflush-headingTwo">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#caseloads-subflush-collapseTwo" aria-expanded="false" aria-controls="caseloads-subflush-collapseTwo">
                                                        Student Management
                                                    </button>
                                                </h3>
                                                <div id="caseloads-subflush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="caseloads-subflush-headingTwo" data-bs-parent="#caseloadsSubAccordion">
                                                    <div class="accordion-body">
                                                        <div class="alert alert-warning text-center m-0" role="alert">
                                                            <b><i class="fa-solid fa-triangle-exclamation"></i> This section is currently under construction. <i class="fa-solid fa-helmet-safety"></i></b>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Caseload Management -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="caseloads-subflush-headingThree">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#caseloads-subflush-collapseThree" aria-expanded="false" aria-controls="caseloads-subflush-collapseThree">
                                                        Caseload Management
                                                    </button>
                                                </h3>
                                                <div id="caseloads-subflush-collapseThree" class="accordion-collapse collapse" aria-labelledby="caseloads-subflush-headingThree" data-bs-parent="#caseloadsSubAccordion">
                                                    <div class="accordion-body">
                                                        <div class="alert alert-warning text-center m-0" role="alert">
                                                            <b><i class="fa-solid fa-triangle-exclamation"></i> This section is currently under construction. <i class="fa-solid fa-helmet-safety"></i></b>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reports -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="flush-headingSeven">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseSeven" aria-expanded="false" aria-controls="flush-collapseSeven">
                                        Reports
                                    </button>
                                </h2>
                                <div id="flush-collapseSeven" class="accordion-collapse collapse" aria-labelledby="flush-headingSeven" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        <h4>Misbudgeted Employees</h4>
                                        <p>
                                            This report displays a list of your employees who have been budgeted a number of days that does not equal the employees total contract days. An employee will show up in this report
                                            multiple times, showing up for each project they are budgeted in. You can view this report for any period, allowing you to plan ahead in the budget, as well as view prior years data.
                                        </p>

                                        <h4>Budgeted Inactive Employees</h4>
                                        <p>
                                            This report shows a list of all inactive employees who are still budgeted into projects.
                                        </p>

                                        <?php if ($_SESSION["role"] == 1) { ?>
                                            <h4>Test Employees</h4>
                                            <p>
                                                This report shows a list of test employees who have been budgeted into projects. It will also display if the test employee should be included in the budget counts or not.
                                                You will be able to delete test employees or toggle the employees cost inclusion directly from this report.
                                            </p>

                                            <h4>Salary Projection (Admin Only)</h4>
                                            <p>
                                                This report shows a list of all employees and their salary in comparison to other DPI public staff who share commonalities, such as DPI assignment position and assignment area.
                                                This report then displays a percantage that indicates what rate a salary would need to be increased or decreased to match the DPI's average for that position.
                                                We also take into account the employee's years of total experience and highest degree obtained when finding the average salary. The number of DPI public staff who share these
                                                characteristics is also indicated.
                                            </p>

                                            <h4>Cash Flow Tracker (Admin Only)</h4>
                                            <p>
                                                This report shows the revenues and expenses breakdown for all periods in chart/graph form.
                                            </p>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if ($_SESSION["role"] == 1) { ?>
                            <!-- Management Tools -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="flush-headingEight">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseEight" aria-expanded="false" aria-controls="flush-collapseEight">
                                        Management Tools (Admin Only)
                                    </button>
                                </h2>
                                <div id="flush-collapseEight" class="accordion-collapse collapse" aria-labelledby="flush-headingEight" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        <!-- Manage Sub-Accordion -->
                                        <div class="accordion accordion-flush" id="manageSubAccordion">
                                            <!-- Admin -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="manage-subflush-headingOne">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-subflush-collapseOne" aria-expanded="false" aria-controls="manage-subflush-collapseOne">
                                                        Admin
                                                    </button>
                                                </h3>
                                                <div id="manage-subflush-collapseOne" class="accordion-collapse collapse" aria-labelledby="manage-subflush-headingOne" data-bs-parent="#manageSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>Compensation Settings</h4>
                                                        <p>
                                                            In the compensation settings category, you'll be able to set the number of days that is the Full-time Equivalent (FTE), and how many hours are in a given workday.
                                                            These numbers will be used when calculating employee compensation, benefits, and expenses, such as their hourly salary and different insurance costs.
                                                        </p>

                                                        <h4>Expense Settings</h4>
                                                        <p>
                                                            In the expense settings category, you'll be able to enter a fund code that will be designated as the fund for overhead costs.
                                                        </p>

                                                        <h4>Maintenance Mode</h4>
                                                        <p>
                                                            Maintenance mode is a setting that when enabled only admin accounts and maintenance accounts will be able to access the BAP. This settings is recommended to be used 
                                                            during the intial setup process, during contract creation times, and during periods of time where you don't want users to make changes, or view data.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Automation -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="manage-subflush-headingEight">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-subflush-collapseEight" aria-expanded="false" aria-controls="manage-subflush-collapseEight">
                                                        Automation
                                                    </button>
                                                </h3>
                                                <div id="manage-subflush-collapseEight" class="accordion-collapse collapse" aria-labelledby="manage-subflush-headingEight" data-bs-parent="#manageSubAccordion">
                                                    <div class="accordion-body">
                                                        <?php include("underConstruction.php"); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Periods -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="manage-subflush-headingTwo">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-subflush-collapseTwo" aria-expanded="false" aria-controls="manage-subflush-collapseTwo">
                                                        Periods
                                                    </button>
                                                </h3>
                                                <div id="manage-subflush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="manage-subflush-headingTwo" data-bs-parent="#manageSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>Managing Periods</h4>
                                                        <p>
                                                            Periods are fiscal cycles that will hold and maintain data for a specific period of time. Typcially broken up by fiscal year. Each period is then broken up into four quarters.
                                                            Each system can only have one active period, which will be the default period in which most datapoints are displayed for; however, some aspects of the system allow the user to
                                                            select which period they want to view and edit. As an admin, you can override which period is the active period, and which periods all users should be able to edit.
                                                        </p>

                                                        <p>
                                                            When creating a new period, we recomment selecting the option to copy the active periods data into the new period, that way you are not starting with a blank slate. As costs, projects, and more
                                                            are on a per-period basis, a blank period will essentially force you to start over.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Quarters -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="manage-subflush-headingThree">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-subflush-collapseThree" aria-expanded="false" aria-controls="manage-subflush-collapseThree">
                                                        Quarters
                                                    </button>
                                                </h3>
                                                <div id="manage-subflush-collapseThree" class="accordion-collapse collapse" aria-labelledby="manage-subflush-headingThree" data-bs-parent="#manageSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>Managing Quarters</h4>
                                                        <p>
                                                            At the moment, quarters are saved globally. That means that the quarters settings are not period specific. The quarter labels will be displayed on contracts and invoices. Locked quarters
                                                            will prevent users from editing the quarters costs, and when providing a service, a locked quarter will not be billed.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Codes -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="manage-subflush-headingFour">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-subflush-collapseFour" aria-expanded="false" aria-controls="manage-subflush-collapseFour">
                                                        Codes
                                                    </button>
                                                </h3>
                                                <div id="manage-subflush-collapseFour" class="accordion-collapse collapse" aria-labelledby="manage-subflush-headingFour" data-bs-parent="#manageSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>Managing Codes</h4>
                                                        <p>
                                                            Codes are used to help manage and make the employees upload simpler. Whenever you upload employees, we will look at the codes you provide and if the code provided matches, we will upload depending on the code set.
                                                            For example: we store health insurance as either none, single, or family; however, these are not always the set code in your financial software. Setting different health insurance codes will allow you to
                                                            not need to manage a financial software export to match the BAP system one-for-one.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Clear -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="manage-subflush-headingFive">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-subflush-collapseFive" aria-expanded="false" aria-controls="manage-subflush-collapseFive">
                                                        Clear
                                                    </button>
                                                </h3>
                                                <div id="manage-subflush-collapseFive" class="accordion-collapse collapse" aria-labelledby="manage-subflush-headingFive" data-bs-parent="#manageSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>Clearing Data</h4>
                                                        <p>
                                                            Clearing data is permanent. The BAP system is backed up frequently in case you need to restore to an earlier copy of your data. To see what is all cleared, more insructions are provided when you go to clear data.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Accounts -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="manage-subflush-headingSix">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-subflush-collapseSix" aria-expanded="false" aria-controls="manage-subflush-collapseSix">
                                                        Accounts
                                                    </button>
                                                </h3>
                                                <div id="manage-subflush-collapseSix" class="accordion-collapse collapse" aria-labelledby="manage-subflush-headingSix" data-bs-parent="#manageSubAccordion">
                                                    <div class="accordion-body">
                                                        <h4>Account Managemnt</h4>
                                                        <p>
                                                            The account access page displays a list of all users that have access to login to the BAP system. From here, you can login as an other use to view what the system looks like through their eyes.
                                                            You can also remove access of a user here. You can also see the users last login time to see who is actively using the system.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Log -->
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="manage-subflush-headingSeven">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-subflush-collapseSeven" aria-expanded="false" aria-controls="manage-subflush-collapseSeven">
                                                        Log
                                                    </button>
                                                </h3>
                                                <div id="manage-subflush-collapseSeven" class="accordion-collapse collapse" aria-labelledby="manage-subflush-headingSeven" data-bs-parent="#manageSubAccordion">
                                                    <div class="accordion-body">
                                                        <p>
                                                            The log shows some basic functions that users have used and what time they performed these actions. That way you can track to see who did what and when.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <!-- Messages -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="flush-headingNine">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseNine" aria-expanded="false" aria-controls="flush-collapseNine">
                                    Messages
                                </button>
                            </h2>
                            <div id="flush-collapseNine" class="accordion-collapse collapse" aria-labelledby="flush-headingNine" data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body">
                                    <h4>BAP Mail</h4>
                                    <p>
                                        The Business Admin Panel (BAP) has a built-in messaging system that allows for quick and easy internal communication. View messages that others have sent to you, and view
                                        messages that you have sent to others.
                                    </p>
                                    <?php if ($_SESSION["role"] == 1) { ?>
                                        <p>
                                            As an admin, you'll be able to mark messages as important before sending them. This will display an icon within the inbox to let users know that it is important to view.
                                        </p>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
    }
    else { goToLogin(); }
    
    include_once("footer.php"); 
?>