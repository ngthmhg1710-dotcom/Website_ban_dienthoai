<?php
session_start();
$_SESSION['test'] = 'Session hoạt động!';
echo $_SESSION['test'];
?>
