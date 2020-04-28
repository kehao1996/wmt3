<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/12
 * Time: 11:46
 */
namespace App\Drive;
use Hyperf\Utils\ApplicationContext;
use Hyperf\DB\DB;
use Hyperf\DB\ConnectionInterface;

class Pdo extends ApplicationContext {

    public $db = null;
    private $_chained = array(
        'fields' => '*',
        'table'  => '',
        'order'  => null,
        'limit'  => null,
        'where'  => '',
        'index'  => null,
        'bind'   => array()
    );

    public function __construct (){
        $this->db = self::getContainer()->get(DB::class);
    }

    public function get(){
        return  self::getContainer()->get(DB::class);
    }


    /**
     * SELECT 语句的快捷方式
     *
     * @param string    $tableName    表名
     * @param array     $where        WHERE 条件数组，仅支持 AND 连接
     * @param string    $fields       要查询的字段，半角逗号分隔，如：field1, field2
     * @param string    $order        排序方法，如：someField DESC
     * @param int|array $limit        限制条数，可以是单个数字或者 array($offset, $num) 格式的数组
     * @return mixed
     */
    public function find($tableName, $where=array(), $fields='*', $order=null, $limit=null)
    {
        $tableName = str_replace(array('`','.'), array('','`.`'), $tableName);
        $bindVals  = array();
        if (is_string($where) && !empty($where))
            $_where = 'WHERE '.$where;
        elseif (is_array($where) && !empty($where))
            $_where = 'WHERE '. implode(' AND ', $this->_parseWhere($where, $bindVals));
        else
            $_where = '';
        $_order    = is_null($order) ? '' : 'ORDER BY '.$order;
        if (is_numeric($limit))
            $_limit = 'LIMIT '.intval($limit);
        elseif (is_array($limit) && count($limit) == 2)
            $_limit = sprintf('LIMIT %s, %s', $limit[0], $limit[1]);
        else
            $_limit = '';

        $sql = sprintf("SELECT %s FROM `%s` %s %s %s", $fields, $tableName, $_where, $_order, $_limit);
        $query = $this->prepare($sql);
        if (!($query instanceof PDOStatement)) return false;
        $res   = $query->execute($bindVals);
        if ($res !== true) return false;
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * 链式查询 - 选择字段
     *
     * @param string $fields 字段列表
     * @return $this
     */
    public function select($fields)
    {
        if (!is_array($fields))
            $fields = explode(',', $fields);
        foreach ($fields as &$field)
        {
            $field = trim($field);
            if (preg_match('/^[a-zA-Z0-9_]+$/', $field))
                $field = '`'.$field.'`';
        }
        unset($field);
        $this->_chained['fields'] = implode(',', $fields);
        return $this;
    }

    /**
     * 链式查询 - 选择表名
     *
     * @param string $tableName 表名
     * @return $this
     */
    public function from($tableName)
    {
        $this->_chained['table'] = str_replace(
            array('`','.'),
            array('','`.`'),
            $tableName
        );
        return $this;
    }


    /**
     * 链式查询 - 指定查询条件
     *
     * @param array $where 查询条件
     * @return $this
     */
    public function where($where)
    {
        if (is_string($where))
            $this->_chained['where'] = 'WHERE '.$where;
        elseif (is_array($where) && !empty($where))
            $this->_chained['where'] = 'WHERE '. implode(' AND ', $this->_parseWhere($where, $this->_chained['bind']));
        else
            $this->_chained['where'] = '';
        return $this;
    }

    /**
     * 链式查询 - 指定排序方法
     *
     * @param string $order 排序方法
     * @return $this
     */
    public function order($order)
    {
        $this->_chained['order'] = $order;
        return $this;
    }

    /**
     * 链式查询 - 指定 LIMIT 参数
     *
     * @param mixed   $offset  LIMIT 第一个参数，也可以写成 array($offset, $num) 以省略第二个参数
     * @param integer $num     LIMIT 第二个参数
     * @return $this
     */
    public function limit($offset, $num=null)
    {
        $limit = is_null($num) ? $offset : array($offset, $num);
        if (is_numeric($limit))
            $this->_chained['limit'] = intval($limit);
        elseif (is_array($limit) && count($limit) == 2)
            $this->_chained['limit'] = implode(',', $limit);
        return $this;
    }


    /**
     * 链式查询 - 获取某列数据
     *
     * @param int $col
     * @return array|bool
     */
    public function getColumn($col=0)
    {
        $index = $this->_chained['index'];
        if (is_null($index) && is_numeric($col))
            return $this->getAll();

        $ret  = array();
        $data = $this->getAll();
        if (is_null($index))
        {
            foreach ($data as $row)
                $ret[] = $row[$col];
        }
        else
        {
            foreach ($data as $row)
                $ret[$row[$index]] = $row[$col];
        }

        return $ret;
    }




    /**
     * 链式查询 - 重置查询参数
     */
    public function clear()
    {
        $this->_chained = array(
            'fields' => '*',
            'table'  => '',
            'order'  => null,
            'limit'  => null,
            'where'  => '',
            'index'  => null,
            'bind'   => array()
        );
        return $this;
    }


    /**
     * 链式查询 - 获取第一条数据
     *
     */
    public function getOne()
    {
        $res = $this->limit('1')->getAll();
        return (is_array($res) && !empty($res)) ? $res[0] : [];
    }

    /**
     * 链式查询 - 获取第一条数据的第一个字段值
     *
     * @return null
     */
    public function getValue()
    {
        $res = $this->getOne();
        $value = null;
        foreach($res as $v){
            if(!empty($v)){
                $value = $v;
            }
            break;
        }

        return $value;
    }

    /**
     * 链式查询 - 获取结果集
     *
     * @param int $style 记录格式，PDO::FETCH_*
     * @param int $arg 与 $style 对应的参数，目前只有 PDO::FETCH_COLUMN 需要
     * @return array|bool
     */
    public function getAll()
    {
        $opt = $this->_chained;
        $sql = sprintf("SELECT %s FROM `%s` %s %s %s",
            $opt['fields'],
            $opt['table'],
            $opt['where'],
            is_null($opt['order']) ? '' : 'ORDER BY '.$opt['order'],
            is_null($opt['limit']) ? '' : 'LIMIT '.$opt['limit']
        );
        $data = $this->db->query($sql,$opt['bind']);

        $index = $opt['index'];
        if (empty($data))
            return array();

        if (is_null($index))
            return $data;
        else
        {
            $ret = array();
            if (is_int($index))
            {
                $fields = ($opt['fields'] == '*')
                    ? array_keys($data[0])
                    : explode(',', $opt['fields']);
                $field  = $fields[$index];
            }
            else
                $field = $index;

            foreach ($data as $row)
                $ret[$row[$field]] = $row;

            return $ret;
        }
    }


    /**
     * INSERT 语句的快捷方式
     *
     * @param string $tableName 表名
     * @param array  $row       要写入的数组
     * @param array  $options   (可选) INSERT 选项，目前只支持 ignore
     * @return bool|int         成功则返回影响的行数，失败则返回 false
     */
    public function insert($tableName, $row, $options=array())
    {
        // 表名
        $tableName = str_replace(array('`','.'), array('','`.`'), $tableName);
        // 带`的字段名称列表
        $fields = array();
        // INSERT 选项
        $option = '';
        if (isset($options['ignore']) && $options['ignore'] == true)
        {
            $option = 'IGNORE';
        }
        // 绑定数据
        $bindVals = array();
        $bindKeys = array();
        foreach ($row as $key => $val)
        {
            $bindKeys[]        = ":$key";
            $fields[]          = "`{$key}`";
            $bindVals[":$key"] = $val;
        }

        $sql   = sprintf("INSERT %s INTO  `%s` (%s) VALUES (%s)", $option, $tableName, implode(', ', $fields), implode(', ', $bindKeys));

        try{
            $id = $this->db->insert($sql,$bindVals);
        }catch (\PDOException $e){
            var_dump($e->getMessage());
            return false;
        }

        return $id;
    }


    /**
     * DELETE 语句的快捷方式
     *
     * @param string $tableName 表名
     * @param array  $where     一个 WHERE 条件数组，仅支持 AND 连接
     * @return bool|int         成功执行则返回影响的行数，失败则返回 false
     */
    public function delete($tableName, $where)
    {
        if (empty($where)) return false;
        // 表名
        $tableName = str_replace(array('`','.'), array('','`.`'), $tableName);
        // WHERE 条件
        $bindVals = array();
        $whereSQL = $this->_parseWhere($where, $bindVals);

        $sql   = sprintf("DELETE FROM `%s` WHERE %s", $tableName, implode(' AND ', $whereSQL));

        try{
            $res   = $this->db->execute($sql,$bindVals);
        }catch (\PDOException $e){
            var_dump($e->getMessage());
            return false;
        }

        return $res;
    }

    public function update($table_name = '',$row = [],$where = []){

        if (empty($row) || empty($where)) return false;
        // 表名
        $tableName = str_replace(array('`','.'), array('','`.`'), $table_name);
        // 字段更新列表
        $fields = array();
        // 绑定数据
        $bindVals = array();
        foreach ($row as $key => $val)
        {
            if (is_array($val))
            {
                $fields[] = "`$key`=`{$val[0]}`{$val[1]}";
                continue;
            }
            $fields[] = "`$key`=:$key";
            $bindVals[":$key"] = $val;
        }
        // WHERE 条件
        $whereSQL = $this->_parseWhere($where, $bindVals);


        $sql   = sprintf("UPDATE `%s` SET %s WHERE %s", $tableName, implode(', ', $fields), implode(' AND ', $whereSQL));
        try{
            $query = $this->db->execute($sql,$bindVals);
        }catch (\PDOException $e){
            echo 'e<br>';
            var_dump($e->getMessage());
            return false;
        }

//        if(!$query){
//            var_dump($table_name,$row,$where);
//            echo 'falsenmot<br>';
//            var_dump($this->db->errorInfo());
//        }

        return $query;

//        return $sql;
//
//        if(!is_array($row)){
//            return false;
//        }
//
//        $sql = "UPDATE {$table_name} ";
//        $param = [];
//        foreach($row as $k=>$v){
//            $sql.="set {$k} = ? ";
//            $param[] = $v;
//        }
//
//        if (is_string($where) && !empty($where))
//            $_where = 'WHERE '.$where;
//        elseif (is_array($where) && !empty($where))
//            $_where = 'WHERE '. implode(' AND ', $this->_parseWhere($where, $bindVals));
//        else
//            $_where = '';
//
//        echo $sql;
//        var_dump($param);
    }

    private function _parseWhere($where, &$bindVals)
    {
        // 返回值
        $whereSQL  = array();
        // 绑定符号计数器
        $bindCnts  = array();
        // 支持的比较符号
        $operators = array('=','<','>','<>','>=','<=','IN','NOTIN','LIKE');

        foreach ($where as $key => $val)
        {
            if (gettype($key) == "integer")
            {
                $whereSQL[] = $val;
                continue;
            }

            $tmp = explode(' ', $key);
            if (count($tmp) == 1) $tmp[1] = '=';
            list($field, $operator) = $tmp;
            if (!in_array($operator, $operators)) continue;

            // 特殊处理 IN 查询
            if (strtoupper($operator) == 'IN')
            {
                if (gettype($val) == 'array')
                {
                    if (!empty($val))
                    {
                        $val = array_map(array($this, 'quote'), $val);
                        $whereSQL[] = sprintf("`%s` IN (%s)", $field, implode(",", $val));
                    }
                }
                elseif (gettype($val) == 'string')
                {

                    $whereSQL[] = sprintf("`%s` IN (%s)", $field, $val);
                }
                continue;
            } elseif(strtoupper($operator) == 'NOTIN')
            {
                if (gettype($val) == 'array')
                {
                    if (!empty($val))
                    {
                        $val = array_map(array($this, 'quote'), $val);
                        $whereSQL[] = sprintf("`%s` NOT IN (%s)", $field, implode(",", $val));
                    }
                }
                elseif (gettype($val) == 'string')
                {

                    $whereSQL[] = sprintf("`%s` NOT IN (%s)", $field, $val);
                }

                continue;
            }



            if (isset($bindCnts[$field]))
            {
                $bindNum = ++$bindCnts[$field];
            }
            else
            {
                $bindNum = $bindCnts[$field] = 1;
            }
            $bindKey = sprintf(':%s_%s', $field, $bindNum);
            $whereSQL[] = sprintf("`%s` %s %s", $field, $operator, $bindKey);
            $bindVals[$bindKey] = $val;
        }

        return $whereSQL;
    }


}
