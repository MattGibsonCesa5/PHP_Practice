<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) || isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"]))
        {
            include("underConstruction.php");
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>