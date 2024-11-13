<?php
session_start();

if ($_POST['update']) {

  $upid = $_POST['upid'];

  $acol = array_column($_SESSION['cart'], 'pro_id');

  if (in_array($_POST['upid'], $acol)) {
    $_SESSION['cart'][$upid]['qty'] = $_POST['qty'];
  } else {
    $item = [
      'pro_id' => $upid,
      'qty' => 1
    ];
    $_SESSION['cart'][$upid] = $item;
  }

  header("location: cart.php");
}

function addToCart($productId, $quantity) {
  $userId = $_SESSION['user_id'];


  // Check if user has an active cart
  global $conn;
  $stmt = $conn->prepare("SELECT cart_id FROM Carts WHERE user_id = ? AND status = 'active'");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();
  $cart = $result->fetch_assoc();


  if (!$cart) {
      // Create a new cart
      $stmt = $conn->prepare("INSERT INTO Carts (user_id) VALUES (?)");
      $stmt->bind_param("i", $userId);
      $stmt->execute();
      $cartId = $conn->insert_id;
  } else {
      $cartId = $cart['cart_id'];
  }


  // Insert or update cart item
  $stmt = $conn->prepare("INSERT INTO CartItems (cart_id, product_id, quantity) VALUES (?, ?, ?)
                          ON DUPLICATE KEY UPDATE quantity = quantity + ?");
  $stmt->bind_param("iiii", $cartId, $productId, $quantity, $quantity);
  $stmt->execute();


  echo "Item added to cart!";
}


function viewCart() {
  $userId = $_SESSION['user_id'];


  global $conn;
  $stmt = $conn->prepare("SELECT Products.name, Products.price, CartItems.quantity
                          FROM CartItems
                          JOIN Carts ON Carts.cart_id = CartItems.cart_id
                          JOIN Products ON Products.product_id = CartItems.product_id
                          WHERE Carts.user_id = ? AND Carts.status = 'active'");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();


  if ($result->num_rows > 0) {
      echo "<h2>Your Cart</h2>";
      echo "<table border='1'>";
      echo "<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr>";


      $total = 0;
      while ($row = $result->fetch_assoc()) {
          $subtotal = $row['price'] * $row['quantity'];
          $total += $subtotal;
          echo "<tr>";
          echo "<td>{$row['name']}</td>";
          echo "<td>₹{$row['price']}</td>";
          echo "<td>{$row['quantity']}</td>";
          echo "<td>₹{$subtotal}</td>";
          echo "<td><a href='?remove={$row['product_id']}'>Remove</a></td>";
          echo "</tr>";
      }


      echo "<tr><td colspan='3'>Total</td><td>₹{$total}</td><td></td></tr>";
      echo "</table>";
  } else {
      echo "<p>Your cart is empty!</p>";
  }
}


function processPayment() {
  $userId = $_SESSION['user_id'];
  global $conn;


  // Fetch the active cart for the user
  $stmt = $conn->prepare("SELECT cart_id FROM Carts WHERE user_id = ? AND status = 'active'");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();
  $cart = $result->fetch_assoc();


  if ($cart) {
      $cartId = $cart['cart_id'];


      // Calculate total amount from cart items
      $stmt = $conn->prepare("SELECT SUM(p.price * ci.quantity) AS total_amount
                               FROM CartItems ci
                               JOIN Products p ON ci.product_id = p.product_id
                               WHERE ci.cart_id = ?");
      $stmt->bind_param("i", $cartId);
      $stmt->execute();
      $result = $stmt->get_result();
      $amount = $result->fetch_assoc()['total_amount'];


      if ($amount === null) {
          echo "<h2>Error: No items in the cart!</h2>";
          return;
      }


      // Insert a new payment record
      $stmt = $conn->prepare("INSERT INTO Payments (user_id, cart_id, payment_status, amount) VALUES (?, ?, 'pending', ?)");
      $stmt->bind_param("iid", $userId, $cartId, $amount);
      $stmt->execute();


      if ($stmt->affected_rows > 0) {
          $paymentId = $conn->insert_id;


          $stmt = $conn->prepare("UPDATE Payments SET payment_status = 'success' WHERE payment_id = ?");
          $stmt->bind_param("i", $paymentId);
          $stmt->execute();


          if ($stmt->affected_rows > 0) {
              echo "<h2 style='text-align: center; color: #28a745; font-size: 2.5rem; margin-top: 20px;'>Payment Successful!</h2>";
              echo "<p style='text-align: center; color: #4A5568; font-size: 1.2rem;'>Thank you for your purchase. Your order is being processed.</p>";
          }           
      }
  } else {
      echo "<h2 style='text-align: center; color: #4A5568; font-size: 1.2rem;'>Error: No active cart found!</h2>";
  }
}




?>
