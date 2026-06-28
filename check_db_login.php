<?php
// check_db_login.php - Проверка БД для login.php
require_once 'config.php';

echo "<h2>Проверка БД для входа</h2>";

try {
    // 1. Какая БД используется
    $db = $pdo->query("SELECT DATABASE() as db")->fetch();
    echo "✅ Текущая БД: <strong>" . $db['db'] . "</strong><br><br>";
    
    // 2. Проверяем таблицу applications
    $stmt = $pdo->query("SHOW TABLES LIKE 'applications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Таблица 'applications' существует<br>";
        
        // 3. Проверяем, есть ли логины и пароли
        $users = $pdo->query("SELECT id, full_name, login, password_hash FROM applications WHERE login IS NOT NULL")->fetchAll();
        
        if (empty($users)) {
            echo "❌ НЕТ ПОЛЬЗОВАТЕЛЕЙ С ЛОГИНАМИ!<br>";
            echo "Нужно зарегистрироваться через форму index.php<br>";
        } else {
            echo "✅ Найдено пользователей с логинами: " . count($users) . "<br>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>ФИО</th><th>Логин</th><th>Пароль (хеш)</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . $user['full_name'] . "</td>";
                echo "<td>" . $user['login'] . "</td>";
                echo "<td>" . substr($user['password_hash'], 0, 20) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "❌ Таблица 'applications' НЕ СУЩЕСТВУЕТ!<br>";
        echo "Нужно создать таблицы в БД " . $db['db'];
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>