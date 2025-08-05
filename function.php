<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');
error_reporting(E_ALL);
file_put_contents('log.txt', "MÉTODO USADO: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

header('Content-Type: application/json');

include "./config/config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require './config/vendor/autoload.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    if (!$nombre || !$correo || !$mensaje) {
        throw new Exception('Todos los campos son obligatorios');
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Correo inválido');
    }

    // Conexión mysqli (si no está en config.php)
    // $connection = new mysqli('localhost', 'usuario', 'clave', 'base');
    if (!$connection || $connection->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

$stmt = $connection->prepare("INSERT INTO informacion (name, email, text, date) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("sss", $nombre, $correo, $mensaje);


    if (!$stmt->execute()) {
        throw new Exception("Error al insertar en base de datos: " . $stmt->error);
    }

    file_put_contents('log.txt', "INSERTADO EN BD\n", FILE_APPEND);

    // Enviar correo
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER; 
    $mail->Password = SMTP_PASS; 
    $mail->SMTPSecure = 'ssl'; 
    $mail->Port = 465;
    $mail->addBCC('proyectos@digital-xen.com'); 

    $mail->setFrom('info@digital-xen.com', 'Xen Digital');
    $mail->addAddress($correo, $nombre);

$mail->isHTML(true);
$mail->CharSet = 'UTF-8';
$mail->Subject = "Gracias por contactarnos, $nombre";
$mail->Body = <<<HTML

<div style="background-color: #00004f; padding: 20px; text-align: center;"><img style="width: 180px; margin-bottom: 10px;" src="https://digital-xen.com/uploads/img/logo-Xen-horizontal-blanco.png" alt="Xen Digital" /></div>

<div style="background-color: #ffffff; padding: 30px 25px; font-family: 'Segoe UI', sans-serif; font-size: 15px; color: #1c1c1c; line-height: 1.6;">
<p>Hola <strong>$nombre</strong>,</p>
<p>Gracias por escribirnos. Hemos recibido tu mensaje correctamente. En breve uno de nuestros asesores se pondr&aacute; en contacto contigo para darte seguimiento.</p>
<p>Si tienes dudas adicionales, puedes escribirnos a <a style="color: #00004f; text-decoration: none; font-weight: bold;" href="mailto:info@digital-xen.com">info@digital-xen.com</a>.</p>
<p style="margin-top: 30px;">Saludos,<br /><strong>Equipo Xen Digital</strong></p>
</div>

<div style="height: 4px; background-color: #ff4f9f;">&nbsp;</div>
<div style="background-color: #f1f1f1; padding: 15px; text-align: center; font-family: 'Segoe UI', sans-serif; font-size: 12px; color: #5f6368;">Xen Digital &middot; Soluciones digitales que impulsan tu marca<br /><a style="color: #00004f; text-decoration: none;" href="https://digital-xen.com">www.digital-xen.com</a></div>
HTML;



    $mail->send();
    file_put_contents('log.txt', "CORREO ENVIADO\n", FILE_APPEND);

    echo json_encode(['success' => true, 'message' => 'Mensaje enviado con éxito']);
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    file_put_contents('log.txt', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}

