<?php
// Récupérer les articles (posts) avec les infos de l'auteur
$articles = $pdo->query("SELECT a.id, a.title, a.content, a.image as image_url, a.created_at, CONCAT(u.prenom, ' ', u.nom) as author_name, u.profile_pic FROM posts a JOIN users u ON a.user_id = u.id WHERE a.status = 'approved' ORDER BY a.created_at DESC LIMIT 10")->fetchAll();
?>

<section class="section" style="background: linear-gradient(to bottom, #ffffff, #f9fafb); padding: 60px 0; border-top: 1px solid var(--border);">
    <div class="container">
        <!-- En-tête de section -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
            <div>
                <h2 style="font-size: 2rem; color: var(--brand); font-weight: 800; margin-bottom: 5px; position: relative; display: inline-block;">
                    À la Une
                    <span style="position: absolute; bottom: 5px; right: -15px; width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></span>
                </h2>
                <p class="text-muted" style="font-size: 1.1rem;">Découvrez les derniers articles de la communauté</p>
            </div>
            
            <a href="add_article.php" class="btn-accent" style="display: flex; align-items: center; gap: 8px; box-shadow: var(--shadow-sm); transition: transform 0.2s;">
                <i class="fas fa-pen-nib"></i> 
                <span>Rédiger un article</span>
            </a>
        </div>
        
        <?php if (count($articles) > 0): ?>
            <!-- Carousel Container -->
            <div class="articles-carousel-wrapper" style="position: relative;">
                
                <!-- Boutons de navigation -->
                <button id="prevBtn" class="nav-btn prev">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <button id="nextBtn" class="nav-btn next">
                    <i class="fas fa-arrow-right"></i>
                </button>
                
                <!-- Piste des articles -->
                <div class="articles-track-container" style="overflow: hidden; padding: 10px 5px; margin: 0 -5px;">
                    <div class="articles-track" id="articlesTrack">
                        <?php foreach ($articles as $article): ?>
                        <div class="article-item">
                            <div class="article-card">
                                <div class="article-image-wrapper">
                                    <?php if ($article['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                    <?php else: ?>
                                        <div class="article-placeholder">
                                            <i class="fas fa-newspaper"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="article-overlay">
                                        <span class="read-more-btn">Lire l'article</span>
                                    </div>
                                </div>
                                
                                <div class="article-content">
                                    <div class="article-meta">
                                        <span class="article-date"><i class="far fa-clock"></i> <?php echo date('d M', strtotime($article['created_at'])); ?></span>
                                    </div>
                                    
                                    <h3 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                                    
                                    <p class="article-excerpt">
                                        <?php echo htmlspecialchars(substr($article['content'], 0, 80)) . (strlen($article['content']) > 80 ? '...' : ''); ?>
                                    </p>
                                    
                                    <div class="article-footer">
                                        <div class="author-info">
                                            <img src="uploads/profiles/<?php echo htmlspecialchars($article['profile_pic'] ?: 'default.png'); ?>" alt="Author">
                                            <span><?php echo htmlspecialchars($article['author_name']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Empty State Design -->
            <div class="empty-state-card">
                <div class="empty-state-icon">
                    <i class="fas fa-feather-alt"></i>
                </div>
                <div class="empty-state-content">
                    <h3>Soyez le premier à inspirer !</h3>
                    <p>Aucun article n'a encore été publié. C'est l'occasion parfaite pour partager votre expertise ou vos idées avec la communauté.</p>
                    <a href="add_article.php" class="btn-submit pulse-effect">
                        Commencer à écrire
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    /* Carousel Layout */
    .articles-track {
        display: flex;
        gap: 25px;
        transition: transform 0.5s cubic-bezier(0.25, 1, 0.5, 1);
    }
    
    .article-item {
        min-width: 320px;
        max-width: 320px;
        flex-shrink: 0;
    }

    /* Card Design */
    .article-card {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .article-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.1);
    }

    /* Image Area */
    .article-image-wrapper {
        position: relative;
        height: 180px;
        overflow: hidden;
    }
    
    .article-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .article-card:hover .article-image-wrapper img {
        transform: scale(1.05);
    }
    
    .article-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #e0f2fe, #f0f9ff);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--brand);
        font-size: 3rem;
        opacity: 0.7;
    }
    
    .article-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.3);
        opacity: 0;
        transition: opacity 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .article-card:hover .article-overlay {
        opacity: 1;
    }
    
    .read-more-btn {
        background: rgba(255,255,255,0.9);
        color: var(--text);
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
        transform: translateY(10px);
        transition: transform 0.3s;
    }
    
    .article-card:hover .read-more-btn {
        transform: translateY(0);
    }

    /* Content Area */
    .article-content {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .article-meta {
        margin-bottom: 8px;
    }
    
    .article-date {
        font-size: 0.8rem;
        color: var(--muted);
        font-weight: 500;
        background: #f1f5f9;
        padding: 4px 8px;
        border-radius: 6px;
    }
    
    .article-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 10px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .article-excerpt {
        font-size: 0.95rem;
        color: var(--muted);
        margin-bottom: 20px;
        line-height: 1.6;
        flex: 1;
    }
    
    .article-footer {
        border-top: 1px solid #f1f5f9;
        padding-top: 15px;
        margin-top: auto;
    }
    
    .author-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .author-info img {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .author-info span {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text);
    }

    /* Navigation Buttons */
    .nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #fff;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        color: var(--brand);
        font-size: 1.1rem;
        cursor: pointer;
        z-index: 10;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .nav-btn:hover {
        background: var(--brand);
        color: #fff;
        transform: translateY(-50%) scale(1.1);
    }
    
    .prev { left: -22px; }
    .next { right: -22px; }
    
    @media (max-width: 768px) {
        .prev { left: 0; }
        .next { right: 0; }
    }

    /* Empty State */
    .empty-state-card {
        background: #fff;
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        border: 2px dashed #e2e8f0;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .empty-state-icon {
        width: 80px;
        height: 80px;
        background: #f0f9ff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: var(--brand);
        font-size: 2.5rem;
    }
    
    .empty-state-content h3 {
        font-size: 1.5rem;
        margin-bottom: 10px;
        color: var(--text);
    }
    
    .empty-state-content p {
        color: var(--muted);
        margin-bottom: 25px;
    }
    
    .pulse-effect {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(15, 118, 110, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(15, 118, 110, 0); }
        100% { box-shadow: 0 0 0 0 rgba(15, 118, 110, 0); }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const track = document.getElementById('articlesTrack');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    if(!track || !prevBtn || !nextBtn) return;

    // Width of card (320px) + gap (25px)
    const itemWidth = 345; 
    let scrollPosition = 0;
    
    function updateButtons() {
        const maxScroll = track.scrollWidth - track.clientWidth;
        // Simple opacity toggle for visual feedback
        prevBtn.style.opacity = scrollPosition <= 0 ? '0.5' : '1';
        nextBtn.style.opacity = scrollPosition >= maxScroll ? '0.5' : '1';
    }

    nextBtn.addEventListener('click', () => {
        const maxScroll = track.scrollWidth - track.clientWidth;
        if (scrollPosition < maxScroll) {
            scrollPosition += itemWidth;
            if (scrollPosition > maxScroll) scrollPosition = maxScroll;
            track.style.transform = `translateX(-${scrollPosition}px)`;
            updateButtons();
        }
    });
    
    prevBtn.addEventListener('click', () => {
        if (scrollPosition > 0) {
            scrollPosition -= itemWidth;
            if (scrollPosition < 0) scrollPosition = 0;
            track.style.transform = `translateX(-${scrollPosition}px)`;
            updateButtons();
        }
    });
    
    // Init state
    updateButtons();
});
</script>