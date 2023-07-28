<?php
require 'vendor/autoload.php';

use RTech\RCarApi\Database;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$dbConnection = (new Database())->connet();