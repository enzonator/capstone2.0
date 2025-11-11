<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/db.php';

$q = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'recent';

switch ($sort) {
    case 'id': $orderBy = "p.id ASC"; break;
    case 'price_asc': $orderBy = "p.price ASC"; break;
    case 'price_desc': $orderBy = "p.price DESC"; break;
    default: $orderBy = "p.created_at DESC"; break;
}

if ($q !== '') {
    $like = "%$q%";
    $sql = "SELECT p.id, p.name, p.type, p.breed, p.price, p.created_at, u.username
            FROM pets p
            JOIN users u ON u.id = p.user_id
            WHERE p.name LIKE ? OR p.breed LIKE ? OR p.type LIKE ? OR u.username LIKE ?
            ORDER BY $orderBy";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $pets = $stmt->get_result();
} else {
    $sql = "SELECT p.id, p.name, p.type, p.breed, p.price, p.created_at, u.username
            FROM pets p
            JOIN users u ON u.id = p.user_id
            ORDER BY $orderBy";
    $pets = $conn->query($sql);
}

include __DIR__ . "/../includes/admin-sidebar.php";
?>

<div class="products-page">
  <div class="content-wrapper">
    <div class="page-header">
      <h2>🐾 Manage Pets</h2>
      <p class="subtitle">View and manage all pet listings</p>
    </div>

    <!-- Search & Sort -->
    <div class="actions-bar">
      <form method="get" class="search-form">
        <input type="text" name="q" placeholder="Search pets, breed, type, seller…" value="<?= htmlspecialchars($q) ?>">
        <button type="submit">
          <span>🔍</span> Search
        </button>
      </form>
      <form method="get" class="sort-form">
        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
        <label>Sort by:</label>
        <select name="sort" onchange="this.form.submit()">
          <option value="recent" <?= $sort==='recent'?'selected':'' ?>>Most Recent</option>
          <option value="id" <?= $sort==='id'?'selected':'' ?>>ID</option>
          <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price: Low → High</option>
          <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: High → Low</option>
        </select>
      </form>
    </div>

    <!-- Table -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>Breed</th>
            <th>Seller</th>
            <th>Price</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($pets && $pets->num_rows > 0): ?>
          <?php while ($row = $pets->fetch_assoc()): ?>
            <tr>
              <td><span class="id-badge">#<?= $row['id'] ?></span></td>
              <td><strong class="pet-name"><?= htmlspecialchars($row['name']) ?></strong></td>
              <td><?= htmlspecialchars($row['type']) ?></td>
              <td><?= htmlspecialchars($row['breed']) ?></td>
              <td><span class="seller-name"><?= htmlspecialchars($row['username']) ?></span></td>
              <td><span class="price-badge">₱<?= number_format($row['price'],2) ?></span></td>
              <td><span class="date-text"><?= date('M d, Y', strtotime($row['created_at'])) ?></span></td>
              <td>
                <div class="actions">
                  <a href="/catshop/admin/pet-details.php?id=<?= $row['id'] ?>" class="btn view" title="View Details">👁️</a>
                  <a href="#" class="btn edit disabled" title="Edit (Coming Soon)">✏️</a>
                  <a href="/catshop/admin/delete-pet.php?id=<?= $row['id'] ?>" class="btn delete" title="Delete Pet"
                     onclick="return confirm('Are you sure you want to delete this pet?');">🗑️</a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" class="empty">
            <div class="empty-state">
              <span class="empty-icon">🐾</span>
              <p>No pets found.</p>
              <?php if ($q !== ''): ?>
                <a href="products.php" class="clear-search">Clear search</a>
              <?php endif; ?>
            </div>
          </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
body {
  background: #faf8f5;
  margin: 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.products-page {
  margin-left: 260px;
  padding: 30px;
  min-height: 100vh;
  width: calc(100% - 260px);
  box-sizing: border-box;
}

.content-wrapper {
  background: #fff;
  padding: 32px;
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(92, 74, 58, 0.08);
  border: 1px solid rgba(245, 230, 211, 0.5);
}

.page-header {
  margin-bottom: 28px;
  padding-bottom: 20px;
  border-bottom: 2px solid #F5E6D3;
}

.page-header h2 {
  margin: 0 0 8px 0;
  font-weight: 600;
  color: #5C4A3A;
  font-size: 28px;
  letter-spacing: -0.5px;
}

.subtitle {
  margin: 0;
  color: #8B7355;
  font-size: 14px;
}

.actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 16px;
  flex-wrap: wrap;
}

.search-form {
  display: flex;
  gap: 8px;
  flex: 1;
  max-width: 400px;
}

.search-form input {
  flex: 1;
  padding: 10px 16px;
  border: 2px solid #F5E6D3;
  border-radius: 10px;
  font-size: 14px;
  color: #5C4A3A;
  transition: all 0.3s ease;
}

.search-form input:focus {
  outline: none;
  border-color: #D4A574;
  box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
}

.search-form button {
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg, #D4A574, #C19A6B);
  color: #fff;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(212, 165, 116, 0.3);
}

.search-form button:hover {
  background: linear-gradient(135deg, #C19A6B, #A67C52);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(212, 165, 116, 0.4);
}

.sort-form {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sort-form label {
  color: #5C4A3A;
  font-weight: 500;
  font-size: 14px;
}

.sort-form select {
  padding: 10px 16px;
  border: 2px solid #F5E6D3;
  border-radius: 10px;
  background: #fff;
  color: #5C4A3A;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.sort-form select:focus {
  outline: none;
  border-color: #D4A574;
  box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
}

.table-container {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #F5E6D3;
}

.table-container table {
  width: 100%;
  border-collapse: collapse;
}

.table-container thead {
  background: linear-gradient(135deg, #F5E6D3, #E8D5BA);
  color: #5C4A3A;
}

.table-container th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #D4A574;
}

.table-container td {
  padding: 16px;
  text-align: left;
  border-bottom: 1px solid #F5E6D3;
  color: #5C4A3A;
  font-size: 14px;
}

.table-container tbody tr {
  transition: all 0.2s ease;
}

.table-container tbody tr:hover {
  background: rgba(245, 230, 211, 0.3);
}

.id-badge {
  background: #F5E6D3;
  color: #5C4A3A;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.pet-name {
  color: #5C4A3A;
  font-weight: 600;
}

.seller-name {
  color: #8B7355;
}

.price-badge {
  background: linear-gradient(135deg, #D4A574, #C19A6B);
  color: #fff;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  display: inline-block;
  box-shadow: 0 2px 6px rgba(212, 165, 116, 0.3);
}

.date-text {
  color: #8B7355;
  font-size: 13px;
}

.actions {
  display: flex;
  gap: 6px;
}

.btn {
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 16px;
  text-decoration: none;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.btn.view { 
  background: rgba(212, 165, 116, 0.15);
  color: #C19A6B;
}

.btn.view:hover { 
  background: rgba(212, 165, 116, 0.25);
  transform: translateY(-1px);
}

.btn.edit { 
  background: rgba(139, 115, 85, 0.15);
  color: #8B7355;
  cursor: not-allowed;
  opacity: 0.6;
}

.btn.delete { 
  background: rgba(220, 53, 69, 0.1);
  color: #dc3545;
}

.btn.delete:hover { 
  background: rgba(220, 53, 69, 0.2);
  transform: translateY(-1px);
}

.empty {
  text-align: center;
  padding: 60px 20px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.empty-icon {
  font-size: 48px;
  opacity: 0.3;
}

.empty-state p {
  color: #8B7355;
  font-size: 16px;
  margin: 0;
}

.clear-search {
  color: #C19A6B;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  padding: 8px 16px;
  border: 2px solid #F5E6D3;
  border-radius: 8px;
  transition: all 0.3s ease;
  margin-top: 8px;
  display: inline-block;
}

.clear-search:hover {
  background: #F5E6D3;
  color: #5C4A3A;
}

/* Responsive Design */
@media (max-width: 768px) {
  .products-page {
    margin-left: 0;
    width: 100%;
    padding: 20px;
  }
  
  .actions-bar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-form {
    max-width: 100%;
  }
}
</style>