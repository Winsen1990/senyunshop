<?php
/**
 * MySQL数据库服务
 * @author winsen
 * @version 1.1.1
 * @date 2015-7-8
 */
class MySQL
{
    protected $host;
    protected $username;
    protected $password;
    protected $dbName;
    protected $conn;
    protected $charset = 'utf8';
    protected $error;
    protected $errno;
    protected $prefix;
    protected $operation_list = array(
        'lt' => '<',
        'elt' => '<=',
        'eq' => '=',
        'neq' => '<>',
        'egt' => '>=',
        'gt' => '>'
    );

    /**
     * 构造函数
     * @param string $host 数据库服务器地址
     * @param string $username 数据库用户名
     * @param string $password 数据库密码
     * @param string $dbName 数据库名
     * @param string $prefix 前缀
     * @param string $charset 数据库链接时采用的编码,默认为utf-8
     * @author winsen
     */
    public function __construct($host, $username, $password, $dbName, $prefix, $charset = 'utf8')
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->dbName = $dbName;
        $this->charset = $charset;
        $this->perfix = $prefix;

        $this->conn = mysqli_connect($host, $username, $password, $dbName);

        if($this->conn)
        {
            mysqli_set_charset($this->conn, $this->charset);
        } else {
            echo 'Can\'t connect to database.';
            $this->error = mysqli_error($this->conn);
            $this->errno = mysqli_errno($this->conn);
            exit;
        }
    }

    /**
     * 返回带前缀的表名
     * @param string $table_name
     * @return string
     * @author winsen
     */
    function table($table_name)
    {
        if($table_name == '') return '';

        return '`'.$this->perfix.$table_name.'`';
    }

    /**
     * 判断当前数据库链接是否有效
     * @return void
     * @author winsen
     */
    public function validate()
    {
        if(!$this->conn)
        {
            echo 'Plz connect to database first.';
            exit;
        }
    }

    /**
     * 返回最后一次插入的自增值
     * @return int 成功返回last_id,失败时返回false
     * @author winsen
     */
    public function get_last_id()
    {
        return mysqli_insert_id($this->conn);
    }

    public function get_affect_rows()
    {
        return mysqli_affected_rows($this->conn);
    }

    /**
     * 过滤掉会引发mysql安全问题的变量
     * @param string $var 需要过滤的变量
     * @return string 过滤后的安全变量
     * @author winsen
     */
    public function escape($var)
    {
        return mysqli_real_escape_string($this->conn, $var);
    }

    /**
     * 以多维数组的形式返回sql语句的查询结果集
     * @param string $sql 需要执行的sql语句
     * @return mixed 成功时返回array,失败时返回null
     * @author winsen
     */
    public function fetchAll($sql)
    {
        global $log;

        $log->record($sql);

        $this->validate();

        $result = mysqli_query($this->conn, $sql);
        $response = array();

        if(!$result)
        {
            return null;
        }

        while($row = mysqli_fetch_assoc($result))
        {
            $response[] = $row;
        }

        if(count($response) > 0)
        {
            return $response;
        } else {
            return null;
        }
    }

    /**
     * 以数组的形式返回sql语句的查询结果集
     * @param string $sql 需要执行的sql语句
     * @return mixed 成功时返回array,失败时返回null
     * @author winsen
     */
    public function fetchRow($sql)
    {
        global $log;

        $this->validate();
        $log->record($sql);

        $result = mysqli_query($this->conn, $sql);
        if(!$result)
        {
            return null;
        }

        if($row = mysqli_fetch_assoc($result))
        {
            return $row;
        } else {
            return null;
        }
    }

    /**
     * 提交sql语句直接执行,无需返回结果
     * @param string $sql 需要执行的sql语句
     * @return bool|resource 成功时返回resource对象,失败时返回false
     * @author winsen
     */
    public function query($sql)
    {
        $this->validate();

        return mysqli_query($this->conn, $sql);
    }

    /**
     * 提交sql语句执行插入操作
     * @param string $sql 需要执行的sql语句
     * @return resource 成功时返回resource对象,失败时返回false
     * @author winsen
     */
    public function insert($sql)
    {
        return $this->query($sql);
    }

    /**
     * 提交sql语句执行删除操作
     * @param string $sql 需要执行的sql语句
     * @return resource 成功时返回resource对象,失败时返回false
     * @author winsen
     */
    public function delete($sql)
    {
        return $this->query($sql);
    }

    /**
     * 提交sql语句执行更新操作
     * @param string $sql 需要执行的sql语句
     * @return int 成功时返回成功更新的数量,失败时返回false
     * @author winsen
     */
    public function update($sql)
    {
        $this->query($sql);

        return mysqli_affected_rows($this->conn);
    }

    /**
     * 提交sql语句查询第一个值
     * @param string $sql 需要执行的sql语句
     * @return mixed 成功时返回相应的值，失败时返回false
     * @author winsen
     */
    public function fetchOne($sql)
    {
        global $log;

        $this->validate();

        $log->record($sql);
        $result = mysqli_query($this->conn, $sql);
        if(!$result)
        {
            return null;
        }

        if($row = mysqli_fetch_row($result))
        {
            return $row[0];
        } else {
            return null;
        }
    }

    public function errmsg()
    {
        return mysqli_error($this->conn);
    }

    /**
     * 开始事务
     */
    public function begin()
    {
        $this->query('begin;');
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        $this->query('rollback;');
    }

    /**
     * 提交并结束事务
     */
    public function commit()
    {
        $this->query('commit;');
    }

    /**
     * 自动插入
     */
    public function autoInsert($table, $data)
    {
        global $log;
        $sql = 'insert into '.$this->table($table);

        $columns = '';
        $values = '';
        foreach($data as $i=>$d)
        {
            if($i == 0)
            {
                $columns = '`'.implode('`,`', array_keys($d)).'`';
            }

            if($values != '') {
                $values .= ',';
            }
            $values .= '(\''.implode('\',\'', $d).'\')';
        }

        $sql = $sql.' ('.$columns.') values '.$values;

        $log->record($sql);
        return $this->insert($sql);
    }

    /**
     * 自动更新插入
     */
    public function autoReplace($table, $data)
    {
        global $log;
        $sql = 'replace into '.$this->table($table);

        $columns = '';
        $values = '';
        foreach($data as $i=>$d)
        {
            if($i == 0)
            {
                $columns = '`'.implode('`,`', array_keys($d)).'`';
            }

            if($values != '') {
                $values .= ',';
            }
            $values .= '(\''.implode('\',\'', $d).'\')';
        }

        $sql = $sql.' ('.$columns.') values '.$values;

        $log->record($sql);
        return $this->insert($sql);
    }

    /**
     * 获取全部记录
     */
    public function all($table, $columns = array(), $condition = null, $limit = null, $order = array()) {
        $sql = 'select ';

        if(empty($columns) || $columns == '*') {
            $sql .= '*';
        } else {
            $express_columns = array();
            foreach($columns as $index => $column) {
                if(strpos($column, '(') !== false) {
                    array_push($express_columns, $column);
                    unset($columns[$index]);
                }
            }

            if(count($columns)) {
                $sql .= '`' . implode('`,`', $columns) . '`';
            }

            if(count($express_columns)) {
                $sql .= implode(',', $express_columns);
            }
        }

        $sql .= ' from '.$this->table($table).' where ';

        if(empty($condition)) {
            $sql .= '1';
        } else {
            $sql .= $this->condition_translator($condition);
        }

        if(is_array($order) && count($order)) {
            $sql .= ' order by ';

            $order_by_conditions = array();
            foreach($order as $order_by) {
                if(is_array($order_by)) {
                    if(count($order_by) == 1) {
                        array_push($order_by_conditions, '`'.$order_by[0].'` ASC');
                    } else {
                        array_push($order_by_conditions, '`'.$order_by[0].'` '.strtoupper($order_by[1]));
                    }
                } else {
                    array_push($order_by_conditions, $order_by);
                }
            }

            $sql .= implode(',', $order_by_conditions);
        }

        if($limit && is_string($limit)) {
            $sql .= ' limit '.$limit;
        }

        return $this->fetchAll($sql);
    }

    /**
     * 获取单条记录
     */
    public function find($table, $columns = array(), $condition = null) {
        $row = $this->all($table, $columns, $condition, 1);

        if($row) {
            return $row[0];
        }

        return $row;
    }

    /**
     * 获取一个字段的值
     */
    public function getColumn($table, $column, $condition = null) {
        $row = $this->all($table, array($column), $condition);

        if($row) {
            return $row[0][$column];
        }

        return $row;
    }

    /**
     * 自动插入一条记录
     *
     */
    public function create($table, $data, $filter = array()) {
        global $log;

        $sql = 'insert into '.$this->table($table);
        $columns = array();
        $values = array();
        $has_filter = !empty($filter);

        foreach($data as $column => $value) {
            if($has_filter) {
                if(!array_search($column, $filter)) {
                    continue;
                }
            }

            $column = $this->escape($column);
            $value = $this->escape($value);

            array_push($columns, '`'.$column.'`');
            array_push($values, '\''.$value.'\'');
        }

        if(count($columns) == count($values)) {
            $sql .= '('.implode(',', $columns).') values ('.implode(',', $values).')';

            $log->record($sql);

            return $this->query($sql);
        }

        return false;
    }

    /**
     * 删除记录
     * @param string $table 表名，不含前缀
     * @param array $conditions 条件
     * @param string $limit 格式为 offset, limit
     * @param array $order 排序依据，格式为 ['字段名', 'ASC|DESC']，如直接提供字段，则默认按升序排列
     * @return bool|resource
     */
    public function destroy($table, $conditions = array(), $limit = null, $order = array()) {
        $sql = 'delete from '.$this->table($table).' where ';

        if(empty($conditions)) {
            $sql .= '1';
        } else {
            $sql .= $this->condition_translator($conditions);
        }

        if(is_array($order) && count($order)) {
            $sql .= ' order by ';

            $order_by_conditions = array();
            foreach($order as $order_by) {
                if(is_array($order_by)) {
                    if(count($order_by) == 1) {
                        array_push($order_by_conditions, '`'.$order_by[0].'` ASC');
                    } else {
                        array_push($order_by_conditions, '`'.$order_by[0].'` '.strtoupper($order_by[1]));
                    }
                } else {
                    array_push($order_by_conditions, $order_by);
                }
            }

            $sql .= implode(',', $order_by_conditions);
        }

        if($limit && is_string($limit)) {
            $sql .= ' limit '.$limit;
        }

        return $this->delete($sql);
    }

    /**
     * 自动更新
     */
    public function autoUpdate($table, $data, $where = 1, $order = '', $limit = '')
    {
        global $log;

        $update = '';
        foreach($data as $key=>$value)
        {
            $value = $this->escape($value);
            $update .= '`'.$key.'`=\''.$value.'\',';
        }
        $update = substr($update, 0, strlen($update)-1);

        $sql = 'update '.$this->table($table).' set %s where %s %s %s';

        if($order != '')
        {
            $order = ' order by '.$order;
        }

        if($limit != '')
        {
            $limit = ' limit '.$limit;
        }

        $sql = sprintf($sql, $update, $where, $order, $limit);

        $log->record($sql);
        return $this->update($sql);
    }

    /**
     * 自动删除
     */
    public function autoDelete($table, $where)
    {
        global $log;

        $sql = 'delete from '.$this->table($table).' where '.$where;
        $log->record($sql);
        return $this->delete($sql);
    }

    protected function condition_translator($conditions = array()) {
        $where = '';
        if(!empty($conditions)) {
            foreach ($conditions as $column => $expression) {
                if ($where != '') {
                    $where .= ' and ';
                }

                $where .= '`' . $this->escape($column) . '`';

                if (is_array($expression)) {
                    $operation = $expression[0];
                    array_shift($expression);
                    $values = $expression;

                    if (empty($values)) {
                        $where .= '=\'' . $this->escape($operation) . '\'';
                    } else {
                        if (isset($this->operation_list[$operation])) {
                            $where .= $this->operation_list[$operation] . '\'' . $this->escape($values[0]) . '\'';
                        } else {
                            switch ($operation) {
                                case 'between':
                                    foreach($values[0] as &$_value) {
                                        $_value = $this->escape($_value);
                                    }
                                    $where .= ' between \'' . implode('\' and \'', $values[0]) . '\'';
                                    break;

                                case 'not in':
                                case 'in':
                                    foreach($values[0] as &$_value) {
                                        $_value = $this->escape($_value);
                                    }

                                    $where .= ' '.$operation.' (\''. implode('\',\'', $values[0]) .'\')';
                                    break;

                                case 'exp':
                                    $where .= $this->escape($values[0]);
                                    break;

                                case 'like':
                                    $where .= ' like \''.$this->escape($values[0]).'\'';
                                    break;

                                default:
                                    $where .= '=\'' . $this->escape($values[0]) . '\'';
                                    break;
                            }
                        }
                    }
                } else {
                    $where .= '=\'' . $this->escape($expression) . '\'';
                }
            }
        }

        return $where;
    }

    /**
     * 析构函数,服务器结束操作时关闭数据库链接
     */
    public function __destruct()
    {
        if($this->conn)
        {
            mysqli_close($this->conn);
        }
    }
}
