<?php
//author Lycradiata
session_start();
session_destroy();
header("Location: admin.php");
exit;
