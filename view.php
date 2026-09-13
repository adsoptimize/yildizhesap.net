<?php
// view.php - Tek hesap detay sayfası
require_once 'config.php';
require_once 'functions.php';
require_once 'StructuredDataHelper.php';

// Hesap ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: hesaplar.php');
    exit;
}

$accountId = (int)$_GET['id'];
$accountManager = new AccountManager();

// Hesap bilgilerini getir
$account = $accountManager->getAccountById($accountId);

if (!$account) {
    header('Location: hesaplar.php');
    exit;
}

// Stok durumunu kontrol et (yönlendirme yapma, sadece bilgi olarak kullan)
$stockStatus = getStockStatus($account['stock_quantity']);
$hasStock = $account['stock_quantity'] > 0;

// Hesap özelliklerini getir
$features = $accountManager->getAccountFeatures($accountId);

// Görüntülenme sayısını artır
$accountManager->incrementViews($accountId);

$page_title = $account['title'] . " - Hesap Detayı";

// Meta Tags for SEO and AI - Default values
$meta_title = $account['title'] . " - Hesap Satın Al";
$meta_description = $account['description'];
$meta_keywords = "hesap satın al, " . $account['title'] . ", premium hesaplar";
$meta_ai_intent = "sosyal medya hesap satın alma";
$meta_ai_subtopics = "sosyal medya hesapları, premium hesaplar";
$meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar";
$meta_category = "sosyal medya hesap satışı";
$meta_content_tone = "profesyonel, satış odaklı";
$meta_reader_interest = "premium hesaplar, hızlı teslimat";
$meta_summary = $account['title'] . " satışı";
$meta_topic_tags = "sosyal medya hesapları, premium hesaplar";
$meta_visual_content = "hesap görselleri";

// Override meta tags based on account ID
if ($accountId == 41) {
    $meta_title = "Facebook Marketplace Hesapları Satın Al";
    $meta_description = "Facebook Marketplace kullanıma hazır, doğrulanmış premium hesaplar. Güvenli yapı, hızlı teslimat, 7/24 destek ve 30 gün garantili.";
    $meta_keywords = "facebook marketplace hesabı satın al, marketplace hesapları, ürün satış hesabı";
    $meta_ai_intent = "facebook marketplace hesaplarını güvenli ve hızlı şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "facebook marketplace hesapları, satış yapmaya hazır hesaplar, doğrulanmış marketplace hesapları satışı";
    $meta_audience = "e ticaret satıcıları, online satış yapanlar, dijital girişimciler";
    $meta_category = "facebook hesap satışı, marketplace hesapları";
    $meta_reader_interest = "marketplace üzerinden satış, güvenli hesaplar, hazır facebook hesapları";
    $meta_summary = "facebook marketplace için hazır doğrulanmış premium hesap satışı";
    $meta_topic_tags = "facebook marketplace, marketplace hesapları, ürün satışı";
    $meta_visual_content = "marketplace ikonları";
} elseif ($accountId == 42) {
    $meta_title = "Kimlik Onaylı Facebook Hesapları Satın Al";
    $meta_description = "Kimlik onaylı Facebook hesapları ile güvenli ve stabil kullanım sağlayın. Premium yapı, hızlı teslimat, 7/24 destek ve 30 gün garantili.";
    $meta_keywords = "kimlik onaylı facebook hesabı satın al, doğrulanmış profil hesapları, güvenli facebook hesap";
    $meta_ai_intent = "kimlik onaylı facebook hesaplarını güvenli şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "kimlik onaylı facebook hesapları, profil hesapları, doğrulanmış hesaplar";
    $meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar, güvenli hesap arayanlar";
    $meta_category = "facebook hesap satışı, kimlik onaylı hesaplar";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "stabil facebook hesapları, kimlikli hesaplar, garantili teslimat";
    $meta_summary = "kimlik onaylı ve premium facebook hesaplarını güvenli şekilde satın alabilirsiniz";
    $meta_topic_tags = "kimlik onaylı facebook, profil hesapları, doğrulanmış hesaplar";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 43) {
    $meta_title = "Güçlendirilmiş Kimlik Onaylı Facebook Hesapları Satın Al";
    $meta_description = "Güçlendirilmiş kimlik onaylı Facebook hesapları ile güvenli ve stabil kullanım elde edin. Premium yapı, hızlı teslimat, 30 gün garanti.";
    $meta_keywords = "güçlendirilmiş kimlik onaylı facebook hesabı satın al, doğrulanmış facebook profil hesapları, güvenli facebook hesap";
    $meta_ai_intent = "güçlendirilmiş kimlik onaylı facebook hesaplarını güvenli ve sorunsuz şekilde satın almak isteyen kullanıcıları bilgilendirme";
    $meta_ai_subtopics = "güçlendirilmiş facebook hesapları, kimlik onaylı hesaplar, doğrulanmış profil hesapları";
    $meta_audience = "dijital pazarlamacılar, sosyal medya yöneticileri, güvenli ve güçlü hesap arayanlar";
    $meta_category = "facebook hesap satışı, kimlik onaylı premium hesaplar";
    $meta_reader_interest = "dayanıklı facebook hesapları, güçlendirilmiş yapılar, sosyal medya pazarlama";
    $meta_summary = "güçlendirilmiş ve kimlik onaylı premium facebook hesaplarını güvenli şekilde satın alabilirsiniz";
    $meta_topic_tags = "güçlendirilmiş facebook hesap satın al, kimlik onaylı hesaplar, doğrulanmış sosyal medya profili";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 44) {
    $meta_title = "Türk Facebook Hesapları Satın Al | Doğrulanmış ve Güvenli";
    $meta_description = "Türk Facebook hesapları ile yerel ve güvenli kullanım sağlayın. Doğrulanmış yapı, premium kalite, hızlı teslimat, 7/24 destek, 30 gün garanti.";
    $meta_keywords = "Türk Facebook hesabı satın al, doğrulanmış Türk Facebook hesapları, güvenli Facebook hesabı";
    $meta_ai_intent = "Türk Facebook hesaplarını güvenli, doğrulanmış ve sorunsuz şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "Türk Facebook hesapları, doğrulanmış Facebook profilleri, yerel Facebook hesapları";
    $meta_audience = "sosyal medya yöneticileri, dijital pazarlamacılar, Türk Facebook hesabı arayan kullanıcılar";
    $meta_category = "Facebook hesap satışı, Türk Facebook hesapları";
    $meta_reader_interest = "Türk Facebook hesapları, yerel hesap kullanımı, garantili teslimat";
    $meta_summary = "doğrulanmış ve güvenli Türk Facebook hesaplarını hızlı teslimat ve 30 gün garanti ile satın alabilirsiniz";
    $meta_topic_tags = "Türk Facebook hesapları, doğrulanmış sosyal medya hesap satışı";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 45) {
    $meta_title = "Yabancı Facebook Hesapları Satın Al | Doğrulanmış ve Güvenli";
    $meta_description = "Yabancı Facebook hesapları ile farklı ülkelerden güvenli ve stabil kullanım sağlayın. Doğrulanmış yapı, premium kalite, hızlı teslimat.";
    $meta_keywords = "yabancı facebook hesabı satın al, doğrulanmış yabancı facebook hesapları, güvenli facebook hesabı";
    $meta_ai_intent = "yabancı facebook hesaplarını güvenli ve sorunsuz şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "yabancı facebook hesapları, doğrulanmış facebook profilleri, global facebook hesapları satışı";
    $meta_audience = "dijital pazarlamacılar, sosyal medya yöneticileri, yabancı facebook hesabı arayan kullanıcılar";
    $meta_category = "facebook hesap satışı, yabancı facebook hesapları";
    $meta_reader_interest = "yabancı facebook hesapları, global kullanım facebook hesap satışı";
    $meta_summary = "doğrulanmış ve güvenli yabancı facebook hesaplarını anında teslimat ve garanti ile satın alabilirsiniz";
    $meta_topic_tags = "yabancı facebook hesapları, doğrulanmış hesaplar, facebook profil satın al";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 46) {
    $meta_title = "10.000 Takipçili Instagram Hesapları Satın Al";
    $meta_description = "10.000 takipçili Instagram hesapları ile hızlı ve güçlü bir başlangıç yapın. Doğrulanmış yapı, premium kalite, hızlı teslimat, 30 gün garanti.";
    $meta_keywords = "10.000 takipçili instagram hesabı satın al, doğrulanmış instagram hesapları, takipçili instagram hesabı";
    $meta_ai_intent = "10.000 takipçili instagram hesaplarını güvenli ve sorunsuz şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "10k instagram hesapları, takipçili instagram hesabı satın al, doğrulanmış instagram profilleri satışı";
    $meta_audience = "dijital pazarlamacılar, sosyal medya yöneticileri, instagram ile işlerini büyütmek isteyenler";
    $meta_category = "instagram hesap satışı, takipçili premium hesaplar";
    $meta_reader_interest = "hazır instagram hesapları, 10k takipçili instagram profili";
    $meta_summary = "10.000 takipçili doğrulanmış instagram hesaplarını güvenli şekilde anında teslimat ve garanti ile satın alabilirsiniz";
    $meta_topic_tags = "instagram hesapları, 10k takipçi, doğrulanmış instagram profili satışı";
    $meta_visual_content = "instagram ikonları";
} elseif ($accountId == 47) {
    $meta_title = "5.000 Takipçili Instagram Hesapları Satın Al";
    $meta_description = "5.000 takipçili Instagram hesapları ile hızlı bir başlangıç yapın. Doğrulanmış yapı, premium kalite, 7/24 destek, 30 gün garanti.";
    $meta_keywords = "5.000 takipçili instagram hesabı satın al, doğrulanmış instagram hesapları satışı, takipçili instagram hesabı satın al";
    $meta_ai_intent = "5.000 takipçili instagram hesaplarını güvenli şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "5k instagram hesapları, takipçili instagram hesapları, doğrulanmış instagram profilleri satışı";
    $meta_audience = "dijital pazarlamacılar, sosyal medya yöneticileri, instagram hesabını büyütmek isteyen kullanıcılar";
    $meta_category = "instagram hesap satışı, takipçili premium hesaplar";
    $meta_reader_interest = "hazır instagram hesapları, 5k takipçi ile sosyal medyada büyümek";
    $meta_summary = "5.000 takipçili doğrulanmış instagram hesaplarını güvenli şekilde anında teslimat ve garanti ile satın alabilirsiniz";
    $meta_topic_tags = "instagram hesapları, 5k takipçili instagram profil satışı";
    $meta_visual_content = "instagram ikonları";
} elseif ($accountId == 49) {
    $meta_title = "20 Adet Gönderili Instagram Hesapları Satın Al";
    $meta_description = "20 adet gönderili Instagram hesapları ile sosyal medya varlığınızı hemen başlatın. Doğrulanmış hesaplar, 7/24 destek, 30 gün garanti.";
    $meta_keywords = "20 gönderili instagram hesabı satın al, doğrulanmış instagram hesapları, premium instagram profilleri";
    $meta_ai_intent = "20 adet gönderili instagram hesaplarını güvenli şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "20 gönderili instagram hesapları, doğrulanmış instagram profilleri, premium instagram hesaplar";
    $meta_audience = "dijital pazarlamacılar, sosyal medya yöneticileri, instagram hesabını hızlıca kullanmak isteyenler";
    $meta_category = "instagram hesap satışı, premium gönderili hesaplar";
    $meta_reader_interest = "hazır instagram hesapları, gönderili profil satışı";
    $meta_summary = "20 adet gönderili, doğrulanmış instagram hesaplarını güvenli şekilde ve 30 gün garanti ile satın alabilirsiniz";
    $meta_topic_tags = "instagram hesap satışı, gönderili hesap satın al, doğrulanmış profil ve premium hesap satın al";
    $meta_visual_content = "instagram ikonları";
} elseif ($accountId == 50) {
    $meta_title = "Tanıtım Onaylı Instagram Hesapları Satın Al";
    $meta_description = "Tanıtım onaylı Instagram hesapları ile sosyal medya kampanyalarınızı güvenle başlatın. Doğrulanmış hesaplar, premium kalite, 30 gün garanti.";
    $meta_keywords = "tanıtım onaylı instagram hesabı satın al, doğrulanmış instagram hesapları, premium instagram profilleri satışı";
    $meta_ai_intent = "tanıtım onaylı instagram hesaplarını güvenli ve sorunsuz şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "tanıtım onaylı instagram hesapları, doğrulanmış instagram profilleri, premium instagram hesap satın al";
    $meta_audience = "dijital pazarlamacılar, sosyal medya yöneticileri, instagram hesabını hızlıca kullanmak isteyenler";
    $meta_category = "instagram hesap satışı, premium tanıtım onaylı hesaplar";
    $meta_reader_interest = "tanıtım onaylı instagram hesapları, hızlı kullanım sosyal medya profil satışı";
    $meta_summary = "tanıtım onaylı, doğrulanmış instagram hesaplarını güvenli şekilde anında teslimat ve 30 gün garanti ile satın alabilirsiniz";
    $meta_topic_tags = "instagram hesapları, tanıtım onaylı hesaplar, doğrulanmış profil ve premium hesap satışı";
    $meta_visual_content = "instagram ikonları";
} elseif ($accountId == 60) {
    $meta_title = "Telegram Hesapları Satın Al | Doğrulanmış ve Güvenli";
    $meta_description = "Doğrulanmış Telegram hesapları ile hızlı ve güvenli iletişime geçin. Premium hesaplar, anında teslimat, 7/24 destek, 30 gün garanti.";
    $meta_keywords = "telegram hesabı satın al, doğrulanmış telegram hesapları, premium telegram profilleri satın al";
    $meta_ai_intent = "telegram hesaplarını güvenli ve sorunsuz şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "doğrulanmış telegram hesapları, premium telegram profilleri, telegram hesabı satın al";
    $meta_audience = "dijital pazarlamacılar, sosyal medya yöneticileri, telegram hesabını hızlıca kullanmak isteyenler";
    $meta_category = "telegram hesap satışı, premium doğrulanmış hesaplar";
    $meta_reader_interest = "doğrulanmış telegram hesapları, sosyal medya reklam yönetimi, sosyal medya pazarlaması ile büyümek isteyenler";
    $meta_summary = "doğrulanmış ve premium Telegram hesaplarını güvenli şekilde anında teslimat ve 30 gün garanti ile satın alabilirsiniz";
    $meta_topic_tags = "telegram hesapları, doğrulanmış hesaplar, premium hesap satın al";
    $meta_visual_content = "telegram ikonları";
} elseif ($accountId == 12) {
    $meta_title = "Kimlik Doğrulanmış Süper Eski Facebook Hesabı Satın Al";
    $meta_description = "2007-2015 yılları arasında açılmış kimlik doğrulamalı süper eski Facebook hesabını güvenli kullanım ve hızlı teslimat avantajıyla satın alın.";
    $meta_keywords = "kimlik doğrulanmış facebook hesabı satın al, süper eski facebook hesabı, premium facebook hesap satın al, doğrulanmış facebook hesap satışı";
    $meta_ai_intent = "kimlik doğrulanmış süper eski facebook hesabı satın almak isteyen kullanıcılara yönelik uygun fiyatlı seçenekler";
    $meta_ai_subtopics = "kimlik doğrulanmış facebook hesapları, süper eski facebook hesapları, premium facebook hesapları, uzun süreli açılmış hesaplar";
    $meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar, reklam yöneticileri, güvenli facebook hesabı arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium hesaplar, facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici";
    $meta_reader_interest = "eski tarihli facebook hesapları, güçlü ve stabil hesaplar ile içerik paylaşımı ve kampanya yönetimi";
    $meta_summary = "2007-2015 arası açılmış kimlik doğrulanmış süper eski facebook hesap satışı";
    $meta_topic_tags = "süper eski facebook hesap, kimlik doğrulama, premium hesap, eski tarihli hesap satın al";
    $meta_visual_content = "facebook hesap görselleri, premium hesap ikonları";
} elseif ($accountId == 13) {
    $meta_title = "Kimlik Doğrulanmış Eski Facebook Hesabı Satın Al";
    $meta_description = "2010-2023 arasında açılmış, kimlik kartlı ve 2FA aktif eski Facebook hesabını yüksek arkadaş sayısı, güvenli yapı ve hızlı teslimatla satın alın.";
    $meta_keywords = "kimlik doğrulanmış facebook hesabı satışı, eski facebook hesap satın al, 2fa aktif facebook hesap, arkadaşlı facebook hesabı satın al";
    $meta_ai_intent = "kimlik doğrulanmış eski facebook hesabı satın almak isteyenlere uygun fiyatlı çözümler";
    $meta_ai_subtopics = "eski facebook hesapları, kimlik kartlı facebook hesapları, 2FA aktif hesaplar, yüksek arkadaşlı hesaplar";
    $meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar, reklam yöneticileri, güvenli hesap arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "uzun süreli facebook hesapları arayanlar, dijital pazarlama ve sosyal medya yönetimi";
    $meta_summary = "2010-2023 arası açılmış kimlik kartlı ve doğrulanmış eski facebook hesap satışı";
    $meta_topic_tags = "eski facebook hesapları satışı, premium hesap yönetimi";
    $meta_visual_content = "facebook hesap ikonları";
} elseif ($accountId == 14) {
    $meta_title = "Kimlik Doğrulanmış Süper Eski Facebook Hesabı Satın Al";
    $meta_description = "2010-2020 arası açılmış kimlik kartlı süper eski Facebook hesabını 2FA aktif yapı ve hızlı teslimat avantajıyla hemen satın alın.";
    $meta_keywords = "kimlik doğrulanmış facebook hesapları, süper eski facebook hesap satın al, 2fa aktif hesap yönetimi";
    $meta_ai_intent = "kimlik doğrulanmış süper eski facebook hesabı satın almak isteyen kullanıcılara uygun ürün satışı";
    $meta_ai_subtopics = "süper eski facebook hesapları, kimlik kartlı hesaplar, 2fa aktif facebook hesapları";
    $meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar, reklam yöneticileri, güvenli ve stabil hesap arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "uzun süreli ve doğrulanmış facebook hesapları satın almak isteyenler, dijital pazarlama ve sosyal medya yönetimi";
    $meta_summary = "2010-2020 arası açılmış kimlik kartlı süper eski facebook hesap satışı";
    $meta_topic_tags = "süper eski facebook hesap, kimlik doğrulama, premium hesap satışı";
    $meta_visual_content = "facebook hesap ikonları";
} elseif ($accountId == 15) {
    $meta_title = "Kimlik Doğrulanmış Eski Facebook Hesabı Satın Al";
    $meta_description = "2010-2023 arası açılmış kimlik kartlı eski Facebook hesaplarını uygun fiyat avantajı ile hızlı ve güvenli teslimatla hemen satın alın.";
    $meta_keywords = "kimlik doğrulanmış facebook hesabı satın al, eski facebook hesap satın al, aktif facebook hesabı uygun fiyatlı";
    $meta_ai_intent = "kimlik doğrulanmış eski facebook hesabı satın almak isteyen kullanıcılara yönelik çözümler";
    $meta_ai_subtopics = "eski facebook hesapları satın al, kimlik kartlı facebook hesaplar, 2fa aktif hesaplar, uid 1000xx hesaplar";
    $meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar, reklam ve sosyal medya yöneticileri";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "uzun süreli facebook hesabı arayanlar, sosyal medya ve dijital pazarlamaya ilgi duyanlar";
    $meta_summary = "2010-2023 arası açılmış kimlik kartlı eski Facebook hesap satışı";
    $meta_topic_tags = "eski facebook hesap satışı, premium hesap satın al";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 16) {
    $meta_title = "Kimlik Doğrulanmış Eski Facebook Hesabı Satın Al";
    $meta_description = "2024 yılında oluşturulmuş kimlik kartlı eski Facebook hesabını uygun fiyat avantajı ve hızlı teslimat güvencesiyle hemen satın alın.";
    $meta_keywords = "kimlik doğrulanmış facebook hesapları, eski facebook hesap satın al, aktif facebook hesap satışı, uid 100xx";
    $meta_ai_intent = "kimlik doğrulanmış eski facebook hesabı satın almak isteyenlere güncel ve aktif hesap seçenekleri";
    $meta_ai_subtopics = "kimlik kartlı facebook hesaplar, 2fa aktif hesap yönetimi, uid 100xx facebook hesapları, eski tarihli sosyal medya hesapları";
    $meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar, reklam yöneticileri, güvenli hesap arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "güncel tarihli facebook hesapları arayanlar, sosyal medya ve dijital pazarlama yönetimi";
    $meta_summary = "2024 tarihli doğrulanmış eski facebook hesap satışı";
    $meta_topic_tags = "eski facebook hesap, 2fa uid 100xx premium hesap satın al";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 17) {
    $meta_title = "Vietnam Kimliği Doğrulanmış Eski Facebook Hesabı Satın Al";
    $meta_description = "2023-2024 döneminde açılmış Vietnam kimlik kartlı Facebook hesabını uygun fiyat avantajı, UID 6155x ve 2FA aktif yapı ile hemen satın alın.";
    $meta_keywords = "vietnam facebook hesabı, kimlik doğrulanmış facebook hesabı, eski facebook hesap satın al, 2fa aktif hesap";
    $meta_ai_intent = "vietnam kimliği doğrulanmış eski facebook hesabı satın almak isteyenler için aktif ve güvenli hesap seçenekleri";
    $meta_ai_subtopics = "vietnam facebook hesapları, kimlik kartlı facebook hesaplar, 2fa aktif hesaplar";
    $meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar, reklam yöneticileri, bölgesel hesap arayanlar";
    $meta_category = "bölgesel sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "güncel tarihli facebook hesapları, bölgesel hesaplar, stabil ve garantili sosyal medya hesapları";
    $meta_summary = "2023-2024 arası açılmış vietnam kimlik kartlı doğrulanmış facebook hesap satışı";
    $meta_topic_tags = "vietnam facebook hesap, kimlik doğrulama, premium hesap satışı";
    $meta_visual_content = "facebook hesap ikonları";
} elseif ($accountId == 18) {
    $meta_title = "Yeniden Açılan Eski Facebook Hesabı Satın Al | Vietnam 2024";
    $meta_description = "2024 açılış tarihli, Vietnam'da yeniden açılmış eski Facebook hesabı satın alın. Kimlik onaylı, anında teslimat, üstelik 30 gün garanti.";
    $meta_keywords = "vietnam facebook hesabı, yeniden açılan facebook hesabı, kimlik onaylı hesap";
    $meta_ai_intent = "vietnam yeniden açılan eski facebook hesabı satın almak isteyen kullanıcılar için güvenli ve kimlik onaylı hesap seçenekleri";
    $meta_ai_subtopics = "vietnam facebook hesapları, yeniden açılan hesaplar, kimlik onaylı facebook hesaplar";
    $meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar, reklam yöneticileri, bölgesel facebook hesap arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "2024 aged facebook hesapları, yeniden açılan hesaplar, bölgesel uyumlu sosyal medya hesapları";
    $meta_summary = "2024 tarihli vietnam yeniden açılan kimlik onaylı eski facebook hesap satışı";
    $meta_topic_tags = "vietnam facebook hesabı, yeniden açılan hesap, kimlik onaylı uid 6156xx premium hesap";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 19) {
    $meta_title = "Vietnamese Reinstated Aged Facebook Account (2010-2023)";
    $meta_description = "2010-2023 açılış tarihli, Vietnam bölgesinde yeniden açılmış eski Facebook hesabı. Kimlik onaylı, UID 100xx ve 2FA destekli, 30 gün garantili.";
    $meta_keywords = "vietnamese facebook account, reinstated aged facebook, kimlik onaylı facebook hesap uygun fiyat";
    $meta_ai_intent = "vietnamese reinstated aged facebook account satın almak isteyen kullanıcılara kimlik onaylı ve güvenli hesap sağlamak";
    $meta_ai_subtopics = "vietnam facebook hesapları, yeniden açılan facebook hesapları, kimlik onaylı hesaplar, aged hesap satışı";
    $meta_audience = "dijital pazarlamacılar, reklam yöneticileri, sosyal medya yöneticileri, bölgesel facebook hesap arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "2010-2023 aged hesaplarla ilgilenenler, yeniden açılan facebook hesap alıcıları, vietnam bölge uyumlu facebook hesap arayanlar";
    $meta_summary = "vietnam bölgesinde yeniden açılan kimlik onaylı 2010-2023 tarihli eski facebook hesap satışı";
    $meta_topic_tags = "vietnam facebook, reinstated account, aged facebook, kimlik onayı";
    $meta_visual_content = "facebook hesap ikonları ve simgeler";
} elseif ($accountId == 20) {
    $meta_title = "Reinstated Aged Facebook Account (2010-2023)";
    $meta_description = "2010-2023 açılış tarihli, yeniden açılmış eski Facebook hesabı hemen satın al. Kimlik onaylı, UID 1000xx ve 2FA destekli, 30 gün garantili.";
    $meta_keywords = "reinstated facebook account, aged facebook account, kimlik onaylı facebook, 2fa facebook hesabı satın al";
    $meta_ai_intent = "reinstated aged facebook account satın almak isteyen kullanıcılar için kimlik onaylı ve güvenli hesap seçenekleri";
    $meta_ai_subtopics = "yeniden açılan facebook hesapları, aged facebook hesapları, kimlik onaylı fb";
    $meta_audience = "dijital pazarlamacılar, reklam yöneticileri, sosyal medya uzmanları, eski facebook hesap arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "2010-2023 aged hesaplar, yeniden açılan facebook hesapları";
    $meta_summary = "kimlik onaylı 2010-2023 tarihli yeniden açılmış eski facebook hesap satışı";
    $meta_topic_tags = "reinstated facebook hesap satış, aged account hemen satın al";
    $meta_visual_content = "facebook hesap ikonları";
} elseif ($accountId == 21) {
    $meta_title = "Reinstated Supper Aged Facebook Account (2007-2015)";
    $meta_description = "2007-2015 açılış tarihli, yeniden açılmış süper eski Facebook hesap satın al. Kimlik onaylı, 2FA destekli, hızlı teslim, 30 gün garantili.";
    $meta_keywords = "reinstated aged facebook, super aged facebook account, kimlik onaylı 2fa facebook hesap satın al";
    $meta_ai_intent = "reinstated supper aged facebook account satın almak isteyenler için uygun seçenekler";
    $meta_ai_subtopics = "reinstated facebook, super aged fb hesaplar, kimlik onaylı facebook 2fa hesaplar";
    $meta_audience = "dijital pazarlamacılar, reklam yöneticileri, sosyal medya uzmanları, eski facebook hesap arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "2007-2015 super aged hesaplar, kimlik doğrulama garantili fb hesap satın almak isteyenler";
    $meta_summary = "2007-2015 tarihli kimlik onaylı yeniden açılmış super eski facebook hesap satışı";
    $meta_topic_tags = "reinstated fb, super aged account, kimlik onaylı 2fa hesap satın al";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 26) {
    $meta_title = "Poland Old Facebook Account (2008-2021)";
    $meta_description = "2008-2021 açılış tarihli Polonya eski Facebook hesabı. 0-1000 arkadaşlı, 2FA ve Live Ads destekli. Anında teslimat ve 30 gün garanti.";
    $meta_keywords = "poland facebook account, old facebook hesaplar, aged fb account yönetimi, live ads fb";
    $meta_ai_intent = "poland old facebook account satın almak isteyen kullanıcılara güvenli ve reklam uyumlu hesap sağlamak";
    $meta_ai_subtopics = "poland facebook hesapları, aged facebook hesaplar, live ads uyumlu fb hesaplar";
    $meta_audience = "dijital pazarlamacılar, reklam yöneticileri, facebook ads kullananlar, bölgesel hesap arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "2008-2021 aged hesaplar, poland bölge facebook hesaplar, live ads destekli hesap satın al";
    $meta_summary = "poland bölgesine ait 2008-2021 tarihli eski facebook hesap satışı";
    $meta_topic_tags = "poland facebook, old account, aged fb satış, 0-1000 friends facebook";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 27) {
    $meta_title = "Taiwan Old Facebook Account";
    $meta_description = "Tayvan bölgesine ait eski Facebook hesabı. Live Ads uyumlu, 30 gün garantili bu hesabı hızlı teslim imkanı ile hemen satın alın.";
    $meta_keywords = "taiwan facebook account, old fb account, live ads facebook satın al";
    $meta_ai_intent = "taiwan old facebook account satın almak isteyen kullanıcılara live ads uyumlu hesap sağlamak";
    $meta_ai_subtopics = "taiwan facebook hesapları, old facebook hesaplar, live ads fb";
    $meta_audience = "dijital pazarlamacılar, reklam yöneticileri, facebook reklam kullananlar, bölgesel fb hesap arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "taiwan bölge facebook, live ads uyumlu hesaplar, eski facebook hesapları";
    $meta_summary = "taiwan bölgesine ait random friends yapılı live ads destekli eski facebook hesap satışı";
    $meta_topic_tags = "taiwan facebook, old account, live ads, random friends";
    $meta_visual_content = "facebook hesap ikonları";
} elseif ($accountId == 28) {
    $meta_title = "UK Old Facebook Account";
    $meta_description = "Birleşik Krallık bölgesine ait eski Facebook hesabı. Live Ads uyumlu, No2FA yapı. Hızlı teslimat ve 30 gün garanti ile güvenli satış.";
    $meta_keywords = "uk facebook account, old fb account, live ads facebook, no2fa facebook, random friends";
    $meta_ai_intent = "uk old facebook account satın almak isteyen kullanıcılara live ads uyumlu ve güvenli hesap sağlamak";
    $meta_ai_subtopics = "uk facebook hesapları, old facebook hesaplar, live ads facebook, no2fa hesaplar, random friends";
    $meta_audience = "dijital pazarlamacılar, reklam yöneticileri, sosyal medya uzmanları, uk facebook hesabı arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "uk bölge uyumlu facebook, live ads destekli hesaplar, eski facebook hesapları";
    $meta_summary = "birleşik krallık bölgesine ait random friends yapılı live ads uyumlu eski facebook hesap satışı";
    $meta_topic_tags = "uk facebook, old account, live ads, no2fa, random friends";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 29) {
    $meta_title = "USA Old Facebook Account (2024)";
    $meta_description = "2024 tarihli ABD lokasyonlu eski Facebook hesabı. 30+ arkadaşlı, Live Ads aktif. Anında teslimat ve 30 gün garanti ile güvenli satış.";
    $meta_keywords = "usa facebook account, old fb account 2024, live ads facebook, abd facebook hesabı";
    $meta_ai_intent = "usa old facebook account satın almak isteyen kullanıcılara live ads uyumlu ve abd lokasyonlu hesap sağlamak";
    $meta_ai_subtopics = "usa facebook hesapları, old facebook hesaplar, live ads facebook, abd lokasyonlu hesaplar";
    $meta_audience = "dijital pazarlamacılar, reklam yöneticileri, sosyal medya uzmanları, abd facebook hesabı arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "abd lokasyonlu facebook hesapları, live ads aktif hesaplar, 30+ arkadaşlı eski hesaplar";
    $meta_summary = "abd lokasyonlu 2024 tarihli live ads destekli eski facebook hesap satışı";
    $meta_topic_tags = "usa facebook, old account, live ads, abd lokasyon, 30+ friends";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 30) {
    $meta_title = "Country Facebook Account | Sub Profile";
    $meta_description = "Ülke lokasyonlu Facebook hesabı. Sub profil oluşturma destekli, doğrulanmış ve premium yapıdadır. Hızlı teslimat, güvenli satış, 30 gün garantili.";
    $meta_keywords = "country facebook account, sub profil facebook, random country fb, premium facebook hesabı";
    $meta_ai_intent = "country facebook account satın almak isteyen kullanıcılara sub profil oluşturulabilen doğrulanmış hesap sunmak";
    $meta_ai_subtopics = "country facebook hesapları, sub profil destekli facebook, random ülke fb hesapları, premium facebook hesaplar";
    $meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar, çoklu profil kullananlar, facebook hesap arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "sub profil açılabilen facebook hesapları, çoklu kullanım";
    $meta_summary = "rastgele ülke lokasyonlu sub profil destekli doğrulanmış facebook hesap satışı";
    $meta_topic_tags = "country fb, sub profile, random country, premium account";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 31) {
    $meta_title = "India Aged Facebook Account | 2FA Açık Eski Hesap";
    $meta_description = "Hindistan lokasyonlu eski Facebook hesabı. Rastgele arkadaşlı, 2FA açık, doğrulanmış ve premium yapıdadır. Güvenli satış, 30 gün garantili.";
    $meta_keywords = "india facebook account, aged facebook hesabı, 2fa açık facebook, random friends fb";
    $meta_ai_intent = "india aged facebook account satın almak isteyen kullanıcılara 2fa aktif ve güvenli hesap sağlamak";
    $meta_ai_subtopics = "india facebook hesapları, aged facebook account, 2fa aktif facebook, random friends fb";
    $meta_audience = "dijital pazarlamacılar, sosyal medya yöneticileri, bölgesel facebook hesap arayanlar";
    $meta_category = "sosyal medya hesap satışı, premium facebook hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "hindistan lokasyonlu eski facebook hesapları, 2fa güvenliği, garantili teslimat";
    $meta_summary = "hindistan lokasyonlu 2fa açık doğrulanmış eski facebook hesap satışı";
    $meta_topic_tags = "india fb, aged account, 2fa on, random friends";
    $meta_visual_content = "facebook ikonları";
} elseif ($accountId == 35) {
    $meta_title = "Doğrulanmış Business Manager Hesapları | 5'li Paket Satın Al";
    $meta_description = "5 adet doğrulanmış Business Manager hesabından oluşan paket. Premium altyapı, güvenli kullanım, anında teslimat ve 30 gün garanti ile sunulur.";
    $meta_keywords = "business manager hesapları, doğrulanmış bm hesap, 5'li business manager reklam hesabı";
    $meta_ai_intent = "doğrulanmış business manager hesapları satın almak isteyen kullanıcılara çoklu ve güvenli çözüm sunmak";
    $meta_ai_subtopics = "business manager hesapları, doğrulanmış bm hesapları, 5'li business manager paketleri";
    $meta_audience = "dijital pazarlama ajansları, reklam yöneticileri, facebook reklam verenler";
    $meta_category = "reklam hesapları, business manager satışı";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "çoklu business manager kullanımı, garantili bm hesapları, güvenli reklam altyapısı";
    $meta_summary = "5 adet doğrulanmış business manager hesabından oluşan premium paket satışı";
    $meta_topic_tags = "business manager, doğrulanmış hesaplar, 5'li paket, reklam yönetimi";
    $meta_visual_content = "business manager ikonları";
} elseif ($accountId == 36) {
    $meta_title = "Kısıttan Dönmüş Doğrulanmış Business Manager Satın Al";
    $meta_description = "Kısıtı kaldırılmış, yeniden aktif edilmiş, doğrulanmış Business Manager hesabı. Premium kullanım, güvenli altyapı, hızlı teslim ile sunulur.";
    $meta_keywords = "kısıttan dönmüş business manager, aktif bm hesabı, doğrulanmış business manager, reklam hesabı satın al";
    $meta_ai_intent = "kısıttan dönmüş ve tekrar aktif edilmiş Business Manager satın almak isteyen kullanıcılara güvenli çözüm sağlamak";
    $meta_ai_subtopics = "kısıttan dönmüş business manager, yeniden açılan bm hesapları, doğrulanmış reklam yönetimi";
    $meta_audience = "reklam yöneticileri, dijital pazarlama uzmanları, dijital ajanslar, aktif Business Manager arayanlar";
    $meta_category = "business manager hesapları, reklam yönetim araçları";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "yeniden aktif edilen business manager, garantili bm hesapları, sorunsuz reklam yönetimi";
    $meta_summary = "kısıtlaması kaldırılmış ve aktif hale getirilmiş doğrulanmış Business Manager hesabı satışı";
    $meta_topic_tags = "business manager, kısıttan dönüş, doğrulanmış bm, reklam altyapısı";
    $meta_visual_content = "business manager ikonları";
} elseif ($accountId == 37) {
    $meta_title = "Doğrulanmış Business Manager Hesapları | 3'lü Paket Satın Al";
    $meta_description = "3'lü paket halinde sunulan doğrulanmış Business Manager hesapları. Premium kullanım, güvenli altyapı, hızlı teslimat, 30 gün garantili.";
    $meta_keywords = "doğrulanmış business manager, 3'lü bm paketi, business manager satın al";
    $meta_ai_intent = "doğrulanmış business manager hesaplarını toplu şekilde satın almak isteyen kullanıcılara güvenli çözüm sağlamak";
    $meta_ai_subtopics = "business manager hesapları, doğrulanmış bm paketleri, toplu business manager satışı";
    $meta_audience = "dijital pazarlama uzmanları, reklam yöneticileri, dijital ajanslar, birden fazla bm ihtiyacı olanlar";
    $meta_category = "business manager hesapları, dijital reklam çözümleri";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "toplu business manager alımı, garantili bm hesapları";
    $meta_summary = "3'lü paket halinde doğrulanmış Business Manager hesaplarının güvenli ve hızlı satışı";
    $meta_topic_tags = "business manager, doğrulanmış bm, 3'lü paket, sosyal medya reklam altyapısı";
    $meta_visual_content = "business manager ikonları";
} elseif ($accountId == 38) {
    $meta_title = "Eski Business Manager Hesap Satın Al | Doğrulanmış Hesaplar";
    $meta_description = "Eski ve doğrulanmış Business Manager hesapları hemen satın alın. Premium kullanım, güvenli yapı, anında teslimat, 30 gün garantili.";
    $meta_keywords = "eski business manager hesap satın al, doğrulanmış business manager uygun fiyatlı";
    $meta_ai_intent = "eski business manager hesaplarını güvenli ve hızlı şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "eski business manager hesapları, doğrulanmış bm, premium business manager";
    $meta_audience = "dijital pazarlama uzmanları, reklam yöneticileri, sosyal medya ajansları, bm ihtiyacı olan kullanıcılar";
    $meta_category = "business manager hesap satışı, dijital reklam çözümleri";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "eski business manager hesapları, garantili bm arayanlar";
    $meta_summary = "eski ve doğrulanmış Business Manager hesaplarının güvenli satış hizmeti";
    $meta_topic_tags = "business manager, eski bm, doğrulanmış hesap, premium bm hesap satışı";
    $meta_visual_content = "business manager ikonları";
} elseif ($accountId == 39) {
    $meta_title = "Business Manager Hesapları Satın Al | Doğrulanmış ve Hazır";
    $meta_description = "Doğrulanmış Business Manager hesapları hemen satın alın. Premium kullanım, güvenli yapı, hızlı teslimat, 30 gün garanti satış imkanı.";
    $meta_keywords = "business manager hesapları, doğrulanmış business manager satın al, bm hesap garantili satış";
    $meta_ai_intent = "business manager hesaplarını hızlı ve güvenli şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "business manager hesapları, doğrulanmış bm, hazır business manager";
    $meta_audience = "reklam verenler, dijital pazarlamacılar, sosyal medya ajansları, business manager ihtiyacı olan kullanıcılar";
    $meta_category = "business manager hesap satışı, dijital reklam hizmetleri";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "hazır business manager, garantili bm hesapları";
    $meta_summary = "doğrulanmış ve kullanıma hazır Business Manager hesaplarının güvenli satışı";
    $meta_topic_tags = "business manager, bm hesapları, doğrulanmış bm, premium hesap satışı";
    $meta_visual_content = "business manager ikonları";
} elseif ($accountId == 40) {
    $meta_title = "250$ Günlük Limitli Kişisel Reklam Hesabı Satın Al";
    $meta_description = "Günlük 250 dolar harcama limitine sahip, doğrulanmış tekli kişisel reklam hesabı. Premium yapı, hızlı teslimat, 30 gün garantili.";
    $meta_keywords = "250 dolar limitli reklam hesabı, kişisel reklam hesabı satın al, günlük harcama limitli hesap";
    $meta_ai_intent = "günlük limitli kişisel reklam hesabını güvenli şekilde satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "kişisel reklam hesabı, günlük limitli reklam hesabı satın al, 250 dolar limitli tekli reklam hesabı satışı";
    $meta_audience = "reklam verenler, dijital pazarlamacılar, bireysel reklam kullanıcıları";
    $meta_category = "reklam hesap satışı, facebook reklam hizmetleri";
    $meta_content_tone = "profesyonel, bilgilendirici, satış odaklı";
    $meta_reader_interest = "hazır reklam hesabı, günlük harcama limitli reklam hesabı";
    $meta_summary = "günlük 250 dolar harcama limitine sahip doğrulanmış kişisel reklam hesabı satışı";
    $meta_topic_tags = "kişisel reklam hesabı, 250 dolar limitli reklam hesabı satışı";
    $meta_visual_content = "reklam hesabı ikonları";
}

// Yapısal veri (Structured Data) oluştur
$structuredDataHelper = new StructuredDataHelper();
$structuredData = $structuredDataHelper->getProductStructuredData($account);

include 'header.php';
?>

<!-- Back Button -->
<div class="back-button-fixed">
    <a href="hesaplar.php" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i>
        Geri Dön
    </a>
</div>

<!-- Modern Account Header -->
<section class="account-header">
    <div class="container">
        <div class="header-card">
            <div class="account-icon">
                <i class="<?php echo getPlatformIcon($account['platform']); ?>"></i>
            </div>
            <div class="account-info">
                <h1><?php echo htmlspecialchars($account['title']); ?></h1>
                <p class="account-type"><?php echo htmlspecialchars($account['account_type']); ?></p>
                <div class="account-badges">
                    <?php if ($account['is_verified']): ?>
                        <span class="badge verified">Doğrulanmış</span>
                    <?php endif; ?>
                    <?php
                    $stockStatus = getStockStatus($account['stock_quantity']);
                    $stockClass = '';
                    if ($stockStatus['status'] === 'low') $stockClass = 'urgent';
                    if ($stockStatus['status'] === 'out') $stockClass = 'out';
                    ?>
                    <span class="badge stock <?php echo $stockClass; ?>"><?php echo $stockStatus['text']; ?></span>
                </div>
            </div>
            <div class="price-section">
                <span class="price"><?php echo formatPrice($account['price']); ?></span>
                <?php if ($account['old_price']): ?>
                    <span class="old-price"><?php echo formatPrice($account['old_price']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Compact Content Grid -->
<section class="content-section">
    <div class="container">
        <div class="content-grid">
            
            <!-- Info Cards -->
            <div class="info-cards">
                <div class="info-card">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i>
                        <h3>Açıklama</h3>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($account['description'])); ?></p>
                </div>

                <div class="info-card">
                    <div class="card-header">
                        <i class="fas fa-star"></i>
                        <h3>Özellikler</h3>
                    </div>
                    <div class="features-grid">
                        <?php if ($account['is_verified']): ?>
                            <span class="feature verified"><i class="fas fa-check-circle"></i> Doğrulanmış</span>
                        <?php endif; ?>
                        <?php if ($account['is_premium']): ?>
                            <span class="feature premium"><i class="fas fa-crown"></i> Premium</span>
                        <?php endif; ?>
                        <?php if ($account['is_secure']): ?>
                            <span class="feature secure"><i class="fas fa-shield-alt"></i> Güvenli</span>
                        <?php endif; ?>
                        <?php if ($account['instant_delivery']): ?>
                            <span class="feature instant"><i class="fas fa-bolt"></i> Anında Teslimat</span>
                        <?php endif; ?>
                        <?php if ($account['support_24_7']): ?>
                            <span class="feature support"><i class="fas fa-headset"></i> 7/24 Destek</span>
                        <?php endif; ?>
                        <?php if ($account['guarantee_30_days']): ?>
                            <span class="feature guarantee"><i class="fas fa-medal"></i> 30 Gün Garanti</span>
                        <?php endif; ?>
                        <?php if ($account['warranty_days'] && $account['warranty_days'] > 0): ?>
                            <span class="feature warranty"><i class="fas fa-calendar-check"></i> <?php echo $account['warranty_days']; ?> Gün Garanti</span>
                        <?php endif; ?>
                        <?php if (!empty($account['features'])): ?>
                            <?php 
                            $additionalFeatures = explode("\n", $account['features']);
                            foreach ($additionalFeatures as $feature): 
                                $feature = trim($feature);
                                if (!empty($feature)):
                            ?>
                                <span class="feature additional"><i class="fas fa-plus"></i> <?php echo htmlspecialchars($feature); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        <?php endif; ?>
                        <?php foreach ($features as $feature): ?>
                            <span class="feature custom"><i class="fas fa-star"></i> <?php echo htmlspecialchars($feature['feature_name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-header">
                        <i class="fas fa-cog"></i>
                        <h3>Teknik Bilgiler</h3>
                    </div>
                    <div class="specs-compact">
                        <?php if ($account['rating'] && $account['rating'] > 0): ?>
                            <div class="spec"><span>Değerlendirme:</span> <?php echo $account['rating']; ?>/5 ⭐</div>
                        <?php endif; ?>
                        <?php if ($account['views'] && $account['views'] > 0): ?>
                            <div class="spec"><span>Görüntülenme:</span> <?php echo number_format($account['views']); ?></div>
                        <?php endif; ?>
                        <?php if ($account['sales_count'] && $account['sales_count'] > 0): ?>
                            <div class="spec"><span>Satış:</span> <?php echo number_format($account['sales_count']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($account['location'])): ?>
                            <div class="spec"><span>Konum:</span> <?php echo htmlspecialchars($account['location']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($account['technical_info'])): ?>
                        <div class="technical-details">
                            <h4><i class="fas fa-info-circle"></i> Detaylı Teknik Bilgiler</h4>
                            <div class="technical-content">
                                <?php echo nl2br(htmlspecialchars($account['technical_info'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Purchase Panel -->
            <div class="purchase-panel">
                <div class="panel-header">
                    <div class="stock-info">
                        <i class="fas fa-box"></i>
                        <span><?php echo $account['stock_quantity']; ?> stokta</span>
                    </div>
                </div>
                
                <div class="quantity-selector">
                    <label>Adet</label>
                    <div class="qty-controls">
                        <button class="qty-btn" onclick="changeQuantity(-1)">-</button>
                        <input type="number" id="quantity" value="1" min="1" max="<?php echo $account['stock_quantity']; ?>" onchange="updateTotal()">
                        <button class="qty-btn" onclick="changeQuantity(1)">+</button>
                    </div>
                </div>

                <div class="total-section">
                    <span class="total-label">Toplam:</span>
                    <span class="total-price" id="totalPrice"><?php echo formatPrice($account['price']); ?></span>
                </div>

                <?php if ($hasStock): ?>
                    <button class="btn btn-primary buy-button" onclick="addToCart()">
                        <i class="fas fa-shopping-cart"></i>
                        Sepete Ekle
                    </button>
                    
                    <button class="btn btn-outline buy-button" onclick="buyNow()" style="margin-top: 0.5rem;">
                        <i class="fas fa-bolt"></i>
                        Hemen Satın Al
                    </button>
                <?php else: ?>
                    <button class="btn btn-secondary buy-button" disabled>
                        <i class="fas fa-ban"></i>
                        Stokta Yok
                    </button>
                    
                    <div class="stock-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Bu hesap şu anda stokta bulunmuyor. Lütfen daha sonra tekrar kontrol edin.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<script>
const basePrice = <?php echo $account['price']; ?>;
const maxStock = <?php echo $account['stock_quantity']; ?>;

function changeQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let newValue = parseInt(quantityInput.value) + change;
    
    if (newValue < 1) newValue = 1;
    if (newValue > maxStock) newValue = maxStock;
    
    quantityInput.value = newValue;
    updateTotal();
}

function updateTotal() {
    const quantity = parseInt(document.getElementById('quantity').value);
    const total = basePrice * quantity;
    
    document.getElementById('totalPrice').textContent = total.toLocaleString('tr-TR') + '<?= getCurrencySymbol() ?>';
}

function addToCart() {
    const quantity = document.getElementById('quantity').value;
    const accountId = <?php echo $account['id']; ?>;
    
    // AJAX ile sepete ekle
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            account_id: accountId,
            quantity: parseInt(quantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Sunucudan gelen mesajı kullan
            showToast(data.message || 'Ürün sepete eklendi!');
            
            // Eğer miktar ayarlandıysa, input'u güncelle
            if (data.final_quantity && data.final_quantity != quantity) {
                document.getElementById('quantity').value = data.final_quantity;
                updateTotal();
            }
            
            // Header'daki sepet sayacını güncelle
            if (data.cart_count) {
                const cartCountElement = document.getElementById('cartCount');
                if (cartCountElement) {
                    cartCountElement.textContent = data.cart_count;
                    if (data.cart_count > 0) {
                        cartCountElement.style.display = 'flex';
                    }
                }
            }
        } else {
            showToast(data.error || 'Bir hata oluştu', 'error');
        }
    })
    .catch(error => {
        showToast('Bağlantı hatası oluştu', 'error');
    });
}

function buyNow() {
    const quantity = document.getElementById('quantity').value;
    const accountId = <?php echo $account['id']; ?>;
    
    // Önce sepete ekle, sonra checkout'a yönlendir
    addToCart();
    
    setTimeout(() => {
        window.location.href = 'checkout.php';
    }, 1000);
}


function showToast(message, type = 'success') {
    // Mevcut toast'ları kaldır
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    toast.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}
</script>

<style>
/* Toast Notifications */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--card-bg);
    color: var(--text-primary);
    padding: 1rem 1.5rem;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transform: translateX(400px);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 9999;
    max-width: 300px;
}

.toast.show {
    transform: translateX(0);
    opacity: 1;
}

.toast.toast-success {
    border-left: 4px solid #28a745;
}

.toast.toast-error {
    border-left: 4px solid #dc3545;
}

.toast i {
    font-size: 1.2rem;
}

.toast.toast-success i {
    color: #28a745;
}

.toast.toast-error i {
    color: #dc3545;
}

/* Fixed Back Button */
.back-button-fixed {
    position: fixed;
    top: 100px;
    left: 20px;
    z-index: 1000;
    animation: slideInLeft 0.5s ease-out;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@media (max-width: 768px) {
    .back-button-fixed {
        top: 80px;
        left: 15px;
    }
    
    .back-button-fixed .btn {
        padding: 8px 20px;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .back-button-fixed {
        top: 70px;
        left: 10px;
    }
    
    .back-button-fixed .btn {
        padding: 6px 16px;
        font-size: 12px;
        gap: 6px;
    }
}

/* Modern Account Header */
.account-header {
    padding: 40px 0;
    background: linear-gradient(135deg, 
        rgba(108, 99, 255, 0.05) 0%, 
        rgba(66, 226, 184, 0.03) 100%);
}

.header-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: var(--inner-shadow);
    backdrop-filter: blur(10px);
    max-width: 800px;
    margin: 0 auto;
}

.account-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    flex-shrink: 0;
}

.account-info {
    flex: 1;
}

.account-info h1 {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--light);
    margin-bottom: 4px;
    font-family: 'Montserrat', sans-serif;
}

.account-type {
    color: var(--gray);
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.account-badges {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.price-section {
    text-align: right;
    flex-shrink: 0;
}

.price {
    font-size: 1.6rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    display: block;
    font-family: 'Montserrat', sans-serif;
}

.old-price {
    font-size: 0.9rem;
    color: var(--gray);
    text-decoration: line-through;
    opacity: 0.7;
}

/* Content Section */
.content-section {
    padding: 30px 0;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 30px;
    max-width: 1000px;
    margin: 0 auto;
}

/* Info Cards */
.info-cards {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    padding: 16px;
    box-shadow: var(--inner-shadow);
    backdrop-filter: blur(10px);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.card-header i {
    color: var(--accent);
    font-size: 1rem;
}

.card-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--light);
    margin: 0;
}

.info-card p {
    color: var(--gray);
    font-size: 0.9rem;
    line-height: 1.5;
    margin: 0;
}

.features-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.feature {
    background: linear-gradient(135deg, 
        rgba(108, 99, 255, 0.1) 0%, 
        rgba(66, 226, 184, 0.1) 100%);
    border: 1px solid rgba(108, 99, 255, 0.2);
    border-radius: 20px;
    padding: 4px 10px;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--primary);
    white-space: nowrap;
}

.specs-compact {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.spec {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
    color: var(--gray);
}

.spec span {
    font-weight: 600;
    color: var(--light);
    min-width: 80px;
}

/* Purchase Panel */
.purchase-panel {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--inner-shadow);
    backdrop-filter: blur(10px);
    position: sticky;
    top: 120px;
    height: fit-content;
}

.panel-header {
    margin-bottom: 16px;
}

.stock-info {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--accent);
    font-size: 0.9rem;
    font-weight: 500;
}

.stock-info i {
    font-size: 0.8rem;
}

.quantity-selector {
    margin-bottom: 16px;
}

.quantity-selector label {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--light);
    margin-bottom: 8px;
}

.qty-controls {
    display: flex;
    align-items: center;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    overflow: hidden;
    background: var(--card-bg);
}

.qty-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: var(--light);
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
    font-size: 0.9rem;
}

.qty-btn:hover {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    color: white;
}

.qty-controls input {
    width: 60px;
    height: 32px;
    border: none;
    text-align: center;
    background: transparent;
    color: var(--light);
    font-weight: 600;
    outline: none;
    font-size: 0.9rem;
    -moz-appearance: textfield; /* Firefox */
}

/* Chrome, Safari, Edge */
.qty-controls input::-webkit-outer-spin-button,
.qty-controls input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.total-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding: 16px 20px;
    background: linear-gradient(135deg, 
        rgba(108, 99, 255, 0.1) 0%, 
        rgba(66, 226, 184, 0.1) 100%);
    border-radius: 8px;
    border: 1px solid rgba(108, 99, 255, 0.2);
    min-height: 50px;
}

.total-label {
    font-weight: 600;
    color: var(--light);
    font-size: 1.1rem;
    font-family: 'Montserrat', sans-serif;
    line-height: 1.2;
    display: flex;
    align-items: center;
}

.total-price {
    font-size: 1.1rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    font-family: 'Montserrat', sans-serif;
    line-height: 1.2;
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.buy-button {
    width: 100%;
    padding: 12px 16px;
    font-size: 0.95rem;
    font-weight: 700;
}

/* Badge Styles */
.badge {
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    backdrop-filter: blur(10px);
}

.badge.verified {
    background: linear-gradient(135deg, rgba(66, 226, 184, 0.9), rgba(16, 185, 129, 0.9));
    color: white;
    border: 1px solid rgba(66, 226, 184, 0.5);
}

.badge.stock {
    background: linear-gradient(135deg, rgba(66, 226, 184, 0.2), rgba(16, 185, 129, 0.2));
    color: var(--accent);
    border: 1px solid rgba(66, 226, 184, 0.3);
}

.badge.stock.urgent {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(217, 119, 6, 0.2));
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.badge.stock.out {
    background: linear-gradient(135deg, rgba(255, 101, 132, 0.2), rgba(239, 68, 68, 0.2));
    color: var(--secondary);
    border: 1px solid rgba(255, 101, 132, 0.3);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .purchase-panel {
        position: static;
        order: -1;
    }
}

@media (max-width: 768px) {
    .account-header {
        padding: 20px 0;
    }
    
    .header-card {
        flex-direction: column;
        text-align: center;
        gap: 16px;
        padding: 16px;
    }
    
    .account-info h1 {
        font-size: 1.2rem;
    }
    
    .price {
        font-size: 1.4rem;
    }
    
    .content-section {
        padding: 20px 0;
    }
    
    .info-card {
        padding: 12px;
    }
    
    .purchase-panel {
        padding: 16px;
    }
}

@media (max-width: 480px) {
    .back-button-fixed {
        top: 60px;
        left: 8px;
    }
    
    .header-card {
        margin: 0 10px;
        padding: 12px;
    }
    
    .account-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .account-info h1 {
        font-size: 1.1rem;
    }
    
    .price {
        font-size: 1.2rem;
    }
    
    .content-section {
        padding: 15px 0;
    }
    
    .content-grid {
        margin: 0 10px;
    }
}

.features-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--gray);
    font-weight: 500;
}

.features-list li i {
    color: #10b981;
    font-size: 0.9rem;
}

.specs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.spec-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--card-border);
}

.spec-label {
    font-weight: 600;
    color: var(--gray);
}

.spec-value {
    font-weight: 700;
    color: var(--light);
}

/* Purchase Card */
.purchase-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 30px;
}

.price-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--card-border);
}

.price-label {
    font-weight: 600;
    color: var(--gray);
    font-size: 1.1rem;
}

.price-value {
    font-size: 2rem;
    font-weight: 800;
    color: var(--accent);
}

.stock-display {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--gray);
    margin-bottom: 25px;
    font-weight: 500;
}

.stock-display i {
    color: var(--primary);
}

.quantity-section {
    margin-bottom: 25px;
}

.quantity-section label {
    display: block;
    font-weight: 600;
    color: var(--light);
    margin-bottom: 10px;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    overflow: hidden;
}

.qty-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: var(--card-bg);
    color: var(--light);
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
    border-right: 1px solid var(--card-border);
}

.qty-btn:hover {
    background: var(--primary);
    color: white;
}

.qty-btn.plus {
    border-right: none;
    border-left: 1px solid var(--card-border);
}

#quantity {
    width: 80px;
    height: 40px;
    border: none;
    text-align: center;
    background: var(--card-bg);
    color: var(--light);
    font-weight: 700;
    outline: none;
}

.total-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: rgba(108, 99, 255, 0.1);
    border-radius: 8px;
    border: 1px solid rgba(108, 99, 255, 0.2);
}

.total-label {
    font-weight: 600;
    color: var(--light);
    font-size: 1.1rem;
}

.total-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--primary);
}

.purchase-buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 30px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 24px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    border: none;
    cursor: pointer;
    gap: 8px;
    font-size: 14px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(0);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
}

.btn:active {
    transform: translateY(1px);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
}

.btn-secondary {
    background: linear-gradient(135deg, var(--secondary) 0%, #ff416c 100%);
    color: white;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #ff416c 0%, var(--secondary) 100%);
}

.btn-outline {
    background: transparent;
    color: var(--gray);
    border: 1px solid var(--card-border);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-outline:hover {
    background: var(--card-border);
    color: var(--light);
}

.security-badges {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid var(--card-border);
}

.security-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--gray);
    font-size: 0.9rem;
}

.security-item i {
    color: #10b981;
    width: 16px;
}


/* Badges */
.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.badge.verified {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.badge.stock {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.badge.stock.urgent {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.badge.stock.out {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.stock-warning {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 8px;
    padding: 12px;
    margin-top: 12px;
    color: #ef4444;
    font-size: 14px;
    text-align: center;
    display: flex;
    align-items: center;
    gap: 8px;
}

.stock-warning i {
    font-size: 16px;
}

.btn-secondary {
    background: #6b7280 !important;
    color: white !important;
    border: none !important;
    cursor: not-allowed !important;
    opacity: 0.6 !important;
}

.btn-secondary:hover {
    background: #6b7280 !important;
    transform: none !important;
}

/* Toast */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--light);
    font-weight: 500;
    box-shadow: var(--shadow);
    backdrop-filter: blur(10px);
    z-index: 10000;
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.3s ease;
}

.toast.show {
    transform: translateX(0);
    opacity: 1;
}

.toast-success {
    border-left: 4px solid #10b981;
}

.toast-success i {
    color: #10b981;
}

/* Feature Styles */
.feature {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
}

.feature.verified {
    color: #10b981;
}

.feature.premium {
    color: #f59e0b;
}

.feature.secure {
    color: #3b82f6;
}

.feature.instant {
    color: #8b5cf6;
}

.feature.support {
    color: #06b6d4;
}

.feature.guarantee {
    color: #10b981;
}

.feature.warranty {
    color: #f59e0b;
}

.feature.additional {
    color: #6b7280;
}

.feature.custom {
    color: #ec4899;
}

/* Technical Details */
.technical-details {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--card-border);
}

.technical-details h4 {
    color: var(--text-primary);
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.technical-content {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid var(--card-border);
    border-radius: 8px;
    padding: 12px;
    font-size: 13px;
    line-height: 1.5;
    color: var(--text-secondary);
}

/* Responsive */
@media (max-width: 1024px) {
    .details-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .purchase-card {
        position: static;
    }
}

@media (max-width: 768px) {
    .hero-content {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .hero-avatar {
        width: 100px;
        height: 100px;
        font-size: 2.5rem;
    }
    
    .hero-info h1 {
        font-size: 2rem;
    }
    
    .current-price {
        font-size: 2rem;
    }
    
    .detail-card {
        padding: 20px;
    }
    
    .features-list {
        grid-template-columns: 1fr;
    }
    
    .specs-grid {
        grid-template-columns: 1fr;
    }
    
    .purchase-card {
        padding: 20px;
    }
}
</style>

<?php include 'footer.php'; ?>
