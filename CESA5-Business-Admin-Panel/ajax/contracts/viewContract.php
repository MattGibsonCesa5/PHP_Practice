<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // include additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (isset($_POST["contract_id"]) && $_POST["contract_id"] <> "") { $contract_id = $_POST["contract_id"]; } else { $contract_id = null; }

        // initialize the contract body
        $contract_body = "";

        // get the contract
        $contractDetails = [];
        if (isset($contract_id) && $contract_id != null)
        {
            $getContract = mysqli_prepare($conn, "SELECT * FROM contracts_created WHERE id=?");
            mysqli_stmt_bind_param($getContract, "i", $contract_id);
            if (mysqli_stmt_execute($getContract))
            {
                $getContractResult = mysqli_stmt_get_result($getContract);
                if (mysqli_num_rows($getContractResult) > 0) // contract found 
                {
                    $contractDetails = mysqli_fetch_assoc($getContractResult);
                    if ($_SESSION["role"] == 1 || (isset($_SESSION["district"]) && ($_SESSION["district"]["id"] == $contractDetails["customer_id"])))
                    {
                        $contract_body = "<embed src=\"".$contractDetails["filepath"]."\" width=\"100%\" height=\"768px\"/>";
                    }
                    else
                    {
                        $contract_body = "<div class='alert alert-danger'>
                            <p class='mb-0'>User is unauthorized to view this document.</p>
                        </div>";
                    }
                }
                else
                {
                    $contract_body = "<div class='alert alert-danger'>
                        <p class='mb-0'>Contract not found.</p>
                    </div>";
                }
            }
            else
            {
                $contract_body = "<div class='alert alert-danger'>
                    <p class='mb-0'>An unexpected error has occurred! Please try again later.</p>
                </div>";
            }
        } 
        else
        {
            $contract_body = "<div class='alert alert-danger'>
                <p class='mb-0'>Contract not found.</p>
            </div>";
        }

        ?>
            <div class="modal fade" tabindex="-1" role="dialog" id="viewContractModal" data-bs-backdrop="static" aria-labelledby="viewContractModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header primary-modal-header">
                            <h5 class="modal-title primary-modal-title" id="viewContractModalLabel">View Contract</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <?php if ((isset($contractDetails["status"]) && $contractDetails["status"] == 0) && 
                                    (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1) &&
                                    ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor")) { ?>
                                <h3 class="mb-1">Information</h3>
                                <p class="mb-1">
                                    Please review the contract displayed below. 
                                    You are able to download and print the contract using the buttons at the top right of the contract. 
                                    To approve the contract, please scroll to the bottom of the modal and review the terms below the contract.
                                    If you believe there are corrections that need to be made, you can hold the contract for review.
                                </p>
                            <?php } ?>

                            <?php echo $contract_body; ?>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php

        // disconnect from the database
        mysqli_close($conn);
    }
?>