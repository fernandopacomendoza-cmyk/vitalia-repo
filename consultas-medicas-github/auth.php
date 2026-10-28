<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/conexion.php';

function volverLogin(string $mensaje, bool $registro = false)
{
    $_SESSION['flash'] = $mensaje;
    header('Location: login.php' . ($registro ? '?registro=1' : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    volverLogin('La sesión expiró. Vuelve a intentarlo.', ($_POST['action'] ?? '') === 'signUp');
}

$action = $_POST['action'] ?? '';
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    volverLogin('Escribe un correo válido y una contraseña.', $action === 'signUp');
}

try {
    if ($action === 'signUp') {
        $firstName = trim($_POST['fName'] ?? '');
        $lastName = trim($_POST['lName'] ?? '');
        if ($firstName === '' || $lastName === '' || mb_strlen($firstName) > 100 || mb_strlen($lastName) > 100 || mb_strlen($password) < 8) {
            volverLogin('Completa todos los datos. La contraseña debe tener al menos 8 caracteres.', true);
        }
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) volverLogin('Ese correo ya tiene una cuenta. Inicia sesión.');

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $conn->prepare('INSERT INTO users (firstName, lastName, email, password) VALUES (?, ?, ?, ?)');
        $insert->bind_param('ssss', $firstName, $lastName, $email, $hash);
        $insert->execute();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$conn->insert_id;
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['email'] = $email;
        header('Location: cuenta.php');
        exit;
    }

    if ($action === 'signIn') {
        $stmt = $conn->prepare('SELECT id, firstName, lastName, email, password FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $valid = $user && password_verify($password, $user['password']);
        // Migra a password_hash si la cuenta ya tenía una contraseña MD5 del proyecto de referencia.
        if (!$valid && $user && preg_match('/^[a-f0-9]{32}$/i', $user['password']) && hash_equals(strtolower($user['password']), md5($password))) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update->bind_param('si', $hash, $user['id']);
            $update->execute();
            $valid = true;
        }
        if (!$valid) volverLogin('Correo o contraseña incorrectos.');

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['first_name'] = $user['firstName'];
        $_SESSION['last_name'] = $user['lastName'];
        $_SESSION['email'] = $user['email'];
        header('Location: cuenta.php');
        exit;
    }
    volverLogin('La solicitud no es válida.');
} catch (mysqli_sql_exception $e) {
    volverLogin('No se pudo completar el acceso. Verifica que la base de datos esté actualizada.');
}
