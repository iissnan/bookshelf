<?php
    session_start();
    header("Content-Type: text/html; charset=utf-8");
    require_once("../require.global.php");
    redirect_unless_login("/login.php");

    require_once(MODEL_DIR . "/book.class.php");
    require_once(MODEL_DIR . "/tag.class.php");
    require_once(MODEL_DIR . "/author.class.php");

    $book_model = new BookModel();

    $smarty->assign("page_title", "编辑书籍");
    $smarty->assign("user", $_SESSION["user"]);

    // 提交数据
    if (isset($_POST["submitted"]) && $_POST["submitted"] == "yes") {
        $title = trim($_POST["title"]);
        $id = trim($_POST["id"]);
        $author = trim($_POST["author"]);
        $intro = trim($_POST["intro"]);
        $pages = trim($_POST["pages"]);
        $category = trim($_POST["category"]);
        $isbn = trim($_POST["isbn"]);
        if ($_FILES["cover"]["name"] != "") {
            $cover = $_FILES["cover"];
        } else {
            $cover = $_POST["current-cover"];
        }
        $douban_link = $_POST["douban_link"];

        $book = array(
            "title" => $title,
            "isbn" => $isbn,
            "cover" => $cover,
            "intro" => $intro,
            "pages" => $pages,
            "douban_link" => $douban_link,
            "update_at" => date("Y-m-d H:i:s")
        );

        if ($title == "" || empty($pages)) {
            $title == "" and $alert->set_message("请输入书籍标题");
            empty($pages) and $alert->set_message("请输入书籍总页数");
            $book["id"] = $id;
            $book = (object)$book;

            $smarty->assign("book", $book);
            $smarty->display("book/edit.tpl");
        } else {
            $result = $book_model->update($book)
                                ->where("id='$id'")
                                ->execute();

            if ($result) {
                $book_model->update_category($id, $category);
                $book_model->update_author($id, $author);
               echo "<script>location.href='result.php?action=edit&code=" . $result . "';</script>";
            } else {
                $alert->set_message("更新失败")->show();
                $smarty->assign("alert", $alert);
                $smarty->display("book/edit.tpl");
            }
        }
    } else {
        // 获取数据
        if (isset($_GET["id"])) {
            $id = (int)$_GET["id"];
            $book = $book_model->get_item("id", $id);
            if ($book->num_rows > 0) {
                $book = $book->fetch_object();

                // 获取作者
                $author_model = new AuthorModel();
                $authors = "";
                $author_result = $author_model->select("*", "books_authors, authors")
                                                ->where("books_authors.book_id=$book->id")
                                                ->where("authors.id=books_authors.author_id")
                                                ->execute();
                if ($author_result) {
                    $author_numbers = $author_result->num_rows;
                    for ($i = 0; $i < $author_numbers; $i++) {
                        $author = $author_result->fetch_object();
                        $authors = $i == 0 ?
                            $author->name :
                            $authors . ", $author->name";
                    }
                }
                $book->author = $authors;

                // 获取分类
                $category_model = new TagModel("categories");
                $categories = "";
                $category_result = $category_model->select("*", "books_categories, categories")
                                                    ->where("books_categories.book_id=$book->id")
                                                    ->where("categories.id=books_categories.category_id")
                                                    ->execute();
                if ($category_result) {
                    $category_numbers = $category_result->num_rows;
                    for ($i = 0; $i < $category_numbers; $i++) {
                        $category = $category_result->fetch_object();
                        $categories = $i == 0 ?
                            $category->name :
                            $categories . ", $category->name";
                    }
                }
                $book->category = $categories;

                $smarty->assign("book", $book);
                $smarty->display("book/edit.tpl");
            } else {
                $alert->set_message("书籍未找到")->show();
                $smarty->assign("alert", $alert);
                $smarty->display("book/edit.tpl");
            }
        } else {
            $alert->set_message("id参数丢失");
            $smarty->assign("alert", $alert);
            $smarty->display("book/edit.tpl");
        }
    }

    isset($book_model) and $book_model->release();
