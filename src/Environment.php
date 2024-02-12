<?php

namespace Src;

/**
 * Environment C:\xampp\htdocs\persistent-framework-php\src
 * @link 
 * @author Roberto Dorado <robertodorado7@gmail.com>
 * @package Src
 */
class Environment
{
    public function loadImmutable()
    {
        $envFile = dirname(__DIR__) . '/.env';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        } else {
            die('.env file not found');
        }
    }
}
