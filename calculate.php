<?php
if(isset($_POST['name'], $_POST['weight'], $_POST['height'])) {
    $name = htmlspecialchars($_POST['name']);
    $weight = floatval($_POST['weight']);
    $height = floatval($_POST['height']);
    
    if($weight <= 0 || $height <= 0) {
        echo "Invalid input values.";
        exit;
    }
    
    $bmi = $weight / ($height * $height);
    
    if($bmi < 18.5) {
        $interpretation = "Underweight";
    } elseif($bmi < 25) {
        $interpretation = "Normal weight";
    } elseif($bmi < 30) {
        $interpretation = "Overweight";
    } else {
        $interpretation = "Obesity";
    }

    // تخزين البيانات في قاعدة البيانات
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'bmi_app';

    $conn = new mysqli($host, $user, $pass, $db);
    if($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO bmi_records (name, weight, height, bmi, interpretation) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdds", $name, $weight, $height, $bmi, $interpretation);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo "Hello, $name. Your BMI is " . number_format($bmi,2) . " ($interpretation).";
} else {
    echo "Data not received.";
}
?>
