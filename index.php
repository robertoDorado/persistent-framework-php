<?php

require __DIR__ . "/vendor/autoload.php";

use Src\Model;

$model = new Model();
$model->uses("operations");
$query = $model->select(["active", "SUM(balance) as total"])->where([
    ["balance", "<", 10000],
    ["balance", ">", 1000]
])->groupBy("active");

var_dump($query->fetch('all', true));