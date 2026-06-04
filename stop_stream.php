<?php
// stop_stream.php
set_time_limit(5);

// 1. Cari PID yang menggunakan port 5001 (Flask)
$pid_output = shell_exec('netstat -ano | findstr :5001');
preg_match('/\s(\d+)\s*$/', $pid_output, $matches);
$pid = $matches[1] ?? null;

if ($pid) {
    // Gunakan taskkill dengan /F (Force) dan /T (Tree) untuk memastikan semua proses terkait mati
    $kill_command = "taskkill /PID $pid /F /T > NUL 2>&1";
    shell_exec($kill_command);
    
    // Tambahkan jeda singkat untuk memastikan sistem OS memproses kill command
    usleep(50000); // Tunggu 50ms (Opsional, tapi membantu stabilitas)

    echo json_encode(["status" => "success", "message" => "Streaming stopped. PID: $pid"]);
} else {
    echo json_encode(["status" => "error", "message" => "No process found on port 5001."]);
}
?>