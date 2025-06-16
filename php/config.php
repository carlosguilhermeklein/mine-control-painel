<?php
// Verificar se o sistema foi instalado
if (!file_exists(__DIR__ . '/installed.lock')) {
    // Redirecionar para instalação se não foi instalado
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        header('Location: install.php');
        exit;
    } else {
        // Para requisições AJAX, retornar erro JSON
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Sistema não instalado. Acesse install.php']);
        exit;
    }
}

// Se chegou aqui, o sistema foi instalado e o arquivo config.php foi gerado
// O conteúdo real será gerado durante a instalação
?>