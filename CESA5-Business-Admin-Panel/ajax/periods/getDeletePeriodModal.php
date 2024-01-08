<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get period ID POST
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }

            ?>
                <div class="modal fade" tabindex="-1" role="dialog" id="deletePeriodModal" data-bs-backdrop="static" aria-labelledby="deletePeriodModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="deletePeriodModalLabel">Delete Period</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                Are you sure you want to delete this period? This will delete all data associated with the period.
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="deletePeriod(<?php echo $period_id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete Period</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
        }
    }
?>