<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}
include "conexion.php";

$id_usuario = $_SESSION["id_usuario"];
$recursos = $conn->query("SELECT * FROM recursos ORDER BY nombre");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_recurso = (int) $_POST["id_recurso"];
    $fecha = $_POST["fecha"];
    $hora_inicio = $_POST["hora_inicio"];
    $hora_fin = $_POST["hora_fin"];

    if ($hora_fin <= $hora_inicio) {
        $error = "La hora de fin debe ser posterior a la de inicio.";
    } else {
        try {
            $sql = "CALL crear_reserva(?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisss", $id_usuario, $id_recurso, $fecha, $hora_inicio, $hora_fin);
            $stmt->execute();
            header("Location: reserva.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            $error = "Error al crear la reserva: " . $e->getMessage();
        }
    }
}

$titulo = "Nueva reserva";
include "includes/header.php";
?>

<div class="mb-4">
    <a href="reserva.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Nueva reserva</h5>
            </div>
            <div class="card-body p-4">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Recurso <span class="text-danger">*</span></label>
                        <select name="id_recurso" class="form-select" required>
                            <option value="">-- Selecciona un recurso --</option>
                            <?php while ($r = $recursos->fetch_assoc()): ?>
                                <option value="<?php echo $r["id_recurso"]; ?>">
                                    <?php echo htmlspecialchars($r["nombre"]); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Hora inicio <span class="text-danger">*</span></label>
                            <input type="time" name="hora_inicio" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Hora fin <span class="text-danger">*</span></label>
                            <input type="time" name="hora_fin" class="form-control" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold">
                            <i class="bi bi-check-lg me-1"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>