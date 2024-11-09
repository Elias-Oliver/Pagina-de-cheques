<?php
$host = "localhost";
$user = "d42024";
$pass = "1234";
$db = "planilla";

// Conectar a la base de datos
$conexion = mysqli_connect($host, $user, $pass, $db);
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Inicializar variables para mensajes y datos de búsqueda
$mensaje = "";
$nombreProveedorBuscado = "";
$idProveedorBuscado = "";
$idProveedorEncontrado = "";

// Obtener el último ID de proveedor disponible
$sqlUltimoID = "SELECT MAX(idProveedor) AS ultimoId FROM proveedores";
$resultadoUltimoID = mysqli_query($conexion, $sqlUltimoID);
$ultimoId = 1; // Comenzar en 1 si no hay proveedores
if ($resultadoUltimoID && mysqli_num_rows($resultadoUltimoID) > 0) {
    $fila = mysqli_fetch_assoc($resultadoUltimoID);
    $ultimoId = $fila['ultimoId'] + 1; // Próximo ID disponible
}

// Procesar formulario de búsqueda de cheque
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buscar_proveedor'])) {
    $idProveedorBuscado = intval($_POST['idProveedorBuscado']); // Asegurarse de que sea un número entero

    // Consulta para buscar el cheque
    $sqlBuscar = "SELECT * FROM proveedores WHERE idProveedor = $idProveedorBuscado";
    $resultBuscar = mysqli_query($conexion, $sqlBuscar);

    if ($resultBuscar && mysqli_num_rows($resultBuscar) > 0) {
        $proveedor = mysqli_fetch_assoc($resultBuscar);
        $nombreProveedorBuscado = $proveedor['nombreProveedor'];
        $idProveedorEncontrado = $proveedor['idProveedor'];
        $mensaje = "Proveedor encontrado: $nombreProveedorBuscado";
    } else {
        $mensaje = "Proveedor con ID $idProveedorBuscado no encontrado.";
    }
}

// Procesar formulario de guardar/modificar proveedor
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_proveedor'])) {
    $nombreProveedor = trim($_POST['nombreProveedor']);
    $idProveedor = intval($_POST['idProveedor']); // Obtener ID del formulario

    if (!empty($nombreProveedor)) {
        $nombreProveedor = mysqli_real_escape_string($conexion, $nombreProveedor);

        // Verificar si el proveedor existe (si su ID es menor que el último ID)
        if ($idProveedor < $ultimoId) {
            // Actualizar el proveedor existente
            $sqlUpdate = "UPDATE proveedores SET nombreProveedor = '$nombreProveedor' WHERE idProveedor = $idProveedor";
            $mensaje = mysqli_query($conexion, $sqlUpdate) ? "Proveedor modificado exitosamente." : "Error al modificar el proveedor: " . mysqli_error($conexion);
        } else {
            // Insertar nuevo proveedor
            $sqlInsert = "INSERT INTO proveedores (nombreProveedor) VALUES ('$nombreProveedor')";
            $mensaje = mysqli_query($conexion, $sqlInsert) ? "Proveedor guardado exitosamente." : "Error al guardar el proveedor: " . mysqli_error($conexion);
        }

        // Reiniciar campos después de guardar/modificar
        $nombreProveedorBuscado = "";
        $idProveedorBuscado = "";
        $idProveedorEncontrado = "";
    } else {
        $mensaje = "El nombre del proveedor no puede estar vacío.";
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="diseñoprueba.css">
    <title>Proveedores</title>
</head>
<body>
    <h2>Gestión de Proveedores</h2>

    <?php if (!empty($mensaje)): ?>
        <p><?php echo $mensaje; ?></p>
    <?php endif; ?>

    <!-- Formulario para buscar proveedor por ID -->
    <form id="formBuscarProveedor" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
        <label for="idProveedorBuscado">Buscar Proveedor por ID:</label>
        <input type="text" id="idProveedorBuscado" name="idProveedorBuscado" placeholder="Ingrese el ID del proveedor" required>
        <button type="submit" name="buscar_proveedor">Buscar Proveedor</button>
    </form>

    <br>

    <!-- Formulario para gestionar proveedores -->
    <form id="formProveedores" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
        <!-- Campo Nombre del Proveedor -->
        <label for="nombreProveedor">Nombre del Proveedor:</label>
        <input type="text" id="nombreProveedor" name="nombreProveedor" placeholder="Ingrese el nombre del proveedor" value="<?php echo htmlspecialchars($nombreProveedorBuscado); ?>" required oninput="validarNombreProveedor()" <?php echo !empty($idProveedorEncontrado) ? 'readonly' : ''; ?>>

        <!-- Campo ID del Proveedor -->
        <label for="idProveedor">ID del Proveedor:</label>
        <input type="text" id="idProveedor" name="idProveedor" value="<?php echo !empty($idProveedorEncontrado) ? htmlspecialchars($idProveedorEncontrado) : htmlspecialchars($ultimoId); ?>" readonly>

        <!-- Campo oculto para detectar el botón de guardado -->
        <input type="hidden" name="guardar_proveedor" value="1">

        <div class="button-container">
            <button type="button" onclick="window.location.href='pruebacheque.php'">Ir a Cheque</button>
            <button type="submit">Guardar Proveedor</button>
            <button type="button" onclick="modificarProveedor()" id="btnModificar" disabled>Modificar</button>
            <button type="button" id="btnNuevoProveedor" onclick="nuevoProveedor(<?php echo $ultimoId; ?>)">Nuevo Proveedor</button>
        </div>
    </form>

    <script>
        // Validar que el nombre del proveedor solo acepte letras
        function validarNombreProveedor() {
            const nombreProveedor = document.getElementById("nombreProveedor");
            nombreProveedor.value = nombreProveedor.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
        }

        // Función para modificar proveedor
        function modificarProveedor() {
            const nombreProveedor = document.getElementById("nombreProveedor");
            nombreProveedor.removeAttribute("readonly"); // Hacer el campo editable
            nombreProveedor.focus(); // Enfocar el campo de nombre
        }

        // Función para manejar el botón "Nuevo Proveedor"
        function nuevoProveedor(siguienteId) {
            // Limpiar el campo de nombre y asignar el siguiente ID
            document.getElementById("nombreProveedor").value = "";
            document.getElementById("idProveedor").value = siguienteId;
            document.getElementById("nombreProveedor").removeAttribute("readonly"); // Asegurar que el campo esté editable
            document.getElementById("nombreProveedor").focus();
        }

        // Activar o desactivar botones según el resultado de la búsqueda
        <?php if (!empty($idProveedorEncontrado)): ?>
            document.getElementById("btnModificar").disabled = false;
        <?php endif; ?>
    </script>

</body>
</html>
