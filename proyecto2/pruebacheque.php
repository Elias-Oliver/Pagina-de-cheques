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

// Obtener el último número de cheque disponible
$sqlUltimoChequeID = "SELECT MAX(numeroCheque) AS ultimoNumero FROM cheques";
$resultadoUltimoChequeID = mysqli_query($conexion, $sqlUltimoChequeID);
$ultimoNumeroCheque = 1; // Comenzar en 1 si no hay cheques
if ($resultadoUltimoChequeID && mysqli_num_rows($resultadoUltimoChequeID) > 0) {
    $fila = mysqli_fetch_assoc($resultadoUltimoChequeID);
    $ultimoNumeroCheque = $fila['ultimoNumero'] + 1; // Próximo número de cheque disponible
}

// Inicializar variables para mensajes y campos
$mensaje = "";
$numeroCheque = $ultimoNumeroCheque;
$proveedorSeleccionado = "";
$monto = "";
$montoLetras = "";
$observaciones = "";

// Procesar formulario de búsqueda de cheque
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buscar_cheque'])) {
    $idchequeBuscado = intval($_POST['idchequeBuscado']);
    $sqlBuscar = "SELECT * FROM cheques WHERE numeroCheque = $idchequeBuscado";
    $resultBuscar = mysqli_query($conexion, $sqlBuscar);

    if ($resultBuscar && mysqli_num_rows($resultBuscar) > 0) {
        $cheques = mysqli_fetch_assoc($resultBuscar);
        $numeroCheque = $cheques['numeroCheque'];
        $proveedorSeleccionado = $cheques['proveedor'];
        $monto = $cheques['Monto'];
        $montoLetras = $cheques['MontoLetras'];
        $observaciones = $cheques['observaciones'];
        $mensaje = "Cheque encontrado: $numeroCheque";
    } else {
        $mensaje = "Cheque con ID $idchequeBuscado no encontrado.";
    }
}

// Procesar formulario de cheque
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_cheque'])) {
    $numeroCheque = intval($_POST['numeroCheque']);
    $proveedor = mysqli_real_escape_string($conexion, $_POST['proveedor']);
    $monto = mysqli_real_escape_string($conexion, $_POST['Monto']);
    $montoLetras = mysqli_real_escape_string($conexion, $_POST['MontoLetras']);
    $observaciones = mysqli_real_escape_string($conexion, $_POST['observaciones']);

    $sqlInsertCheque = "INSERT INTO cheques (numeroCheque, proveedor, Monto, MontoLetras, observaciones) 
                        VALUES ('$numeroCheque', '$proveedor', '$monto', '$montoLetras', '$observaciones')";
    
    if (mysqli_query($conexion, $sqlInsertCheque)) {
        $mensaje = "Cheque registrado exitosamente.";
        $ultimoNumeroCheque++; // Incrementar el último número
        $numeroCheque = $ultimoNumeroCheque; // Preparar para el siguiente cheque
    } else {
        $mensaje = "Error al registrar el cheque: " . mysqli_error($conexion);
    }
}

// Procesar anulación de cheque
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['anular_cheque'])) {
    $numeroCheque = intval($_POST['numeroCheque']);
    $sqlEliminarCheque = "DELETE FROM cheques WHERE numeroCheque = $numeroCheque";
    if (mysqli_query($conexion, $sqlEliminarCheque)) {
        $mensaje = "Cheque anulado exitosamente.";
        $proveedorSeleccionado = "";
        $monto = "";
        $montoLetras = "";
        $observaciones = "";
        $numeroCheque = $ultimoNumeroCheque; // Reiniciar el número para el siguiente cheque
    } else {
        $mensaje = "Error al anular el cheque: " . mysqli_error($conexion);
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
    <script src="jquery3-4.min.js" type="text/javascript"></script>
    <title>Registrar Cheque</title>
</head>
<body>

    <h2>Registrar Cheque</h2>

    <?php if (!empty($mensaje)): ?>
        <p><?php echo $mensaje; ?></p>
    <?php endif; ?>

       <!-- Contenedor para los formularios -->
       <div class="container">
        <!-- Formulario para buscar cheque por ID -->
        <form id="formBuscarCheque" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <label for="idchequeBuscado">Buscar Cheque por ID:</label>
            <input type="text" id="idchequeBuscado" name="idchequeBuscado" placeholder="Ingrese el ID del cheque" required>
            <button type="submit" name="buscar_cheque">Buscar Cheque</button>
        </form>

        <!-- Formulario para registrar cheque -->
        <form id="formCheque" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div>
                <label for="numeroCheque">Número del Cheque:</label>
                <input type="number" id="numeroCheque" name="numeroCheque" value="<?php echo $numeroCheque; ?>" readonly>
            </div>

            <!-- Campo Proveedor (Desplegable) -->
            <label for="proveedor">Proveedor:</label>
            <select id="proveedor" name="proveedor" required>
                <option value="">Seleccione un proveedor</option>
                <?php
                $conexion = mysqli_connect($host, $user, $pass, $db);
                $busca_proveedor = mysqli_query($conexion, "SELECT * FROM proveedores");
                while($proveedor_encontrado = mysqli_fetch_assoc($busca_proveedor)) {
                    $selected = ($proveedorSeleccionado == $proveedor_encontrado['idProveedor']) ? 'selected' : '';
                    echo "<option value='". $proveedor_encontrado["idProveedor"]."' $selected>".$proveedor_encontrado["nombreProveedor"]."</option>";
                }
                $conexion->close();
                ?>
            </select>

            <!-- Campos Monto y Monto en Letras -->
            <div class="Monto-container">
                <div>
                    <label for="Monto">Monto:</label>
                    <input type="text" id="Monto" name="Monto" placeholder="Ingrese el Monto en números" value="<?php echo isset($monto) ? $monto : ''; ?>" required oninput="validarMonto(); convertirEnLetras()">
                </div>
                <div>
                    <label for="MontoLetras">Monto en Letras:</label>
                    <input type="text" id="MontoLetras" name="MontoLetras" value="<?php echo isset($montoLetras) ? $montoLetras : ''; ?>" readonly>
                </div>
            </div>

            <!-- Campo Observaciones -->
            <label for="observaciones">Observaciones:</label>
            <textarea id="observaciones" name="observaciones" rows="4" maxlength="500" placeholder="Ingrese las observaciones" oninput="validarObservaciones()" required><?php echo isset($observaciones) ? $observaciones : ''; ?></textarea>
            <span id="contadorObservaciones">0/500</span>

            <!-- Botones Enviar, Ir a Proveedores y Anular Cheque -->
            <div class="button-container">
                <button type="submit" name="guardar_cheque">Enviar Cheque</button>
                <button type="button" onclick="window.location.href='proveedores.php'">Ir a Proveedores</button>
                <button type="submit" name="anular_cheque">Anular Cheque</button>
                <button type="button" id="btnlimpiarcheque" onclick="limpiarCheque(<?php echo $ultimoNumeroCheque; ?>)">Limpiar Cheque</button>
            </div>
        </form>
    </div>

    <div id="mensajeExito" style="display:none; color: green; margin-top: 20px;">Cheque registrado exitosamente.</div>

    <script>
        // Validar que el monto solo acepte números y un punto decimal
        function validarMonto() {
            const Monto = document.getElementById("Monto");
            let valor = Monto.value;
            valor = valor.replace(/[^0-9.]/g, '');
            const partes = valor.split('.');
            if (partes.length > 2) {
                valor = partes[0] + '.' + partes[1];
            }
            if (partes[1] && partes[1].length > 2) {
                valor = partes[0] + '.' + partes[1].slice(0, 2);
            }
            Monto.value = valor;
        }
        // para convertir el monto a letras 
        function convertirEnLetras() {
    const Monto = document.getElementById("Monto").value;
    const MontoLetras = document.getElementById("MontoLetras");

    MontoLetras.value = numeroALetras(Monto);
}

  // Función para convertir número a letras (de 1 a 5000) incluyendo decimales
  function numeroALetras(num) {
            const partes = num.split(".");
            const parteEntera = parseInt(partes[0]);
            const parteDecimal = partes[1] ? parseInt(partes[1]) : 0;

            const unidades = ['cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
            const decenas = ['diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
            const especiales = ['once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
            const centenas = ['cien', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

            function convertirEntero(num) {
                if (num < 10) return unidades[num];
                if (num === 10) return decenas[0];  // Solucionar el caso especial de 10
                if (num >= 10 && num < 20) return especiales[num - 11];
                if (num >= 20 && num < 100) {
                    let dec = Math.floor(num / 10);
                    let uni = num % 10;
                    return uni === 0 ? decenas[dec - 1] : decenas[dec - 1] + ' y ' + unidades[uni];
                }
                if (num >= 100 && num < 1000) {
                    let cent = Math.floor(num / 100);
                    let resto = num % 100;
                    if (resto === 0) return cent === 1 ? 'cien' : centenas[cent - 1];
                    return cent === 1 ? 'ciento ' + convertirEntero(resto) : centenas[cent - 1] + ' ' + convertirEntero(resto);
                }
                if (num >= 1000 && num <= 5000) {
                    let mil = Math.floor(num / 1000);
                    let resto = num % 1000;
                    // Si el resto es 0, no se debe agregar "cero"
                    if (resto === 0) {
                        return mil === 1 ? 'mil' : convertirEntero(mil) + ' mil';
                    }
                    return mil === 1 ? 'mil ' + convertirEntero(resto) : convertirEntero(mil) + ' mil ' + convertirEntero(resto);
                }
                return "el limite es 5000";
            }

            let resultado = convertirEntero(parteEntera);

            if (parteDecimal > 0) {
                resultado += " con " + convertirEntero(parteDecimal) + " centavos";
            }

            return resultado;
        }


        // Contar caracteres en observaciones
        document.getElementById("observaciones").addEventListener("input", function() {
            const contador = document.getElementById("contadorObservaciones");
            const length = this.value.length;
            contador.textContent = length + "/500";
        });

        // Función para limpiar campos
        function limpiarCheque(numeroCheque) {
            document.getElementById("numeroCheque").value = numeroCheque;
            document.getElementById("proveedor").value = "";
            document.getElementById("Monto").value = "";
            document.getElementById("MontoLetras").value = "";
            document.getElementById("observaciones").value = "";
            document.getElementById("contadorObservaciones").textContent = "0/500";
        }
    </script>
</body>
</html>
