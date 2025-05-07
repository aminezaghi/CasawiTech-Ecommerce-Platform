<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
$conn = getPDO();

function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate inputs
    $name = validateInput($_POST['name']);
    $prix = floatval($_POST['prix']);
    $category = validateInput($_POST['category']);
    $description = validateInput($_POST['description']);
    $image = validateInput($_POST['image']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $old_price = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null;

    // Enhanced validation
    $errors = [];

    if (empty($name)) {
        $errors[] = "Product name cannot be empty.";
    }

    if ($prix <= 0) {
        $errors[] = "Price must be greater than zero.";
    }

    if ($stock_quantity < 0) {
        $errors[] = "Stock quantity cannot be negative.";
    }

    if (!filter_var($image, FILTER_VALIDATE_URL)) {
        $errors[] = "Invalid image URL.";
    }

    if (empty($category)) {
        $errors[] = "Category must be selected.";
    }

    if (empty($description)) {
        $errors[] = "Product description cannot be empty.";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO produit (name, prix, old_price, category, description, lien, stock_quantity, created_at) 
                VALUES (:name, :prix, :old_price, :category, :description, :lien, :stock_quantity, NOW())";
        $stmt = $conn->prepare($sql);

        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':prix', $prix);
        $stmt->bindValue(':old_price', $old_price);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':lien', $image);
        $stmt->bindValue(':stock_quantity', $stock_quantity, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $_SESSION['message'] = "<div class='bg-green-100 text-green-700 p-4 rounded-lg mb-6'>Product has been added successfully.</div>";
        } else {
            $_SESSION['message'] = "<div class='bg-red-100 text-red-700 p-4 rounded-lg mb-6'>Failed to add product. Please try again.</div>";
        }
    } else {
        $_SESSION['message'] = "<div class='bg-red-100 text-red-700 p-4 rounded-lg mb-6'>" . implode("<br>", $errors) . "</div>";
    }
}

// Redirect back to the add product page
header("Location: add_product.php");
exit;
?>

