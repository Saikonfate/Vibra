<?php

$usuario = 'root';
$senha = '';
$database = 'db_vibra';
$host = 'localhost';

$mysqli = new mysqli($host, $usuario, $senha, $database);

if($mysqli->error) {
    die("Falha ao conectaor ao banco de dados: " . $mysqli->error);
}