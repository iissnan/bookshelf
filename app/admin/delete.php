<?php
    session_start();
    header("Content-Type: text/html; charset=utf-8");
    require_once("../include/auth.php");
    !isLogin() and header("location: ../login.php");

    require_once("../include/smarty.php");
    require_once("../class/book.class.php");

    if (isset($_GET["id"]) && $_GET["id"] != "") {
        $item_id = $_GET["id"];
        $book_model = new BookModel();
        $result = (int)$book_model->remove("id='$item_id'");
        $result and $book_model->updateCategory($item_id, "");

        echo "<script>location.href = 'result.php?action=delete&code=" . $result . "'</script>";
    } else {
        $smarty->display("admin/delete.tpl");
    }

