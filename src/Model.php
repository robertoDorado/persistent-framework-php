<?php
namespace Src;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Model C:\xampp\htdocs\persistent-class-php\src
 * @link 
 * @author Roberto Dorado <robertodorado7@gmail.com>
 * @package Src
 */
class Model
{
    private $dbName;

    private $query;

    public $table;

    private $fields;

    private $values = [];

    private $where;

    private $group_by;

    private $inner_join;

    private $data;

    private $keys = [];

    private $in;

    private $order_by;

    public $id = [];

    /**
     * Connection constructor
     */
    public function __construct()
    {
        Connection::pdo();
        if (preg_match("/dbname=[a-zA-Z0-9\-\.\_]+/", Connection::$dsn, $database)) {
            $this->dbName = $database[0];
        }
        $this->dbName = preg_replace("/dbname=/", '', $this->dbName);
    }

    public function orderBy(string $column, bool $reverse = false)
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        if ($reverse) {
            $this->order_by = "ORDER BY {$column} DESC";
        }else {
            $this->order_by = "ORDER BY {$column}";
        }

        $this->query = "SELECT {$this->fields} FROM 
        {$this->dbName}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function in (string $column, array $values)
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        if (!is_array($values)) {
            throw new \Exception('values não é um array');
        }

        foreach ($values as $v) {
            array_push($this->values, $v);
        }

        $values = array_map(function($item) {
            return "?";
        }, $values);

        if (preg_match("/WHERE/", $this->where)) {
            $this->in .= "AND {$column} IN (" . implode(', ', $values) . ")";
        }else {
            $this->in .= "WHERE {$column} IN (" . implode(', ', $values) . ")";
        }

        $this->query = "SELECT {$this->fields} FROM 
        {$this->dbName}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function delete(bool $debug = false)
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->query = "DELETE {$this->dbName}.{$this->table} FROM {$this->dbName}.{$this->table} 
        {$this->inner_join} {$this->where} {$this->in}";

        if ($debug) {
            return $this->debug();
        }else {
            try {
                $pdo = Connection::pdo()->prepare($this->query);
    
                if (!empty($this->values)) {
                    foreach ($this->values as $key => $value) {
                        if (!preg_match("/\d+/", $value)) {
                            $pdo->bindValue($key + 1, $value, PDO::PARAM_STR);
                        }else {
                            $pdo->bindValue($key + 1, $value, PDO::PARAM_INT);
                        }
                    }
                }
    
                if ($pdo->execute()) {
                    return true;
                } else {
                    return false;
                }
            }catch(PDOException $e) {
                return $e->getMessage();
            }

        }

    }

    public function save()
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        if (empty($this->data)) {
            throw new \Exception('os dados para inserir estão vazios');
        }

        
        $keys = array_keys($this->data);

        $binders = array_map(function ($item) {
            return ":{$item}";
        }, $keys);

        if (in_array("id", $keys)) {
            $params = '';
            foreach ($binders as $key => $value) {
                $key_param = preg_replace("/:{1}/", '', $value);
                if ($key_param != "id") {
                    $params .= "{$key_param} = {$value}, ";
                }
            }

            $params = preg_replace("/\,+\s$/", '', $params);

            $this->query = "UPDATE {$this->dbName}.{$this->table} SET {$params} WHERE id = :id";
            try {
                $pdo = Connection::pdo();
                $pdo->beginTransaction();
                $stmt = $pdo->prepare($this->query);
                foreach ($this->data as $key => $value) {
                    $stmt->bindValue(":{$key}", $value);
                }
    
                $stmt->execute();
                $pdo->commit();
            } catch (\PDOException $e) {
                Connection::pdo()->rollBack();
                return $e->getMessage();
            }
        }

        $this->query = "INSERT INTO {$this->dbName}.{$this->table}
        (" . implode(", ", $keys) . ")
        VALUES (" . implode(', ', $binders) . ")";

        try {
            $pdo = Connection::pdo();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare($this->query);
            foreach ($this->data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }

            $stmt->execute();
            $this->id = $pdo->lastInsertId();
            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            return $e->getMessage();
        }
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function findById(string $id, bool $array = false)
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        if (empty($this->fields)) {
            $this->query = "SELECT * FROM {$this->dbName}.{$this->table} WHERE id = :id";
        }else {
            $this->query = "SELECT {$this->fields} FROM {$this->dbName}.{$this->table} WHERE id = :id";
        }
        $pdo = Connection::pdo()->prepare($this->query);
        $pdo->bindValue(":id", $id);
        $pdo->execute();

        if ($pdo->rowCount() == 0) {
            throw new \Exception('não existem registros com esse id');
        }

        if ($array) {
            return $pdo->fetch(PDO::FETCH_ASSOC);
        } else {
            return $pdo->fetch(PDO::FETCH_OBJ);
        }
    }

    public function rightJoin(string $table_join, string $column_join, string $operator, string $table, string $column)
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->inner_join = "RIGHT JOIN {$this->dbName}.{$table_join} ON
        ({$this->dbName}.{$table_join}.{$column_join} {$operator} {$this->dbName}.{$table}.{$column})
        {$this->inner_join}";

        $this->query = "SELECT {$this->fields} FROM 
        {$this->dbName}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function leftJoin(string $table_join, string $column_join, string $operator, string $table, string $column)
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->inner_join = "LEFT JOIN {$this->dbName}.{$table_join} ON
        ({$this->dbName}.{$table_join}.{$column_join} {$operator} {$this->dbName}.{$table}.{$column})
        {$this->inner_join}";

        $this->query = "SELECT {$this->fields} FROM 
        {$this->dbName}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function join(string $table_join, string $column_join, string $operator, string $table, string $column)
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->inner_join = "INNER JOIN {$this->dbName}.{$table_join} ON
        ({$this->dbName}.{$table_join}.{$column_join} {$operator} {$this->dbName}.{$table}.{$column})
        {$this->inner_join}";

        $this->query = "SELECT {$this->fields} FROM 
        {$this->dbName}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function groupBy(string $column_name)
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->group_by = "GROUP BY {$column_name}";

        if (empty($this->fields)) {
            $this->query = "SELECT * FROM {$this->dbName}.{$this->table} 
            {$this->inner_join} {$this->where} {$this->in}
            {$this->group_by} {$this->order_by}";
        } else {
            $this->query = "SELECT {$this->fields} FROM {$this->dbName}.{$this->table} 
            {$this->inner_join} {$this->where} {$this->in}
            {$this->group_by} {$this->order_by}";
        }

        return $this;
    }

    public function count(string $alias = '', bool $obj = false, bool $debug = false)
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->query = "SELECT COUNT({$this->dbName}.{$this->table}.id) {$alias} FROM
        {$this->dbName}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";

        if ($debug) {
            return $this->query;
        }

        try {
            $pdo = Connection::pdo()->prepare($this->query);
            $pdo->execute();

            if ($pdo->rowCount() == 0) {
                throw new \Exception('não retornou resultados');
            }

            if ($obj) {
                return $pdo->fetch(PDO::FETCH_OBJ);
            } else {
                return $pdo->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function debug()
    {
        return ['query' => $this->query, 'params' => $this->values];
    }

    public function fetch($reference = 'all', $array = true)
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        if (empty($this->fields)) {
            throw new \Exception('os campos não podem estar vazio');
        }

        try {
            $pdo = Connection::pdo()->prepare($this->query);

            if (!empty($this->values)) {
                foreach ($this->values as $key => $value) {
                    if (!preg_match("/\d+/", $value)) {
                        $pdo->bindValue($key + 1, $value, PDO::PARAM_STR);
                    }else {
                        $pdo->bindValue($key + 1, $value, PDO::PARAM_INT);
                    }
                }
            }

            $pdo->execute();

            if ($pdo->rowCount() == 0) {
                return [];
            }

            $fetchStyle = [
                'first' => [
                    true => function(PDOStatement $pdo) {
                        return $pdo->fetch(PDO::FETCH_OBJ);
                    },
                    false => function (PDOStatement $pdo) {
                        return $pdo->fetch(PDO::FETCH_ASSOC);
                    }
                ],
                'all' => [
                    true => function(PDOStatement $pdo) {
                        return $pdo->fetchAll(PDO::FETCH_ASSOC);
                    },
                    false => function(PDOStatement $pdo) {
                        return $pdo->fetchAll(PDO::FETCH_OBJ);
                    }
                ],
            ];
            
            if (!empty($fetchStyle[$reference][$array])) {
                return $fetchStyle[$reference][$array]($pdo);
            } else {
                throw new \Exception('A referência ou a validação de array e objeto pode estar incorreta');
            }
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function where(array $data)
    {
        if (empty($this->dbName)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $clause = '';
        $and = '';

        if (count($data) > 1) {
            $and .= "AND";
        }

        foreach($data as $value) {

            $key = array_shift($value);
            $operator = array_shift($value);
            $value = implode('', $value);

            array_push($this->keys, "?");
            array_push($this->values, $value);

            $clause .= "{$key} {$operator} ? {$and} ";
        }

        $clause = preg_replace("/AND\s$/", '', $clause);
        $this->where = "WHERE " . $clause;

        $this->query = "SELECT {$this->fields} FROM {$this->dbName}.{$this->table}
        {$this->inner_join} {$this->where} {$this->in} {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function uses(string $table)
    {
        $this->table = $table;
        return $this;
    }

    public function select(array $data = [])
    {
        if (!is_array($data)) {
            throw new \Exception('obrigatório ser array o parametro');
        }

        if (empty($this->table)) {
            throw new \Exception('tabela não pode ser vazia');
        }

        if (empty($data)) {
            $fields = "*";
        } else {
            $fields = implode(', ', $data);
        }

        $this->fields = $fields;

        $this->query = "SELECT {$this->fields} FROM {$this->dbName}.{$this->table}";
        return $this;
    }
}
