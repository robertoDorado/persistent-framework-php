<?php

require __DIR__ . "/vendor/autoload.php";

use Src\Model;

$model = new Model();
$model->table = "operations";
$query = $model->select(["id", "active", "status"]);
var_dump($query->fetch());