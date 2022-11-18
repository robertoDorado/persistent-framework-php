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
            }else {
                $value = "'{$value}'";
            }

            $this->values[] = $value;
            $clausule .= "{$this->dbname}.{$this->table}.{$key} {$value} {$and} ";
        }

        $clausule = preg_replace("/AND\s$/", '', $clausule);

        $this->query = "SELECT {$this->fields} FROM {$this->dbname}.{$this->table} WHERE {$clausule}";
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
