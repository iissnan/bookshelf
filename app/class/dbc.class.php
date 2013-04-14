<?php
class DatabaseConnection {
    private $hostname;
    private $username;
    private $password;
    private $database;

    public $db;


    /**
     * 配置数据库连接参数
     *
     * @param {string} $hostname
     * @param {string} $username
     * @param {string} $password
     * @param {string} $database
     */
    public function __construct($hostname, $username, $password, $database) {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;

        $this->connect();
    }

    /**
     * 连接数据库
     */
    public function connect() {
        try {
            @$db = new mysqli($this->hostname, $this->username, $this->password);

            // $db->connect_error 需要PHP ver > 5.2.9
            if ($db->connect_error) {
                throw new Exception("Error: Could not to connect to MySQL.");
            }
            $this->db = $db;

            // 数据库选择
            if (isset($this->database)) {
                $this->use_database($this->database);
                if ($this->db->errno) {
                    throw new Exception($this->db->error);
                }
            }
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * 选择数据库
     *
     * @param $database
     */
    public function use_database($database) {
        $this->db->select_db($database);
    }


    /**
     * 获取$table下的数据，默认限制10条
     *
     * @param $table
     * @param string $filter
     * @param int $offset
     * @param int $row_count
     * @return mixed
     */
    public function get($table, $row_count=10, $offset=0, $filter="") {
        $filter == "" or $filter = "WHERE $filter";
        $query = "SELECT * FROM $table $filter ORDER BY id LIMIT $offset, $row_count";
        return $this->execute($query);
    }

    /*
     * 关联查询
     *
     * @param string $table
     * @param string $join_table 关联表
     * @param integer $row_count 获取行数
     * @param integer $offset 位移
     * @param string $filter 等价关联条件
     */
    public function getJoin($table, $join_table, $row_count=10, $offset=0, $filter) {
        $filter == "" or $filter = "WHERE $filter";
        $query = "SELECT * FROM $table, $join_table $filter ORDER BY $table.id LIMIT $offset, $row_count";
        return $this->execute($query);
    }

    /**
     * 获取数据的总条目
     *
     * @param $table
     * @param string $filter
     * @param array $join_table 关联表
     * @return mixed
     */
    public function count($table, $filter, $join_table) {
        $filter == "" or $filter = "WHERE $filter";

        // 关联表格处理
        if ($join_table) {
            array_unshift($join_table, $table);
            $table = join(",", $join_table);
        }
        $query = "SELECT count(*) AS total FROM $table $filter";
        return $this->execute($query);
    }

    /**
     * 向$table插入数据
     *
     * @param $table
     * @param array $fields
     * @param array $values
     * @return boolean 执行成功或者失败
     */
    public function insert($table, $fields, $values) {
        $fields = join(",", $fields);

        // 格式化values
        foreach ($values as &$value) {

            // 处理特殊字符 mysqli_real_escape_string
            $value = "'" . $this->db->real_escape_string($value) . "'";
        }
        $values = join(",", $values);

        $query = "INSERT INTO $table ($fields) VALUES($values)";
        return $this->execute($query);
    }

    /**
     * 更新数据
     *
     * @param string $table 表名字
     * @param array $pair 更新的键值对
     * @param string $filter 过滤语句
     * @return boolean 执行成功或者失败
     */
    public function update($table, $pair, $filter) {
        $update_fields = "";
        $index = 0;
        foreach ($pair as $field => $value) {
            $update_fields = $index == 0 ?
                $field . "='" . $value ."'" :
                $update_fields . "," . $field . "='" . $value . "'";
            $index++;
        }
        $filter == "" or $filter = "WHERE $filter";
        $query = "UPDATE $table SET $update_fields $filter";
        return $this->execute($query);
    }

    /**
     * 关联更新
     *
     * @param string $table
     * @param string $join_tables
     * @param array $data
     * @param string $filter
     * @return mixed
     */
    public function updateJoin($table, $join_tables, $data, $filter) {
        $update_fields = "";
        $index = 0;
        foreach ($data as $field => $value) {
            $update_fields = $index == 0 ?
                $field . "='" . $value ."'" :
                $update_fields . "," . $field . "='" . $value . "'";
            $index++;
        }
        $filter == "" or $filter = "WHERE $filter";
        $query = "UPDATE $table, $join_tables SET $update_fields $filter";
        return $this->execute($query);
    }

    /**
     * 删除$table下的数据
     *
     * @param string $table 表名字
     * @param string $filter 过滤语句
     * @return boolean 执行成功或者失败
     */
    public function remove($table, $filter="") {
        $filter == "" or $filter = "WHERE $filter";
        $query = "DELETE FROM $table $filter";
        return $this->execute($query);
    }

    /**
     * 执行查询
     *
     * @param string $query 查询语句
     * @return mixed 查询执行结果
     */
    public function execute($query) {
        //echo($query . "<br />");
        $this->db->query("SET NAMES 'utf8'");
        return $this->db->query($query);
    }

    /**
     * 释放数据库链接
     */
    public function close() {
        $this->db->close();
    }
}
