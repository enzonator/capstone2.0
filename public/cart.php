<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$removeSuccess = false;

// Check verification status
$verification_status = 'not verified';
$verifyQuery = $conn->prepare("SELECT verification_status FROM users WHERE id = ?");
$verifyQuery->bind_param("i", $user_id);
$verifyQuery->execute();
$verifyResult = $verifyQuery->get_result();
if ($verifyResult->num_rows > 0) {
    $verifyUser = $verifyResult->fetch_assoc();
    $verification_status = $verifyUser['verification_status'] ?? 'not verified';
}

// Handle Remove from Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_cart'])) {
    $cart_id = intval($_POST['cart_id']);
    
    // Verify the cart item belongs to the current user before deleting
    $deleteSql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("ii", $cart_id, $user_id);
    
    if ($deleteStmt->execute()) {
        $removeSuccess = true;
    }
}

include_once "../includes/header.php";

// Fetch cart items joined with pet details
$sql = "
    SELECT 
        c.id AS cart_id,
        p.id AS pet_id,
        p.name AS pet_name,
        p.price
    FROM cart c
    JOIN pets p ON c.pet_id = p.id
    WHERE c.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$total = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Fetch the first image for this pet
        $imgSql = "SELECT filename FROM pet_images WHERE pet_id = ? LIMIT 1";
        $imgStmt = $conn->prepare($imgSql);
        $imgStmt->bind_param("i", $row['pet_id']);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();
        $imgRow = $imgResult->fetch_assoc();
        
        $row['image'] = $imgRow ? $imgRow['filename'] : 'default-pet.jpg';
        
        $cart_items[] = $row;
        $total += $row['price'];
    }
}
?>

<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($removeSuccess): ?>
<script>
Swal.fire({
    title: 'Success!',
    text: 'Item removed from cart successfully!',
    icon: 'success',
    confirmButtonText: 'OK',
    confirmButtonColor: '#5a4a3a'
}).then(() => {
    window.location.href = 'cart.php';
});
</script>
<?php endif; ?>

<style>
/* Remove default container margin from header */
body {
    margin: 0 !important;
    padding: 0 !important;
    background: linear-gradient(135deg, #F5F0E8 0%, #E8DCC8 100%) !important;
}

.container.mt-4 {
    margin: 0 !important;
    padding: 0 !important;
    max-width: 100% !important;
}

.dashboard {
    display: flex;
    min-height: 100vh;
    margin: 0;
    width: 100%;
}

.cart-container {
    flex-grow: 1;
    padding: 30px;
    max-width: 100%;
    width: 100%;
    background: linear-gradient(135deg, #F5F0E8 0%, #E8DCC8 100%);
}

/* Header Section */
.cart-header {
    background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
    color: white;
    padding: 35px 30px;
    border-radius: 20px;
    margin-bottom: 30px;
    box-shadow: 0 8px 24px rgba(139, 111, 71, 0.25);
    position: relative;
    overflow: hidden;
}

.cart-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
}

.cart-header-content {
    position: relative;
    z-index: 1;
}

.cart-header h2 {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 15px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.cart-header p {
    margin: 0;
    opacity: 0.95;
    font-size: 1.05rem;
    font-weight: 400;
}

.cart-count {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    margin-left: 10px;
}

/* Cart Content */
.cart-content {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 4px 16px rgba(139, 111, 71, 0.12);
    border: 1px solid rgba(212, 196, 176, 0.3);
}

.select-all-section {
    padding: 18px 20px;
    background: rgba(139, 111, 71, 0.06);
    border: 2px solid rgba(139, 111, 71, 0.15);
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.select-all-section input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #8B6F47;
}

.select-all-section label {
    font-weight: 600;
    color: #5D4E37;
    cursor: pointer;
    user-select: none;
    font-size: 1rem;
}

.cart-items {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.cart-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    background: #fafafa;
    border: 2px solid rgba(139, 111, 71, 0.15);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.cart-item:hover {
    border-color: rgba(139, 111, 71, 0.3);
    box-shadow: 0 4px 12px rgba(139, 111, 71, 0.1);
    transform: translateY(-2px);
}

.cart-item-left {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
}

.cart-item-checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #8B6F47;
}

.cart-item-image {
    width: 110px;
    height: 110px;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid rgba(139, 111, 71, 0.2);
    transition: all 0.3s ease;
}

.cart-item-image:hover {
    border-color: #8B6F47;
    transform: scale(1.05);
}

.cart-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-item-info {
    flex: 1;
}

.cart-item-info h5 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #5D4E37;
    margin-bottom: 8px;
}

.cart-item-info h5 a {
    text-decoration: none;
    color: inherit;
    transition: color 0.3s ease;
}

.cart-item-info h5 a:hover {
    color: #8B6F47;
}

.cart-item-price {
    font-size: 1.3rem;
    font-weight: 700;
    color: #8B6F47;
}

.btn-remove {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    border: 2px solid transparent;
    color: white;
    padding: 10px 18px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.btn-remove:hover {
    background: transparent;
    color: #dc3545;
    border-color: #dc3545;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

.cart-summary {
    margin-top: 30px;
    padding: 25px;
    background: rgba(139, 111, 71, 0.06);
    border: 2px solid rgba(139, 111, 71, 0.15);
    border-radius: 12px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: #5D4E37;
}

.cart-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 20px;
}

.btn {
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-secondary {
    background: rgba(139, 111, 71, 0.15);
    color: #5D4E37;
    border-color: rgba(139, 111, 71, 0.3);
}

.btn-secondary:hover {
    background: rgba(139, 111, 71, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 111, 71, 0.2);
}

.btn-primary {
    background: linear-gradient(135deg, #6B5540 0%, #8B6F47 100%);
    color: white;
    border-color: transparent;
    box-shadow: 0 2px 8px rgba(107, 85, 64, 0.4);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5D4E37 0%, #6B5540 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(93, 78, 55, 0.5);
}

.btn-primary:disabled {
    background: #d4c4b0;
    border-color: #d4c4b0;
    color: #8d7d6d;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-primary:disabled:hover {
    transform: none;
    box-shadow: none;
}

/* Empty State */
.empty-cart {
    text-align: center;
    padding: 60px 20px;
}

.empty-cart-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #A0826D;
    opacity: 0.5;
}

.empty-cart h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #5D4E37;
    margin-bottom: 12px;
}

.empty-cart p {
    font-size: 1.05rem;
    color: #6c757d;
    margin-bottom: 30px;
}

/* Custom Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(4px);
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: white;
    padding: 35px;
    border-radius: 16px;
    border: 2px solid rgba(139, 111, 71, 0.3);
    box-shadow: 0 10px 40px rgba(90, 74, 58, 0.3);
    max-width: 450px;
    width: 90%;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    font-weight: 700;
    font-size: 1.3rem;
    color: #5D4E37;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-message {
    color: #6c757d;
    font-size: 1rem;
    margin-bottom: 30px;
    line-height: 1.6;
}

.modal-buttons {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.modal-btn {
    padding: 12px 28px;
    border: 2px solid transparent;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.modal-btn-ok {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.modal-btn-ok:hover {
    background: transparent;
    color: #dc3545;
    border-color: #dc3545;
    transform: scale(1.05);
}

.modal-btn-cancel {
    background: rgba(139, 111, 71, 0.15);
    color: #5D4E37;
    border-color: rgba(139, 111, 71, 0.3);
}

.modal-btn-cancel:hover {
    background: rgba(139, 111, 71, 0.25);
    transform: scale(1.05);
}

/* Responsive */
@media (max-width: 991px) {
    .dashboard {
        flex-direction: column;
    }
    
    .cart-container {
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .cart-content {
        padding: 20px;
    }

    .cart-header h2 {
        font-size: 1.8rem;
    }

    .cart-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .cart-item-left {
        width: 100%;
    }

    .cart-actions {
        flex-direction: column;
        width: 100%;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }

    .total-row {
        font-size: 1.2rem;
    }
}
</style>

<div class="dashboard">
    <?php include_once "../includes/sidebar.php"; ?>
    
    <div class="cart-container">
        <!-- Header -->
        <div class="cart-header">
            <div class="cart-header-content">
                <h2>
                    <i class="bi bi-cart-fill"></i>
                    My Cart
                    <span class="cart-count"><?= count($cart_items); ?> <?= count($cart_items) === 1 ? 'Item' : 'Items' ?></span>
                </h2>
                <p>Review your selected pets before checkout</p>
            </div>
        </div>

        <?php if (count($cart_items) > 0): ?>
            <div class="cart-content">
                <!-- Select All Section -->
                <div class="select-all-section">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                    <label for="selectAll">Select All Items</label>
                </div>

                <!-- Cart Items -->
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-left">
                                <div class="cart-item-checkbox">
                                    <input type="checkbox"
                                           class="item-checkbox"
                                           data-price="<?= $item['price'] ?>"
                                           data-pet-id="<?= $item['pet_id'] ?>"
                                           onchange="updateTotal()">
                                </div>
                                <div class="cart-item-image" onclick="window.location.href='pet-details.php?id=<?= $item['pet_id'] ?>'">
                                    <img src="../uploads/<?= htmlspecialchars($item['image']) ?>"
                                         alt="<?= htmlspecialchars($item['pet_name']) ?>">
                                </div>
                                <div class="cart-item-info">
                                    <h5>
                                        <a href="pet-details.php?id=<?= $item['pet_id'] ?>">
                                            <?= htmlspecialchars($item['pet_name']) ?>
                                        </a>
                                    </h5>
                                    <div class="cart-item-price">₱<?= number_format($item['price'], 2) ?></div>
                                </div>
                            </div>
                            <form method="POST" id="removeForm_<?= $item['cart_id'] ?>" style="margin:0;">
                                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                <input type="hidden" name="remove_from_cart" value="1">
                                <button type="button" class="btn-remove" onclick="showRemoveModal(<?= $item['cart_id'] ?>)">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="total-row">
                        <span>Selected Items Total:</span>
                        <span id="selectedTotal">₱0.00</span>
                    </div>
                </div>

                <!-- Cart Actions -->
                <div class="cart-actions">
                    <a href="products.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Continue Shopping
                    </a>
                    <button id="checkoutBtn" class="btn btn-primary" onclick="proceedToCheckout()" disabled>
                        Proceed to Checkout <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="empty-cart">
                    <div class="empty-cart-icon"><i class="bi bi-cart-x"></i></div>
                    <h3>Your Cart is Empty</h3>
                    <p>Start adding some adorable pets to your cart!</p>
                    <a href="products.php" class="btn btn-primary">
                        <i class="bi bi-search"></i> Browse Pets
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Custom Remove Confirmation Modal -->
    <div class="modal-overlay" id="removeModal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="bi bi-exclamation-triangle-fill" style="color: #dc3545;"></i>
                Remove Item
            </div>
            <div class="modal-message">Are you sure you want to remove this pet from your cart?</div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="modal-btn modal-btn-ok" onclick="confirmRemove()">Remove</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentCartId = null;

    // Toggle Select All
    function toggleSelectAll(checkbox) {
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        itemCheckboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
        updateTotal();
    }

    // Update total based on selected items
    function updateTotal() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        let total = 0;
        
        checkboxes.forEach(cb => {
            total += parseFloat(cb.dataset.price);
        });

        document.getElementById('selectedTotal').textContent = '₱' + total.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Enable/disable checkout button
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (checkboxes.length > 0) {
            checkoutBtn.disabled = false;
        } else {
            checkoutBtn.disabled = true;
        }

        // Update "Select All" checkbox state
        const allCheckboxes = document.querySelectorAll('.item-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        selectAllCheckbox.checked = allCheckboxes.length === checkboxes.length && allCheckboxes.length > 0;
    }

    // Proceed to checkout with selected items
    function proceedToCheckout() {
        const selectedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
        
        if (selectedCheckboxes.length === 0) {
            Swal.fire({
                title: 'No Items Selected',
                text: 'Please select at least one item to checkout.',
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#5a4a3a'
            });
            return;
        }

        // Check verification status
        const verificationStatus = '<?= $verification_status ?>';
        
        if (verificationStatus === 'not verified') {
            Swal.fire({
                title: 'Account Not Verified',
                text: 'Please verify your account to proceed with checkout.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Verify Now',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#5a4a3a',
                cancelButtonColor: '#8d7d6d',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'verify.php';
                }
            });
            return;
        }
        
        if (verificationStatus === 'pending') {
            Swal.fire({
                title: 'Verification Pending',
                text: 'Your verification has been submitted. Please wait for admin approval before you can proceed with checkout.',
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#5a4a3a',
            });
            return;
        }

        // If verified, proceed to checkout
        const petIds = Array.from(selectedCheckboxes).map(cb => cb.dataset.petId);
        window.location.href = 'checkout.php?pet_ids=' + petIds.join(',');
    }

    function showRemoveModal(cartId) {
        currentCartId = cartId;
        document.getElementById('removeModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('removeModal').classList.remove('active');
        currentCartId = null;
    }

    function confirmRemove() {
        if (currentCartId) {
            document.getElementById('removeForm_' + currentCartId).submit();
        }
    }

    // Close modal when clicking outside
    document.getElementById('removeModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
</script>

<?php include_once "../includes/footer.php"; ?>