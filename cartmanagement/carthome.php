<?php
session_start();
include 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Generate a unique session ID for each user if not already set
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

$session_id = $_SESSION['session_id'];

// Add to Cart Logic
if (isset($_GET['pro_id'])) {
    $proid = $_GET['pro_id'];

    // Check if the product is already in the cart for this session
    $checkCartQuery = "SELECT * FROM cart WHERE session_id = '$session_id' AND product_id = $proid";
    $result = $conn->query($checkCartQuery);

    if ($result->num_rows > 0) {
        // If product exists in cart, increment the quantity
        $updateCartQuery = "UPDATE cart SET quantity = quantity + 1 WHERE session_id = '$session_id' AND product_id = $proid";
        if ($conn->query($updateCartQuery) === FALSE) {
            echo "Error: " . $conn->error;
        }
    } else {
        // If not in cart, add it with an initial quantity of 1
        $insertCartQuery = "INSERT INTO cart (session_id, product_id, quantity) VALUES ('$session_id', $proid, 1)";
        if ($conn->query($insertCartQuery) === FALSE) {
            echo "Error: " . $conn->error;
        }
    }

    // Update the stock of the product in the database
    // Fetch the current stock
    $productQuery = "SELECT stock FROM products WHERE product_id = $proid";
    $productResult = $conn->query($productQuery);
    $product = $productResult->fetch_assoc();

    // Ensure there is enough stock
    if ($product['stock'] > 0) {
        // Decrease stock by 1 (or by the quantity in the cart)
        $updateStockQuery = "UPDATE products SET stock = stock - 1 WHERE product_id = $proid";
        $conn->query($updateStockQuery);
    } else {
        // If no stock left, disable "Add to Cart" and show an error message
        echo "Sorry, this product is out of stock.";
    }

    // Redirect back to the page to update the cart display
    header("Location: carthome.php");
    exit;
}

// Function to handle checkout
if (isset($_POST['checkout'])) {
    // Begin transaction
    $conn->begin_transaction();

    try {
        // Calculate total amount
        $cartQuery = "SELECT * FROM cart WHERE session_id = '$session_id'";
        $cartResult = $conn->query($cartQuery);

        if ($cartResult->num_rows > 0) {
            // Calculate total amount
            $totalAmount = 0;
            $orderItems = [];

            while ($cartItem = $cartResult->fetch_assoc()) {
                // Fetch product details
                $productQuery = "SELECT * FROM products WHERE product_id = " . $cartItem['product_id'];
                $productResult = $conn->query($productQuery);
                $product = $productResult->fetch_assoc();

                $totalAmount += $product['price'] * $cartItem['quantity'];

                // Prepare order items
                $orderItems[] = [
                    'product_id' => $product['product_id'],
                    'quantity' => $cartItem['quantity'],
                    'price' => $product['price']
                ];

                // Update stock in products table
                $updateStockQuery = "UPDATE products SET stock = stock - " . $cartItem['quantity'] . " WHERE product_id = " . $product['product_id'];
                $conn->query($updateStockQuery);
            }

            // Insert order record into orders table
            $orderQuery = "INSERT INTO orders (session_id, total_amount) VALUES ('$session_id', $totalAmount)";
            if ($conn->query($orderQuery) === FALSE) {
                throw new Exception("Failed to insert order");
            }

            // Get the last inserted order ID
            $order_id = $conn->insert_id;

            // Insert order items into order_items table
            foreach ($orderItems as $item) {
                $orderItemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ($order_id, " . $item['product_id'] . ", " . $item['quantity'] . ", " . $item['price'] . ")";
                if ($conn->query($orderItemQuery) === FALSE) {
                    throw new Exception("Failed to insert order item");
                }
            }

            // Commit the transaction
            $conn->commit();

            // Clear the cart after successful checkout
            $clearCartQuery = "DELETE FROM cart WHERE session_id = '$session_id'";
            $conn->query($clearCartQuery);

            // Redirect to a confirmation page or success message
            header("Location: order_confirmation.php?order_id=" . $order_id);
            exit;
        } else {
            throw new Exception("No items in the cart");
        }
    } catch (Exception $e) {
        // Rollback transaction in case of an error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}

// Fetch products from the database to display with updated stock
$productQuery = "SELECT * FROM products";
$productResult = $conn->query($productQuery);

if (!$productResult) {
    die("Query failed: " . $conn->error);
}

include 'header.php';
?>

<div class="container">
  <h1 style="text-align:center;">ITEMS</h1>

  <div class="product-list">
    <?php
    if ($productResult->num_rows > 0) {
        while ($product = $productResult->fetch_assoc()) {
            // Check if the stock is 0
            $isOutOfStock = $product['stock'] == 0;
            echo '<div class="product-card">';
            echo '<h5>' . htmlspecialchars($product['name']) . '</h5>';
            echo '<p>' . htmlspecialchars($product['description']) . '</p>';
            echo '<p>Price: $' . htmlspecialchars($product['price']) . '</p>';
            echo '<p>Remaining Stock: ' . htmlspecialchars($product['stock']) . '</p>';
            
            // Disable the button if stock is 0
            if ($isOutOfStock) {
                echo '<button class="btn btn-danger" disabled>Out of Stock</button>';
            } else {
                echo '<a href="carthome.php?pro_id=' . htmlspecialchars($product['product_id']) . '" class="btn btn-success">Add to Cart</a>';
            }
            
            echo '</div>';
        }
    } else {
        echo "<p>No products available.</p>";
    }
    ?>
  </div>

  <!-- Checkout button -->
  <form method="POST" action="carthome.php">
    <button type="submit" name="checkout" class="btn btn-primary" style="margin-top: 20px;color:blue">Checkout</button>
  </form>
</div>

