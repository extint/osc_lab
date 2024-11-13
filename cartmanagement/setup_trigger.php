<?php
include 'db_connect.php';

// SQL to create the trigger
$createTriggerSQL = "
    CREATE TRIGGER update_product_stock AFTER INSERT ON order_history
    FOR EACH ROW
    BEGIN
        UPDATE products 
        SET stock = stock - NEW.quantity
        WHERE product_id = NEW.product_id;
    END;
";

// Execute the query to create the trigger
if ($conn->query($createTriggerSQL) === TRUE) {
    echo "Trigger created successfully.";
} else {
    echo "Error creating trigger: " . $conn->error;
}

$conn->close();
?>
