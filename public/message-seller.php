<?php
ob_start(); // Prevent "headers already sent" errors
session_start();
require_once "../config/db.php";
include_once "../includes/header.php";

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$pet_id    = intval($_GET['pet_id'] ?? 0);
$seller_id = intval($_GET['seller_id'] ?? 0);

if (!$pet_id || !$seller_id) {
    die("Invalid request.");
}

// Fetch pet info
$petSql = "SELECT name FROM pets WHERE id = ?";
$petStmt = $conn->prepare($petSql);
if (!$petStmt) { die("Prepare failed: " . $conn->error); }
$petStmt->bind_param("i", $pet_id);
$petStmt->execute();
$pet = $petStmt->get_result()->fetch_assoc();

// Mark unread messages as read (on page load)
$markSql = "UPDATE messages SET is_read = 1 
            WHERE pet_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0";
$markStmt = $conn->prepare($markSql);
if ($markStmt) {
    $markStmt->bind_param("iii", $pet_id, $seller_id, $user_id);
    $markStmt->execute();
    $markStmt->close();
}

// Handle new message
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty(trim($_POST['message']))) {
    $message = trim($_POST['message']);

    // Insert message
    $sql = "INSERT INTO messages (pet_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iiis", $pet_id, $user_id, $seller_id, $message);
    $stmt->execute();
    $stmt->close();

    // Create notification for the seller
    $senderUsername = $_SESSION['username'] ?? 'Someone';
    $petName = $pet['name'] ?? 'your pet';
    $notificationMessage = "$senderUsername sent you a message about $petName";
    
    $notifSql = "INSERT INTO notifications (user_id, message, type, pet_id, is_read, created_at) 
                 VALUES (?, ?, 'new_message', ?, 0, NOW())";
    $notifStmt = $conn->prepare($notifSql);
    if ($notifStmt) {
        $notifStmt->bind_param("isi", $seller_id, $notificationMessage, $pet_id);
        $notifStmt->execute();
        $notifStmt->close();
    }

    // Redirect to avoid form resubmission
    header("Location: message-seller.php?pet_id={$pet_id}&seller_id={$seller_id}");
    exit();
}

// Initial fetch of messages
$msgSql = "SELECT m.*, u.username 
           FROM messages m
           JOIN users u ON m.sender_id = u.id
           WHERE m.pet_id = ? AND 
                 ((m.sender_id = ? AND m.receiver_id = ?) OR 
                  (m.sender_id = ? AND m.receiver_id = ?))
           ORDER BY m.created_at ASC";
$msgStmt = $conn->prepare($msgSql);
if (!$msgStmt) { die("Prepare failed: " . $conn->error); }
$msgStmt->bind_param("iiiii", $pet_id, $user_id, $seller_id, $seller_id, $user_id);
$msgStmt->execute();
$messages = $msgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$msgStmt->close();
?>

<style>
/* Color scheme matching the header */
:root {
    --primary-bg: #EADDCA;
    --primary-text: #5D4E37;
    --accent-color: #8B6F47;
    --hover-color: #A0826D;
    --badge-color: #D2691E;
    --white: #ffffff;
    --light-accent: rgba(139, 111, 71, 0.1);
    --sent-message: #F5E6D3;
    --received-message: #FAF5EF;
}

.chat-container {
    max-width: 900px;
    margin: 40px auto;
    background: var(--white);
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(93, 78, 55, 0.15);
    border: 2px solid var(--primary-bg);
}

.chat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--primary-bg);
}

.back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: var(--primary-bg);
    border: 2px solid var(--accent-color);
    border-radius: 8px;
    text-decoration: none;
    color: var(--primary-text);
    font-size: 15px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.back-button:hover {
    background: var(--accent-color);
    color: var(--white);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 111, 71, 0.3);
}

.chat-title {
    margin: 0;
    color: var(--primary-text);
    font-size: 24px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.chat-title i {
    color: var(--accent-color);
}

.messages {
    max-height: 550px;
    min-height: 400px;
    overflow-y: auto;
    margin-bottom: 25px;
    padding: 20px;
    border: 2px solid var(--primary-bg);
    border-radius: 12px;
    background: linear-gradient(to bottom, #FDFBF7 0%, var(--white) 100%);
}

.messages::-webkit-scrollbar {
    width: 8px;
}

.messages::-webkit-scrollbar-track {
    background: var(--primary-bg);
    border-radius: 10px;
}

.messages::-webkit-scrollbar-thumb {
    background: var(--accent-color);
    border-radius: 10px;
}

.messages::-webkit-scrollbar-thumb:hover {
    background: var(--hover-color);
}

.message {
    margin: 12px 0;
    padding: 14px 18px;
    border-radius: 12px;
    max-width: 75%;
    word-wrap: break-word;
    box-shadow: 0 2px 8px rgba(93, 78, 55, 0.1);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message.sent {
    background: linear-gradient(135deg, var(--sent-message) 0%, #EED9C4 100%);
    margin-left: auto;
    text-align: right;
    border: 1px solid var(--accent-color);
    border-left: 4px solid var(--accent-color);
}

.message.received {
    background: var(--received-message);
    margin-right: auto;
    text-align: left;
    border: 1px solid var(--primary-bg);
    border-left: 4px solid var(--badge-color);
}

.message strong {
    color: var(--primary-text);
    font-weight: 700;
    font-size: 14px;
}

.message-content {
    color: var(--primary-text);
    margin-top: 6px;
    line-height: 1.5;
    font-size: 15px;
}

.message small {
    display: block;
    margin-top: 8px;
    color: var(--accent-color);
    font-size: 12px;
    font-weight: 500;
}

.no-messages {
    text-align: center;
    padding: 60px 20px;
    color: var(--accent-color);
    font-style: italic;
}

.no-messages i {
    font-size: 48px;
    color: var(--primary-bg);
    margin-bottom: 15px;
}

.send-form {
    display: flex;
    gap: 12px;
    background: var(--primary-bg);
    padding: 18px;
    border-radius: 12px;
    border: 2px solid var(--accent-color);
}

.send-form textarea {
    flex: 1;
    resize: none;
    padding: 14px 16px;
    border-radius: 8px;
    border: 2px solid var(--accent-color);
    background: var(--white);
    color: var(--primary-text);
    font-size: 15px;
    font-family: inherit;
    transition: all 0.3s ease;
}

.send-form textarea:focus {
    outline: none;
    border-color: var(--hover-color);
    box-shadow: 0 0 0 3px rgba(139, 111, 71, 0.1);
}

.send-form textarea::placeholder {
    color: var(--accent-color);
    opacity: 0.6;
}

.send-form button {
    background: var(--accent-color);
    color: var(--white);
    border: none;
    padding: 14px 28px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.send-form button:hover {
    background: var(--hover-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 111, 71, 0.3);
}

.send-form button:active {
    transform: translateY(0);
}

/* Responsive design */
@media (max-width: 768px) {
    .chat-container {
        margin: 20px;
        padding: 20px;
    }

    .chat-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .chat-title {
        font-size: 20px;
    }

    .messages {
        max-height: 400px;
        min-height: 300px;
    }

    .message {
        max-width: 85%;
    }

    .send-form {
        flex-direction: column;
        gap: 10px;
    }

    .send-form button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="chat-container">
    <div class="chat-header">
        <a href="my-messages.php" class="back-button">
            <i class="bi bi-arrow-left"></i>
            Back to Messages
        </a>
        <h2 class="chat-title">
            <i class="bi bi-chat-dots-fill"></i>
            <?= htmlspecialchars($pet['name'] ?? 'Chat'); ?>
        </h2>
    </div>

    <div class="messages" id="messages">
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message <?= ($msg['sender_id'] == $user_id) ? 'sent' : 'received' ?>">
                    <strong><?= htmlspecialchars($msg['username']); ?></strong>
                    <div class="message-content">
                        <?= nl2br(htmlspecialchars($msg['message'])); ?>
                    </div>
                    <small>
                        <i class="bi bi-clock"></i>
                        <?= date("M d, Y h:i A", strtotime($msg['created_at'])); ?>
                    </small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-messages">
                <i class="bi bi-chat-text"></i>
                <p>No messages yet. Start the conversation!</p>
            </div>
        <?php endif; ?>
    </div>

    <form method="POST" class="send-form" id="sendForm">
        <textarea 
            name="message" 
            rows="2" 
            placeholder="Type your message..." 
            required 
            id="messageInput"
        ></textarea>
        <button type="submit">
            <i class="bi bi-send-fill"></i>
            Send
        </button>
    </form>
</div>

<script>
let lastMessageCount = 0;

function scrollMessagesToBottom() {
    const container = document.getElementById('messages');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

function renderMessages(messages) {
    const container = document.getElementById('messages');

    // If the number of messages increased → auto update
    if (messages.length > lastMessageCount) {
        container.innerHTML = ""; 

        if (messages.length === 0) {
            container.innerHTML = `
                <div class="no-messages">
                    <i class="bi bi-chat-text"></i>
                    <p>No messages yet. Start the conversation!</p>
                </div>
            `;
        } else {
            messages.forEach(msg => {
                const div = document.createElement('div');
                div.className = "message " + (msg.sender_id == <?= $user_id ?> ? "sent" : "received");
                
                const date = new Date(msg.created_at);
                const formattedDate = date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                }) + ' ' + date.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit', 
                    hour12: true 
                });
                
                div.innerHTML = `
                    <strong>${msg.username}</strong>
                    <div class="message-content">
                        ${msg.message.replace(/\n/g, "<br>")}
                    </div>
                    <small>
                        <i class="bi bi-clock"></i>
                        ${formattedDate}
                    </small>
                `;
                container.appendChild(div);
            });
        }

        scrollMessagesToBottom();
        lastMessageCount = messages.length;
    }
}

function fetchMessages() {
    fetch("fetch-messages.php?pet_id=<?= $pet_id ?>&seller_id=<?= $seller_id ?>")
        .then(res => res.json())
        .then(data => {
            renderMessages(data);
        })
        .catch(err => console.error("Error fetching messages:", err));
}

document.addEventListener("DOMContentLoaded", () => {
    scrollMessagesToBottom(); // Scroll to bottom on initial load
    fetchMessages(); // initial load
    setInterval(fetchMessages, 2000); // check every 2 seconds
});
</script>

<?php include_once "../includes/footer.php"; ?>
<?php ob_end_flush(); ?>