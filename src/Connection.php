<?php
namespace Src;

use PDO;
use PDOException;

/**
 * Connection C:\xampp\htdocs\persistent-framework-php\src
 * @link 
 * @author Roberto Dorado <robertodorado7@gmail.com>
 * @package Src
 */
class Connection
{
    public static $dsn;

    public static function pdo()
    {
        Environment::loadImmutable();
        self::$dsn = 'mysql:host=' . $_ENV['DB_HOST'] 
            . ';port=' . $_ENV['PORT'] . ';dbname=' . $_ENV['DB_NAME'] . '';
        try {
            return new PDO(self::$dsn, $_ENV['USERNAME'], $_ENV['PASSWORD'], [
                PDO::ATTR_ERRMODE, 
                PDO::ERRMODE_EXCEPTION
            ]);
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}
