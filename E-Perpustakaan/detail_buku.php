<?php
require_once __DIR__ . '/koneksi.php';

$id_buku = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_buku) {
    header('Location: index.php');
    exit;
}

// Ambil detail buku
$stmt = $pdo->prepare('SELECT * FROM buku WHERE id_buku = ?');
$stmt->execute([$id_buku]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: index.php');
    exit;
}

// Ambil rating & review
$stmt = $pdo->prepare('SELECT nama_pengunjung, rating, review, created_at 
                       FROM rating_review WHERE id_buku = ? 
                       ORDER BY created_at DESC LIMIT 10');
$stmt->execute([$id_buku]);
$reviews = $stmt->fetchAll();

// Ambil rekomendasi
$stmt = $pdo->prepare('SELECT id_buku, judul, pengarang, cover_buku 
                       FROM buku 
                       WHERE kategori = ? AND id_buku != ? 
                       ORDER BY total_rating DESC LIMIT 4');
$stmt->execute([$book['kategori'], $id_buku]);
$rekomendasi = $stmt->fetchAll();

// Hitung rating bintang
$ratingValue = floatval($book['total_rating'] ?? 0);
$jumlahRating = intval($book['jumlah_rating'] ?? 0);
$fullStars = floor($ratingValue);
$hasHalfStar = ($ratingValue - $fullStars) >= 0.5;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($book['judul']) ?> - Perpustakaan Cendekia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Efek timbul untuk card */
        .detail-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: var(--glass);
            backdrop-filter: blur(16px);
            border-radius: 24px;
        }
        .detail-card:hover { transform: translateY(-8px); box-shadow: 0 25px 45px -12px rgba(0,0,0,0.2); }
        
        /* Efek timbul untuk tombol */
        .btn-primary, .nav-cta { transition: all 0.35s cubic-bezier(0.2,0.9,0.4,1.1); position: relative; overflow: hidden; }
        .btn-primary:hover, .nav-cta:hover { transform: translateY(-4px) scale(1.02); box-shadow: 0 15px 30px -10px rgba(214,168,79,0.4); }
        .btn-primary:active, .nav-cta:active { transform: translateY(0px) scale(0.98); }
        
        /* Efek shimmer */
        .btn-primary::before, .nav-cta::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        .btn-primary:hover::before, .nav-cta:hover::before { left: 100%; }
        
        /* Efek timbul untuk review card */
        .review-item { transition: all 0.35s cubic-bezier(0.2,0.9,0.4,1.1); }
        .review-item:hover { transform: translateX(8px) translateY(-3px); box-shadow: 0 10px 25px -10px rgba(0,0,0,0.15); }
        
        /* Efek timbul untuk rekomendasi */
        .rek-item { transition: all 0.35s cubic-bezier(0.175,0.885,0.32,1.275); }
        .rek-item:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 15px 30px -12px rgba(0,0,0,0.2); }
        
        /* Efek timbul untuk input */
        input, select, textarea { transition: all 0.3s cubic-bezier(0.2,0.9,0.4,1.1); border: 2px solid #e0e0e0; }
        input:hover, select:hover, textarea:hover { transform: translateY(-2px); border-color: var(--gold); box-shadow: 0 5px 15px -8px rgba(214,168,79,0.3); }
        input:focus, select:focus, textarea:focus { transform: translateY(-3px); border-color: var(--gold); box-shadow: 0 0 0 4px rgba(214,168,79,0.15); outline: none; }
        
        /* Efek rating star */
        .rating-star { transition: all 0.2s ease; cursor: pointer; }
        .rating-star:hover { transform: scale(1.2); filter: drop-shadow(0 0 5px rgba(214,168,79,0.5)); }
        
        /* Animasi fade in */
        .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; transform: translateY(30px); }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        
        /* Scroll reveal */
        .scroll-reveal { opacity: 0; transform: translateY(30px); transition: all 0.6s cubic-bezier(0.25,0.46,0.45,0.94); }
        .scroll-reveal.revealed { opacity: 1; transform: translateY(0); }
        
        /* Dark mode adjustment */
        .dark input, .dark select, .dark textarea { background: #1a2a4a; border-color: #3a4a6a; color: white; }
    </style>
</head>
<body>
<header id="navbar" class="site-header fixed left-0 right-0 top-0 z-50 py-4">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 lg:px-8">
        <a href="index.php" class="brand flex items-center gap-3">
            <span class="brand-icon"><i class="fa-solid fa-layer-group"></i></span>
            <span>
                <strong class="text-lg">Cendekia Library</strong>
                <small class="text-xs text-gray-500 block">Perpustakaan Cendekia Nusantara</small>
            </span>
        </a>
        <a href="index.php" class="back-button inline-flex items-center gap-2 px-5 py-2 rounded-full bg-gradient-to-r from-[#f4d991] to-[#d6a84f] text-[#071a35] font-semibold text-sm transition-all duration-300 hover:scale-105 hover:shadow-lg">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
        </a>
    </nav>
</header>

    <main class="pt-32 pb-20 px-5">
        <div class="mx-auto max-w-6xl">
            <!-- Detail Buku: Cover & Info -->
            <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] mb-10 fade-in">
                <!-- Cover -->
                <div class="detail-card p-6">
                    <?php if ($book['cover_buku']): ?>
                        <img src="<?= htmlspecialchars($book['cover_buku']) ?>" alt="<?= htmlspecialchars($book['judul']) ?>" class="w-full rounded-xl shadow-lg transition-transform duration-500 hover:scale-105">
                    <?php else: ?>
                        <div class="bg-gradient-to-br from-[#0d2b55] to-[#1557a8] h-96 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-book-open text-7xl text-white/50"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Info Buku -->
                <div class="detail-card p-6">
                    <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($book['judul']) ?></h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        <i class="fa-solid fa-user"></i> <?= htmlspecialchars($book['pengarang']) ?> | 
                        <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($book['kategori']) ?> | 
                        <i class="fa-solid fa-calendar"></i> <?= $book['tahun_terbit'] ?>
                    </p>
                    
                    <!-- Rating -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex gap-1">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $fullStars): ?>
                                    <i class="fa-solid fa-star rating-star" style="color: #d6a84f;"></i>
                                <?php elseif ($i == $fullStars + 1 && $hasHalfStar): ?>
                                    <i class="fa-solid fa-star-half-alt rating-star" style="color: #d6a84f;"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star rating-star" style="color: #d6a84f;"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="text-sm text-gray-500">(<?= $jumlahRating ?> ulasan)</span>
                    </div>
                    
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-6">
                        <?= htmlspecialchars($book['deskripsi'] ?? 'Belum ada deskripsi untuk buku ini.') ?>
                    </p>
                    
                    <div class="flex items-center gap-4 flex-wrap">
                        <span class="bg-green-100 dark:bg-green-900/30 px-4 py-2 rounded-full">
                            <i class="fa-solid fa-box"></i> Stok: <?= $book['stok'] ?>
                        </span>
                        <a href="index.php?pinjam=<?= $book['id_buku'] ?>#pinjam" class="btn-primary inline-block bg-gradient-to-r from-[#f4d991] to-[#d6a84f] text-[#071a35] font-bold px-6 py-2 rounded-full">
                            <i class="fa-solid fa-hand-peace"></i> Pinjam Buku
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Rating & Review -->
            <div class="detail-card p-6 mb-10 scroll-reveal">
                <h2 class="text-2xl font-bold mb-6"><i class="fa-solid fa-star" style="color: #d6a84f;"></i> Rating & Review</h2>
                
                <form id="reviewForm" class="mb-8 p-6" style="border-radius: 28px;">
                    <input type="hidden" name="id_buku" value="<?= $book['id_buku'] ?>">
                    
                    <div class="grid gap-5 md:grid-cols-2">
                        <!-- Kolom Nama -->
                        <div>
                            <label class="block mb-2 font-semibold text-sm">
                                <i class="fa-solid fa-user mr-2" style="color: #d6a84f;"></i> Nama Anda
                            </label>
                            <input type="text" name="nama" placeholder="Contoh: Ahmad Wijaya" required 
                                class="w-full px-4 py-3 rounded-2xl border-2 border-gray-200 focus:border-[#d6a84f] focus:ring-4 focus:ring-[#d6a84f]/20 transition-all">
                        </div>
                        
                        <!-- Kolom Rating -->
                        <div>
                            <label class="block mb-2 font-semibold text-sm">
                                <i class="fa-solid fa-star mr-2" style="color: #d6a84f;"></i> Rating
                            </label>
                            <select name="rating" required 
                                    class="w-full px-4 py-3 rounded-2xl border-2 border-gray-200 focus:border-[#d6a84f] focus:ring-4 focus:ring-[#d6a84f]/20 transition-all">
                                <option value="">Pilih rating</option>
                                <option value="5">⭐⭐⭐⭐⭐ - Luar biasa</option>
                                <option value="4">⭐⭐⭐⭐ - Bagus</option>
                                <option value="3">⭐⭐⭐ - Cukup</option>
                                <option value="2">⭐⭐ - Kurang</option>
                                <option value="1">⭐ - Buruk</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Kolom Review -->
                    <div class="mt-5">
                        <label class="block mb-2 font-semibold text-sm">
                            <i class="fa-solid fa-message mr-2" style="color: #d6a84f;"></i> Review (opsional)
                        </label>
                        <textarea name="review" rows="3" placeholder="Tulis pendapat Anda tentang buku ini..." 
                                class="w-full px-4 py-3 rounded-2xl border-2 border-gray-200 focus:border-[#d6a84f] focus:ring-4 focus:ring-[#d6a84f]/20 transition-all resize-none"></textarea>
                    </div>
                    
                    <!-- Tombol Submit -->
                    <button type="submit" id="submitReview" 
                            class="mt-5 px-6 py-3 bg-gradient-to-r from-[#f4d991] to-[#d6a84f] text-[#071a35] font-bold rounded-2xl w-full transition-all hover:scale-[1.02] active:scale-[0.98]">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Review
                    </button>
                </form>
                
                <!-- Daftar Review yang sudah masuk -->
                <div id="reviewsList" class="space-y-4">
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="p-5" style="border-radius: 20px;">
                                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-user-circle text-xl" style="color: #d6a84f;"></i>
                                        <span class="font-semibold"><?= htmlspecialchars($review['nama_pengunjung']) ?></span>
                                    </div>
                                    <span class="text-xs text-gray-500"><?= date('d M Y', strtotime($review['created_at'])) ?></span>
                                </div>
                                <div class="flex gap-1 mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $review['rating']): ?>
                                            <i class="fa-solid fa-star" style="color: #d6a84f; font-size: 13px;"></i>
                                        <?php else: ?>
                                            <i class="fa-regular fa-star" style="color: #d6a84f; font-size: 13px;"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed"><?= htmlspecialchars($review['review'] ?? 'Tidak ada review') ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-10 text-gray-500">
                            <i class="fa-regular fa-star text-4xl mb-2 block"></i>
                            <p>Belum ada review. Jadilah yang pertama!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Rekomendasi Buku -->
            <?php if (count($rekomendasi) > 0): ?>
            <div class="detail-card p-6 scroll-reveal">
                <h2 class="text-2xl font-bold mb-6"><i class="fa-solid fa-thumbs-up"></i> Rekomendasi Lainnya</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach ($rekomendasi as $rek): ?>
                        <a href="detail_buku.php?id=<?= $rek['id_buku'] ?>" class="rek-item block text-center p-4" style="border-radius: 16px;">
                            <?php if ($rek['cover_buku']): ?>
                                <img src="<?= htmlspecialchars($rek['cover_buku']) ?>" class="w-full h-32 object-cover rounded-lg mb-3 transition-transform duration-300 hover:scale-105">
                            <?php else: ?>
                                <div class="bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 h-32 rounded-lg flex items-center justify-center mb-3">
                                    <i class="fa-solid fa-book text-3xl text-gray-500"></i>
                                </div>
                            <?php endif; ?>
                            <p class="font-semibold text-sm"><?= htmlspecialchars($rek['judul']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($rek['pengarang']) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Review form submit
        const reviewForm = document.getElementById('reviewForm');
        const submitBtn = document.getElementById('submitReview');
        
        if (reviewForm) {
            reviewForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(reviewForm);
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
                
                try {
                    const res = await fetch('api/kirim_rating.php', { method: 'POST', body: formData });
                    const result = await res.json();
                    alert(result.success ? '✅ ' + result.message : '❌ ' + result.message);
                    if (result.success) { reviewForm.reset(); setTimeout(() => location.reload(), 1000); }
                } catch (err) { alert('❌ Terjadi kesalahan'); }
                finally { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Review'; }
            });
        }
        
        // Navbar scroll
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
        
        // Scroll reveal
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('revealed'); });
        }, { threshold: 0.1 });
        document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
    </script>
    <script>
    // Cek dan terapkan tema dari localStorage
    const savedTheme = localStorage.getItem('library-theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark');
    }
    
    // Fungsi untuk toggle theme (jika ingin tambah tombol)
    function toggleTheme() {
        if (document.body.classList.contains('dark')) {
            document.body.classList.remove('dark');
            localStorage.setItem('library-theme', 'light');
        } else {
            document.body.classList.add('dark');
            localStorage.setItem('library-theme', 'dark');
        }
    }
</script>
</body>
</html>
