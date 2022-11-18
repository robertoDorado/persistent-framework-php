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

    public function where($data)
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

        $clausule = '';
        $and = '';
        
        if (count($data) >= 2) {
            $and .= "AND";
        }
        
        foreach ($data as $key => $value) {

            if (!preg_match("/=/", $key)) {
                throw new \Exception('clausula where invalida');
            }

            if (preg_match("/^\d+$/", $value)) {
                $value = preg_replace("/''/", '', $value);
            }else {
                $value = "'{$value}'";
            }

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
            $data = array_map(function($item) {
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
        return new \PDO($this->dsn, $this->username, $this->password);
    }
}
