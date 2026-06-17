<?php
// ============================================================
//  app/views/tracking/index.php — Halaman Utama & Tracking
// ============================================================
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';
require_once dirname(__DIR__, 2) . '/controllers/TrackingController.php';

$tracker = new TrackingController($conn);
$invoice = isset($_GET['inv']) ? trim($_GET['inv']) : '';
$trackResult = $tracker->tracking($invoice);

$order = $trackResult['order'];
$error = $trackResult['error'];

$docRoot   = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$appRoot   = str_replace('\\', '/', dirname(__DIR__, 3));
$baseUrl   = '/' . trim(str_replace($docRoot, '', $appRoot), '/');
$baseUrl   = rtrim($baseUrl, '/');
$publicUrl = $baseUrl . '/public';

// Urutan status untuk timeline
$statusFlow = $tracker->getStatusFlow();
$currentIdx = $order ? $tracker->getStatusIndex($order['status']) : -1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaundryKu — Layanan Laundry Modern & Terpercaya</title>
    
    <!-- Meta SEO -->
    <meta name="description" content="LaundryKu adalah layanan laundry premium, cepat, bersih, dan terpercaya dengan sistem tracking real-time. Hubungi kami untuk cuci kering, cuci setrika, dry cleaning, dan antar jemput.">
    <meta name="keywords" content="laundry, cuci kering, setrika, dry cleaning, antar jemput laundry, tracking laundry">
    
    <!-- CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $publicUrl ?>/css/style.css" rel="stylesheet">
    
    <!-- Iconify -->
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
</head>
<body>

    <!-- ── Sticky Blur Navbar ───────────────────────────────────────── -->
    <nav class="sticky-navbar" id="navbar">
        <div class="nav-container">
            <a href="#home" class="nav-brand">
                <div class="nav-brand-icon">
                    <iconify-icon icon="mdi:washing-machine"></iconify-icon>
                </div>
                <span class="nav-brand-text">Laundry<span style="color: var(--mint);">Ku</span></span>
            </a>
            <ul class="nav-menu d-none d-md-flex">
                <li><a href="#home">Home</a></li>
                <li><a href="#services">Layanan</a></li>
                <li><a href="#pricing">Harga</a></li>
                <li><a href="#tracking">Lacak Pesanan</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= $baseUrl ?>/app/views/auth/login.php" class="nav-cta-btn d-none d-sm-inline-block">Portal Admin</a>
                <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle Menu">
                    <iconify-icon icon="mdi:menu"></iconify-icon>
                </button>
            </div>
        </div>
    </nav>

    <!-- ── Mobile Navigation Sidebar (Drawer Menu) ──────────────────── -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
    <div class="mobile-nav-sidebar" id="mobileNavSidebar">
        <div class="mobile-nav-header">
            <a href="#home" class="nav-brand">
                <div class="nav-brand-icon">
                    <iconify-icon icon="mdi:washing-machine"></iconify-icon>
                </div>
                <span class="nav-brand-text">Laundry<span style="color: var(--mint);">Ku</span></span>
            </a>
            <button class="mobile-nav-close" id="mobileNavClose" aria-label="Close Menu">
                <iconify-icon icon="mdi:close"></iconify-icon>
            </button>
        </div>
        <ul class="mobile-nav-menu">
            <li><a href="#home">Home</a></li>
            <li><a href="#services">Layanan</a></li>
            <li><a href="#pricing">Harga</a></li>
            <li><a href="#tracking">Lacak Pesanan</a></li>
            <li><a href="<?= $baseUrl ?>/app/views/auth/login.php" style="background: var(--navy-deep); color: #fff; text-align: center; margin-top: 10px;">Portal Admin</a></li>
        </ul>
    </div>

    <!-- ── Hero Section ─────────────────────────────────────────────── -->
    <section class="hero-section" id="home">
        <div class="hero-bg-shapes">
            <div class="hero-shape hero-shape-1"></div>
            <div class="hero-shape hero-shape-2"></div>
        </div>
        <div class="container hero-content">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 text-center text-lg-start">
                    <div class="hero-tag">
                        <iconify-icon icon="mdi:leaf" style="font-size: 16px;"></iconify-icon>
                        Eco-Friendly & Cepat Terpercaya
                    </div>
                    <h1 class="hero-headline">Pakaian Bersih Sempurna Tanpa Repot</h1>
                    <p class="hero-subheadline">
                        Sistem laundry modern berbasis digital. Lacak cucian Anda secara real-time dari proses pencucian hingga siap diantar langsung ke depan pintu rumah Anda.
                    </p>
                    <div class="hero-buttons justify-content-center justify-content-lg-start">
                        <a href="#tracking" class="btn btn-primary btn-lg px-4 py-3">Lacak Cucian</a>
                        <a href="#services" class="btn btn-outline-primary btn-lg px-4 py-3" style="background:#fff;">Lihat Layanan</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="illustration-wrapper">
                        <!-- Modern Animated Washing Machine Illustration -->
                        <div class="washing-machine-container">
                            <div class="machine-top-panel">
                                <div class="panel-dial"></div>
                                <div class="panel-display">00:45</div>
                                <div class="panel-lights">
                                    <div class="panel-light"></div>
                                    <div class="panel-light"></div>
                                </div>
                            </div>
                            <div class="machine-body">
                                <div class="machine-door-outer">
                                    <div class="machine-door-glass">
                                        <div class="drum-water"></div>
                                        <div class="drum-clothes"></div>
                                        <div class="bubble-container">
                                            <div class="bubble bubble-1"></div>
                                            <div class="bubble bubble-2"></div>
                                            <div class="bubble bubble-3"></div>
                                            <div class="bubble bubble-4"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Interactive Service Cards ────────────────────────────────── -->
    <section class="services-section" id="services">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Layanan Premium Kami</h2>
                <p class="section-desc">Pilihan perawatan terbaik untuk segala jenis pakaian dan tekstil rumah tangga Anda.</p>
            </div>
            <div class="row g-4">
                <!-- Card 1 -->
                <div class="col-md-6 col-lg-3">
                    <div class="service-card">
                        <div class="service-icon-wrapper">
                            <iconify-icon icon="mdi:tumble-dryer"></iconify-icon>
                        </div>
                        <h4 class="service-card-title">Cuci Kering</h4>
                        <p class="service-card-desc">Cucian dicuci bersih, dikeringkan sempurna, dilipat rapi, dan siap digunakan kembali.</p>
                        <a href="#pricing" class="service-link">
                            Detail Paket <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="col-md-6 col-lg-3">
                    <div class="service-card">
                        <div class="service-icon-wrapper">
                            <iconify-icon icon="mdi:iron"></iconify-icon>
                        </div>
                        <h4 class="service-card-title">Cuci Setrika</h4>
                        <p class="service-card-desc">Kombinasi cuci bersih dan setrika presisi agar pakaian tampak mulus bebas kusut.</p>
                        <a href="#pricing" class="service-link">
                            Detail Paket <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="col-md-6 col-lg-3">
                    <div class="service-card">
                        <div class="service-icon-wrapper">
                            <iconify-icon icon="mdi:washing-machine"></iconify-icon>
                        </div>
                        <h4 class="service-card-title">Dry Cleaning</h4>
                        <p class="service-card-desc">Perawatan khusus pakaian berbahan sensitif (jas, kebaya, sutra) menggunakan cairan premium.</p>
                        <a href="#pricing" class="service-link">
                            Detail Paket <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <!-- Card 4 -->
                <div class="col-md-6 col-lg-3">
                    <div class="service-card">
                        <div class="service-icon-wrapper">
                            <iconify-icon icon="mdi:magnify"></iconify-icon>
                        </div>
                        <h4 class="service-card-title">Informasi Tracking</h4>
                        <p class="service-card-desc">Pantau status cucian Anda secara real-time dari proses pencucian hingga siap diambil atau diantar.</p>
                        <a href="#tracking" class="service-link">
                            Lacak Pesanan <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Premium Price Cards ─────────────────────────────────────── -->
    <section class="pricing-section" id="pricing">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Harga Jujur & Terjangkau</h2>
                <p class="section-desc">Pilih paket layanan yang sesuai dengan kebutuhan dan budget Anda tanpa biaya tersembunyi.</p>
            </div>
            <div class="row g-4 justify-content-center">
                <?php 
                $servicesQ = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY price_per_kg ASC");
                $count = 0;
                while($svc = $servicesQ->fetch_assoc()): 
                    $count++;
                    $isPopular = ($count == 2) ? 'featured' : '';
                    $kPrice = ($svc['price_per_kg'] / 1000) . 'k';
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="price-card <?= $isPopular ?>">
                        <div class="price-header">
                            <div class="price-name"><?= htmlspecialchars($svc['name']) ?></div>
                            <div class="price-value-box">
                                <span class="price-currency">Rp</span>
                                <span class="price-amount"><?= $kPrice ?></span>
                                <span class="price-unit">/ kg</span>
                            </div>
                        </div>
                        <ul class="price-features">
                            <li><iconify-icon icon="mdi:check-circle"></iconify-icon> Selesai dalam <?= $svc['duration_days'] ?> Hari</li>
                            <li><iconify-icon icon="mdi:check-circle"></iconify-icon> <?= htmlspecialchars($svc['description']) ?></li>
                        </ul>
                        <a href="#tracking" class="price-cta">Pilih Paket</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- ── Statistics Section ──────────────────────────────────────── -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number">12k+</div>
                        <div class="stat-text">Pelanggan Puas & Setia</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number">45k+</div>
                        <div class="stat-text">Pesanan Selesai Diproses</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number">99.8%</div>
                        <div class="stat-text">Kecepatan Layanan Sesuai Estimasi</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Real-Time Order Tracking Feature ────────────────────────── -->
    <section class="tracking-sec" id="tracking">
        <div class="container">
            <div class="section-header text-center">
                <h2 class="section-title">Lacak Pesanan Real-Time</h2>
                <p class="section-desc">Pantau status cucian Anda kapan saja dengan memasukkan nomor invoice pesanan Anda.</p>
            </div>
            
            <div class="tracking-card-box">
                <form method="GET" action="#tracking" class="mb-4">
                    <div class="row g-2">
                        <div class="col-sm-9">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0">
                                    <iconify-icon icon="mdi:clock-fast" style="color: var(--primary); font-size: 24px;"></iconify-icon>
                                </span>
                                <input type="text" name="inv" id="invoiceInput" class="form-control form-control-lg border-start-0 shadow-none" 
                                       placeholder="MASUKKAN NOMOR INVOICE (cth: LND-20260617-0001)"
                                       value="<?= htmlspecialchars($invoice) ?>" required style="text-transform: uppercase;">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <button type="submit" class="btn btn-primary w-100 h-100 py-3" style="font-size: 16px;">
                                <i class="bi bi-search me-2"></i>Lacak
                            </button>
                        </div>
                    </div>
                </form>

                <?php if ($invoice !== ''): ?>
                    <?php if ($order): ?>
                        <!-- Info Ringkas Invoice -->
                        <div class="row g-3 mb-5 mt-4">
                            <div class="col-6 col-md-3">
                                <div class="info-block">
                                    <div style="font-size: 11px; color: var(--muted); text-transform: uppercase; font-weight: 700;">Pelanggan</div>
                                    <div style="font-weight: 700; color: var(--navy-deep); font-size: 14px;"><?= htmlspecialchars($order['cust_name']) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="info-block">
                                    <div style="font-size: 11px; color: var(--muted); text-transform: uppercase; font-weight: 700;">Layanan</div>
                                    <div style="font-weight: 700; color: var(--navy-deep); font-size: 14px;"><?= htmlspecialchars($order['svc_name']) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="info-block">
                                    <div style="font-size: 11px; color: var(--muted); text-transform: uppercase; font-weight: 700;">Berat & Total</div>
                                    <div style="font-weight: 700; color: var(--navy-deep); font-size: 14px;"><?= $order['weight_kg'] ?> kg / <?= rupiah($order['total_price']) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="info-block">
                                    <div style="font-size: 11px; color: var(--muted); text-transform: uppercase; font-weight: 700;">Pembayaran</div>
                                    <div style="margin-top: 2px;"><?= paymentBadge($order['payment_status'] ?? 'Unpaid') ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Stepper Timeline Status -->
                        <h5 class="fw-bold mb-4" style="color: var(--navy-deep);">Perkembangan Cucian Anda</h5>
                        <div class="timeline-wrapper-modern">
                            <!-- Garis progress warna dinamis -->
                            <?php 
                            $percentMap = [0 => 0, 1 => 25, 2 => 50, 3 => 75, 4 => 100];
                            $widthPct = isset($percentMap[$currentIdx]) ? $percentMap[$currentIdx] : 0;
                            ?>
                            <div class="timeline-progress-bar d-none d-lg-block" style="width: <?= $widthPct ?>%"></div>

                            <?php 
                            $statusKeys = array_keys($statusFlow);
                            foreach ($statusFlow as $statusKey => $data):
                                $idx = array_search($statusKey, $statusKeys);
                                $class = '';
                                if ($idx < $currentIdx) $class = 'done';
                                elseif ($idx === $currentIdx) $class = 'active';

                                // Map status key ke Iconify Icon
                                $iconMap = [
                                    'Queued'    => 'mdi:clock-fast',
                                    'Washing'   => 'mdi:spray-bottle',
                                    'Ironing'   => 'mdi:iron',
                                    'Ready'     => 'mdi:washing-machine',
                                    'Completed' => 'mdi:leaf'
                                ];
                                $iconifyName = $iconMap[$statusKey] ?? 'mdi:help';
                            ?>
                            <div class="step-node <?= $class ?>">
                                <div class="step-icon-box">
                                    <iconify-icon icon="<?= $iconifyName ?>"></iconify-icon>
                                </div>
                                <div class="step-label-modern"><?= htmlspecialchars($data['label']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($order['notes']): ?>
                            <div class="p-3 bg-light rounded mb-4" style="border-left: 4px solid var(--primary);">
                                <small class="text-muted d-block uppercase fw-bold mb-1">Catatan Pesanan:</small>
                                <span class="text-dark" style="font-size: 13px;"><?= htmlspecialchars($order['notes']) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Estimasi selesai -->
                        <?php if ($order['estimated_done']): ?>
                            <div class="alert alert-info d-flex align-items-center gap-2 mt-4" style="border-radius: 12px;">
                                <iconify-icon icon="mdi:clock-fast" style="font-size: 20px;"></iconify-icon>
                                <span>Estimasi Siap Diambil: <strong><?= date('d M Y H:i', strtotime($order['estimated_done'])) ?></strong></span>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Error Alert jika tidak ketemu -->
                        <div class="alert alert-danger p-4 text-center mt-4" style="border-radius: 12px;">
                            <iconify-icon icon="mdi:headset" style="font-size: 36px; display: block; margin: 0 auto 10px; color: var(--navy-light);"></iconify-icon>
                            <span style="font-size: 14px;"><?= $error ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ── Footer ─────────────────────────────────────────────────── -->
    <footer class="footer-modern">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-5">
                    <div class="footer-brand-title">Laundry<span style="color: var(--mint);">Ku</span></div>
                    <p class="footer-desc">
                        Penyedia jasa cuci modern terintegrasi secara digital. Kami berkomitmen memberikan kualitas cuci terbaik, tepat waktu, dan amanah menggunakan detergen ramah lingkungan.
                    </p>
                    <div class="footer-socials">
                        <a href="https://wa.me/6282197719828" class="footer-social-link" target="_blank"><i class="bi bi-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 offset-lg-1">
                    <div class="footer-title">Tautan Cepat</div>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#services">Layanan</a></li>
                        <li><a href="#pricing">Harga</a></li>
                        <li><a href="#tracking">Lacak Pesanan</a></li>
                    </ul>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="footer-title">Hubungi Kami</div>
                    <ul class="footer-links">
                        <li class="d-flex gap-2 align-items-start text-white-50">
                            <i class="bi bi-geo-alt-fill text-white"></i>
                            <span>Bantul, Yogyakarta</span>
                        </li>
                        <li class="d-flex gap-2 align-items-center text-white-50">
                            <i class="bi bi-telephone-fill text-white"></i>
                            <span>0821-9771-9828</span>
                        </li>
                        <li class="d-flex gap-2 align-items-center text-white-50">
                            <i class="bi bi-envelope-fill text-white"></i>
                            <span>support@laundryku.co.id</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> LaundryKu. Hak Cipta Dilindungi Undang-Undang.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $publicUrl ?>/js/tracking.js"></script>
    
    <!-- Navbar Scroll Styling Script -->
    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Mobile Navigation Toggle
        const mobileToggle = document.getElementById('mobileNavToggle');
        const mobileClose = document.getElementById('mobileNavClose');
        const mobileSidebar = document.getElementById('mobileNavSidebar');
        const mobileOverlay = document.getElementById('mobileNavOverlay');
        const mobileLinks = document.querySelectorAll('.mobile-nav-menu a');

        function toggleMobileMenu() {
            mobileSidebar.classList.toggle('open');
            mobileOverlay.classList.toggle('open');
        }

        if (mobileToggle) mobileToggle.addEventListener('click', toggleMobileMenu);
        if (mobileClose) mobileClose.addEventListener('click', toggleMobileMenu);
        if (mobileOverlay) mobileOverlay.addEventListener('click', toggleMobileMenu);

        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileSidebar.classList.remove('open');
                mobileOverlay.classList.remove('open');
            });
        });
        
        // Auto scroll ke tracking section jika ada pencarian
        <?php if ($invoice !== ''): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const trackingSection = document.getElementById('tracking');
                if (trackingSection) {
                    trackingSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
