<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            ?>
                <div class="row justify-content-center">
                    <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4 col-xxl-4">
                        <h1 class="text-center">Upload Schools</h1>

                        <form action="processUploadSchools.php" method="POST" enctype="multipart/form-data">
                            <div class="modal-body">
                                <input type="file" name="fileToUpload" id="fileToUpload">
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Schools</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php
        }
    }
?>