<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CUSTOMERS"))
        {
            // get parameters from POST
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }
            if (isset($_POST["customer_id"]) && $_POST["customer_id"] <> "") { $customer_id = $_POST["customer_id"]; } else { $customer_id = null; }

            if (verifyPeriod($conn, $period_id))
            {
                if (verifyCustomer($conn, $customer_id))
                {
                    // get the period name
                    $period_name = getPeriodName($conn, $period_id);

                    ?>
                        <div class="modal fade" tabindex="-1" role="dialog" id="viewCustomerServicesModal" data-bs-backdrop="static" aria-labelledby="viewCustomerServicesModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="viewCustomerServicesModalLabel"><?php echo $period_name; ?> Customer Services</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <table class="report_table w-100">
                                            <thead>
                                                <tr>
                                                    <th class="text-center py-1 px-2">ID</th>
                                                    <th class="text-center py-1 px-2">Name</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <?php
                                                    $getServices = mysqli_prepare($conn, "SELECT DISTINCT s.id, s.name FROM services s 
                                                                                        JOIN services_provided sp ON s.id=sp.service_id
                                                                                        WHERE sp.period_id=? AND sp.customer_id=?
                                                                                        ORDER BY s.name ASC");
                                                    mysqli_stmt_bind_param($getServices, "ii", $period_id, $customer_id);
                                                    if (mysqli_stmt_execute($getServices))
                                                    {
                                                        $getServicesResults = mysqli_stmt_get_result($getServices);
                                                        if (mysqli_num_rows($getServicesResults) > 0) // services found
                                                        {
                                                            while ($service = mysqli_fetch_array($getServicesResults))
                                                            {
                                                                // store service details locally
                                                                $service_id = $service["id"];
                                                                $service_name = $service["name"];

                                                                // create the table row
                                                                echo "<tr>
                                                                    <td class='text-center'>".$service_id."</td>
                                                                    <td class='text-center'>".$service_name."</td>
                                                                </tr>";
                                                            }
                                                        }
                                                    }
                                                ?>
                                            </tbody>
                                        </table>
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
?>