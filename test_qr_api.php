<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ORIGIN'] = 'http://localhost:8000';
$_GET['action'] = 'generate-qr';

// fake session mock
session_start();
$_SESSION['user'] = ['id' => 1, 'role' => 'admin'];

// Mock input
$payload = json_encode(["amount" => 1.18, "currency" => "USD"]);
file_put_contents('php://memory', $payload);
// but api.php uses php://input

require 'backend/routes/api.php';
