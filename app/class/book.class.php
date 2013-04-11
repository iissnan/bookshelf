<?php

require_once("dbc.class.php");

/**
 * Book Model
 */
class Book {
    private $dbc;
    private $table = "books";

    public function __construct() {
        $this->dbc = new DatabaseConnection("localhost", "root", "123456", "bookshelf");
    }

    /**
     * 添加书籍
     *
     * @param string $title
     * @param string $author
     * @param string $isbn
     * @param array $cover
     * @param string $douban_link
     * @return mixed
     */
    public function add($title, $author, $isbn, $cover, $douban_link) {
        $result = $this->dbc->insert(
            $this->table,
            array("title", "author", "isbn", "cover", "douban_link"),
            array($title, $author, $isbn, $this->handleCover($cover), $douban_link)
        );

        return $result;
    }


    /**
     * 获取书籍的总数
     *
     * @return number
     */
    public function total() {
        $result = $this->dbc->count($this->table);
        return !$result ? 0 : $result->fetch_object()->total;
    }

    /**
     * 获取多本书籍
     *
     * @param string $filter 过滤条件
     * @param number $row_count 数量
     * @param number $offset 偏移量
     * @return mixed
     */
    public function getItems($row_count, $offset, $filter){
        return $this->dbc->get($this->table, $filter, $row_count, ($offset - 1) * $row_count);
    }

    /*
     * 获取关联查询的结果
     *
     * @param array $join_table
     * @param integer $row_count
     * @param integer $offset
     * @param string $filter
     */
    public function getJoinItems($join_tables, $row_count, $offset, $filter) {
        return $this->dbc->getJoin(
            $this->table,
            join(",", $join_tables),
            $row_count,
            ($offset - 1) * $row_count,
            $filter
        );
    }

    /**
     * 获取指定key的书籍，key若为数字则用id检索，若为string则用title检索
     *
     * @param mixed $key (int)id 或者 (string)title
     * @return mixed
     */
    public function getItem($key) {
        $filter = gettype($key) == "string" ? "title = '$key'" : " id = $key";
        return $this->dbc->get($this->table, $filter, 1);
    }

    /**
     * 更新书籍
     *
     * @param string $id
     * @param string $title 标题
     * @param string $author 作者
     * @param string $isbn ISBN
     * @param array $cover 封面（文件上传数组）
     * @param string $douban_link 豆瓣链接
     *
     * @return boolean 执行成功或者失败
     */
    public function update($id, $title, $author, $isbn, $cover, $douban_link) {
        $cover = gettype($cover) == "array" ? $this->handleCover($cover) : $cover;
        $fields = array(
            "title" => $title,
            "author" => $author,
            "isbn" => $isbn,
            "cover" => $cover,
            "douban_link" => $douban_link,
            "update_at" => date("Y-m-d H:i:s")
        );
        return $this->dbc->update($this->table, $fields, "id=$id");
    }

    /**
     * 删除书籍
     *
     * @param $id
     * @return boolean
     */
    public function remove($id) {
        return $this->dbc->remove($this->table, "id = $id");
    }

    /**
     * 封面处理
     * @param $cover
     * @return string
     */
    protected function handleCover($cover) {
        // 封面存储路径
        define("DIR_COVER", "../cover");

        // 上传的图片最大限制为500K
        define("MAX_SIZE", 500000);

        if (!is_dir(DIR_COVER)) {
            mkdir(DIR_COVER);
        }

        // 允许的上传图片类型
        $allow_mimes = array("image/png", "image/jpeg", "image/gif");
        if ($cover["size"] == 0) {}
        if ($cover["size"] > MAX_SIZE) {
            echo "图片大小必需小于500K";
            return "";
        }
        if (! in_array($cover["type"], $allow_mimes)) {
            echo "仅支持PNG, GIF和JPG格式的图片";
            return "";
        }
        if (!move_uploaded_file($cover["tmp_name"], DIR_COVER . '/' . $cover["name"])) {
            echo "图片上传失败";
            return "";
        } else {
            return DIR_COVER . "/" . $cover["name"];
        }
    }
}