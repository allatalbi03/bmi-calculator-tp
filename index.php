<?php
require 'config.php';
session_start();


if(!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

$result = "";
$chart_data = [];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $weight = (float)$_POST['weight'];
    $height = (float)$_POST['height'];
    
    if($weight > 0 && $height > 0) {
        $bmi = $weight / ($height * $height);
        $bmi = round($bmi, 2);
        
        if($bmi < 18.5) {
            $interpretation = "Underweight";
            $alert_class = "warning";
        } elseif($bmi < 25) {
            $interpretation = "Normal weight";
            $alert_class = "success";
        } elseif($bmi < 30) {
            $interpretation = "Overweight";
            $alert_class = "info";
        } else {
            $interpretation = "Obesity";
            $alert_class = "danger";
        }
        
        // حفظ في الجلسة
        array_push($_SESSION['history'], [
            'date' => date('Y-m-d H:i'),
            'bmi' => $bmi,
            'status' => $interpretation
        ]);
        
        // حفظ في قاعدة البيانات
        $stmt = $pdo->prepare("INSERT INTO bmi_history (name, weight, height, bmi, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $weight, $height, $bmi, $interpretation]);
        
        $result = [
            'message' => "Hello, $name. Your BMI is $bmi ($interpretation).",
            'class' => $alert_class,
            'bmi' => $bmi
        ];
        
        // تحضير بيانات للرسم البياني
        $stmt = $pdo->query("SELECT date, bmi FROM bmi_history ORDER BY date DESC LIMIT 5");
        $chart_data = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced BMI Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .bmi-progress {
            height: 30px;
            border-radius: 15px;
        }
        .history-item {
            transition: all 0.3s;
        }
        .history-item:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="text-center">BMI Calculator</h2>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($result)): ?>
                            <div class="alert alert-<?= $result['class'] ?>">
                                <?= $result['message'] ?>
                                <div class="progress mt-3 bmi-progress">
                                    <div class="progress-bar progress-bar-striped bg-<?= $result['class'] ?>" 
                                         role="progressbar" 
                                         style="width: <?= min(100, $result['bmi']*3) ?>%" 
                                         aria-valuenow="<?= $result['bmi'] ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="50">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h4>BMI History</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>BMI</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach(array_reverse($_SESSION['history']) as $record): ?>
                                                <tr class="history-item">
                                                    <td><?= $record['date'] ?></td>
                                                    <td><?= $record['bmi'] ?></td>
                                                    <td><span class="badge bg-<?= 
                                                        $record['status'] == 'Underweight' ? 'warning' : 
                                                        ($record['status'] == 'Normal weight' ? 'success' : 
                                                        ($record['status'] == 'Overweight' ? 'info' : 'danger'))
                                                    ?>"><?= $record['status'] ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <?php if(!empty($chart_data)): ?>
                                <div class="mt-4">
                                    <canvas id="bmiChart"></canvas>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <form method="post" class="mt-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="weight" class="form-label">Weight (kg)</label>
                                <input type="number" step="0.1" class="form-control" id="weight" name="weight" required min="30" max="300">
                            </div>
                            <div class="mb-3">
                                <label for="height" class="form-label">Height (m)</label>
                                <input type="number" step="0.01" class="form-control" id="height" name="height" required min="1" max="2.5">
                                <div class="form-text">Example: 1.75 for 175 cm</div>
                            </div>
                           