<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <title>Simple Cart</title>
</head>

<body>
  <nav class="navbar">
    <div class="container_header">
      <a class="navbar-brand" href="cart.php">Cart</a>
      <a class="navbar-brand" href="carthome.php">Home</a>
      <div class="cart-info">
        <!-- <a class="cart-button" href="cart.php">Cart  -->
          <?php if (isset($_SESSION['cart'])) : ?>
            <?php echo count($_SESSION['cart']); ?>
          <?php endif; ?>
        <!-- </a> -->
      </div>
    </div>
  </nav>
