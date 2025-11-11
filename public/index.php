<?php
session_start();
include '../config/db.php';
include_once "../includes/header.php";

// Check verification status from users table
$showVerificationPopup = false;
$verificationMessage = '';
$verificationTitle = '';
$verificationIcon = 'warning';
$showVerifyButton = false;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Check verification_status column in users table
    $query = $conn->prepare("SELECT verification_status FROM users WHERE id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $status = $user['verification_status'];
        
        if ($status == 'not verified') {
            $showVerificationPopup = true;
            $verificationTitle = 'Account Not Verified';
            $verificationMessage = 'Please verify your account to access all features of CatShop.';
            $verificationIcon = 'warning';
            $showVerifyButton = true;
        } elseif ($status == 'pending') {
            $showVerificationPopup = true;
            $verificationTitle = 'Verification Pending';
            $verificationMessage = 'Your verification has been submitted. Please wait for admin approval. We will notify you once your account is verified.';
            $verificationIcon = 'info';
            $showVerifyButton = false;
        }
    }
}
?>

<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f8f5f0;
    }

    /* Carousel Section */
    .carousel-container {
        position: relative;
        width: 100%;
        height: 500px;
        overflow: hidden;
        background: #EADDCA;
    }

    .carousel-slide {
        display: none;
        width: 100%;
        height: 100%;
        position: relative;
    }

    .carousel-slide.active {
        display: block;
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .carousel-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .carousel-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        z-index: 2;
        width: 90%;
        max-width: 800px;
    }

    .carousel-content h1 {
        font-size: 48px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 16px;
        text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
    }

    .carousel-content p {
        font-size: 20px;
        color: #fff;
        margin-bottom: 30px;
        text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);
    }

    .carousel-btn {
        display: inline-block;
        background: #5a4a3a;
        color: #EADDCA;
        padding: 14px 35px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .carousel-btn:hover {
        background: #4a3a2a;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    }

    /* Carousel Navigation */
    .carousel-nav {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 12px;
        z-index: 3;
    }

    .carousel-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        border: 2px solid #fff;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .carousel-dot.active {
        background: #fff;
        width: 40px;
        border-radius: 6px;
    }

    .carousel-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(90, 74, 58, 0.7);
        color: #EADDCA;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        z-index: 3;
    }

    .carousel-arrow:hover {
        background: rgba(90, 74, 58, 0.9);
        transform: translateY(-50%) scale(1.1);
    }

    .carousel-arrow.prev {
        left: 20px;
    }

    .carousel-arrow.next {
        right: 20px;
    }

    /* Overlay for better text readability */
    .carousel-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(0,0,0,0.4));
        z-index: 1;
    }

    /* Featured Section */
    .featured-pets {
        padding: 60px 20px;
        background: #f8f5f0;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .section-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .section-header h2 {
        font-size: 36px;
        font-weight: 700;
        color: #5a4a3a;
        margin-bottom: 12px;
    }

    .section-header p {
        font-size: 18px;
        color: #7d6d5d;
    }

    /* Pet Grid */
    .pet-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
    }

    .pet-card {
        background: #fff;
        border: 2px solid #EADDCA;
        border-radius: 12px;
        padding: 0;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(90, 74, 58, 0.08);
    }

    .pet-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(90, 74, 58, 0.15);
        border-color: #d4c4b0;
    }

    .pet-card-image {
        width: 100%;
        height: 160px;
        object-fit: cover;
        background: #EADDCA;
    }

    .pet-card-content {
        padding: 15px;
    }

    .pet-card h3 {
        font-size: 18px;
        font-weight: 600;
        color: #5a4a3a;
        margin-bottom: 6px;
    }

    .pet-seller {
        font-size: 13px;
        color: #8d7d6d;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .pet-price {
        font-size: 20px;
        font-weight: 700;
        color: #5a4a3a;
        margin: 12px 0;
    }

    .view-btn {
        display: block;
        width: 100%;
        padding: 10px;
        background: #EADDCA;
        color: #5a4a3a;
        border: 2px solid #EADDCA;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        transition: all 0.3s ease;
    }

    .view-btn:hover {
        background: #5a4a3a;
        color: #EADDCA;
        border-color: #5a4a3a;
    }

    .no-pets {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        color: #7d6d5d;
        font-size: 18px;
    }

    .no-pets a {
        color: #5a4a3a;
        font-weight: 600;
        text-decoration: none;
        border-bottom: 2px solid #EADDCA;
        transition: border-color 0.3s ease;
    }

    .no-pets a:hover {
        border-bottom-color: #5a4a3a;
    }

    /* Browse More Button */
    .browse-more-container {
        text-align: center;
        margin-top: 40px;
        padding-top: 20px;
    }

    .browse-more-btn {
        display: inline-block;
        background: #5a4a3a;
        color: #EADDCA;
        padding: 14px 40px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(90, 74, 58, 0.2);
        border: 2px solid #5a4a3a;
    }

    .browse-more-btn:hover {
        background: transparent;
        color: #5a4a3a;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(90, 74, 58, 0.3);
    }

    /* Why Choose Us Section */
    .why-choose-us {
        padding: 80px 20px;
        background: #fff;
    }

    .why-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .why-header h2 {
        font-size: 36px;
        font-weight: 700;
        color: #5a4a3a;
        margin-bottom: 15px;
    }

    .why-header p {
        font-size: 18px;
        color: #7d6d5d;
        max-width: 700px;
        margin: 0 auto;
    }

    .why-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin-bottom: 60px;
    }

    .why-card {
        background: #f8f5f0;
        border: 2px solid #EADDCA;
        border-radius: 16px;
        padding: 35px 25px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .why-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 30px rgba(90, 74, 58, 0.15);
        border-color: #d4c4b0;
        background: #fff;
    }

    .why-icon {
        width: 70px;
        height: 70px;
        background: #EADDCA;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 32px;
        transition: all 0.3s ease;
    }

    .why-card:hover .why-icon {
        background: #5a4a3a;
        transform: scale(1.1);
    }

    .why-card h3 {
        font-size: 22px;
        font-weight: 600;
        color: #5a4a3a;
        margin-bottom: 15px;
    }

    .why-card p {
        font-size: 15px;
        color: #7d6d5d;
        line-height: 1.7;
    }

    /* Testimonials */
    .testimonials {
        background: linear-gradient(135deg, #EADDCA 0%, #d4c4b0 100%);
        padding: 50px 30px;
        border-radius: 20px;
        margin-top: 20px;
    }

    .testimonials h3 {
        text-align: center;
        font-size: 28px;
        font-weight: 700;
        color: #5a4a3a;
        margin-bottom: 40px;
    }

    .testimonial-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
    }

    .testimonial-card {
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(90, 74, 58, 0.1);
        position: relative;
    }

    .testimonial-quote {
        font-size: 48px;
        color: #EADDCA;
        position: absolute;
        top: 10px;
        left: 20px;
        line-height: 1;
    }

    .testimonial-text {
        font-size: 15px;
        color: #5a4a3a;
        line-height: 1.7;
        margin-bottom: 20px;
        font-style: italic;
        padding-top: 25px;
    }

    .testimonial-author {
        display: flex;
        align-items: center;
        gap: 15px;
        padding-top: 15px;
        border-top: 2px solid #EADDCA;
    }

    .author-avatar {
        width: 50px;
        height: 50px;
        background: #EADDCA;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 600;
        color: #5a4a3a;
    }

    .author-info h4 {
        font-size: 16px;
        font-weight: 600;
        color: #5a4a3a;
        margin-bottom: 3px;
    }

    .author-info p {
        font-size: 13px;
        color: #8d7d6d;
    }

    .rating {
        color: #e67e22;
        font-size: 16px;
        margin-top: 10px;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .pet-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    @media (max-width: 992px) {
        .pet-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .carousel-container {
            height: 400px;
        }

        .carousel-content h1 {
            font-size: 32px;
        }

        .carousel-content p {
            font-size: 16px;
        }

        .carousel-arrow {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }

        .section-header h2 {
            font-size: 28px;
        }

        .pet-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
    }

    @media (max-width: 480px) {
        .carousel-container {
            height: 300px;
        }

        .carousel-content h1 {
            font-size: 24px;
        }

        .carousel-content p {
            font-size: 14px;
        }

        .carousel-arrow {
            width: 35px;
            height: 35px;
        }

        .carousel-arrow.prev {
            left: 10px;
        }

        .carousel-arrow.next {
            right: 10px;
        }

        .pet-grid {
            grid-template-columns: 1fr;
        }

        .why-grid {
            grid-template-columns: 1fr;
        }

        .testimonial-grid {
            grid-template-columns: 1fr;
        }

        .why-choose-us {
            padding: 60px 20px;
        }
    }
</style>

<!-- Carousel Section -->
<section class="carousel-container">
    <!-- Slide 1 -->
    <div class="carousel-slide active">
        <img src="../uploads/carousel1.avif" alt="Welcome to CatShop">
        <div class="carousel-overlay"></div>
        <div class="carousel-content">
            <h1>Welcome to CatShop 🐾</h1>
            <p>Find your perfect furry friend or sell your pet safely in our trusted community.</p>
            <a href="sell.php" class="carousel-btn">+ Sell Your Pet</a>
        </div>
    </div>

    <!-- Slide 2 -->
    <div class="carousel-slide">
        <img src="../uploads/carousel2.avif" alt="Trusted Pet Marketplace">
        <div class="carousel-overlay"></div>
        <div class="carousel-content">
            <h1>Trusted Pet Marketplace</h1>
            <p>Connect with verified sellers and find healthy, happy pets.</p>
            <a href="products.php" class="carousel-btn">Browse Pets</a>
        </div>
    </div>

    <!-- Slide 3 -->
    <div class="carousel-slide">
        <img src="../uploads/carousel3.avif" alt="Adopt a Pet Today">
        <div class="carousel-overlay"></div>
        <div class="carousel-content">
            <h1>Adopt a Pet Today</h1>
            <p> We ensure all animals are healthy and ready for adoption.</p>
            <a href="adoption.php" class="carousel-btn"> Start Adopting</a>
        </div>
    </div>

    <!-- Slide 4 -->
    <div class="carousel-slide">
        <img src="../uploads/carousel4.avif" alt="Join Our Community">
        <div class="carousel-overlay"></div>
        <div class="carousel-content">
            <h1>Join Our Community</h1>
            <p>Thousands of happy pet owners have found their companions here.</p>
            <a href="register.php" class="carousel-btn">Sign Up Now</a>
        </div>
    </div>

    <!-- Navigation Arrows -->
    <button class="carousel-arrow prev" onclick="changeSlide(-1)">‹</button>
    <button class="carousel-arrow next" onclick="changeSlide(1)">›</button>

    <!-- Navigation Dots -->
    <div class="carousel-nav">
        <span class="carousel-dot active" onclick="currentSlide(1)"></span>
        <span class="carousel-dot" onclick="currentSlide(2)"></span>
        <span class="carousel-dot" onclick="currentSlide(3)"></span>
        <span class="carousel-dot" onclick="currentSlide(4)"></span>
    </div>
</section>

<script>
let slideIndex = 1;
let slideTimer;

// Auto-play carousel
function autoSlide() {
    slideTimer = setInterval(() => {
        changeSlide(1);
    }, 5000); // Change slide every 5 seconds
}

// Start auto-play on page load
autoSlide();

function changeSlide(n) {
    clearInterval(slideTimer);
    showSlide(slideIndex += n);
    autoSlide();
}

function currentSlide(n) {
    clearInterval(slideTimer);
    showSlide(slideIndex = n);
    autoSlide();
}

function showSlide(n) {
    let slides = document.getElementsByClassName("carousel-slide");
    let dots = document.getElementsByClassName("carousel-dot");
    
    if (n > slides.length) { slideIndex = 1 }
    if (n < 1) { slideIndex = slides.length }
    
    for (let i = 0; i < slides.length; i++) {
        slides[i].classList.remove("active");
    }
    
    for (let i = 0; i < dots.length; i++) {
        dots[i].classList.remove("active");
    }
    
    slides[slideIndex - 1].classList.add("active");
    dots[slideIndex - 1].classList.add("active");
}
</script>

<!-- Featured Pets -->
<section class="featured-pets">
    <div class="container">
        <div class="section-header">
            <h2>🐾 Newly Listed Pets</h2>
            <p>Hand-picked furry friends looking for a loving home</p>
        </div>

        <div class="pet-grid">
            <?php
            // Fetch ONLY AVAILABLE pets with seller info + first image
            $result = $conn->query("
                SELECT p.id, p.name, p.breed, p.age, p.price, p.status, u.username,
                       COALESCE(
                           (
                               SELECT pi.filename 
                               FROM pet_images pi 
                               WHERE pi.pet_id = p.id 
                               ORDER BY pi.id ASC 
                               LIMIT 1
                           ),
                           p.image
                       ) AS image
                FROM pets p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.status = 'available'
                ORDER BY p.id DESC
                LIMIT 10
            ");

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()):
                    $image = $row['image'] ?: 'no-image.png';
            ?>
            <div class="pet-card">
                <img src="../uploads/<?php echo htmlspecialchars($image); ?>" 
                     alt="<?php echo htmlspecialchars($row['name']); ?>"
                     class="pet-card-image">
                <div class="pet-card-content">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p class="pet-seller">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <?php echo htmlspecialchars($row['username'] ?? 'Unknown'); ?>
                    </p>
                    <p class="pet-price">₱<?php echo number_format($row['price'], 2); ?></p>
                    <a href="pet-details.php?id=<?php echo $row['id']; ?>" class="view-btn">
                        View Details
                    </a>
                </div>
            </div>
            <?php
                endwhile;
            } else {
                echo '<div class="no-pets"><p>No pets available right now. Be the first to <a href="sell.php">sell your pet</a>!</p></div>';
            }
            ?>
        </div>

        <!-- Browse More Button -->
        <div class="browse-more-container">
            <a href="products.php" class="browse-more-btn">Browse More Pets →</a>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="why-choose-us">
    <div class="container">
        <div class="why-header">
            <h2>Why Choose CatShop? 🌟</h2>
            <p>We're committed to connecting loving families with healthy, happy pets through a safe and trusted platform</p>
        </div>

        <div class="why-grid">
            <!-- Responsible Sourcing -->
            <div class="why-card">
                <div class="why-icon">🏆</div>
                <h3>Verified Sellers Only</h3>
                <p>All our sellers go through a strict verification process to ensure they meet our ethical breeding and care standards. Your pet's wellbeing is our top priority.</p>
            </div>

            <!-- Health Certifications -->
            <div class="why-card">
                <div class="why-icon">🏥</div>
                <h3>Health Guaranteed</h3>
                <p>Every pet comes with complete vet health checks, up-to-date vaccinations, and medical records. We ensure your new companion is healthy and ready for their forever home.</p>
            </div>

            <!-- First-Time Owner Support -->
            <div class="why-card">
                <div class="why-icon">📚</div>
                <h3>First-Time Owner Resources</h3>
                <p>New to pet ownership? We provide comprehensive guides, 24/7 support, and expert advice to help you give your pet the best care from day one.</p>
            </div>

            <!-- Safe Transactions -->
            <div class="why-card">
                <div class="why-icon">🔒</div>
                <h3>Safe & Secure Platform</h3>
                <p>Our platform ensures secure transactions, verified identities, and protected communications. Buy and sell with complete peace of mind.</p>
            </div>

            <!-- Quality Assurance -->
            <div class="why-card">
                <div class="why-icon">✓</div>
                <h3>Quality Assurance</h3>
                <p>We maintain high standards for pet care, breeding practices, and seller accountability. Only the best make it to our marketplace.</p>
            </div>

            <!-- Post-Purchase Support -->
            <div class="why-card">
                <div class="why-icon">💝</div>
                <h3>Lifetime Support</h3>
                <p>Our relationship doesn't end at purchase. We offer ongoing support, advice, and resources throughout your pet's entire life journey.</p>
            </div>
        </div>
    </div>
</section>

<?php include_once "../includes/footer.php"; ?>

<!-- Popup for verification status -->
<?php if ($showVerificationPopup): ?>
<script>
<?php if ($showVerifyButton): ?>
Swal.fire({
    title: '<?php echo $verificationTitle; ?>',
    text: '<?php echo $verificationMessage; ?>',
    icon: '<?php echo $verificationIcon; ?>',
    showCancelButton: true,
    confirmButtonText: 'Verify Now',
    cancelButtonText: 'Later',
    confirmButtonColor: '#5a4a3a',
    cancelButtonColor: '#8d7d6d',
}).then((result) => {
    if (result.isConfirmed) {
        window.location.href = 'verify.php';
    }
});
<?php else: ?>
Swal.fire({
    title: '<?php echo $verificationTitle; ?>',
    text: '<?php echo $verificationMessage; ?>',
    icon: '<?php echo $verificationIcon; ?>',
    confirmButtonText: 'OK',
    confirmButtonColor: '#5a4a3a',
});
<?php endif; ?>
</script>
<?php endif; ?>