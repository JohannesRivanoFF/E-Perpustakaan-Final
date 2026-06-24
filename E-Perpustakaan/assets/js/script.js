// ==================== SCRIPT.JS - VERSI FINAL ====================

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
const paymentPanel = document.getElementById("paymentPanel");
const paymentStatus = document.getElementById("paymentStatus");
const paymentReference = document.getElementById("paymentReference");
const paymentAmount = document.getElementById("paymentAmount");
const paymentQrWrap = document.getElementById("paymentQrWrap");
const paymentQrImage = document.getElementById("paymentQrImage");
const paymentQrString = document.getElementById("paymentQrString");
const paymentOpenQr = document.getElementById("paymentOpenQr");
const paymentCopyReference = document.getElementById("paymentCopyReference");
const paymentCheckStatus = document.getElementById("paymentCheckStatus");
const historyBody = document.getElementById("historyBody");
const tanggalPinjam = document.getElementById("tanggalPinjam");
const tanggalKembali = document.getElementById("tanggalKembali");
const toast = document.getElementById("toast");

let counterStarted = false;
let activePaymentReference = "";
let paymentPollTimer = null;

// ==================== HILANGKAN LOADER ====================
window.addEventListener("load", () => {
  setTimeout(() => {
    if (loader) loader.classList.add("hidden");
  }, 500);
  loadStats();
  loadBooks();
  loadHistory();
});

// ==================== LOAD STATISTIK ====================
async function loadStats() {
  try {
    const response = await fetch("api/statistik.php?_t=" + Date.now(), {
      headers: { "Cache-Control": "no-cache" },
    });
    const data = await response.json();

    if (data.success && data.stats) {
      const statElements = {
        total_buku: "stat_total_buku",
        total_anggota: "stat_total_anggota",
        total_peminjaman: "stat_total_peminjaman",
        buku_tersedia: "stat_buku_tersedia",
      };

      for (const [key, id] of Object.entries(statElements)) {
        const el = document.getElementById(id);
        if (el) {
          el.textContent = (data.stats[key] || 0).toLocaleString("id-ID");
        }
      }

      counters.forEach((counter) => {
        const key = counter.getAttribute("data-stat");
        if (key && data.stats[key] !== undefined) {
          const value = data.stats[key];
          counter.setAttribute("data-target", value);
          if (!counterStarted) counter.textContent = "0";
        }
      });
    }
  } catch (error) {
    console.error("Error loadStats:", error);
  }
}

// ==================== LOAD BUKU ====================
async function loadBooks() {
  if (!bookGrid) return;

  try {
    const search = document.getElementById("searchInput")?.value || "";
    const kategori = document.getElementById("kategoriFilter")?.value || "";
    const sort = document.getElementById("sortFilter")?.value || "terbaru";

    const response = await fetch(
      `api/filter_buku.php?search=${encodeURIComponent(search)}&kategori=${encodeURIComponent(kategori)}&sort=${sort}&_t=${Date.now()}`,
    );
    const data = await response.json();

    if (data.success) {
      // Update kategori options
      const kategoriSelect = document.getElementById("kategoriFilter");
      if (kategoriSelect && data.categories) {
        const currentValue = kategoriSelect.value;
        kategoriSelect.innerHTML = '<option value="">Semua Kategori</option>';
        data.categories.forEach((kat) => {
          const option = document.createElement("option");
          option.value = kat;
          option.textContent = kat;
          kategoriSelect.appendChild(option);
        });
        kategoriSelect.value = currentValue;
      }

      // Render buku
      if (data.books && data.books.length > 0) {
        renderBooks(data.books);
      } else {
        bookGrid.innerHTML =
          '<p class="col-span-full text-center">Tidak ada buku ditemukan</p>';
      }
    }
  } catch (error) {
    console.error("Error loadBooks:", error);
    if (bookGrid) {
      bookGrid.innerHTML =
        '<p class="col-span-full text-center text-red-500">Gagal memuat buku</p>';
    }
  }
}

// ==================== RENDER BUKU DENGAN BINTANG RATING ====================
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
        ? `<img src="${book.cover_buku}?t=${Date.now()}" alt="${escapeHtml(book.judul)}" style="width: 100%; height: 220px; object-fit: cover;">`
        : `<i class="fa-solid ${icon}"></i>`;

      // Fungsi untuk menampilkan bintang rating
      const ratingValue = parseFloat(book.total_rating) || 0;
      const jumlahRating = parseInt(book.jumlah_rating) || 0;

      let starsHtml = "";
      const fullStars = Math.floor(ratingValue);
      const hasHalfStar = ratingValue - fullStars >= 0.5;

      for (let i = 1; i <= 5; i++) {
        if (i <= fullStars) {
          starsHtml +=
            '<i class="fa-solid fa-star" style="color: #d6a84f;"></i>';
        } else if (i === fullStars + 1 && hasHalfStar) {
          starsHtml +=
            '<i class="fa-solid fa-star-half-alt" style="color: #d6a84f;"></i>';
        } else {
          starsHtml +=
            '<i class="fa-regular fa-star" style="color: #d6a84f;"></i>';
        }
      }

      const ratingText =
        ratingValue > 0 ? `(${jumlahRating} ulasan)` : "Belum ada rating";

      return `
            <article class="book-card reveal visible">
                <a href="detail_buku.php?id=${book.id_buku}" class="block">
                    <div class="book-cover ${coverClass}">${coverHtml}</div>
                    <div class="book-body">
                        <h3>${escapeHtml(book.judul)}</h3>
                        <p>${escapeHtml(book.pengarang)}</p>
                        <span>${escapeHtml(book.kategori || "Umum")} / ${book.tahun_terbit} / Stok ${book.stok}</span>
                        <div class="rating-container" style="margin-top: 8px;">
                            <div class="stars" style="display: flex; gap: 2px; font-size: 12px;">
                                ${starsHtml}
                            </div>
                            <span class="rating-text" style="font-size: 11px; color: #888;">${ratingText}</span>
                        </div>
                        <div class="book-action" style="margin-top: 12px;">
                            Lihat Detail <i class="fa-solid fa-arrow-right"></i>
                        </div>
                    </div>
                </a>
            </article>
        `;
    })
    .join("");
}

// ==================== LOAD HISTORY ====================
async function loadHistory() {
  if (!historyBody) return;
  try {
    const response = await fetch("api/statistik.php?_t=" + Date.now());
    const data = await response.json();
    if (data.success && data.history?.length) {
      historyBody.innerHTML = data.history
        .map((item) => {
          const isDone = item.status === "dikembalikan";
          return `
                    <tr>
                        <td>${escapeHtml(item.nama)}</td>
                        <td>${escapeHtml(item.judul)}</td>
                        <td>${formatDate(item.tanggal_pinjam)}</td>
                        <td><span class="status-badge ${isDone ? "done" : ""}">${isDone ? "Dikembalikan" : "Dipinjam"}</span></td>
                    </tr>
                `;
        })
        .join("");
    } else {
      historyBody.innerHTML =
        '<tr><td colspan="4" class="table-empty">Belum ada riwayat peminjaman</td></tr>';
    }
  } catch (error) {
    historyBody.innerHTML =
      '<tr><td colspan="4" class="table-empty">Gagal memuat riwayat</td></tr>';
  }
}

// ==================== LOAD BUKU UNTUK FORM ====================
async function loadAvailableBooks() {
  if (!bookSelect) return;
  bookSelect.innerHTML = '<option value="">Memuat buku...</option>';
  try {
    const response = await fetch("api/get_buku.php?_t=" + Date.now());
    const data = await response.json();
    if (data.success && data.books?.length) {
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
    bookSelect.innerHTML = '<option value="">Gagal memuat buku</option>';
  }
}

// ==================== UTILITY FUNCTIONS ====================
function escapeHtml(value) {
  if (!value) return "";
  return String(value)
    .replace(/[&<>]/g, function (m) {
      if (m === "&") return "&amp;";
      if (m === "<") return "&lt;";
      if (m === ">") return "&gt;";
      return m;
    })
    .replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, function (c) {
      return c;
    });
}

function formatDate(dateString) {
  if (!dateString) return "-";
  const date = new Date(dateString);
  return isNaN(date.getTime())
    ? dateString
    : date.toLocaleDateString("id-ID", {
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
  setTimeout(() => toast.classList.remove("show"), 3000);
}

function showFormMessage(message, type = "error") {
  if (!formMessage) return;
  formMessage.textContent = message;
  formMessage.className = `form-message show ${type}`;
}

function clearFormMessage() {
  if (formMessage) {
    formMessage.textContent = "";
    formMessage.className = "form-message";
  }
}

function formatCurrency(amount, currency = "IDR") {
  const value = Number(amount) || 0;
  if (value <= 0) return "Menunggu nominal";

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(value);
}

function paymentStatusLabel(status) {
  const labels = {
    pending: "Menunggu scan QR",
    verified: "Pembayaran terverifikasi",
    manual: "Pembayaran manual",
    failed: "Pembayaran gagal",
    expired: "Pembayaran kedaluwarsa",
  };

  return labels[status] || "Menunggu pembayaran";
}

function copyText(value) {
  if (!value) return;

  if (navigator.clipboard?.writeText) {
    navigator.clipboard.writeText(value).then(() => {
      showToast("Reference disalin", "success");
    });
    return;
  }

  const input = document.createElement("textarea");
  input.value = value;
  input.setAttribute("readonly", "");
  input.style.position = "fixed";
  input.style.opacity = "0";
  document.body.appendChild(input);
  input.select();
  document.execCommand("copy");
  input.remove();
  showToast("Reference disalin", "success");
}

function updatePaymentStatus(status) {
  if (!paymentStatus) return;
  paymentStatus.textContent = paymentStatusLabel(status);
  paymentStatus.dataset.status = status || "pending";
}

function renderPaymentPanel(payment) {
  if (!paymentPanel || !payment?.reference) return;

  activePaymentReference = payment.reference;
  paymentPanel.hidden = false;
  updatePaymentStatus(payment.status || "pending");

  if (paymentReference) {
    paymentReference.textContent = payment.reference;
    paymentReference.title = "Klik untuk menyalin reference";
  }

  if (paymentAmount) {
    paymentAmount.textContent = formatCurrency(
      payment.amount,
      payment.currency || "IDR",
    );
  }

  const hasQrUrl = Boolean(payment.qr_url);
  const hasQrString = Boolean(payment.qr_string);
  const qrStringIsUrl = /^https?:\/\//i.test(payment.qr_string || "");

  if (paymentQrWrap) paymentQrWrap.hidden = !(hasQrUrl || hasQrString);

  if (paymentQrImage) {
    paymentQrImage.hidden = !hasQrUrl;
    if (hasQrUrl) paymentQrImage.src = payment.qr_url;
  }

  if (paymentQrString) {
    paymentQrString.hidden = !hasQrString;
    paymentQrString.textContent = hasQrString ? payment.qr_string : "";
  }

  if (paymentOpenQr) {
    paymentOpenQr.hidden = !(hasQrUrl || qrStringIsUrl);
    if (qrStringIsUrl) {
      paymentOpenQr.href = payment.qr_string;
      paymentOpenQr.innerHTML =
        'Bayar Sekarang <i class="fa-solid fa-up-right-from-square"></i>';
    } else if (hasQrUrl) {
      paymentOpenQr.href = payment.qr_url;
      paymentOpenQr.innerHTML =
        'Buka QR <i class="fa-solid fa-up-right-from-square"></i>';
    }
  }

  paymentPanel.scrollIntoView({ behavior: "smooth", block: "center" });
}

async function refreshPaymentStatus(showResult = false) {
  if (!activePaymentReference) return;

  try {
    const response = await fetch(
      `api/status_pembayaran.php?reference=${encodeURIComponent(activePaymentReference)}&_t=${Date.now()}`,
      { headers: { "Cache-Control": "no-cache" } },
    );
    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.message || "Status pembayaran belum bisa dicek");
    }

    const payment = data.payment || {};
    updatePaymentStatus(payment.status || "pending");

    if (showResult) {
      showToast(
        paymentStatusLabel(payment.status),
        payment.status === "verified" ? "success" : "error",
      );
    }

    if (payment.status === "verified" && paymentPollTimer) {
      clearInterval(paymentPollTimer);
      paymentPollTimer = null;
    }
  } catch (error) {
    if (showResult) showToast(error.message, "error");
  }
}

function startPaymentPolling() {
  if (paymentPollTimer) clearInterval(paymentPollTimer);

  let checks = 0;
  paymentPollTimer = setInterval(() => {
    checks += 1;
    refreshPaymentStatus(false);

    if (checks >= 12) {
      clearInterval(paymentPollTimer);
      paymentPollTimer = null;
    }
  }, 5000);
}

// ==================== NAVBAR & THEME ====================
function updateNavbar() {
  if (navbar) navbar.classList.toggle("scrolled", window.scrollY > 20);
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

// ==================== SMART TRANSISI MODE GELAP/TERANG ====================

function setTheme(isDark) {
  // Tambahkan class untuk animasi
  document.body.classList.add("mode-transition");

  // Efek fade ringan
  const overlay = document.createElement("div");
  overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.05);
        pointer-events: none;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.2s ease;
    `;
  document.body.appendChild(overlay);
  overlay.style.opacity = "1";

  // Ubah tema setelah sedikit delay
  setTimeout(() => {
    document.body.classList.toggle("dark", isDark);
    localStorage.setItem("library-theme", isDark ? "dark" : "light");

    const icon = isDark ? "fa-sun" : "fa-moon";
    [themeToggle, mobileThemeToggle].forEach((btn) => {
      if (btn) btn.innerHTML = `<i class="fa-solid ${icon}"></i>`;
    });

    // Animasi ikon
    [themeToggle, mobileThemeToggle].forEach((btn) => {
      if (btn) {
        btn.classList.add("theme-toggle-transition");
        setTimeout(() => {
          btn.classList.remove("theme-toggle-transition");
        }, 300);
      }
    });
  }, 50);

  // Hapus overlay setelah transisi
  setTimeout(() => {
    overlay.style.opacity = "0";
    setTimeout(() => {
      if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
      document.body.classList.remove("mode-transition");
    }, 200);
  }, 350);
}

// Inisialisasi tema
const savedTheme = localStorage.getItem("library-theme");
setTheme(savedTheme === "dark");
setTheme(localStorage.getItem("library-theme") === "dark");
[themeToggle, mobileThemeToggle].forEach((btn) => {
  if (btn)
    btn.addEventListener("click", () =>
      setTheme(!document.body.classList.contains("dark")),
    );
});

// ==================== REVEAL OBSERVER (LANDING PAGE) ====================
const landingRevealObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("visible");
        landingRevealObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.16 },
);
reveals.forEach((el) => landingRevealObserver.observe(el));

// ==================== SCROLL REVEAL UNTUK ELEMEN LAIN ====================
const scrollRevealElements = document.querySelectorAll(
  ".feature-card, .stat-card, .book-card, .mini-feature, .borrow-form, .history-table-wrap, .admin-stat-grid article, .admin-panel",
);
const scrollRevealObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("revealed");
        scrollRevealObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.1, rootMargin: "0px 0px -30px 0px" },
);
scrollRevealElements.forEach((el) => {
  el.classList.add("reveal-on-scroll");
  scrollRevealObserver.observe(el);
});

// ==================== FORM SUBMIT ====================
if (borrowForm) {
  borrowForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    clearFormMessage();
    if (paymentPanel) paymentPanel.hidden = true;
    activePaymentReference = "";
    if (paymentPollTimer) clearInterval(paymentPollTimer);

    const formData = new FormData(borrowForm);
    const required = [
      "nama",
      "alamat",
      "no_hp",
      "email",
      "id_buku",
      "tanggal_pinjam",
      "tanggal_kembali",
    ];
    if (required.some((field) => !formData.get(field)?.trim())) {
      showFormMessage("Semua field wajib diisi", "error");
      return;
    }
    if (!/^[0-9]{9,15}$/.test(formData.get("no_hp"))) {
      showFormMessage("Nomor HP harus 9-15 digit angka", "error");
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.get("email"))) {
      showFormMessage("Email tidak valid", "error");
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
      if (!response.ok || !data.success)
        throw new Error(data.message || "Peminjaman gagal");

      const payment = data.payment || {};
      let successMessage = data.message;
      if (payment.reference) {
        successMessage += ` Reference pembayaran: ${payment.reference}.`;
      }

      borrowForm.reset();
      setDefaultDates();
      showFormMessage(successMessage, "success");
      renderPaymentPanel(payment);
      if (payment.reference && payment.status === "pending") {
        startPaymentPolling();
      }
      showToast(data.message, "success");
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
          'Kirim Pengajuan <i class="fa-solid fa-paper-plane"></i>';
      }
    }
  });
}

if (paymentReference) {
  paymentReference.addEventListener("click", () =>
    copyText(activePaymentReference),
  );
}

if (paymentCopyReference) {
  paymentCopyReference.addEventListener("click", () =>
    copyText(activePaymentReference),
  );
}

if (paymentCheckStatus) {
  paymentCheckStatus.addEventListener("click", () =>
    refreshPaymentStatus(true),
  );
}

// ==================== ANIMASI COUNTER ====================
const counterObserver = new IntersectionObserver(
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
      counterObserver.disconnect();
    }
  },
  { threshold: 0.35 },
);
const statsSection = document.getElementById("statistik");
if (statsSection) counterObserver.observe(statsSection);

// ==================== INISIALISASI ====================
setDefaultDates();
loadAvailableBooks();

// Auto refresh tiap 30 detik
// setInterval(() => {
//   loadStats();
//   loadBooks();
//   loadHistory();
//   loadAvailableBooks();
// }, 30000);

// window.addEventListener("focus", () => {
//   loadStats();
//   loadBooks();
//   loadHistory();
//   loadAvailableBooks();
// });

// Smooth scroll untuk semua anchor link
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    const href = this.getAttribute("href");
    if (href === "#" || href === "") return;
    const target = document.querySelector(href);
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
      target.style.transition = "all 0.3s ease";
      target.style.boxShadow = "0 0 0 3px rgba(214, 168, 79, 0.5)";
      setTimeout(() => (target.style.boxShadow = ""), 1000);
    }
  });
});

// Ripple effect untuk tombol
document
  .querySelectorAll(
    ".primary-button, .secondary-button, .book-action, .admin-icon",
  )
  .forEach((btn) => {
    btn.addEventListener("click", function (e) {
      this.style.transform = "scale(0.98)";
      setTimeout(() => (this.style.transform = ""), 200);
    });
  });
// ========== SMART TRANSITION UNTUK SEMUA ELEMEN ==========
// Daftar semua elemen yang akan dianimasi saat scroll
const allAnimateElements = [
  // Landing Page
  ".section-heading",
  ".feature-card",
  ".stat-card",
  ".book-card",
  ".borrow-steps div",
  ".borrow-form",
  ".history-table-wrap",
  ".history-table tbody tr",
  ".mini-feature",
  ".database-schema",
  ".cta-section .reveal",
  ".footer-section",
  // Admin Panel
  ".admin-sidebar nav a",
  ".admin-topbar",
  ".admin-stat-grid article",
  ".admin-panel",
  ".admin-table tbody tr",
  ".admin-pagination",
  ".admin-alert",
];

// Buat observer untuk semua elemen
const smartTransitionObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("revealed");
        // Optional: tetap observe untuk element yang muncul lagi
        // smartTransitionObserver.unobserve(entry.target);
      }
    });
  },
  {
    threshold: 0.1,
    rootMargin: "0px 0px -20px 0px",
  },
);

// Tambahkan class dan observe semua elemen
allAnimateElements.forEach((selector) => {
  document.querySelectorAll(selector).forEach((el) => {
    el.classList.add("reveal-on-scroll");
    smartTransitionObserver.observe(el);
  });
});

// Khusus untuk history table rows (sudah ada class terpisah)
document.querySelectorAll(".history-table tbody tr").forEach((el) => {
  el.classList.add("reveal-on-scroll");
  smartTransitionObserver.observe(el);
});

// Khusus untuk admin table rows
document.querySelectorAll(".admin-table tbody tr").forEach((el) => {
  el.classList.add("reveal-on-scroll");
  smartTransitionObserver.observe(el);
});
// ==================== SMART TRANSITION & PARALLAX ====================

// Parallax effect untuk hero section
/*window.addEventListener("scroll", () => {
  const hero = document.querySelector(".hero-section");
  if (hero) {
    const scrolled = window.pageYOffset;
    hero.style.backgroundPositionY = `${scrolled * 0.3}px`;
  }
});*/

// Observer untuk reveal-on-scroll elements
const revealElements = document.querySelectorAll(
  ".reveal-on-scroll, .feature-card, .stat-card, .book-card, .mini-feature, .admin-stat-grid article, .admin-panel, .admin-table tbody tr, .admin-sidebar nav a, .admin-topbar, .history-table tbody tr, .database-schema, .footer-section",
);

const revealObserverGlobal = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("revealed");
      }
    });
  },
  { threshold: 0.1, rootMargin: "0px 0px -20px 0px" },
);

revealElements.forEach((el) => {
  el.classList.add("reveal-on-scroll");
  revealObserverGlobal.observe(el);
});

// Staggered delays untuk cards
document
  .querySelectorAll(".feature-card, .stat-card, .book-card, .mini-feature")
  .forEach((el, idx) => {
    const delay = (idx % 6) + 1;
    el.style.transitionDelay = `${delay * 0.05}s`;
  });

// Smooth scroll untuk semua anchor
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    const href = this.getAttribute("href");
    if (href === "#" || href === "") return;
    const target = document.querySelector(href);
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
      target.style.transition = "all 0.3s ease";
      target.style.boxShadow = "0 0 0 3px rgba(214, 168, 79, 0.5)";
      setTimeout(() => (target.style.boxShadow = ""), 1000);
    }
  });
});
// ==================== SMART TRANSISI UNTUK ADMIN ====================
// Observer untuk elemen admin yang muncul saat scroll
const adminRevealElements = document.querySelectorAll(
  ".admin-stat-grid article, .admin-panel, .admin-table tbody tr, .admin-alert, .admin-filter, .admin-pagination",
);

const adminObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        // Tambahkan delay berdasarkan index untuk efek staggered
        setTimeout(() => {
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0)";
        }, index * 50);
        adminObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.1, rootMargin: "0px 0px -20px 0px" },
);

adminRevealElements.forEach((el) => {
  // Set initial state
  if (!el.style.opacity) {
    el.style.opacity = "0";
    el.style.transform = "translateY(20px)";
    el.style.transition = "all 0.5s ease";
  }
  adminObserver.observe(el);
});

// Efek hover smooth untuk card admin
document.querySelectorAll(".admin-stat-grid article").forEach((card) => {
  card.addEventListener("mouseenter", () => {
    card.style.transition = "all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94)";
  });
});

// Animasi untuk modal/alert yang muncul
const alerts = document.querySelectorAll(".admin-alert");
alerts.forEach((alert) => {
  alert.style.animation = "alertSlideIn 0.4s ease forwards";
  // Auto hide setelah 5 detik untuk success alert
  if (alert.classList.contains("success")) {
    setTimeout(() => {
      alert.style.opacity = "0";
      alert.style.transform = "translateY(-20px)";
      setTimeout(() => {
        if (alert.parentNode) alert.style.display = "none";
      }, 300);
    }, 5000);
  }
});
// ==================== PARALLAX UNTUK SECTION TENTANG ====================
window.addEventListener("scroll", () => {
  const tentangSection = document.querySelector("#tentang");
  if (tentangSection) {
    const rect = tentangSection.getBoundingClientRect();
    const scrolled = window.pageYOffset;

    // Parallax ringan untuk background section
    if (rect.top < window.innerHeight && rect.bottom > 0) {
      const offset = (scrolled - rect.top) * 0.05;
      tentangSection.style.backgroundPositionY = `${offset}px`;
    }
  }
});

// Observer untuk feature cards dengan efek staggered yang lebih smooth
const featureCards = document.querySelectorAll(".feature-card");
const cardObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add("revealed");
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0) rotateX(0)";
        }, index * 80);
        cardObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.2, rootMargin: "0px 0px -50px 0px" },
);

featureCards.forEach((card) => {
  card.style.opacity = "0";
  card.style.transform = "translateY(50px) rotateX(10deg)";
  card.style.transition = "all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)";
  cardObserver.observe(card);
});
// ==================== PARALLAX UNTUK SECTION STATISTIK ====================
window.addEventListener("scroll", () => {
  const statsSection = document.querySelector(".stats-section");
  if (statsSection) {
    const rect = statsSection.getBoundingClientRect();
    const scrolled = window.pageYOffset;

    // Parallax ringan untuk background
    if (rect.top < window.innerHeight && rect.bottom > 0) {
      const offset = (scrolled - rect.top) * 0.08;
      statsSection.style.backgroundPositionY = `${offset}px`;
    }
  }
});

// Observer untuk stat cards dengan efek staggered
const statCards = document.querySelectorAll(".stat-card");
const statObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add("revealed");
          entry.target.style.opacity = "1";
          entry.target.style.transform =
            "perspective(1000px) rotateX(0) translateY(0)";
        }, index * 80);
        statObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.3, rootMargin: "0px 0px -50px 0px" },
);

statCards.forEach((card) => {
  card.style.opacity = "0";
  card.style.transform = "perspective(1000px) rotateX(15deg) translateY(30px)";
  card.style.transition = "all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)";
  statObserver.observe(card);
});
// ==================== PARALLAX ZOOM UNTUK SECTION KOLEKSI ====================
window.addEventListener("scroll", () => {
  const koleksiSection = document.querySelector("#koleksi");
  const bookCards = document.querySelectorAll(".book-card");

  if (koleksiSection) {
    const rect = koleksiSection.getBoundingClientRect();
    const scrolled = window.pageYOffset;

    // Parallax ringan untuk background
    if (rect.top < window.innerHeight && rect.bottom > 0) {
      const offset = (scrolled - rect.top) * 0.05;
      koleksiSection.style.backgroundPositionY = `${offset}px`;
    }
  }

  // Efek zoom parallax pada cover saat scroll (opsional)
  bookCards.forEach((card) => {
    const rect = card.getBoundingClientRect();
    const isVisible = rect.top < window.innerHeight - 100;

    if (isVisible) {
      card.style.opacity = "1";
      card.style.transform = "translateY(0) scale(1)";
    }
  });
});

// Observer untuk book cards dengan efek staggered
const bookCards = document.querySelectorAll(".book-card");
const bookObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add("revealed");
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0) scale(1)";
        }, index * 100);
        bookObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.2, rootMargin: "0px 0px -50px 0px" },
);

bookCards.forEach((card) => {
  card.style.opacity = "0";
  card.style.transform = "translateY(40px) scale(0.96)";
  card.style.transition = "all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)";
  bookObserver.observe(card);
});

// Efek mouse move parallax untuk cover buku (opsional)
document.querySelectorAll(".book-card").forEach((card) => {
  const cover = card.querySelector(".book-cover");

  card.addEventListener("mousemove", (e) => {
    const rect = card.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width;
    const y = (e.clientY - rect.top) / rect.height;

    if (cover) {
      const moveX = (x - 0.5) * 10;
      const moveY = (y - 0.5) * 10;
      cover.style.transform = `scale(1.02) translate(${moveX}px, ${moveY}px)`;
    }
  });

  card.addEventListener("mouseleave", () => {
    if (cover) {
      cover.style.transform = "scale(1) translate(0, 0)";
    }
  });
});
// ==================== PARALLAX UNTUK HALAMAN ADMIN ANGGOTA ====================

// Efek parallax ringan untuk background admin body
window.addEventListener("scroll", () => {
  const adminBody = document.querySelector(".admin-body");
  if (adminBody) {
    const scrolled = window.pageYOffset;
    // Parallax ringan untuk background (sangat halus)
    adminBody.style.backgroundPosition = `center ${scrolled * 0.05}px`;
  }
});

// Efek parallax untuk panel admin saat scroll
const adminPanels = document.querySelectorAll(
  ".admin-panel, .admin-stat-grid article",
);
const panelObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.transform = "translateY(0)";
          entry.target.style.opacity = "1";
        }, index * 50);
        panelObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.1, rootMargin: "0px 0px -20px 0px" },
);

adminPanels.forEach((panel) => {
  panel.style.transform = "translateY(20px)";
  panel.style.opacity = "0";
  panel.style.transition = "all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)";
  panelObserver.observe(panel);
});

// Mouse move parallax untuk cards (efek depth)
document
  .querySelectorAll(".admin-stat-grid article, .admin-panel")
  .forEach((card) => {
    card.addEventListener("mousemove", (e) => {
      const rect = card.getBoundingClientRect();
      const x = (e.clientX - rect.left) / rect.width;
      const y = (e.clientY - rect.top) / rect.height;

      const moveX = (x - 0.5) * 2;
      const moveY = (y - 0.5) * 2;

      card.style.transform = `translateY(-3px) perspective(500px) rotateX(${moveY}deg) rotateY(${moveX}deg)`;
    });

    card.addEventListener("mouseleave", () => {
      card.style.transform =
        "translateY(0) perspective(500px) rotateX(0deg) rotateY(0deg)";
    });
  });
// ==================== PARALLAX & SMART TRANSISI UNTUK LOGIN ====================

// Parallax effect untuk background login
window.addEventListener("scroll", () => {
  const loginBody = document.querySelector(".admin-login-body");
  if (loginBody) {
    const scrolled = window.pageYOffset;
    loginBody.style.backgroundPosition = `center ${scrolled * 0.1}px`;
  }
});

// Efek floating untuk card login (3D tilt)
const loginCard = document.querySelector(".admin-login-card");
if (loginCard) {
  loginCard.addEventListener("mousemove", (e) => {
    const rect = loginCard.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width;
    const y = (e.clientY - rect.top) / rect.height;

    const rotateX = (y - 0.5) * 5;
    const rotateY = (x - 0.5) * 5;

    loginCard.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-5px)`;
  });

  loginCard.addEventListener("mouseleave", () => {
    loginCard.style.transform =
      "perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0)";
  });
}

// Efek focus yang smooth untuk input
const loginInputs = document.querySelectorAll(".admin-login-card input");
loginInputs.forEach((input) => {
  input.addEventListener("focus", () => {
    input.parentElement.querySelector("span").style.color = "#d6a84f";
  });

  input.addEventListener("blur", () => {
    input.parentElement.querySelector("span").style.color = "";
  });
});

// Efek typing animation untuk placeholder (opsional)
const passwordInput = document.querySelector('input[type="password"]');
if (passwordInput) {
  passwordInput.addEventListener("focus", () => {
    if (passwordInput.value === "") {
      passwordInput.placeholder = "Masukkan password admin";
    }
  });

  passwordInput.addEventListener("blur", () => {
    passwordInput.placeholder = "admin123";
  });
}
// Filter dan pencarian buku
const searchForm = document.getElementById("searchForm");
const searchInput = document.getElementById("searchInput");
const kategoriFilter = document.getElementById("kategoriFilter");
const sortFilter = document.getElementById("sortFilter");

async function loadFilteredBooks() {
  const search = searchInput ? searchInput.value : "";
  const kategori = kategoriFilter ? kategoriFilter.value : "";
  const sort = sortFilter ? sortFilter.value : "terbaru";

  try {
    const response = await fetch(
      `api/filter_buku.php?search=${encodeURIComponent(search)}&kategori=${encodeURIComponent(kategori)}&sort=${sort}`,
    );
    const data = await response.json();

    if (data.success) {
      renderBooks(data.books);
      // Update kategori options if needed
      if (
        kategoriFilter &&
        data.categories &&
        kategoriFilter.options.length <= 1
      ) {
        data.categories.forEach((cat) => {
          const option = document.createElement("option");
          option.value = cat;
          option.textContent = cat;
          kategoriFilter.appendChild(option);
        });
      }
    }
  } catch (error) {
    console.error("Error loading filtered books:", error);
  }
}

if (searchForm) {
  searchForm.addEventListener("submit", (e) => {
    e.preventDefault();
    loadFilteredBooks();
  });
}

// Override renderBooks to add link to detail
const originalRenderBooks = renderBooks;
window.renderBooks = function (books) {
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
        ? `<img src="${book.cover_buku}?t=${Date.now()}" alt="${escapeHtml(book.judul)}" style="width: 100%; height: 220px; object-fit: cover;">`
        : `<i class="fa-solid ${icon}"></i>`;

      const ratingValue = book.total_rating || 0;
      const stars =
        "★".repeat(Math.floor(ratingValue)) +
        "☆".repeat(5 - Math.floor(ratingValue));

      return `
            <article class="book-card reveal visible">
                <a href="detail_buku.php?id=${book.id_buku}" class="block">
                    <div class="book-cover ${coverClass}">${coverHtml}</div>
                    <div class="book-body">
                        <h3>${escapeHtml(book.judul)}</h3>
                        <p>${escapeHtml(book.pengarang)}</p>
                        <span>${escapeHtml(book.kategori || "Umum")} / ${book.tahun_terbit} / Stok ${book.stok}</span>
                        <div class="text-gold text-sm mt-1">${stars} (${book.jumlah_rating || 0})</div>
                        <div class="book-action mt-3">Lihat Detail <i class="fa-solid fa-arrow-right"></i></div>
                    </div>
                </a>
            </article>
        `;
    })
    .join("");
};
// ==================== OTOMATIS PILIH BUKU DARI URL ====================
function autoSelectBookFromUrl() {
  const urlParams = new URLSearchParams(window.location.search);
  const pinjamBukuId = urlParams.get("pinjam");

  if (pinjamBukuId) {
    // Scroll ke form peminjaman
    const pinjamSection = document.getElementById("pinjam");
    if (pinjamSection) {
      pinjamSection.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    // Tunggu select box terisi
    const waitForSelect = setInterval(() => {
      const bookSelect = document.getElementById("bookSelect");
      if (bookSelect && bookSelect.options.length > 1) {
        clearInterval(waitForSelect);

        // Pilih buku yang sesuai
        for (let i = 0; i < bookSelect.options.length; i++) {
          if (bookSelect.options[i].value == pinjamBukuId) {
            bookSelect.value = pinjamBukuId;

            // Efek highlight
            bookSelect.style.transition = "all 0.3s ease";
            bookSelect.style.borderColor = "#d6a84f";
            bookSelect.style.boxShadow = "0 0 0 3px rgba(214, 168, 79, 0.3)";
            bookSelect.style.backgroundColor = "rgba(214, 168, 79, 0.1)";

            setTimeout(() => {
              bookSelect.style.borderColor = "";
              bookSelect.style.boxShadow = "";
              bookSelect.style.backgroundColor = "";
            }, 2000);

            // Tampilkan notifikasi
            const selectedText = bookSelect.options[i].text;
            showToast(
              `Buku "${selectedText.split(" - ")[0]}" siap dipinjam!`,
              "success",
            );
            break;
          }
        }
      }
    }, 100);

    // Timeout setelah 5 detik
    setTimeout(() => clearInterval(waitForSelect), 5000);
  }
}

// Jalankan saat halaman selesai loading
document.addEventListener("DOMContentLoaded", function () {
  autoSelectBookFromUrl();
});
// ==================== TAMBAHAN EFEK PENCARIAN ====================
// Jalankan setelah halaman load
document.addEventListener("DOMContentLoaded", function () {
  const searchBtn = document.querySelector("#searchForm button[type='submit']");
  const bookGrid = document.getElementById("bookGrid");

  if (searchBtn) {
    // Efek klik pada tombol cari
    searchBtn.addEventListener("click", function (e) {
      // Efek visual tombol
      this.style.transform = "scale(0.95)";
      setTimeout(() => {
        if (this) this.style.transform = "";
      }, 200);

      // Efek loading pada grid buku
      if (bookGrid) {
        bookGrid.style.opacity = "0.5";
        bookGrid.style.transform = "translateY(10px)";
        bookGrid.style.transition = "all 0.3s ease";

        setTimeout(() => {
          if (bookGrid) {
            bookGrid.style.opacity = "1";
            bookGrid.style.transform = "translateY(0)";
          }
        }, 500);
      }
    });
  }
});
// ==================== REFRESH STATUS ====================
async function refreshPaymentStatus(showResult = false) {
  if (!activePaymentReference) return;

  try {
    const response = await fetch(
      `api/status_pembayaran.php?reference=${encodeURIComponent(activePaymentReference)}&_t=${Date.now()}`,
      { headers: { "Cache-Control": "no-cache" } },
    );
    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.message || "Status pembayaran belum bisa dicek");
    }

    const payment = data.payment || {};
    updatePaymentStatus(payment.status || "pending");

    if (showResult) {
      showToast(
        paymentStatusLabel(payment.status),
        payment.status === "verified" ? "success" : "error",
      );
    }

    // ✅ AUTO REFRESH jika status verified
    if (payment.status === "verified") {
      if (paymentPollTimer) {
        clearInterval(paymentPollTimer);
        paymentPollTimer = null;
      }
      // Reload halaman setelah 2 detik agar data terbaru muncul
      setTimeout(() => {
        location.reload();
      }, 2000);
    }
  } catch (error) {
    if (showResult) showToast(error.message, "error");
  }
}

// ==================== POLLING ====================
function startPaymentPolling() {
  if (paymentPollTimer) clearInterval(paymentPollTimer);

  let checks = 0;
  paymentPollTimer = setInterval(() => {
    checks += 1;
    refreshPaymentStatus(false);

    // Jika sudah 12 kali cek (60 detik), hentikan polling
    if (checks >= 12) {
      clearInterval(paymentPollTimer);
      paymentPollTimer = null;
    }
  }, 5000);
}
