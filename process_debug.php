<?php
// process_debug.php - Обработчик с полным логированием
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// ========== ЛОГИРУЕМ ВСЕ ДАННЫЕ ==========
$log_data = [
    'time' => date('Y-m-d H:i:s'),
    'get' => $_GET,
    'post' => $_POST,
    'server' => [
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? '',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? ''
    ]
];

file_put_contents('debug_process.log', print_r($log_data, true) . "\n---\n", FILE_APPEND);

echo "<h2>🔍 ОТЛАДКА PROCESS.PHP</h2>";

// Получаем данные
$full_name = trim($_GET['full_name'] ?? '');
$phone = trim($_GET['phone'] ?? '');
$email = trim($_GET['email'] ?? '');
$birth_date = trim($_GET['birth_date'] ?? '');
$gender = $_GET['gender'] ?? '';
$languages = $_GET['languages'] ?? [];
$biography = trim($_GET['biography'] ?? '');
$contract_accepted = isset($_GET['contract_accepted']) ? 1 : 0;

echo "<h3>Полученные данные:</h3>";
echo "<pre>";
echo "full_name: '$full_name'\n";
echo "phone: '$phone'\n";
echo "email: '$email'\n";
echo "birth_date: '$birth_date'\n";
echo "gender: '$gender'\n";
echo "languages: "; print_r($languages);
echo "biography: '" . substr($biography, 0, 100) . "'\n";
echo "contract_accepted: $contract_accepted\n";
echo "</pre>";

// Валидация
$errors = [];

if (empty($full_name)) $errors['full_name'] = 'ФИО обязательно';
if (empty($phone)) $errors['phone'] = 'Телефон обязателен';
if (empty($email)) $errors['email'] = 'Email обязателен';
if (empty($birth_date)) $errors['birth_date'] = 'Дата рождения обязательна';
if (empty($gender)) $errors['gender'] = 'Пол обязателен';
if (empty($languages)) $errors['languages'] = 'Выберите язык';
if (!$contract_accepted) $errors['contract_accepted'] = 'Примите контракт';

if (!empty($errors)) {
    echo "<h3 style='color:red'>❌ ОШИБКИ ВАЛИДАЦИИ:</h3>";
    echo "<pre>";
    print_r($errors);
    echo "</pre>";
    exit;
}

echo "<h3 style='color:green'>✅ Валидация пройдена</h3>";

// ========== СОХРАНЕНИЕ В БД ==========
try {
    echo "<h3>Сохранение в БД...</h3>";
    
    // Проверяем, есть ли таблица
    $check = $pdo->query("SHOW TABLES LIKE 'applications'");
    if ($check->rowCount() == 0) {
        die("❌ Таблица 'applications' НЕ СУЩЕСТВУЕТ!");
    }
    
    // Вставляем данные
    $sql = "INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, contract_accepted) 
            VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':full_name' => $full_name,
        ':phone' => $phone,
        ':email' => $email,
        ':birth_date' => $birth_date,
        ':gender' => $gender,
        ':biography' => $biography,
        ':contract_accepted' => $contract_accepted
    ]);
    
    if ($result) {
        $id = $pdo->lastInsertId();
        echo "<h3 style='color:green'>✅ ЗАПИСЬ СОЗДАНА! ID: $id</h3>";
        
        // Проверяем, что запись действительно есть
        $check = $pdo->query("SELECT * FROM applications WHERE id = $id")->fetch();
        echo "<pre>";
        print_r($check);
        echo "</pre>";
        
        // Генерируем логин и пароль
        $login = strtolower(preg_replace('/[^a-zA-Z]/', '', $full_name));
        $login = substr($login, 0, 8) . '_' . rand(100, 999);
        $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%'), 0, 12);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Сохраняем логин и пароль
        $updateStmt = $pdo->prepare("UPDATE applications SET login = :login, password_hash = :hash WHERE id = :id");
        $updateStmt->execute([
            ':login' => $login,
            ':hash' => $password_hash,
            ':id' => $id
        ]);
        
        echo "<h3 style='color:green'>✅ Логин и пароль сохранены!</h3>";
        echo "🔑 Логин: <strong>$login</strong><br>";
        echo "🔒 Пароль: <strong>$password</strong><br>";
        
    } else {
        echo "<h3 style='color:red'>❌ ОШИБКА ВСТАВКИ</h3>";
        print_r($stmt->errorInfo());
    }
    
} catch (PDOException $e) {
    echo "<h3 style='color:red'>❌ ОШИБКА БД: " . $e->getMessage() . "</h3>";
    echo "Код: " . $e->getCode() . "<br>";
}
?>