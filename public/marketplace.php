<?php
include '../includes/header.php';
include '../includes/db.php';

// Fetch pets listed by users
$stmt = $conn->prepare("SELECT * FROM user_pets ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container my-5">
  <h2 class="mb-4 text-center">🐾 Pet Marketplace</h2>
  <p class="text-muted text-center mb-5">Browse pets listed by other users.</p>

  <div class="row g-4">
    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="col-md-4">
          <div class="card shadow-sm h-100">
            <img src="../uploads/<?php echo htmlspecialchars($row['image']); ?>" 
                 class="card-img-top" 
                 alt="<?php echo htmlspecialchars($row['name']); ?>">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
              <p class="card-text text-muted"><?php echo htmlspecialchars($row['description']); ?></p>
              <div class="mt-auto d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success">₱<?php echo number_format($row['price']); ?></span>
                <a href="cart.php?pet_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                  Add to Cart
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="col-12 text-center">
        <p class="text-muted">No pets listed yet. Be the first to <a href="sell.php">sell your pet</a>!</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
