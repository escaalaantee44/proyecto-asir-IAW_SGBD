<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION["rol"] != "admin") {
    die("Acceso denegado");
}
include "conexion.php";

if (!isset($_GET["id"])) {
    header("Location: recurso.php");
    exit();
}

$id = (int) $_GET["id"];

$sql = "DELETE FROM recursos WHERE id_recurso = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location:recurso.php");
    exit();
} else {
    die("No se pudo eliminar el recurso. Es posible que tenga reservas asociadas.");
}
