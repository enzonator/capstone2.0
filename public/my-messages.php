<?php
session_start();
require_once "../config/db.php";
include_once "../includes/header.php";

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch list of conversations (with unread count)
$sql = "
    SELECT 
        m.pet_id,
        p.name AS pet_name,
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END AS other_user_id,
        u.username AS other_username,
        MAX(m.created_at) AS last_message_time,
        SUBSTRING_INDEX(MAX(CONCAT(m.created_at, '|', m.message)), '|', -1) AS last_message,
        SUM(
            CASE 
                WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 
                ELSE 0 
            END
        ) AS unread_count
    FROM messages m
    JOIN pets p ON m.pet_id = p.id
    JOIN users u ON u.id = 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY m.pet_id, other_user_id
    ORDER BY last_message_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total unread
$total_unread = array_sum(array_column($conversations, 'unread_count'));
?>

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

.messages-container {
    flex-grow: 1;
    padding: 30px;
    max-width: 100%;
    width: 100%;
    background: linear-gradient(135deg, #F5F0E8 0%, #E8DCC8 100%);
}

/* Header Section */
.messages-header {
    background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
    color: white;
    padding: 35px 30px;
    border-radius: 20px;
    margin-bottom: 30px;
    box-shadow: 0 8px 24px rgba(139, 111, 71, 0.25);
    position: relative;
    overflow: hidden;
}

.messages-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
}

.messages-header-content {
    position: relative;
    z-index: 1;
}

.messages-header h2 {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 15px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.messages-header p {
    margin: 0;
    opacity: 0.95;
    font-size: 1.05rem;
    font-weight: 400;
}

.unread-count {
    display: inline-block;
    background: rgba(220, 53, 69, 0.9);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    margin-left: 10px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

/* Messages Content */
.messages-content {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 4px 16px rgba(139, 111, 71, 0.12);
    border: 1px solid rgba(212, 196, 176, 0.3);
}

/* Conversation card */
.conversation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 20px;
    margin-bottom: 12px;
    border: 2px solid rgba(139, 111, 71, 0.15);
    border-radius: 12px;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    background: #fafafa;
    position: relative;
    overflow: hidden;
}

.conversation::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: #8B6F47;
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.conversation:hover {
    background: rgba(139, 111, 71, 0.08);
    border-color: rgba(139, 111, 71, 0.3);
    transform: translateX(8px);
}

.conversation:hover::before {
    transform: scaleY(1);
}

.conversation.has-unread {
    background: rgba(220, 53, 69, 0.05);
    border-color: rgba(220, 53, 69, 0.2);
}

.conversation .info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.conversation .pet {
    font-weight: 700;
    color: #8B6F47;
    font-size: 1.05rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.conversation .username {
    color: #5D4E37;
    font-weight: 600;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.conversation .last-msg {
    color: #6c757d;
    font-size: 0.9rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 500px;
}

.conversation .meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
    margin-left: 20px;
}

.conversation .time {
    font-size: 0.85rem;
    color: #888;
    white-space: nowrap;
}

/* Unread badge */
.unread-badge {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
    animation: pulse 2s infinite;
}

/* Empty State */
.empty-messages {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-messages i {
    font-size: 4rem;
    color: #A0826D;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-messages h3 {
    color: #5D4E37;
    font-size: 1.5rem;
    margin-bottom: 10px;
}

.empty-messages p {
    font-size: 1.05rem;
    margin-bottom: 25px;
}

.empty-messages a {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
    color: white;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(139, 111, 71, 0.3);
}

.empty-messages a:hover {
    background: linear-gradient(135deg, #A0826D 0%, #8B6F47 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 111, 71, 0.4);
}

/* Responsive */
@media (max-width: 991px) {
    .dashboard {
        flex-direction: column;
    }
    
    .messages-container {
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .messages-content {
        padding: 20px;
    }

    .messages-header h2 {
        font-size: 1.8rem;
    }

    .conversation {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .conversation .meta {
        flex-direction: row;
        width: 100%;
        justify-content: space-between;
        margin-left: 0;
    }

    .conversation .last-msg {
        max-width: 100%;
    }
}
</style>

<div class="dashboard">
    <!-- Sidebar -->
    <?php include_once "../includes/sidebar.php"; ?>

    <!-- Messages Section -->
    <div class="messages-container">
        <!-- Header -->
        <div class="messages-header">
            <div class="messages-header-content">
                <h2>
                    <i class="bi bi-chat-dots-fill"></i>
                    My Messages
                    <?php if ($total_unread > 0): ?>
                        <span class="unread-count"><?= $total_unread; ?> Unread</span>
                    <?php endif; ?>
                </h2>
                <p>Stay connected with sellers and buyers</p>
            </div>
        </div>

        <!-- Messages Content -->
        <div class="messages-content">
            <?php if (!empty($conversations)): ?>
                <?php foreach ($conversations as $c): ?>
                    <a class="conversation <?= (int)$c['unread_count'] > 0 ? 'has-unread' : ''; ?>" 
                       href="message-seller.php?pet_id=<?= $c['pet_id']; ?>&seller_id=<?= $c['other_user_id']; ?>">
                        <div class="info">
                            <div class="pet">
                                <i class="bi bi-paw"></i>
                                <?= htmlspecialchars($c['pet_name']); ?>
                            </div>
                            <div class="username">
                                <i class="bi bi-person-circle"></i>
                                <?= htmlspecialchars($c['other_username']); ?>
                            </div>
                            <div class="last-msg">
                                <i class="bi bi-chat-left-text"></i>
                                <?= htmlspecialchars($c['last_message']); ?>
                            </div>
                        </div>
                        <div class="meta">
                            <div class="time">
                                <i class="bi bi-clock"></i>
                                <?= date('M d, Y h:i A', strtotime($c['last_message_time'])); ?>
                            </div>
                            <?php if ((int)$c['unread_count'] > 0): ?>
                                <span class="unread-badge"><?= (int)$c['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-messages">
                    <i class="bi bi-chat-left"></i>
                    <h3>No Messages Yet</h3>
                    <p>Start a conversation with a seller to inquire about their pets!</p>
                    <a href="products.php">
                        <i class="bi bi-search"></i> Browse Pets
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>