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
                    // store contract details
                    $contractDetails = mysqli_fetch_assoc($getContractResult);

                    // verify contract is for the district of the user
                    if ($_SESSION["role"] == 1 || (isset($_SESSION["district"]) && ($_SESSION["district"]["id"] == $contractDetails["customer_id"])))
                    {
                        ?>
                            <!-- Sign Contract Modal -->
                            <div class="modal fade" tabindex="-1" role="dialog" id="signContractModal" data-bs-backdrop="static" aria-labelledby="signContractModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="signContractModalLabel">Sign Contract</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <?php if (isset($contractDetails["status"]) && $contractDetails["status"] == 0 && 
                                                    (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1) &&
                                                    ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor")) { ?>
                                                <!-- Contract Form -->
                                                <form id="contract-form" class="needs-validation" novalidate>
                                                    <h4 class="mb-1">Instructions</h4>
                                                    <p class="mb-3">
                                                        After reviewing the contract and terms, you can approve the contract or request changes if you believe there are corrections that need to be made.
                                                    </p>

                                                    <!-- Electronic Signature -->
                                                    <h4 class="mb-1">Electronic Signature</h4>
                                                    <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                                        <!-- First Name -->
                                                        <div class="form-group col-6 pe-2">
                                                            <label for="signature-fname"><span class="required-field">*</span> First Name:</label>
                                                            <input type="text" class="form-control w-100" id="signature-fname" name="signature-fname" required>
                                                        </div>

                                                        <!-- Last Name -->
                                                        <div class="form-group col-6 px-2">
                                                            <label for="signature-lname"><span class="required-field">*</span> Last Name:</label>
                                                            <input type="text" class="form-control w-100" id="signature-lname" name="signature-lname" required>
                                                        </div>
                                                    </div>

                                                    <!-- Acknowledgement -->
                                                    <h4 class="mb-1">Contract Acknowledgement</h4>
                                                    <div class="form-row d-flex mb-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" role="checkbox" id="acknowledgement" name="acknowledgement" required>
                                                            <label class="form-check-label" for="acknowledgement">
                                                                By checking this box and clicking the <b>Acknowledge & Approve</b> button below, 
                                                                I acknowledge and approve the terms of the contract service agreement for the <?php echo getPeriodName($conn, $contractDetails["period_id"]); ?> fiscal year.
                                                            </label>
                                                        </div>
                                                    </div>
                                                </form>
                                            <?php } else if ($_SESSION["role"] == 1 && (isset($contractDetails["status"]) && $contractDetails["status"] == 0)) { ?>
                                                <div class="alert alert-warning m-0">
                                                    <h4 class="my-1">Pending Approval</h4>
                                                </div>
                                            <?php } else if (isset($contractDetails["status"]) && $contractDetails["status"] == 1) { ?>
                                                <div class="alert alert-success m-0">
                                                    <h4 class="mb-1">Contract Acknowledged & Approved</h4>
                                                    <p class="my-1"><b>Approved By: </b><?php echo getUserDisplayName($conn, $contractDetails["action_user"]); ?></p>
                                                    <p class="my-1"><b>Approval Date: </b><?php echo date("n/j/Y g:ia", strtotime($contractDetails["action_time"])); ?></p>
                                                </div>
                                            <?php } else if (isset($contractDetails["status"]) && $contractDetails["status"] == 3) { ?>
                                                <div class="alert alert-secondary m-0">
                                                    <h4 class="mb-1">Contract Changes Requested</h4>
                                                    <p class="my-1"><b>Changes Requested By: </b><?php echo getUserDisplayName($conn, $contractDetails["action_user"]); ?></p>
                                                    <p class="my-1"><b>Changes Requested At: </b><?php echo date("n/j/Y g:ia", strtotime($contractDetails["action_time"])); ?></p>
                                                </div>
                                            <?php } ?>
                                        </div>

                                        <div class="modal-footer">
                                            <?php if (isset($contractDetails["status"]) && $contractDetails["status"] == 0 && 
                                                    (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1) &&
                                                    ($_SESSION["district"]["role"] == "Admin" || $_SESSION["district"]["role"] == "Editor")) { ?>
                                                <button type="button" class="btn btn-success px-3" onclick="acknowledgeContract(<?php echo $contract_id; ?>, 1);"><i class="fa-solid fa-check"></i> Acknowledge & Approve</button>
                                                <button type="button" class="btn bg-warning text-white px-3" onclick="acknowledgeContract(<?php echo $contract_id; ?>, 3);"><i class="fa-solid fa-file-pen"></i> Request Changes</button>
                                            <?php } ?>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Sign Contract Modal -->
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>