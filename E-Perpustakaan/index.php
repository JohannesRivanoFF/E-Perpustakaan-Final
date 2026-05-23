<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Portal resmi Perpustakaan Cendekia Nusantara untuk katalog koleksi, layanan peminjaman, dan informasi sirkulasi buku.">
  <meta name="keywords" content="Perpustakaan Cendekia Nusantara, katalog buku PCN, peminjaman buku online, layanan perpustakaan">
  <meta name="author" content="Perpustakaan Cendekia Nusantara">
  <meta name="theme-color" content="#071a35">
  <meta property="og:title" content="Perpustakaan Cendekia Nusantara">
  <meta property="og:description" content="Akses katalog, ajukan peminjaman, dan pantau layanan sirkulasi buku Perpustakaan Cendekia Nusantara.">
  <meta property="og:type" content="website">
  <title>Perpustakaan Cendekia Nusantara</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div id="loader" class="loader-screen" aria-label="Memuat halaman">
    <div class="loader-mark">
      <i class="fa-solid fa-book-open-reader"></i>
    </div>
    <p>Menyiapkan katalog Cendekia</p>
  </div>

  <header id="navbar" class="site-header fixed left-0 right-0 top-0 z-50">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-8">
      <a href="#home" class="brand flex items-center gap-3" aria-label="Perpustakaan Cendekia Nusantara">
        <span class="brand-icon"><i class="fa-solid fa-layer-group"></i></span>
        <span>
          <strong>Cendekia Library</strong>
          <small>Perpustakaan Cendekia Nusantara</small>
        </span>
      </a>

      <button id="menuToggle" class="menu-toggle lg:hidden" aria-label="Buka menu" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
      </button>

      <div id="navMenu" class="nav-menu">
        <a href="#home">Beranda</a>
        <a href="#tentang">Layanan</a>
        <a href="#koleksi">Katalog</a>
        <a href="#pinjam">Form Pinjam</a>
        <a href="#statistik">Sirkulasi</a>
        <a href="#kontak">Kontak</a>
      </div>

      <div class="hidden items-center gap-3 lg:flex">
        <button id="themeToggle" class="icon-button" aria-label="Aktifkan dark mode">
          <i class="fa-solid fa-moon"></i>
        </button>
        <a href="#pinjam" class="nav-cta">Ajukan Pinjam</a>
      </div>
    </nav>
  </header>

  <main>
<section id="home" class="hero-section relative overflow-hidden px-5 pt-32 lg:px-8">
  <div class="particles" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span>
  </div>
  <div class="hero-grid mx-auto grid max-w-7xl items-center gap-14 pb-20 pt-12 lg:grid-cols-[1.05fr_0.95fr] lg:pb-28">
    <div class="reveal">
      <span class="eyebrow"><i class="fa-solid fa-book-open-reader"></i> Portal Layanan Perpustakaan Cendekia Nusantara</span>
      <h1 class="text-white drop-shadow-lg">Temukan koleksi bacaan Cendekia dan ajukan peminjaman dari sini.</h1>
      <p class="hero-subtitle text-white/90 drop-shadow-md">
        Portal ini disiapkan untuk siswa, mahasiswa, guru, dan pengunjung Perpustakaan Cendekia Nusantara agar pencarian buku, pengajuan pinjam, dan pencatatan sirkulasi berlangsung lebih tertib.
      </p>
      <div class="context-tags mt-7">
        <span class="bg-white/20 backdrop-blur-sm">Ruang Baca Utama</span>
        <span class="bg-white/20 backdrop-blur-sm">Koleksi Akademik</span>
        <span class="bg-white/20 backdrop-blur-sm">Referensi Siswa</span>
        <span class="bg-white/20 backdrop-blur-sm">Layanan Sirkulasi</span>
      </div>
      <div class="mt-9 flex flex-col gap-4 sm:flex-row">
        <a href="#pinjam" class="primary-button">
          Ajukan Peminjaman <i class="fa-solid fa-arrow-right"></i>
        </a>
        <a href="#cta" class="secondary-button bg-white/20 backdrop-blur-sm">
          Lihat Sirkulasi Hari Ini <i class="fa-solid fa-chart-simple"></i>
        </a>
      </div>
      <div class="trust-row mt-10">
        <span class="text-white drop-shadow-md"><i class="fa-solid fa-shield-halved"></i> Data anggota tercatat</span>
        <span class="text-white drop-shadow-md"><i class="fa-solid fa-bolt"></i> Pengajuan cepat</span>
        <span class="text-white drop-shadow-md"><i class="fa-solid fa-database"></i> Stok koleksi diperbarui</span>
      </div>
    </div>

    <div class="hero-visual reveal" aria-label="Ilustrasi perpustakaan digital modern">
      <div class="glass-panel library-ui">
        <div class="panel-top">
          <span></span><span></span><span></span>
        </div>
        <div class="library-toolbar">
          <div>
            <small>Meja Layanan Cendekia</small>
            <strong>Sirkulasi Koleksi Aktif</strong>
          </div>
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <div class="shelf-row">
          <div class="book-stack gold"></div>
          <div class="book-stack blue"></div>
          <div class="book-stack white"></div>
          <div class="book-stack gold short"></div>
        </div>
        <div class="mini-dashboard">
          <div><i class="fa-solid fa-book"></i><span>Koleksi rak utama tersedia</span></div>
          <div><i class="fa-solid fa-users"></i><span>Anggota Cendekia terdaftar</span></div>
          <div><i class="fa-solid fa-arrow-right-arrow-left"></i><span>Peminjaman harian tercatat</span></div>
        </div>
        <div class="database-card">
          <i class="fa-solid fa-database"></i>
          <div>
            <strong>Arsip Sirkulasi PCN</strong>
            <p>Katalog, anggota, dan transaksi layanan</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

    <section id="tentang" class="section-pad">
      <div class="mx-auto max-w-7xl px-5 lg:px-8">
        <div class="section-heading reveal">
          <span class="section-kicker">Layanan Cendekia</span>
          <h2>Perpustakaan Cendekia Nusantara melayani peminjaman koleksi akademik dan bacaan umum.</h2>
          <p>Portal ini menyatukan kebutuhan pengunjung dan petugas: pengunjung mengajukan peminjaman, petugas memantau stok, status buku, dan riwayat sirkulasi dari ruang kerja petugas.</p>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-5">
          <article class="feature-card reveal">
            <i class="fa-solid fa-book-open"></i>
            <h3>Katalog Rak Cendekia</h3>
            <p>Daftar buku pelajaran, teknologi, literasi umum, dan referensi kampus tersusun berdasarkan kategori.</p>
          </article>
          <article class="feature-card reveal">
            <i class="fa-solid fa-id-card"></i>
            <h3>Kartu Anggota Digital</h3>
            <p>Setiap peminjam dikenali dari nomor HP agar riwayat kunjungan dan peminjaman mudah ditelusuri.</p>
          </article>
          <article class="feature-card reveal">
            <i class="fa-solid fa-right-left"></i>
            <h3>Meja Sirkulasi</h3>
            <p>Petugas dapat melihat buku yang sedang dipinjam, batas kembali, dan status pengembalian.</p>
          </article>
          <article class="feature-card reveal">
            <i class="fa-solid fa-boxes-stacked"></i>
            <h3>Stok Rak Otomatis</h3>
            <p>Jumlah eksemplar mengikuti aktivitas peminjaman sehingga daftar buku tersedia tetap akurat.</p>
          </article>
          <article class="feature-card reveal">
            <i class="fa-solid fa-gears"></i>
            <h3>Ruang Kerja Petugas</h3>
            <p>Ruang kerja petugas membantu tim perpustakaan memperbarui koleksi, anggota, dan transaksi harian.</p>
          </article>
        </div>
      </div>
    </section>

<section id="statistik" class="section-pad stats-section">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <div class="section-heading reveal">
      <span class="section-kicker">Sirkulasi Hari Ini</span>
      <h2>Ringkasan layanan Perpustakaan Cendekia Nusantara.</h2>
      <p>Angka berikut memperlihatkan jumlah koleksi, anggota terdaftar, transaksi, dan eksemplar yang masih dapat dipinjam dari rak Cendekia.</p>
    </div>

    <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <article class="stat-card reveal">
        <i class="fa-solid fa-book"></i>
        <span class="counter" id="stat_total_buku" data-stat="total_buku" data-target="0">0</span>
        <p>Judul di Katalog</p>
      </article>
      <article class="stat-card reveal">
        <i class="fa-solid fa-users"></i>
        <span class="counter" id="stat_total_anggota" data-stat="total_anggota" data-target="0">0</span>
        <p>Anggota Cendekia</p>
      </article>
      <article class="stat-card reveal">
        <i class="fa-solid fa-clipboard-list"></i>
        <span class="counter" id="stat_total_peminjaman" data-stat="total_peminjaman" data-target="0">0</span>
        <p>Catatan Peminjaman</p>
      </article>
      <article class="stat-card reveal">
        <i class="fa-solid fa-circle-check"></i>
        <span class="counter" id="stat_buku_tersedia" data-stat="buku_tersedia" data-target="0">0</span>
        <p>Eksemplar Siap Pinjam</p>
      </article>
    </div>
  </div>
</section>

    <section id="koleksi" class="section-pad">
      <div class="mx-auto max-w-7xl px-5 lg:px-8">
        <div class="section-heading reveal">
          <span class="section-kicker">Katalog Rak Utama</span>
          <h2>Koleksi yang paling sering dicari pengunjung Cendekia.</h2>
          <p>Pilih buku dari daftar koleksi aktif. Buku yang stoknya habis otomatis tidak ditawarkan pada formulir peminjaman.</p>
        </div>

        <div id="bookGrid" class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <article class="book-card reveal">
            <div class="book-cover cover-one"><i class="fa-solid fa-brain"></i></div>
            <div class="book-body">
              <h3>Algoritma Cerdas</h3>
              <p>Rina Mahardika</p>
              <span>2023</span>
              <a href="#pinjam" class="book-action">Pinjam <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </article>
          <article class="book-card reveal">
            <div class="book-cover cover-two"><i class="fa-solid fa-network-wired"></i></div>
            <div class="book-body">
              <h3>Basis Data Modern</h3>
              <p>Aditya Prakoso</p>
              <span>2022</span>
              <a href="#pinjam" class="book-action">Pinjam <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </article>
          <article class="book-card reveal">
            <div class="book-cover cover-three"><i class="fa-solid fa-graduation-cap"></i></div>
            <div class="book-body">
              <h3>Manajemen Pendidikan</h3>
              <p>Sinta Lestari</p>
              <span>2021</span>
              <a href="#pinjam" class="book-action">Pinjam <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </article>
          <article class="book-card reveal">
            <div class="book-cover cover-four"><i class="fa-solid fa-code"></i></div>
            <div class="book-body">
              <h3>Web Development</h3>
              <p>Fajar Nugroho</p>
              <span>2024</span>
              <a href="#pinjam" class="book-action">Pinjam <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section id="pinjam" class="section-pad borrow-section">
      <div class="mx-auto grid max-w-7xl gap-10 px-5 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
        <div class="section-heading text-left reveal">
          <span class="section-kicker">Form Layanan Sirkulasi</span>
          <h2>Ajukan peminjaman koleksi Cendekia sebelum mengambil buku di meja petugas.</h2>
          <p>Isi data sesuai identitas anggota. Setelah pengajuan masuk, petugas dapat memverifikasi buku dan menyerahkannya di ruang layanan.</p>
          <div class="borrow-steps">
            <div><i class="fa-solid fa-user-plus"></i><span>Data peminjam dicocokkan dengan anggota Cendekia</span></div>
            <div><i class="fa-solid fa-book-bookmark"></i><span>Pilihan buku mengikuti stok rak yang masih tersedia</span></div>
            <div><i class="fa-solid fa-database"></i><span>Pengajuan tersimpan ke riwayat sirkulasi petugas</span></div>
          </div>
        </div>

        <form id="borrowForm" class="borrow-form glass-panel reveal" novalidate>
          <div class="form-grid">
            <label>
              <span>Nama Lengkap</span>
              <input type="text" name="nama" placeholder="Nama sesuai kartu anggota" maxlength="120" autocomplete="name" required>
            </label>
            <label>
              <span>Nomor HP</span>
              <input type="tel" name="no_hp" placeholder="Nomor aktif untuk konfirmasi" inputmode="numeric" maxlength="15" autocomplete="tel" required>
            </label>
          </div>

          <label>
            <span>Alamat</span>
            <textarea name="alamat" rows="3" placeholder="Kelas, program studi, unit kerja, atau alamat domisili" maxlength="500" autocomplete="street-address" required></textarea>
          </label>

          <label>
            <span>Pilih Buku</span>
            <select id="bookSelect" name="id_buku" required>
              <option value="">Memuat buku tersedia...</option>
            </select>
          </label>

          <div class="form-grid">
            <label>
              <span>Tanggal Pinjam</span>
              <input id="tanggalPinjam" type="date" name="tanggal_pinjam" required>
            </label>
            <label>
              <span>Tanggal Kembali</span>
              <input id="tanggalKembali" type="date" name="tanggal_kembali" required>
            </label>
          </div>

          <p id="formMessage" class="form-message" role="alert"></p>
          <button id="submitBorrow" type="submit" class="primary-button w-full">
              Kirim Pengajuan <i class="fa-solid fa-paper-plane"></i>
          </button>
        </form>
      </div>
    </section>

    <section id="riwayat" class="section-pad history-section">
      <div class="mx-auto max-w-7xl px-5 lg:px-8">
        <div class="section-heading reveal">
          <span class="section-kicker">Catatan Meja Layanan</span>
          <h2>Aktivitas terbaru dari ruang sirkulasi Cendekia.</h2>
          <p>Riwayat ini menampilkan peminjaman terakhir agar petugas dan pengunjung mengetahui pergerakan koleksi secara cepat.</p>
        </div>

        <div class="history-table-wrap reveal">
          <table class="history-table">
            <thead>
              <tr>
                <th>Nama Peminjam</th>
                <th>Judul Buku</th>
                <th>Tanggal Pinjam</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="historyBody">
              <tr>
                <td colspan="4" class="table-empty">Memuat riwayat peminjaman...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="section-pad feature-highlight">
      <div class="mx-auto grid max-w-7xl gap-10 px-5 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
        <div class="section-heading text-left reveal">
          <span class="section-kicker">Arsip Layanan PCN</span>
          <h2>Dibangun mengikuti alur kerja harian Perpustakaan Cendekia Nusantara.</h2>
          <p>Setiap buku, anggota, dan transaksi peminjaman dicatat sebagai arsip layanan agar petugas lebih mudah menelusuri koleksi, memeriksa stok, dan menyiapkan laporan sirkulasi.</p>
          <div class="database-schema">
            <h3>Catatan yang Dikelola</h3>
            <code>Profil Anggota: nama, alamat, dan nomor kontak aktif</code>
            <code>Koleksi Buku: judul, kategori, pengarang, cover, tahun terbit, dan stok rak</code>
            <code>Riwayat Sirkulasi: tanggal pinjam, batas kembali, dan status pengembalian</code>
          </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <article class="mini-feature reveal"><i class="fa-solid fa-pen-to-square"></i><span>CRUD Koleksi Buku</span></article>
          <article class="mini-feature reveal"><i class="fa-solid fa-user-pen"></i><span>Direktori Anggota</span></article>
          <article class="mini-feature reveal"><i class="fa-solid fa-calendar-check"></i><span>Transaksi Peminjaman</span></article>
          <article class="mini-feature reveal"><i class="fa-solid fa-rotate"></i><span>Trigger Stok Otomatis</span></article>
          <article class="mini-feature reveal"><i class="fa-solid fa-database"></i><span>Arsip Layanan Terhubung</span></article>
          <article class="mini-feature reveal"><i class="fa-solid fa-chart-pie"></i><span>Dashboard Petugas</span></article>
        </div>
      </div>
    </section>

    <section id="cta" class="cta-section my-20 px-6 py-16 text-center lg:px-10">
      <div class="mx-auto max-w-3xl reveal">
        <span class="section-kicker">Layanan Terpadu Cendekia</span>
        <h2>Siapkan pengajuan sebelum datang ke meja petugas.</h2>
        <p>Pengunjung dapat memilih buku dari katalog aktif, sementara petugas Perpustakaan Cendekia Nusantara menindaklanjuti pengajuan melalui ruang kerja petugas.</p>
        <div class="mt-9 flex flex-col justify-center gap-4 sm:flex-row">
          <a href="#pinjam" class="primary-button">Ajukan Buku Sekarang <i class="fa-solid fa-rocket"></i></a>
          <a href="admin/login.php" class="secondary-button light">Ruang Petugas <i class="fa-solid fa-user-shield"></i></a>
        </div>
      </div>
    </section>
  </main>

  <footer id="kontak" class="footer-section px-5 py-12 lg:px-8">
    <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1.2fr_0.8fr_0.8fr]">
      <div>
        <a href="#home" class="brand flex items-center gap-3">
          <span class="brand-icon"><i class="fa-solid fa-layer-group"></i></span>
          <span>
            <strong>Cendekia Library</strong>
            <small>Perpustakaan Cendekia Nusantara</small>
          </span>
        </a>
        <p class="mt-5 max-w-md">Portal layanan koleksi dan sirkulasi buku milik Perpustakaan Cendekia Nusantara.</p>
      </div>
      <div>
        <h3>Kontak Layanan</h3>
        <p><i class="fa-solid fa-envelope"></i> layanan@cendekialibrary.sch.id</p>
        <p><i class="fa-solid fa-phone"></i> +62 812-2400-1188</p>
        <p><i class="fa-solid fa-location-dot"></i> Gedung Literasi Cendekia, Lantai 1</p>
      </div>
      <div>
        <h3>Kanal Cendekia</h3>
        <div class="social-links">
          <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
          <a href="#" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
        </div>
      </div>
    </div>
    <div class="mx-auto mt-10 max-w-7xl border-t border-white/10 pt-6 text-sm">
      <p>&copy; 2026 Perpustakaan Cendekia Nusantara. Layanan katalog dan sirkulasi buku.</p>
    </div>
  </footer>

  <button id="mobileThemeToggle" class="mobile-theme-toggle lg:hidden" aria-label="Aktifkan dark mode">
    <i class="fa-solid fa-moon"></i>
  </button>

  <div id="toast" class="toast" role="status" aria-live="polite">
    <i class="fa-solid fa-circle-check"></i>
    <span>Berhasil</span>
  </div>

  <script src="script.js"></script>
</body>
</html>
