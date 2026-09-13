<?php
/**
 * Structured Data Helper
 * Schema.org structured data (JSON-LD) oluşturmak için yardımcı sınıf
 */

class StructuredDataHelper {
    
    private $baseUrl;
    private $siteName;
    
    public function __construct($baseUrl = 'https://yildizhesap.net', $siteName = 'Yıldız Hesap') {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->siteName = $siteName;
    }
    
    /**
     * Kategori sayfası için yapısal veri oluştur
     */
    public function getCategoryStructuredData($categorySlug, $categoryName, $categoryDescription, $accounts) {
        $pageUrl = $this->baseUrl . '/' . $categorySlug;
        
        // Kategori bilgilerini yapılandır
        $data = [
            '@context' => 'https://schema.org',
            '@graph' => []
        ];
        
        // WebPage tanımı
        $data['@graph'][] = [
            '@type' => 'WebPage',
            '@id' => $pageUrl . '#webpage',
            'url' => $pageUrl,
            'name' => $categoryName,
            'description' => $categoryDescription,
            'inLanguage' => 'tr-TR',
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => $this->baseUrl . '/#website'
            ],
            'mainEntity' => [
                '@id' => $pageUrl . '#itemlist'
            ]
        ];
        
        // ItemList tanımı
        $itemList = [
            '@type' => 'ItemList',
            '@id' => $pageUrl . '#itemlist',
            'name' => $categoryName,
            'itemListOrder' => 'https://schema.org/ItemListUnordered',
            'numberOfItems' => count($accounts),
            'itemListElement' => []
        ];
        
        // Özel kategoriler için açıklama ekle
        $categoryDescriptions = [
            'ana-hesaplar-dogrulanmis' => 'Kimlik doğrulaması yapılmış, ana hesap olarak kullanılan ve reklam ile Business Manager uyumlu Facebook ve sosyal medya hesapları.',
            'direncli-hesaplar' => 'Daha önce kısıt almış, itiraz veya inceleme sonrası yeniden aktif edilmiş doğrulanmış Facebook ana hesapları.',
            'business-manager' => 'Reklam verme, sayfa ve varlık yönetimi için kullanılan, doğrulanmış ve kullanıma hazır Business Manager hesapları.',
            'facebook-marketplace' => 'Doğrulanmış ve güvenli şekilde satılan Facebook MarketPlace hesapları.',
            'facebook-hesaplari' => 'Doğrulanmış ve güvenli Facebook hesapları, yabancı ve Türk seçenekleri, kimlik onaylı seçenekler dahil.',
            'instagram-hesaplari' => 'Doğrulanmış İnstagram hesapları, tanıtım onaylı ve takipçi sayısına göre çeşitlendirilmiş seçenekler.',
            'telegram-hesaplari' => 'Doğrulanmış Telegram hesapları.'
        ];
        
        if (isset($categoryDescriptions[$categorySlug])) {
            $itemList['description'] = $categoryDescriptions[$categorySlug];
        }
        
        // Hesapları listeye ekle
        foreach ($accounts as $index => $account) {
            $itemList['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $account['title']
            ];
        }
        
        $data['@graph'][] = $itemList;
        
        // Breadcrumb tanımı
        $data['@graph'][] = $this->getBreadcrumb($pageUrl, $categoryName, 'tr');
        
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    /**
     * Ürün detay sayfası için yapısal veri oluştur
     */
    public function getProductStructuredData($account) {
        $productUrl = $this->baseUrl . '/' . $account['seo_slug'];
        
        $data = [
            '@context' => 'https://schema.org',
            '@graph' => []
        ];
        
        // Product tanımı
        $product = [
            '@type' => 'Product',
            '@id' => $productUrl . '#product',
            'name' => $account['title'],
            'description' => $account['description'],
            'image' => $account['image_url'] ?? 'https://www.businesshesap.com/storage/category_images/facebook-50-li-reklam-hesabi-satin-al.png',
            'category' => $account['category_name'] ?? 'sosyal medya hesap satışı',
            'brand' => $this->siteName
        ];
        
        // Fiyat bilgisi (Offer)
        $product['offers'] = [
            '@type' => 'Offer',
            'url' => $productUrl,
            'priceCurrency' => 'USD',
            'price' => number_format($account['price'], 2, '.', ''),
            'priceValidUntil' => date('Y-12-31', strtotime('+1 year')),
            'availability' => $this->getAvailabilityStatus($account['stock_quantity'] ?? 0),
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => [
                '@type' => 'Organization',
                'name' => $this->siteName,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressCountry' => 'TR'
                ]
            ],
            'shippingDetails' => [
                '@type' => 'OfferShippingDetails',
                'shippingDestination' => [
                    '@type' => 'DefinedRegion',
                    'name' => $account['location'] ?? 'Türkiye',
                    'addressCountry' => 'TR'
                ],
                'shippingRate' => [
                    '@type' => 'MonetaryAmount',
                    'value' => '0',
                    'currency' => 'USD'
                ],
                'deliveryTime' => [
                    '@type' => 'ShippingDeliveryTime',
                    'handlingTime' => [
                        '@type' => 'QuantitativeValue',
                        'minValue' => 0,
                        'maxValue' => 0,
                        'unitCode' => 'DAY'
                    ],
                    'transitTime' => [
                        '@type' => 'QuantitativeValue',
                        'minValue' => 0,
                        'maxValue' => 1,
                        'unitCode' => 'DAY'
                    ]
                ]
            ],
            'hasMerchantReturnPolicy' => [
                '@type' => 'MerchantReturnPolicy',
                'returnPolicyCategory' => 'http://schema.org/MerchantReturnNotPermitted',
                'applicableCountry' => 'TR'
            ]
        ];
        
        // Ürün özellikleri
        $additionalProperties = [];
        
        if ($account['is_verified']) {
            $additionalProperties[] = ['@type' => 'PropertyValue', 'name' => 'Doğrulanmış', 'value' => 'Evet'];
        }
        
        if ($account['is_premium']) {
            $additionalProperties[] = ['@type' => 'PropertyValue', 'name' => 'Premium', 'value' => 'Evet'];
        }
        
        if ($account['is_secure']) {
            $additionalProperties[] = ['@type' => 'PropertyValue', 'name' => 'Güvenli', 'value' => 'Evet'];
        }
        
        if ($account['instant_delivery']) {
            $additionalProperties[] = ['@type' => 'PropertyValue', 'name' => 'Anında Teslimat', 'value' => 'Evet'];
        }
        
        if ($account['support_24_7']) {
            $additionalProperties[] = ['@type' => 'PropertyValue', 'name' => '7/24 Destek', 'value' => 'Evet'];
        }
        
        if ($account['guarantee_30_days']) {
            $additionalProperties[] = ['@type' => 'PropertyValue', 'name' => '30 Gün Garanti', 'value' => 'Evet'];
        }
        
        // Teknik bilgilerden ek özellikler çıkar
        if (!empty($account['technical_info'])) {
            $technicalLines = explode("\n", $account['technical_info']);
            foreach ($technicalLines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // "Key: Value" formatını arat
                if (strpos($line, ':') !== false) {
                    list($key, $value) = array_map('trim', explode(':', $line, 2));
                    $additionalProperties[] = [
                        '@type' => 'PropertyValue',
                        'name' => $key,
                        'value' => $value
                    ];
                }
            }
        }
        
        if (!empty($additionalProperties)) {
            $product['additionalProperty'] = $additionalProperties;
        }
        
        $data['@graph'][] = $product;
        
        // Breadcrumb - ürün isminden dil algıla
        $language = $this->detectLanguage($account['title']);
        $data['@graph'][] = $this->getBreadcrumb($productUrl, $account['title'], $language);
        
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    /**
     * Breadcrumb oluştur
     */
    private function getBreadcrumb($pageUrl, $pageName, $language = 'tr') {
        $homeLabel = ($language === 'en') ? 'Home' : 'Ana Sayfa';
        
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $pageUrl . '#breadcrumb',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => $homeLabel,
                    'item' => $this->baseUrl . '/'
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $pageName,
                    'item' => $pageUrl
                ]
            ]
        ];
    }
    
    /**
     * Ürün veya kategori isminden dili algıla
     */
    private function detectLanguage($text) {
        // İngilizce pattern kontrolü (kelime başında büyük harf, İngilizce kelimeler)
        $englishPatterns = [
            '/\b(Account|Accounts|Manager|Old|Aged|Reinstated|Business|Random|Vietnamese|India|Taiwan|Poland|USA|UK|Thailand|Indonesia|Philippines|Super|Country|Profile|Fb)\b/i'
        ];
        
        foreach ($englishPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return 'en';
            }
        }
        
        return 'tr';
    }
    
    /**
     * Stok durumuna göre availability durumu döndür
     */
    private function getAvailabilityStatus($stockQuantity) {
        if ($stockQuantity > 10) {
            return 'https://schema.org/InStock';
        } elseif ($stockQuantity > 0) {
            return 'https://schema.org/LimitedAvailability';
        } else {
            return 'https://schema.org/OutOfStock';
        }
    }
    
    /**
     * Kategori slug'ına göre özel açıklama ve başlık döndür
     */
    public static function getCategoryMetaData($categorySlug, $categoryName) {
        $metaData = [
            'tum-hesaplar' => [
                'name' => 'Premium Hesaplar',
                'description' => 'Facebook premium hesap satın alın! Doğrulanmış, yüksek kaliteli ve hızlı teslimat garantili hesaplarla güvenli alışveriş yapın!'
            ],
            'ana-hesaplar-dogrulanmis' => [
                'name' => 'Doğrulanmış Ana Hesaplar',
                'description' => 'Doğrulanmış eski Facebook ve sosyal medya hesaplarını güvenli ödeme yöntemleri, hızlı teslimat ve premium seçeneklerle hemen satın alın.'
            ],
            'direncli-hesaplar' => [
                'name' => 'Dirençli Hesaplar',
                'description' => 'Eski tarihli Facebook hesaplarını en uygun premium seçenekler ile güvenli ödeme ve anlık teslimat garantisiyle hemen satın alın.'
            ],
            'old-accounts' => [
                'name' => 'Old Accounts',
                'description' => 'Doğrulanmış eski Facebook premium hesaplarını kolayca seçin ve güvenli ödeme ile hızlı teslimat garantisiyle hemen satın alın.'
            ],
            'business-manager' => [
                'name' => 'Business Manager',
                'description' => 'Reklam geçmişi bulunan, günlük harcama limitli ve doğrulanmış Business Manager hesaplarını güvenli ödeme ve hızlı teslimat avantajıyla edinin.'
            ],
            'facebook-marketplace' => [
                'name' => 'Facebook MarketPlace',
                'description' => 'Reklam vermeye hazır, Marketplace erişimi açık Facebook hesaplarını hızlı teslimat ve güvenli ödeme seçenekleriyle hemen satın alın.'
            ],
            'facebook-hesaplari' => [
                'name' => 'Facebook Hesapları',
                'description' => 'Farklı kullanım amaçlarına ve reklama uygun Facebook hesaplarını güvenli ödeme, hızlı teslimat ve net hesap bilgileriyle kolayca satın alın.'
            ],
            'instagram-hesaplari' => [
                'name' => 'İnstagram Hesapları',
                'description' => 'Farklı kullanım senaryolarına uygun Instagram hesaplarını şeffaf bilgiler, güvenli ödeme ve hızlı teslimat altyapısıyla satın alın.'
            ],
            'onayli-twitter-hesaplari' => [
                'name' => 'Onaylı Twitter Hesapları',
                'description' => 'Farklı kullanım amaçlarına uygun X (Twitter) hesaplarını güvenli ödeme altyapısı ve hızlı erişim avantajıyla hemen satın alın.'
            ],
            'tiktok-hesaplari' => [
                'name' => 'TikTok Hesapları',
                'description' => 'Farklı kullanım amaçlarına uygun TikTok hesaplarını sade yapı, güvenli ödeme altyapısı ve hızlı erişim avantajıyla hemen satın alın.'
            ],
            'mail-gmail-outlook-hotmail-hesaplari' => [
                'name' => 'Mail, Gmail, Outlook, Hotmail Hesapları',
                'description' => 'Gmail, Outlook ve Hotmail hesaplarını farklı kullanım senaryolarına uygun yapı, sorunsuz erişim ve hızlı kullanım avantajıyla satın alın.'
            ],
            'telegram-hesaplari' => [
                'name' => 'Telegram Hesapları',
                'description' => 'Telegram hesaplarını farklı kullanım amaçlarına uygun yapı, hızlı erişim ve sorunsuz kullanım avantajlarıyla uygun fiyata kolayca edinin.'
            ]
        ];
        
        // Eğer tanımlıysa özel meta data döndür, değilse varsayılan kullan
        if (isset($metaData[$categorySlug])) {
            return $metaData[$categorySlug];
        }
        
        return [
            'name' => $categoryName,
            'description' => $categoryName . ' kategorisindeki premium hesapları güvenli ödeme ve hızlı teslimat avantajıyla satın alın.'
        ];
    }
    
    /**
     * FAQ sayfası için yapısal veri oluştur
     */
    public function getFAQStructuredData($faqs) {
        $pageUrl = $this->baseUrl . '/sikca-sorulan-sorular';
        
        $data = [
            '@context' => 'https://schema.org',
            '@graph' => []
        ];
        
        // FAQPage tanımı
        $faqPage = [
            '@type' => 'FAQPage',
            '@id' => $pageUrl . ' #faq',
            'mainEntity' => []
        ];
        
        foreach ($faqs as $faq) {
            $faqPage['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }
        
        $data['@graph'][] = $faqPage;
        $data['@graph'][] = $this->getBreadcrumb($pageUrl, 'Sıkça Sorulan Sorular');
        
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    /**
     * Hizmetler sayfası için yapısal veri oluştur
     */
    public function getServicesStructuredData($categories) {
        $pageUrl = $this->baseUrl . '/hizmetler';
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            '@id' => $pageUrl . '#service',
            'name' => 'Hizmetlerimiz',
            'description' => 'Doğrulanmış Facebook, Instagram, Twitter ve diğer premium hesaplarla işlerinizi büyütün. Güvenli ödeme ve hızlı teslimat fırsatını kaçırmayın!',
            'disambiguatingDescription' => 'Doğrulanmış hesaplar, Businnes Manager, Dirençli Hesaplar, Facebook, Instagram, Telegram, Tiktok, Twitter hesapları ve diğer dijital araçları kapsayan premium hizmetler.',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $pageUrl,
                'url' => $pageUrl,
                'name' => 'Hizmetlerimiz'
            ],
            'provider' => [
                '@type' => 'Organization',
                'name' => $this->siteName,
                'url' => $this->baseUrl . '/',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $this->baseUrl . '/images/mockup.png'
                ]
            ],
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'Global'
            ],
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name' => 'Hizmetlerimiz Kapsamı',
                'itemListElement' => []
            ],
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                '@id' => $pageUrl . '#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $this->baseUrl . '/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Hizmetlerimiz', 'item' => $pageUrl]
                ]
            ]
        ];
        
        foreach ($categories as $category) {
            $data['hasOfferCatalog']['itemListElement'][] = [
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => $category['name'],
                    'description' => 'Premium ' . $category['name'] . ' satış hizmetleri. Doğrulanmış hesaplar, yüksek kalite garantisi, anında teslimat ve 7/24 destek.'
                ]
            ];
        }
        
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    /**
     * İletişim sayfası için yapısal veri oluştur
     */
    public function getContactStructuredData($contactEmail, $contactPhone) {
        $pageUrl = $this->baseUrl . '/iletisim';
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $pageUrl . '#organization',
            'name' => $this->siteName,
            'url' => $pageUrl,
            'description' => 'Facebook hesap satın alma ve diğer konularda herhangi bir sorunuz varsa bize hemen ulaşın. 7/24 canlı destekle hemen yardım alın.',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $this->baseUrl . '/images/mockup.png'
            ],
            'contactPoint' => [
                [
                    '@type' => 'ContactPoint',
                    'contactType' => 'Customer Support',
                    'name' => 'WhatsApp Destek',
                    'telephone' => $contactPhone,
                    'availableLanguage' => ['Turkish', 'English'],
                    'areaServed' => 'Global',
                    'description' => 'WhatsApp ile anında yardım alın.'
                ],
                [
                    '@type' => 'ContactPoint',
                    'contactType' => 'Customer Support',
                    'name' => 'E-posta Destek',
                    'email' => $contactEmail,
                    'availableLanguage' => ['Turkish', 'English'],
                    'areaServed' => 'Global',
                    'description' => 'Detaylı yardım için e-posta ile iletişime geçin.'
                ]
            ],
            'interactionStatistic' => [
                [
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'https://schema.org/RespondedToCustomer',
                    'userInteractionCount' => 5,
                    'name' => 'Ortalama Yanıt Süresi (dakika)'
                ],
                [
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'https://schema.org/RespondedToCustomer',
                    'userInteractionCount' => 98,
                    'name' => 'Memnuniyet Oranı (%)'
                ],
                [
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'https://schema.org/ServiceChannel',
                    'userInteractionCount' => 7,
                    'name' => 'Canlı Destek 7/24'
                ]
            ],
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                '@id' => $pageUrl . '#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $this->baseUrl . '/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'İletişim', 'item' => $pageUrl]
                ]
            ]
        ];
        
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    /**
     * Legal sayfa (KVKK, Gizlilik vb.) için yapısal veri oluştur
     */
    public function getLegalPageStructuredData($slug, $title) {
        $pageUrl = $this->baseUrl . '/' . $slug;
        
        $descriptions = [
            'kvkk' => 'Kişisel verilerinizin korunması, işlenme amaçları, veri güvenliği ve yasal haklarınız hakkında detaylı bilgilendirme.',
            'gizlilik-politikasi' => 'Kişisel bilgilerinizin nasıl toplandığı, kullanıldığı, korunduğu ve hangi durumlarda paylaşıldığı hakkında detaylı bilgiler.'
        ];
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $pageUrl . '#webpage',
            'url' => $pageUrl,
            'name' => $title,
            'description' => $descriptions[$slug] ?? $title,
            'inLanguage' => 'tr',
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                '@id' => $pageUrl . '#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => $this->baseUrl . '/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $title, 'item' => $pageUrl]
                ]
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->siteName,
                'url' => $this->baseUrl,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $this->baseUrl . '/images/mockup.png'
                ],
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'contactType' => 'Customer Support',
                    'email' => 'satisdestek@yildizhesap.net',
                    'availableLanguage' => ['Turkish', 'English']
                ]
            ]
        ];
        
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
