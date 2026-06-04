<?php
// start_stream.php

$project_root = "C:\\xampp\\htdocs\\smart_parking\\";
// Ganti dengan path Python yang sudah dikoreksi ke lokasi umum (misalnya C:\\Python313\\python.exe)
$python_interpreter = "C:\\Python313\\python.exe"; 
$python_script = $project_root . "detect_parking_stream.py";

// Perintah START: Jalankan di background dan silent
$command = "CHDIR /D " . escapeshellarg($project_root) . " && start /b " . escapeshellarg($python_interpreter) . " " . escapeshellarg($python_script) . " > NUL 2>&1";
shell_exec($command);

echo json_encode(["status" => "success", "message" => "Streaming started"]);
?>