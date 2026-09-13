<!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <?php 
                    $footerBusinessTitle = $siteSettings->get('footer_business_title', 'BusinessHesap');
                    $footerBusinessDescription = $siteSettings->get('footer_business_description', 'Kaliteli sosyal medya hesapları ile işinizi büyütmeniz için buradayız. Güvenli alışverişin premium adresi.');
                    $footerSocialIcons = $siteSettings->getJsonSetting('footer_social_icons', [
                        ['platform' => 'Facebook', 'url' => 'https://facebook.com', 'icon' => 'fab fa-facebook-f'],
                        ['platform' => 'Instagram', 'url' => 'https://instagram.com', 'icon' => 'fab fa-instagram'],
                        ['platform' => 'Twitter', 'url' => 'https://twitter.com', 'icon' => 'fab fa-twitter'],
                        ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com', 'icon' => 'fab fa-linkedin-in'],
                        ['platform' => 'YouTube', 'url' => 'https://youtube.com', 'icon' => 'fab fa-youtube']
                    ]);
                    ?>
                    <h3><?php echo htmlspecialchars($footerBusinessTitle); ?></h3>
                    <p><?php echo htmlspecialchars($footerBusinessDescription); ?></p>
                    <div class="social-icons">
                        <?php foreach ($footerSocialIcons as $social): ?>
                            <a href="<?php echo htmlspecialchars($social['url']); ?>" aria-label="<?php echo htmlspecialchars($social['platform']); ?>" target="_blank">
                                <i class="<?php echo htmlspecialchars($social['icon']); ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="footer-column">
                    <?php 
                    $footerQuickLinksTitle = $siteSettings->get('footer_quick_links_title', 'Hızlı Erişim');
                    $footerQuickLinks = $siteSettings->getJsonSetting('footer_quick_links', [
                        ['name' => 'Anasayfa', 'url' => '/', 'icon' => 'fas fa-chevron-right'],
                        ['name' => 'Tüm Hesaplar', 'url' => 'tum-hesaplar', 'icon' => 'fas fa-chevron-right'],
                        ['name' => 'Hizmetlerimiz', 'url' => 'hizmetler', 'icon' => 'fas fa-chevron-right'],
                        ['name' => 'Nasıl Çalışır?', 'url' => 'nasil-calisir.php', 'icon' => 'fas fa-chevron-right'],
                        ['name' => 'SSS', 'url' => 'sikca-sorulan-sorular', 'icon' => 'fas fa-chevron-right']
                    ]);
                    ?>
                    <h3><?php echo htmlspecialchars($footerQuickLinksTitle); ?></h3>
                    <ul>
                        <?php foreach ($footerQuickLinks as $link): ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($link['url']); ?>">
                                    <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i> 
                                    <?php echo htmlspecialchars($link['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>İletişim</h3>
                    <ul>
                        <?php 
                        $contactPhone = $siteSettings->get('contact_phone', '+90 555 000 00 00');
                        $contactEmail = $siteSettings->get('contact_email', 'info@businesshesap.com');
                        $contactWhatsapp = $siteSettings->get('contact_whatsapp', '+90 555 000 00 00');
                        $contactAddress = $siteSettings->get('contact_address', 'İstanbul, Türkiye');
                        
                        // Clean phone number for tel and whatsapp links
                        $cleanPhone = preg_replace('/[^0-9+]/', '', $contactPhone);
                        $cleanWhatsapp = preg_replace('/[^0-9+]/', '', $contactWhatsapp);
                        ?>
                        <li><a href="tel:<?php echo htmlspecialchars($cleanPhone); ?>"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contactPhone); ?></a></li>
                        <li><a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contactEmail); ?></a></li>
                        <li><a href="https://wa.me/<?php echo htmlspecialchars($cleanWhatsapp); ?>" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp: <?php echo htmlspecialchars($contactWhatsapp); ?></a></li>
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($contactAddress); ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Ödeme Yöntemleri</h3>
                    <p>Tüm kredi kartları ve banka transferi ile güvenli ödeme</p>
                    <div class="payment-methodssa">
                        <div class="payment-methodsa" title="Visa"><i class="fab fa-cc-visa"></i></div>
                        <div class="payment-methodsa" title="Mastercard"><i class="fab fa-cc-mastercard"></i></div>
                        <div class="payment-methodsa" title="American Express"><i class="fab fa-cc-amex"></i></div>
                        <div class="payment-methodsa" title="PayPal"><i class="fab fa-cc-paypal"></i></div>
                        <div class="payment-methodsa" title="Bitcoin"><i class="fab fa-bitcoin"></i></div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <?php 
                $footerCopyright = $siteSettings->get('footer_copyright', '&copy; 2025 BusinessHesap.com - Tüm Hakları Saklıdır.');
                ?>
                <p><?php echo $footerCopyright; ?> | <a href="kvvk.php">KVKK</a> ve <a href="privacy.php">Gizlilik Politikası</a></p>
            </div>
        </div>
    </footer>

    <script>
        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentNode;
                const isActive = item.classList.contains('active');
                
                // Close all items
                document.querySelectorAll('.faq-item').forEach(el => {
                    el.classList.remove('active');
                });
                
                // Open clicked item if it wasn't active
                if (!isActive) {
                    item.classList.add('active');
                }
            });
        });

        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                const nav = document.querySelector('nav ul');
                const isVisible = nav.style.display === 'flex';
                
                if (isVisible) {
                    nav.style.display = 'none';
                } else {
                    nav.style.display = 'flex';
                    nav.style.flexDirection = 'column';
                    nav.style.position = 'absolute';
                    nav.style.top = '100%';
                    nav.style.left = '0';
                    nav.style.right = '0';
                    nav.style.backgroundColor = 'rgba(15, 23, 42, 0.98)';
                    nav.style.backdropFilter = 'blur(10px)';
                    nav.style.padding = '20px';
                    nav.style.gap = '15px';
                    nav.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.3)';
                    nav.style.borderTop = '1px solid var(--card-border)';
                    nav.style.borderBottom = '1px solid var(--card-border)';
                    nav.style.zIndex = '999';
                }
            });
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    
                    // Counter animation for numbers
                    if (entry.target.classList.contains('stat-number')) {
                        animateCounter(entry.target);
                    }
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.stat-card, .service-card, .feature-card, .category-card').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'all 0.6s ease-out';
            observer.observe(element);
        });

        // Counter animation function
        function animateCounter(element) {
            const target = parseInt(element.textContent.replace(/[^\d]/g, ''));
            const duration = 2000;
            const step = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                
                // Format number with original suffix
                const originalText = element.textContent;
                const suffix = originalText.replace(/[\d,]/g, '');
                element.textContent = Math.floor(current).toLocaleString('tr-TR') + suffix;
            }, 16);
        }

        // Page loading animation
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
        });

        // Scroll to top functionality
        let scrollToTopBtn = document.createElement('button');
        scrollToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        scrollToTopBtn.className = 'scroll-to-top';
        scrollToTopBtn.setAttribute('aria-label', 'Yukarı çık');
        document.body.appendChild(scrollToTopBtn);

        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });

        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Form validation helper
        function validateForm(form) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            return isValid;
        }

        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('Global error:', e.error);
            // In production, you might want to send this to a logging service
        });

        // Global unhandled promise rejection handler
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled promise rejection:', e.reason);
            // In production, you might want to send this to a logging service
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const nav = document.querySelector('nav ul');
            const mobileBtn = document.querySelector('.mobile-menu-btn');
            
            if (nav && nav.style.display === 'flex' && 
                !nav.contains(e.target) && 
                !mobileBtn.contains(e.target)) {
                nav.style.display = 'none';
            }
        });

        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            // Close dropdowns with Escape key
            if (e.key === 'Escape') {
                document.querySelectorAll('.filter-dropdown.active').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
                
                // Close mobile menu
                const nav = document.querySelector('nav ul');
                if (nav && nav.style.display === 'flex') {
                    nav.style.display = 'none';
                }
            }
        });

        // Performance monitoring (optional)
        if (typeof PerformanceObserver !== 'undefined') {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.entryType === 'largest-contentful-paint') {
                        console.log('LCP:', entry.renderTime || entry.loadTime);
                    }
                }
            });
            
            observer.observe({entryTypes: ['largest-contentful-paint']});
        }

        // Service Worker registration (optional, for PWA features)
        if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('SW registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>

    <style>
    .payment-methodssa {
  display: flex;          /* Yan yana sıralama için */
  gap: 15px;             /* İkonlar arası boşluk */
  align-items: center;   /* Dikey hizalama */
  flex-wrap: wrap;       /* Dar ekranlarda altına geç */
}

.payment-methodsa {
  font-size: 2.5rem;     /* İkon boyutu */
  color: #ccc;           /* İkon rengi */
  transition: transform 0.2s; /* Hover animasyonu */
}

.payment-methodsa:hover {
  transform: scale(1.1); /* Hover efekti */
}
    /* Scroll to top button */
    .scroll-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
        border: none;
        border-radius: 50%;
        color: white;
        font-size: 18px;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px);
        transition: all 0.3s ease;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(108, 99, 255, 0.3);
    }

    .scroll-to-top.visible {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .scroll-to-top:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 25px rgba(108, 99, 255, 0.4);
    }

    /* Form error states */
    .form-field.error,
    input.error,
    textarea.error,
    select.error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }

    /* Loading animation */
    body:not(.loaded) {
        overflow: hidden;
    }

    body:not(.loaded)::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--dark);
        z-index: 9999;
        opacity: 1;
        transition: opacity 0.5s ease;
    }

    body.loaded::before {
        opacity: 0;
        pointer-events: none;
    }

    /* Accessibility improvements */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Focus styles */
    *:focus {
        outline: 2px solid var(--accent);
        outline-offset: 2px;
    }

    button:focus,
    .btn:focus,
    input:focus,
    textarea:focus,
    select:focus {
        outline: 2px solid var(--accent);
        outline-offset: 2px;
    }

    /* High contrast mode support */
    @media (prefers-contrast: high) {
        :root {
            --card-border: rgba(255, 255, 255, 0.3);
            --gray: #b0b0b0;
        }
    }

    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
    }

    /* Print styles */
    @media print {
        header,
        footer,
        .filters-bar,
        .scroll-to-top,
        .toast {
            display: none !important;
        }
        
        body {
            background: white !important;
            color: black !important;
        }
        
        .container {
            max-width: none !important;
            padding: 0 !important;
        }
    }

    /* Mobile responsive improvements */
    @media (max-width: 576px) {
        .scroll-to-top {
            bottom: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
            font-size: 16px;
        }
        
        .footer-grid {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        .social-icons {
            justify-content: center;
        }
        
        .payment-methods {
            justify-content: center;
        }
    }

    /* Dark mode support (if needed in future) */
    @media (prefers-color-scheme: dark) {
        /* Already using dark theme, but can add overrides here if needed */
    }
    </style>

</body>
</html>