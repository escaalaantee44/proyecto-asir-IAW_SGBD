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
include "includes/header.php";

if (!isset($_GET["id"])) {
    header("Location: recurso.php");
    exit();
}

$id = (int) $_GET["id"];

$sql = "SELECT * FROM recursos WHERE id_recurso = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$recurso = $result->fetch_assoc();

if (!$recurso) {
    die("Recurso no encontrado");
}

$categorias = $conn->query("SELECT * FROM categorias ORDER BY nombre");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $capacidad = (int) $_POST["capacidad"];
    $estado = $_POST["estado"];
    $id_categoria = (int) $_POST["id_categoria"];

    if ($nombre == "") {
        $error = "El nombre es obligatorio";
    } else {
        $sql = "UPDATE recursos
                SET nombre = ?, descripcion = ?, capacidad = ?, estado = ?, id_categoria = ?
                WHERE id_recurso = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisii", $nombre, $descripcion, $capacidad, $estado, $id_categoria, $id);
        if ($stmt->execute()) {
            header("Location: recurso.php");
            exit();
        } else {
            $error = "Error al actualizar el recurso";
        }
    }
}
?>

<div class="row">
    <div class="col-md-6">
        <h2>Editar recurso</h2>
        <a href="recurso.php" class="btn btn-secondary mb-3">Volver</a>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control"
                    value="<?php echo htmlspecialchars($recurso["nombre"]); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control"><?php echo htmlspecialchars($recurso["descripcion"]); ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Capacidad</label>
                <input type="number" name="capacidad" class="form-control"
                    value="<?php echo $recurso["capacidad"]; ?>" min="1">
            </div>

            <div class="mb-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="activo" <?php if ($recurso["estado"] == "activo") echo "selected"; ?>>Activo</option>
                    <option value="inactivo" <?php if ($recurso["estado"] == "inactivo") echo "selected"; ?>>Inactivo</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Categoría</label>
                <select name="id_categoria" class="form-select" required>
                    <?php while ($c = $categorias->fetch_assoc()) { ?>
                        <option value="<?php echo $c["id_categoria"]; ?>"
                            <?php if ($c["id_categoria"] == $recurso["id_categoria"]) echo "selected"; ?>>
                            <?php echo htmlspecialchars($c["nombre"]); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <button class="btn btn-primary">Guardar cambios</button>
        </form>
    </div>
</div>

<?php include "includes/footer.php"; ?>