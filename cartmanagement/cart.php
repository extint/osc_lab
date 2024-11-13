<?php session_start(); ?>
<?php include 'header.php'; ?>

<div class="container">
  <h1>Your Cart</h1>
  <a href="emptycart.php" class="btn btn-danger">Empty Cart</a>
  <table class="cart-table">
    <thead>
      <tr>
        <th>S.no</th>
        <th>Product Name</th>
        <th>Quantity</th>
        <th colspan="2">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if (isset($_SESSION['cart'])) :
        $i = 1;
        foreach ($_SESSION['cart'] as $cart) :
      ?>
          <tr>
            <td><?php echo $i; ?></td>
            <td>Product <?= $cart['pro_id']; ?></td>
            <td>
              <form action="update.php" method="post">
                <input type="number" value="<?= $cart['qty']; ?>" name="qty" min="1">
                <input type="hidden" name="upid" value="<?= $cart['pro_id']; ?>">
            </td>
            <td>
              <input type="submit" name="update" value="Update" class="btn btn-warning">
              </form>
            </td>
            <td><a class="btn btn-danger" href="removecartitem.php?id=<?= $cart['pro_id']; ?>">Remove</a></td>
          </tr>
      <?php
          $i++;
        endforeach;
      endif;
      ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
