<?php
    session_start();
    header("Content-Type: text/html; charset=utf-8");
    require_once("../include/auth.php");
    !isLogin() and header("location: ../login.php");

    require_once("../include/smarty.php");
    require_once("../class/model.class.php");
    require_once("../class/book.class.php");

    if (isset($_POST["submitted"]) && $_POST["submitted"] == "yes") {
        $title = trim($_POST["title"]);
        $author = trim($_POST["author"]);
        $isbn = trim($_POST["isbn"]);
        $category = trim($_POST["category"]);
        $douban_link = trim($_POST["douban_link"]);
        $cover = $_FILES["cover"];

        // title为必需值
        if ($title == "") {
            $alert = "<div class='alert alert-error' id='alert'>请输入标题</div>";
            $smarty->assign("alert", $alert);
            $smarty->assign(array(
                "title" => $title,
                "author" => $author,
                "isbn" => $isbn,
                "douban_link" => $douban_link,
                "category" => $category
            ));
            $smarty->display("admin/add.tpl");
        } else {
            $book_model = new BookModel("books");
            $book_data = array(
                "title" => $title,
                "isbn" => $isbn,
                "cover" => $cover,
                "douban_link" => $douban_link,
                "create_at" => date("Y-m-d H:i:s")
            );

            $result = $book_model->add($book_data);
            if ($result) {
                $book_id = $book_model->dbc->db->insert_id;
                $isProcessWell = true;

                // 分类处理
                $isProcessWell = $book_model->add_category($book_id, $category);

                // 作者处理
                $isProcessWell = $book_model->add_author($book_id, $author);

                if ($isProcessWell) {
                    echo "<script>location.href = 'result.php?action=add&code=" . $result . "';</script>";
                } else {
                    $alert = "<div class='alert alert-error' id='alert'>" . $book_model->dbc->db->error . "</div>";
                    $smarty->assign("alert", $alert);
                    $smarty->display("admin/add.tpl");
                }
            } else {
                $alert = "<div class='alert alert-error' id='alert'>" . $book_model->dbc->db->error . "</div>";
                $smarty->assign("alert", $alert);
                $smarty->display("admin/add.tpl");
            }
        }
    } else {
        $smarty->display("admin/add.tpl");
    }