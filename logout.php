<?php

if(!Isset($_SESSION)) {
    session_start();
}

session_destroy();

header("Location: /PI.3/Login/login.php");
?>