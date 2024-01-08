<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["masquerade"]) && $_SESSION["masquerade"] == 1)
        {
            // override current SESSION variables with the stored masquerade variable
            $_SESSION["id"] = $_SESSION["masq_id"];
            $_SESSION["email"] = $_SESSION["masq_email"];
            $_SESSION["role"] = $_SESSION["masq_role"];
            $_SESSION["masquerade"] = 0;
            
            // destroy/unset masquerade SESSION vars
            unset($_SESSION["masquerade"]);
            unset($_SESSION["masq_id"]);
            unset($_SESSION["masq_email"]);
            unset($_SESSION["masq_role"]);
            unset($_SESSION["district"]);
        }
    }
?>