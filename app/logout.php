<?php
    session_start();
    unset($_SESSION["user"]);
    session_destroy();
    setcookie("bs_auth", "", time() - 1);

    require_once("smarty.php");
    $smarty->display("logout.tpl");
