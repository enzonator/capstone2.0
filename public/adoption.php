<?php
session_start();
require_once "../config/db.php";
include_once "../includes/header.php";

// Fetch available and pending cats for adoption with application counts
$sql = "SELECT ac.*, 
        (SELECT COUNT(*) FROM adoption_applications WHERE cat_id = ac.id) as application_count
        FROM adoption_cats ac 
        WHERE ac.status IN ('Available', 'Pending') 
        ORDER BY ac.created_at DESC";
$result = $conn->query($sql);
$cats = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cats[] = $row;
    }
}
?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f5f0e8 0%, #e8dcc8 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .adoption-header {
        text-align: center;
        margin-bottom: 30px;
        background: white;
        padding: 40px 20px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .adoption-header h1 {
        font-size: 3em;
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .subtitle {
        font-size: 1.3em;
        color: #7f8c8d;
    }

    .info-banner {
        background: linear-gradient(135deg, #d4c4a8 0%, #c9b896 100%);
        color: #3d3020;
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(234, 221, 202, 0.4);
    }

    .info-banner h3 {
        font-size: 1.8em;
        margin-bottom: 10px;
    }

    .info-banner p {
        font-size: 1.1em;
        line-height: 1.6;
    }

    .filter-section {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .search-box, .filter-select {
        padding: 12px 20px;
        border: 2px solid #ddd;
        border-radius: 25px;
        font-size: 1em;
        outline: none;
        transition: all 0.3s;
    }

    .search-box {
        flex: 1;
        min-width: 250px;
    }

    .search-box:focus, .filter-select:focus {
        border-color: #c9b896;
        box-shadow: 0 0 0 3px rgba(234, 221, 202, 0.3);
    }

    .filter-select {
        background: white;
        cursor: pointer;
    }

    .add-cat-btn {
        padding: 12px 25px;
        background: linear-gradient(135deg, #d4c4a8 0%, #c9b896 100%);
        color: #3d3020;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        font-size: 1em;
        transition: all 0.3s;
        white-space: nowrap;
    }

    .add-cat-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(234, 221, 202, 0.5);
        background: linear-gradient(135deg, #EADDCA 0%, #d4c4a8 100%);
    }

    .cats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 30px;
    }

    .cat-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transition: transform 0.3s, box-shadow 0.3s;
        animation: fadeIn 0.5s;
        position: relative;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .cat-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .cat-card.hidden {
        display: none;
    }

    .cat-image {
        position: relative;
        height: 280px;
        overflow: hidden;
    }

    .cat-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .badge {
        position: absolute;
        top: 10px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: bold;
        color: white;
        z-index: 10;
    }

    .badge.vaccinated {
        left: 10px;
        background: #27ae60;
    }

    .badge.neutered {
        right: 10px;
        background: #3498db;
    }

    .status-badge {
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9em;
        font-weight: bold;
        background: #ffc107;
        color: #000;
        z-index: 10;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .application-count-badge {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: bold;
        box-shadow: 0 3px 10px rgba(255, 107, 53, 0.4);
        z-index: 10;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .cat-details {
        padding: 20px;
    }

    .cat-details h3 {
        font-size: 1.8em;
        color: #2c3e50;
        margin-bottom: 15px;
    }

    .cat-info {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 15px;
    }

    .info-item {
        color: #555;
        font-size: 0.95em;
    }

    .description {
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .health-status {
        background: #e8f5e9;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
        color: #2e7d32;
    }

    .adoption-fee {
        font-size: 1.3em;
        color: #e74c3c;
        margin-bottom: 15px;
        font-weight: bold;
    }

    .adopt-btn {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #d4c4a8 0%, #c9b896 100%);
        color: #3d3020;
        border: none;
        border-radius: 10px;
        font-size: 1.1em;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
    }

    .adopt-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(234, 221, 202, 0.5);
        background: linear-gradient(135deg, #EADDCA 0%, #d4c4a8 100%);
    }

    .no-cats {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 15px;
        font-size: 1.2em;
        color: #7f8c8d;
    }

    @media (max-width: 768px) {
        .adoption-header h1 {
            font-size: 2em;
        }
        
        .cats-grid {
            grid-template-columns: 1fr;
        }
        
        .filter-section {
            flex-direction: column;
        }
        
        .search-box, .filter-select {
            width: 100%;
        }
    }
</style>

<div class="container">
    <header class="adoption-header">
        <h1>🐱 Adopt a Cat</h1>
        <p class="subtitle">Give a loving cat a forever home</p>
    </header>

    <div class="info-banner">
        <h3>Why Adopt?</h3>
        <p>Every cat deserves a loving home. Adoption saves lives and gives cats a second chance at happiness. All our adoption cats are health-checked, vaccinated, and ready for their new families.</p>
    </div>

    <div class="filter-section">
        <input type="text" id="searchInput" placeholder="Search by name or breed..." class="search-box">
        <select id="genderFilter" class="filter-select">
            <option value="">All Genders</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
        <select id="ageFilter" class="filter-select">
            <option value="">All Ages</option>
            <option value="kitten">Kitten (0-1 year)</option>
            <option value="young">Young (1-3 years)</option>
            <option value="adult">Adult (3-7 years)</option>
            <option value="senior">Senior (7+ years)</option>
        </select>
        <a href="add-adoption-cat.php" class="add-cat-btn">+ List Cat for Adoption</a>
    </div>

    <div class="cats-grid" id="catsGrid">
        <?php if (empty($cats)): ?>
            <div class="no-cats">
                <p>No cats available for adoption at the moment. Please check back soon!</p>
            </div>
        <?php else: ?>
            <?php foreach ($cats as $cat): ?>
                <div class="cat-card" 
                     data-name="<?php echo htmlspecialchars(strtolower($cat['name'])); ?>"
                     data-breed="<?php echo htmlspecialchars(strtolower($cat['breed'])); ?>"
                     data-gender="<?php echo htmlspecialchars($cat['gender']); ?>"
                     data-age="<?php echo $cat['age']; ?>">
                    
                    <div class="cat-image">
                        <img src="../uploads/<?php echo htmlspecialchars($cat['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($cat['name']); ?>">
                        
                        <?php if ($cat['status'] === 'Pending'): ?>
                            <span class="status-badge">⏳ Pending Review</span>
                        <?php endif; ?>
                        
                        <?php if ($cat['vaccinated']): ?>
                            <span class="badge vaccinated">✓ Vaccinated</span>
                        <?php endif; ?>
                        <?php if ($cat['neutered']): ?>
                            <span class="badge neutered">✓ Neutered/Spayed</span>
                        <?php endif; ?>
                        
                        <?php if ($cat['application_count'] > 0): ?>
                            <span class="application-count-badge">
                                <span>👥</span>
                                <span><?php echo $cat['application_count']; ?> <?php echo $cat['application_count'] == 1 ? 'application' : 'applications'; ?></span>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cat-details">
                        <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                        <div class="cat-info">
                            <span class="info-item"><strong>Breed:</strong> <?php echo htmlspecialchars($cat['breed']); ?></span>
                            <span class="info-item"><strong>Age:</strong> <?php echo $cat['age']; ?> year(s)</span>
                            <span class="info-item"><strong>Gender:</strong> <?php echo htmlspecialchars($cat['gender']); ?></span>
                        </div>
                        <p class="description"><?php echo htmlspecialchars($cat['description']); ?></p>
                        
                        <?php if ($cat['health_status']): ?>
                            <div class="health-status">
                                <strong>Health:</strong> <?php echo htmlspecialchars($cat['health_status']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="adoption-fee">
                            <strong>Adoption Fee:</strong> ₱<?php echo number_format($cat['adoption_fee'], 2); ?>
                        </div>
                        
                        <button class="adopt-btn" onclick="openAdoptionForm(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>')">
                            <?php echo $cat['status'] === 'Pending' ? 'Apply to Adopt (Under Review)' : 'Apply to Adopt'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    // Filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const genderFilter = document.getElementById('genderFilter');
        const ageFilter = document.getElementById('ageFilter');
        const catCards = document.querySelectorAll('.cat-card');

        function filterCats() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedGender = genderFilter.value;
            const selectedAge = ageFilter.value;

            catCards.forEach(card => {
                const name = card.dataset.name;
                const breed = card.dataset.breed;
                const gender = card.dataset.gender;
                const age = parseInt(card.dataset.age);

                // Search filter
                const matchesSearch = name.includes(searchTerm) || breed.includes(searchTerm);

                // Gender filter
                const matchesGender = !selectedGender || gender === selectedGender;

                // Age filter
                let matchesAge = true;
                if (selectedAge === 'kitten') matchesAge = age <= 1;
                else if (selectedAge === 'young') matchesAge = age > 1 && age <= 3;
                else if (selectedAge === 'adult') matchesAge = age > 3 && age <= 7;
                else if (selectedAge === 'senior') matchesAge = age > 7;

                // Show or hide card
                if (matchesSearch && matchesGender && matchesAge) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        // Add event listeners
        searchInput.addEventListener('input', filterCats);
        genderFilter.addEventListener('change', filterCats);
        ageFilter.addEventListener('change', filterCats);
    });

    // Open adoption form
    function openAdoptionForm(catId, catName) {
        // Redirect to adoption application form
        window.location.href = `adoption-form.php?cat_id=${catId}&cat_name=${encodeURIComponent(catName)}`;
    }
</script>

<?php include_once "../includes/footer.php"; ?>