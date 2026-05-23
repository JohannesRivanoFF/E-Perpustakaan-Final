// ==================== SCRIPT.JS YANG SUDAH DIPERBAIKI ====================

const loader = document.getElementById("loader");
const navbar = document.getElementById("navbar");
const menuToggle = document.getElementById("menuToggle");
const navMenu = document.getElementById("navMenu");
const themeToggle = document.getElementById("themeToggle");
const mobileThemeToggle = document.getElementById("mobileThemeToggle");
const counters = document.querySelectorAll(".counter");
const reveals = document.querySelectorAll(".reveal");
const bookGrid = document.getElementById("bookGrid");
const borrowForm = document.getElementById("borrowForm");
const bookSelect = document.getElementById("bookSelect");
const formMessage = document.getElementById("formMessage");
const submitBorrow = document.getElementById("submitBorrow");
const historyBody = document.getElementById("historyBody");
const tanggalPinjam = document.getElementById("tanggalPinjam");
const tanggalKembali = document.getElementById("tanggalKembali");
const toast = document.getElementById("toast");

let counterStarted = false;

// Hilangkan loader setelah load
window.addEventListener("load", () => {
  setTimeout(() => {
    if (loader) loader.classList.add("hidden");
  }, 500);

  // Load semua data
  loadStats();
  loadBooks();
  loadHistory();
});

// ==================== FUNGSI LOAD STATISTIK ====================
async function loadStats() {
  try {
    const response = await fetch("api/statistik.php?_t=" + Date.now(), {
      headers: { "Cache-Control": "no-cache" },
    });
    const data = await response.json();

    console.log("Data stats:", data);

    if (data.success && data.stats) {
      // Update angka statistik langsung
      const statElements = {
        total_buku: "stat_total_buku",
        total_anggota: "stat_total_anggota",
        total_peminjaman: "stat_total_peminjaman",
        buku_tersedia: "stat_buku_tersedia",
      };

      for (const [key, id] of Object.entries(statElements)) {
        const el = document.getElementById(id);
        if (el) {
          const value = data.stats[key] || 0;
          el.textContent = value.toLocaleString("id-ID");
        }
      }

      // Update juga untuk counter yang pakai class
      counters.forEach((counter) => {
        const key = counter.getAttribute("data-stat");
        if (key && data.stats[key] !== undefined) {
          const value = data.stats[key];
          counter.setAttribute("data-target", value);
          if (counterStarted) {
            counter.textContent = value.toLocaleString("id-ID");
          } else {
            counter.textContent = "0";
          }
        }
      });
    }
  } catch (error) {
    console.error("Error loadStats:", error);
  }
}

// ==================== FUNGSI LOAD BUKU ====================
async function loadBooks() {
  if (!bookGrid) return;

  try {
    const response = await fetch("api/statistik.php?_t=" + Date.now(), {
      headers: { "Cache-Control": "no-cache" },
    });
    const data = await response.json();

    if (data.success && data.books && data.books.length > 0) {
      renderBooks(data.books);
    } else {
      bookGrid.innerHTML =
        '<p class="col-span-full text-center">Belum ada buku tersedia</p>';
    }
  } catch (error) {
    console.error("Error loadBooks:", error);
    bookGrid.innerHTML =
      '<p class="col-span-full text-center text-red-500">Gagal memuat buku</p>';
  }
}

function renderBooks(books) {
  if (!bookGrid) return;

  const icons = [
    "fa-brain",
    "fa-network-wired",
    "fa-graduation-cap",
    "fa-code",
  ];
  const covers = ["cover-one", "cover-two", "cover-three", "cover-four"];

  bookGrid.innerHTML = books
    .map((book, index) => {
      const icon = icons[index % icons.length];
      const coverClass = covers[index % covers.length];
      const coverHtml = book.cover_buku
        ? `<img src="../${book.cover_buku}?t=${Date.now()}" alt="${escapeHtml(book.judul)}">`
        : `<i class="fa-solid ${icon}"></i>`;

      return `
            <article class="book-card reveal visible">
                <div class="book-cover ${coverClass}">${coverHtml}</div>
                <div class="book-body">
                    <h3>${escapeHtml(book.judul)}</h3>
                    <p>${escapeHtml(book.pengarang)}</p>
                    <span>${escapeHtml(book.kategori || "Umum")} / ${book.tahun_terbit} / Stok ${book.stok}</span>
                    <a href="#pinjam" class="book-action">Pinjam <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </article>
        `;
    })
    .join("");
}

// ==================== FUNGSI LOAD HISTORY ====================
async function loadHistory() {
  if (!historyBody) return;

  try {
    const response = await fetch("api/statistik.php?_t=" + Date.now(), {
      headers: { "Cache-Control": "no-cache" },
    });
    const data = await response.json();

    if (data.success && data.history && data.history.length > 0) {
      historyBody.innerHTML = data.history
        .map((item) => {
          const isDone = item.status === "dikembalikan";
          const label = isDone ? "Dikembalikan" : "Dipinjam";
          return `
                    <tr>
                        <td>${escapeHtml(item.nama)}</td>
                        <td>${escapeHtml(item.judul)}</td>
                        <td>${formatDate(item.tanggal_pinjam)}</td>
                        <td><span class="status-badge ${isDone ? "done" : ""}">${label}</span></td>
                    </tr>
                `;
        })
        .join("");
    } else {
      historyBody.innerHTML =
        '<tr><td colspan="4" class="table-empty">Belum ada riwayat peminjaman</td></tr>';
    }
  } catch (error) {
    console.error("Error loadHistory:", error);
    historyBody.innerHTML =
      '<tr><td colspan="4" class="table-empty">Gagal memuat riwayat</td></tr>';
  }
}

// ==================== FUNGSI LOAD BUKU UNTUK FORM ====================
async function loadAvailableBooks() {
  if (!bookSelect) return;

  bookSelect.innerHTML = '<option value="">Memuat buku...</option>';

  try {
    const response = await fetch("api/get_buku.php?_t=" + Date.now(), {
      headers: { "Cache-Control": "no-cache" },
    });
    const data = await response.json();

    if (data.success && data.books && data.books.length > 0) {
      bookSelect.innerHTML = '<option value="">Pilih buku</option>';
      data.books.forEach((book) => {
        const option = document.createElement("option");
        option.value = book.id_buku;
        option.textContent = `${book.judul} - ${book.pengarang} (Stok: ${book.stok})`;
        bookSelect.appendChild(option);
      });
      bookSelect.disabled = false;
    } else {
      bookSelect.innerHTML =
        '<option value="">Tidak ada buku tersedia</option>';
      bookSelect.disabled = true;
    }
  } catch (error) {
    console.error("Error loadAvailableBooks:", error);
    bookSelect.innerHTML = '<option value="">Gagal memuat buku</option>';
  }
}

// ==================== UTILITY FUNCTIONS ====================
function escapeHtml(value) {
  if (!value) return "";
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function formatDate(dateString) {
  if (!dateString) return "-";
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return dateString;
  return date.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

function setDefaultDates() {
  if (!tanggalPinjam || !tanggalKembali) return;
  const today = new Date();
  const returnDate = new Date();
  returnDate.setDate(today.getDate() + 7);
  tanggalPinjam.value = today.toISOString().slice(0, 10);
  tanggalKembali.value = returnDate.toISOString().slice(0, 10);
  tanggalKembali.min = tanggalPinjam.value;
}

function showToast(message, type = "success") {
  if (!toast) return;
  toast.className = `toast show ${type}`;
  toast.innerHTML = `<i class="fa-solid ${type === "success" ? "fa-circle-check" : "fa-circle-exclamation"}"></i><span>${escapeHtml(message)}</span>`;
  setTimeout(() => {
    toast.classList.remove("show");
  }, 3000);
}

function showFormMessage(message, type = "error") {
  if (!formMessage) return;
  formMessage.textContent = message;
  formMessage.className = `form-message show ${type}`;
}

function clearFormMessage() {
  if (!formMessage) return;
  formMessage.textContent = "";
  formMessage.className = "form-message";
}

// ==================== NAVBAR & THEME ====================
function updateNavbar() {
  if (navbar) {
    navbar.classList.toggle("scrolled", window.scrollY > 20);
  }
}
window.addEventListener("scroll", updateNavbar);
updateNavbar();

if (menuToggle && navMenu) {
  menuToggle.addEventListener("click", () => {
    const isOpen = navMenu.classList.toggle("open");
    menuToggle.setAttribute("aria-expanded", String(isOpen));
    menuToggle.innerHTML = isOpen
      ? '<i class="fa-solid fa-xmark"></i>'
      : '<i class="fa-solid fa-bars"></i>';
  });
}

function setTheme(isDark) {
  document.body.classList.toggle("dark", isDark);
  localStorage.setItem("library-theme", isDark ? "dark" : "light");
  const icon = isDark ? "fa-sun" : "fa-moon";
  [themeToggle, mobileThemeToggle].forEach((btn) => {
    if (btn) btn.innerHTML = `<i class="fa-solid ${icon}"></i>`;
  });
}

const savedTheme = localStorage.getItem("library-theme");
setTheme(savedTheme === "dark");

[themeToggle, mobileThemeToggle].forEach((btn) => {
  if (btn) {
    btn.addEventListener("click", () =>
      setTheme(!document.body.classList.contains("dark")),
    );
  }
});

// ==================== ANIMASI & OBSERVER ====================
const revealObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("visible");
        revealObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.16 },
);
reveals.forEach((el) => revealObserver.observe(el));

// ==================== FORM SUBMIT ====================
if (borrowForm) {
  borrowForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    clearFormMessage();

    const formData = new FormData(borrowForm);
    const required = [
      "nama",
      "alamat",
      "no_hp",
      "id_buku",
      "tanggal_pinjam",
      "tanggal_kembali",
    ];
    const isEmpty = required.some((field) => !formData.get(field)?.trim());

    if (isEmpty) {
      showFormMessage("Semua field wajib diisi", "error");
      return;
    }

    if (!/^[0-9]{9,15}$/.test(formData.get("no_hp"))) {
      showFormMessage("Nomor HP harus 9-15 digit angka", "error");
      return;
    }

    if (formData.get("tanggal_kembali") < formData.get("tanggal_pinjam")) {
      showFormMessage(
        "Tanggal kembali tidak boleh kurang dari tanggal pinjam",
        "error",
      );
      return;
    }

    if (submitBorrow) {
      submitBorrow.disabled = true;
      submitBorrow.innerHTML =
        'Memproses... <i class="fa-solid fa-spinner fa-spin"></i>';
    }

    try {
      const response = await fetch("api/pinjam.php?_t=" + Date.now(), {
        method: "POST",
        body: formData,
        headers: { "Cache-Control": "no-cache" },
      });
      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || "Peminjaman gagal");
      }

      borrowForm.reset();
      setDefaultDates();
      showFormMessage(data.message, "success");
      showToast(data.message, "success");

      // Refresh semua data
      await Promise.all([
        loadStats(),
        loadBooks(),
        loadHistory(),
        loadAvailableBooks(),
      ]);
    } catch (error) {
      showFormMessage(error.message, "error");
      showToast(error.message, "error");
    } finally {
      if (submitBorrow) {
        submitBorrow.disabled = false;
        submitBorrow.innerHTML =
          'Simpan Peminjaman <i class="fa-solid fa-paper-plane"></i>';
      }
    }
  });
}

// ==================== INISIALISASI ====================
setDefaultDates();
loadAvailableBooks();

// Auto refresh setiap 30 detik
setInterval(() => {
  loadStats();
  loadBooks();
  loadHistory();
  loadAvailableBooks();
}, 30000);

// Refresh saat tab aktif kembali
window.addEventListener("focus", () => {
  loadStats();
  loadBooks();
  loadHistory();
  loadAvailableBooks();
});

// Animasi counter
const statsObserver = new IntersectionObserver(
  (entries) => {
    if (entries.some((e) => e.isIntersecting) && !counterStarted) {
      counterStarted = true;
      counters.forEach((counter) => {
        const target = parseInt(counter.getAttribute("data-target") || "0");
        if (target > 0) {
          let current = 0;
          const increment = target / 50;
          const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
              counter.textContent = target.toLocaleString("id-ID");
              clearInterval(timer);
            } else {
              counter.textContent = Math.floor(current).toLocaleString("id-ID");
            }
          }, 20);
        }
      });
      statsObserver.disconnect();
    }
  },
  { threshold: 0.35 },
);
const statsSection = document.getElementById("statistik");
if (statsSection) statsObserver.observe(statsSection);
