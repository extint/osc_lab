<?php
include 'db_connect.php';

// Get the order ID from the URL
$order_id = $_GET['order_id'];

// Fetch the order details
$orderQuery = "SELECT * FROM orders WHERE order_id = $order_id";
$orderResult = $conn->query($orderQuery);
$order = $orderResult->fetch_assoc();

// Fetch the order items
$orderItemsQuery = "SELECT oi.*, p.name, p.price FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = $order_id";
$orderItemsResult = $conn->query($orderItemsQuery);

include 'header.php';
?>

<div class="container">
    <h1>Order Confirmation</h1>
    <p>Thank you for your order! Your order ID is #<?php echo $order_id; ?></p>
    <p>Total Amount: $<?php echo number_format($order['total_amount'], 2); ?></p>

    <h3>Order Items:</h3>
    <ul>
        <?php while ($item = $orderItemsResult->fetch_assoc()) { ?>
            <li>
                <?php echo $item['name']; ?> (x<?php echo $item['quantity']; ?>) - $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
            </li>
        <?php } ?>
    </ul>
</div>

<!-- <?php include 'footer.php'; ?> -->
