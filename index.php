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

    private $values;

    private $where;

    private $group_by;

    /**
     * Connection constructor
     */
    public function __construct($dsn, $username, $password)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;

        if (preg_match("/dbname=[a-zA-Z0-9\-\.\_]+/", $this->dsn, $database)) {
            $this->dbname = $database[0];
        }
        $this->dbname = preg_replace("/dbname=/", '', $this->dbname);
    }

    public function groupBy(string $column_name, string $data = 'all', bool $obj = false, bool $debug = false)
    {
        if (empty($this->dbname)) {
            throw new \Exception('banco não pode estar vazio');
        }

        if (empty($this->table)) {
            throw new \Exception('tabela não pode estar vazio');
        }

        $this->group_by = "{$this->dbname}.{$this->table}.{$column_name}";

        if (empty($this->fields)) {
            $this->query = "SELECT * FROM {$this->dbname}.{$this->table} {$this->where} 
            GROUP BY {$this->group_by}";
        }else {
            $this->query = "SELECT {$this->fields} FROM {$this->dbname}.{$this->table} {$this->where} 
            GROUP BY {$this->group_by}";
        }

        if ($debug) {
            return $this->query;
        }

        try {
            $pdo = $this->connection()->prepare($this->query);
            $pdo->execute();

            if ($pdo->rowCount() == 0) {
                throw new \Exception('não retornou resultados');
            }

            if ($obj && $data == 'first') {
                return $pdo->fetch(PDO::FETCH_OBJ);
            } elseif ($obj && $data == 'all') {
                return $pdo->fetchAll(PDO::FETCH_OBJ);
            } elseif (!$obj && $data == 'first') {
                return $pdo->fetch(PDO::FETCH_ASSOC);
            } elseif (!$obj && $data == 'all') {
                return $pdo->fetchAll(PDO::FETCH_ASSOC);
            }

        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function count(string $alias = '', bool $obj = false, bool $debug = false)
    {
        if (empty($this->dbname)) {
            throw new \Exception('banco não pode estar vazio');
        }

        if (empty($this->table)) {
            throw new \Exception('tabela não pode estar vazio');
        }

        $this->query = "SELECT COUNT({$this->dbname}.{$this->table}.id) {$alias} FROM {$this->dbname}.{$this->table} {$this->where}";

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
        return $this->query;
    }

    public function fetch($reference = 'all', $array = false)
    {
        if (empty($this->dbname)) {
            throw new \Exception('banco não pode estar vazio');
        }

        if (empty($this->table)) {
            throw new \Exception('tabela não pode estar vazio');
        }

        if (empty($this->fields)) {
            throw new \Exception('os campos não podem estar vazio');
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
                throw new \Exception('a query não retornou nenhum resultado');
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

    public function where($data)
    {
        if (empty($this->dbname)) {
            throw new \Exception('banco não pode estar vazio');
        }

        if (empty($this->table)) {
            throw new \Exception('tabela não pode estar vazio');
        }

        $clausule = '';
        $and = '';

        if (count($data) >= 2) {
            $and .= "AND";
        }

        foreach ($data as $key => $value) {

            if (preg_match("/^\d+$/", $value)) {
                $value = preg_replace("/''/", '', $value);
            } elseif (preg_match("/()/", $value)) {
                $value = preg_replace("/''/", '', $value);
            } else {
                $value = "'{$value}'";
            }

            $this->values[] = $value;
            $clausule .= "{$this->dbname}.{$this->table}.{$key} {$value} {$and} ";
        }

        $clausule = preg_replace("/AND\s$/", '', $clausule);
        $this->where = "WHERE " . $clausule;

        $this->query = "SELECT {$this->fields} FROM {$this->dbname}.{$this->table} {$this->where}";
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
            $fields = "{$this->dbname}.{$this->table}.*";
        } else {
            $data = array_map(function ($item) {
                return "{$this->dbname}.{$this->table}.{$item}";
            }, $data);

            $fields = implode(', ', $data);
        }

        $this->fields = $fields;

        $this->query = "SELECT {$this->fields} FROM {$this->dbname}.{$this->table}";
        return $this;
    }


    /**
     * conexão
     *
     * @return mixed
     */
    public function connection()
    {
        try {
            return new \PDO($this->dsn, $this->username, $this->password);
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}
