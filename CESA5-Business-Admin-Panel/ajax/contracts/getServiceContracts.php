<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize the contracts array
        $contracts = [];

        // include additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_SERVICE_CONTRACTS"))
        {
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null)
            {
                // get all contracts that were created for the period
                $directory = "../../local_data/service_contracts/$period/";
                $files = scandir($directory, 1);
                for ($f = 0; $f < count($files); $f++)
                {
                    // get the customer ID from the file name (ID is pre .pdf file extension)
                    $file = $files[$f];
                    $customer_id = pathinfo($file, PATHINFO_FILENAME);

                    // verify the customer ID is a number
                    if (is_numeric($customer_id))
                    {
                        // check to see if the customer still exists
                        $checkCustomer = mysqli_prepare($conn, "SELECT id, name FROM customers WHERE id=?");
                        mysqli_stmt_bind_param($checkCustomer, "i", $customer_id);
                        if (mysqli_stmt_execute($checkCustomer))
                        {
                            $checkCustomerResult = mysqli_stmt_get_result($checkCustomer);
                            if (mysqli_num_rows($checkCustomerResult) > 0) // customer exists; continue
                            {
                                $customer_details = mysqli_fetch_array($checkCustomerResult);
                                $customer_name = $customer_details["name"];

                                // build the actions column
                                $actions = "";
                                $actions .= "<div class='row justify-content-center'>
                                    <div class='col-sm-12 col-md-12 col-lg-12 col-xl-6 col-xxl-6 p-1'><button class='btn btn-primary w-100 h-100' type='button' onclick='getViewServiceContractModal(\"".$customer_id."\");'><i class='fa-solid fa-eye'></i> View Contract</button></div>
                                    <!-- <div class='col-sm-12 col-md-12 col-lg-12 col-xl-6 col-xxl-6 p-1'><button class='btn btn-primary w-100 h-100' type='button' onclick='uploadServiceContractToDrive(\"".$customer_id."\");'><i class='fa-solid fa-cloud-arrow-up'></i> Upload Contract To Drive <i class='fa-brands fa-google-drive'></i></button></div> -->
                                </div>";

                                // create table entry
                                $customer_contract = [];
                                $customer_contract["customer_id"] = $customer_id;
                                $customer_contract["customer_name"] = $customer_name;
                                $customer_contract["actions"] = $actions;
                                $contracts[] = $customer_contract;
                            }
                        }
                    }
                }
            }
        }

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $contracts;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>