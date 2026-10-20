<?php
session_start();
unset($_SESSION['staff_id'], $_SESSION['staff_name'], $_SESSION['staff_role']);
header('Location: ../login.php');
exit;
