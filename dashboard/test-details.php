<?php
session_start();
$_SESSION['user_id'] = 4; // Use tenant 1 admin
$_SESSION['tenant_id'] = 1;
$_GET['id'] = 1;

include 'get-booking-details.php';
