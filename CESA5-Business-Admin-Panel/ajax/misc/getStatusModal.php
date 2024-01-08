<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]))
        {
            // get the parameters from POST
            if (isset($_POST["mode"])) { $mode = $_POST["mode"]; } else { $mode = null; }
            if (isset($_POST["title"])) { $title = $_POST["title"]; } else { $title = null; }
            if (isset($_POST["body"])) { $body = $_POST["body"]; } else { $body = null; }

            if ($title != null && $body != null)
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="<?php echo $mode; ?>StatusModal" data-bs-backdrop="static" aria-labelledby="<?php echo $mode; ?>StatusModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="<?php echo $mode; ?>StatusModalLabel"><?php echo $title ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <?php echo $body; ?>
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
?>