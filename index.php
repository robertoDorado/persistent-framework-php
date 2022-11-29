<?php

/**
 * Connection Class
 * @link 
 * @author Roberto Dorado
 * @package Php\class
 */
class Conn
{
    private $dsn;

    private $username;

    private $password;

    private $query;

    private $dbname;

    private $table;

    private $fields;

    private $values = [];

    private $where;

    private $group_by;

    private $inner_join;

    private $data;

    private $options;

    private $keys = [];

    private $in;

    private $order_by;

    /**
     * Connection constructor
     */
    public function __construct($dsn, $username, $password, $options)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;

        if (preg_match("/dbname=[a-zA-Z0-9\-\.\_]+/", $this->dsn, $database)) {
            $this->dbname = $database[0];
        }
        $this->dbname = preg_replace("/dbname=/", '', $this->dbname);
    }

    public function orderBy(string $column, bool $reverse = false)
    {
        if (empty($this->dbname)) {
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
        {$this->dbname}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function in (string $column, array $values)
    {
        if (empty($this->dbname)) {
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
        {$this->dbname}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function delete()
    {
        if (empty($this->dbname)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        try {
            $pdo = $this->connection()->prepare($this->query);

            if (!empty($this->values)) {
                foreach ($this->values as $value) {
                    $pdo->bindValue(":{$value}", $value);
                }
            }

            $pdo->execute();

            if ($pdo->rowCount() == 0) {
                return [];
            }
        }catch(PDOException $e) {
            return $e->getMessage();
        }

        $this->query = "DELETE FROM {$this->dbname}.{$this->table} {$this->where}";
        try {
            $pdo = $this->connection()->prepare($this->query);

            if ($pdo->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            return $e->getMessage();
        }
    }

    public function save()
    {
        if (empty($this->dbname)) {
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

            $this->query = "UPDATE {$this->dbname}.{$this->table} SET {$params} WHERE id = :id";
            try {
                $pdo = $this->connection()->prepare($this->query);
                foreach ($this->data as $key => $value) {
                    $pdo->bindValue(":{$key}", $value);
                }
    
                if ($pdo->execute()) {
                    return true;
                } else {
                    return false;
                }
            } catch (\PDOException $e) {
                return $e->getMessage();
            }
        }

        $this->query = "INSERT INTO {$this->dbname}.{$this->table}
        (" . implode(", ", $keys) . ")
        VALUES (" . implode(', ', $binders) . ")";

        try {
            $pdo = $this->connection()->prepare($this->query);
            foreach ($this->data as $key => $value) {
                $pdo->bindValue(":{$key}", $value);
            }

            if ($pdo->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            return $e->getMessage();
        }
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function findById($id, $array = false)
    {
        if (empty($this->dbname)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->query = "SELECT {$this->fields} FROM {$this->dbname}.{$this->table} WHERE id = :id";
        $pdo = $this->connection()->prepare($this->query);
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
        if (empty($this->dbname)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->inner_join = "RIGHT JOIN {$this->dbname}.{$table_join} ON
        ({$this->dbname}.{$table_join}.{$column_join} {$operator} {$this->dbname}.{$table}.{$column})
        {$this->inner_join}";

        $this->query = "SELECT {$this->fields} FROM 
        {$this->dbname}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function leftJoin(string $table_join, string $column_join, string $operator, string $table, string $column)
    {
        if (empty($this->dbname)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->inner_join = "LEFT JOIN {$this->dbname}.{$table_join} ON
        ({$this->dbname}.{$table_join}.{$column_join} {$operator} {$this->dbname}.{$table}.{$column})
        {$this->inner_join}";

        $this->query = "SELECT {$this->fields} FROM 
        {$this->dbname}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function join(string $table_join, string $column_join, string $operator, string $table, string $column)
    {
        if (empty($this->dbname)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->inner_join = "INNER JOIN {$this->dbname}.{$table_join} ON
        ({$this->dbname}.{$table_join}.{$column_join} {$operator} {$this->dbname}.{$table}.{$column})
        {$this->inner_join}";

        $this->query = "SELECT {$this->fields} FROM 
        {$this->dbname}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function groupBy(string $column_name)
    {
        if (empty($this->dbname)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->group_by = "GROUP BY {$column_name}";

        if (empty($this->fields)) {
            $this->query = "SELECT * FROM {$this->dbname}.{$this->table} 
            {$this->inner_join} {$this->where} {$this->in}
            {$this->group_by} {$this->order_by}";
        } else {
            $this->query = "SELECT {$this->fields} FROM {$this->dbname}.{$this->table} 
            {$this->inner_join} {$this->where} {$this->in}
            {$this->group_by} {$this->order_by}";
        }

        return $this;
    }

    public function count(string $alias = '', bool $obj = false, bool $debug = false)
    {
        if (empty($this->dbname)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        $this->query = "SELECT COUNT({$this->dbname}.{$this->table}.id) {$alias} FROM
        {$this->dbname}.{$this->table} {$this->inner_join} {$this->where} {$this->in}
        {$this->group_by} {$this->order_by}";

        if ($debug) {
            return $this->query;
        }

        try {
            $pdo = $this->connection()->prepare($this->query);
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

    public function fetch($reference = 'all', $array = false)
    {
        if (empty($this->dbname)) {
            throw new \Exception('precisa declarar o banco de dados');
        }

        if (empty($this->table)) {
            throw new \Exception('precisa declarar o nome da tabela');
        }

        if (empty($this->fields)) {
            throw new \Exception('os campos não podem estar vazio');
        }

        try {
            $pdo = $this->connection()->prepare($this->query);

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

            if ($reference == 'first' && !$array) {
                return $pdo->fetch(PDO::FETCH_OBJ);
            } elseif ($reference == 'first' && $array) {
                return $pdo->fetch(PDO::FETCH_ASSOC);
            } elseif ($reference == 'all' && $array) {
                return $pdo->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($reference == 'all' && !$array) {
                return $pdo->fetchAll(PDO::FETCH_OBJ);
            } else {
                throw new \Exception('a referencia ou a validação de array e objeto pode estar incorreto');
            }
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function where(array $data)
    {
        if (empty($this->dbname)) {
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

        $this->query = "SELECT {$this->fields} FROM {$this->dbname}.{$this->table}
        {$this->inner_join} {$this->where} {$this->in} {$this->group_by} {$this->order_by}";
        return $this;
    }

    public function uses($table)
    {
        $this->table = $table;
        return $this;
    }

    public function select($data = [])
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

        $this->query = "SELECT {$this->fields} FROM {$this->dbname}.{$this->table}";
        return $this;
    }

    public function connection()
    {
        try {
            return new \PDO($this->dsn, $this->username, $this->password, $this->options);
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}
