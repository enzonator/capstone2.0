<?php
require_once __DIR__ . '/../includes/header.php';
if (!isAdmin()) { header('Location: /catshop/public/index.php'); exit; }

if ($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf($_POST['csrf'] ?? '')) {
  $id = (int)($_POST['id'] ?? 0);
  $status = $_POST['status'] ?? 'Pending';
  $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
  $stmt->bind_param('si', $status, $id);
  $stmt->execute(); $stmt->close();
}

$sql = "SELECT o.id, u.username, o.total_amount, o.status, o.created_at
        FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC";
$orders = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<h2>Orders</h2>
<?php foreach ($orders as $o): ?>
  <div class="card">
    <h4>Order #<?= (int)$o['id'] ?> • <?= htmlspecialchars($o['username']) ?></h4>
    <p>Total: ₱<?= number_format($o['total_amount'],2) ?> • Status: <?= htmlspecialchars($o['status']) ?> • <?= $o['created_at'] ?></p>
    <form method="post" style="display:flex; gap:8px; align-items:center">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
      <select name="status">
        <?php foreach (['Pending','Paid','Shipped','Completed','Cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $s===$o['status']?'selected':'' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Update</button>
    </form>
    <?php
      $oi = $conn->query("SELECT p.name, oi.quantity, oi.price FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=".(int)$o['id'])->fetch_all(MYSQLI_ASSOC);
    ?>
    <ul>
      <?php foreach ($oi as $row): ?>
        <li><?= htmlspecialchars($row['name']) ?> × <?= (int)$row['quantity'] ?> — ₱<?= number_format($row['price'],2) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endforeach; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
