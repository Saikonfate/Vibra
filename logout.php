<?php

if(!Isset($_SESSION)) {
    session_start();
}

session_destroy();

header("Location: login.php");
?>