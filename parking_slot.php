<?php
include 'config.php';

// ambil data posisi & status dari database
$query = "
  SELECT s.slot_number, s.x, s.y, s.width, s.height, COALESCE(p.status, 'Free') AS status
  FROM parking_slots s
  LEFT JOIN parking_status p ON s.slot_number = p.slot_number
  ORDER BY s.slot_number ASC
";
$result = mysqli_query($conn, $query);
$slots = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Parking Layout - SmartPark</title>
  <meta http-equiv="refresh" content="5"> <!-- refresh otomatis -->
  <style>
    body {
      background-color: #1e1e1e;
      color: white;
      text-align: center;
      font-family: Arial, sans-serif;
    }

    .parking-area {
      position: relative;
      display: inline-block;
    }

    .parking-area img {
      width: 1000px; /* ubah sesuai resolusi carParkImg.png */
      border-radius: 8px;
    }

    .slot {
      position: absolute;
      border: 3px solid;
      border-radius: 6px;
      text-align: center;
      font-weight: bold;
      font-size: 14px;
      color: white;
      padding-top: 4px;
      box-sizing: border-box;
    }

    .occupied {
      border-color: red;
      background-color: rgba(255, 0, 0, 0.3);
    }

    .free {
      border-color: lime;
      background-color: rgba(0, 255, 0, 0.3);
    }

    h2 {
      margin-top: 20px;
      color: #fc9d22;
    }
  </style>
</head>
<body>

  <h2>Smart Parking Live Layout</h2>

  <div class="parking-area">
    <img src="Assets/carParkImg.png" alt="Parking Layout">

    <?php foreach ($slots as $slot): ?>
      <div
        class="slot <?php echo strtolower($slot['status']); ?>"
        style="
          left: <?php echo $slot['x']; ?>px;
          top: <?php echo $slot['y']; ?>px;
          width: <?php echo $slot['width']; ?>px;
          height: <?php echo $slot['height']; ?>px;
        "
      >
        <?php echo 'P-' . str_pad($slot['slot_number'], 2, '0', STR_PAD_LEFT); ?>
      </div>
    <?php endforeach; ?>
  </div>

</body>
</html>
