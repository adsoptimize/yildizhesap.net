/**
 * Replaces the legal pages with content written for this site.
 *
 * Every page shipped as an unfilled template: `[Firma Adı]`,
 * `[destek@siteadresi.com]`, `[0 (XXX) XXX XX XX]` and so on were still
 * literal in the published HTML, and the privacy page carried double-encoded
 * UTF-8 in its meta description. Beyond looking unfinished, placeholder legal
 * pages are a direct trust signal against a commercial site.
 *
 * Content facts are centralised in `FACTS` so a phone or address change is a
 * one-line edit rather than a search across six HTML blobs.
 *
 * Run: npx tsx scripts/update-legal-pages.ts
 */

import { PrismaClient } from "@prisma/client";

const prisma = new PrismaClient();

const FACTS = {
  siteName: "YildizHesap.net",
  shortName: "YildizHesap",
  url: "https://yildizhesap.net",
  email: "adsoptimize.ai2026@gmail.com",
  phone: "+90 537 658 77 77",
  whatsapp: "+905376587777",
  telegram: "@yildizlimited",
  address: "İstanbul, Türkiye",
  foundedYear: "2021",
  /** Shown as the "last updated" line at the bottom of each document. */
  updated: "17.09.2026",
} as const;

const { siteName, url, email, phone, telegram, address, foundedYear, updated } =
  FACTS;

/** Shared contact block so every document points at the same channels. */
const contactBlock = `<h3>İletişim</h3>
<p>Bu metinle ilgili her türlü soru, talep ve başvurunuz için bize aşağıdaki kanallardan ulaşabilirsiniz:</p>
<ul>
<li><strong>E-posta:</strong> ${email}</li>
<li><strong>Telefon / WhatsApp:</strong> ${phone}</li>
<li><strong>Telegram:</strong> ${telegram}</li>
<li><strong>Adres:</strong> ${address}</li>
</ul>
<p><em>Son güncelleme: ${updated}</em></p>`;

type LegalContent = {
  pageType: string;
  title: string;
  metaDescription: string;
  content: string;
};

const PAGES: readonly LegalContent[] = [
  {
    pageType: "kvvk",
    title: "KVKK Aydınlatma Metni",
    metaDescription: `${siteName} KVKK aydınlatma metni: kişisel verilerinizin hangi amaçla işlendiği, kimlere aktarıldığı ve 6698 sayılı Kanun kapsamındaki haklarınız.`,
    content: `<h2>KVKK Aydınlatma Metni</h2>
<p>Bu aydınlatma metni, 6698 sayılı Kişisel Verilerin Korunması Kanunu'nun ("KVKK") 10. maddesi uyarınca, veri sorumlusu sıfatıyla ${siteName} tarafından hazırlanmıştır.</p>

<h3>1. Veri Sorumlusu</h3>
<p>Kişisel verileriniz, <strong>${siteName}</strong> (${url}) tarafından veri sorumlusu sıfatıyla, aşağıda açıklanan kapsamda işlenmektedir. İletişim bilgilerimiz bu metnin sonunda yer almaktadır.</p>

<h3>2. İşlenen Kişisel Veriler</h3>
<p>Sitemizi kullanma biçiminize göre aşağıdaki veriler işlenebilir:</p>
<ul>
<li><strong>Kimlik ve iletişim verileri:</strong> ad, soyad, kullanıcı adı, e-posta adresi ve varsa telefon numarası.</li>
<li><strong>Müşteri işlem verileri:</strong> sipariş kodu, sipariş içeriği, sipariş tarihi, tutar ve ödeme durumu.</li>
<li><strong>İşlem güvenliği verileri:</strong> IP adresi, oturum bilgisi, giriş kayıtları ve tarayıcı bilgisi.</li>
<li><strong>Talep ve şikâyet verileri:</strong> destek talepleri ve iletişim formu üzerinden ilettiğiniz mesajların içeriği.</li>
</ul>
<p><strong>Kredi kartı bilgileriniz tarafımızca hiçbir aşamada görülmez ve saklanmaz.</strong> Ödeme işlemleri, lisanslı ödeme kuruluşlarının kendi güvenli altyapısı üzerinde gerçekleşir; bize yalnızca ödemenin başarılı olup olmadığı bilgisi iletilir.</p>

<h3>3. İşleme Amaçları</h3>
<ul>
<li>Siparişinizin oluşturulması, ödemenin doğrulanması ve satın aldığınız ürünün teslim edilmesi,</li>
<li>Üyelik kaydınızın oluşturulması ve hesabınıza erişiminizin sağlanması,</li>
<li>Sipariş takibi, destek taleplerinin karşılanması ve garanti süreçlerinin yürütülmesi,</li>
<li>Dolandırıcılık ve kötüye kullanımın önlenmesi ile işlem güvenliğinin sağlanması,</li>
<li>Mali mevzuat başta olmak üzere hukuki yükümlülüklerimizin yerine getirilmesi.</li>
</ul>

<h3>4. Hukuki Sebepler</h3>
<p>Kişisel verileriniz KVKK'nın 5. maddesinde yer alan şu sebeplere dayanılarak işlenir: sözleşmenin kurulması veya ifasıyla doğrudan doğruya ilgili olması, veri sorumlusunun hukuki yükümlülüğünü yerine getirebilmesi için zorunlu olması, bir hakkın tesisi veya korunması için veri işlemenin zorunlu olması ve temel hak ve özgürlüklerinize zarar vermemek kaydıyla meşru menfaatlerimiz için zorunlu olması.</p>

<h3>5. Verilerin Aktarılması</h3>
<p>Kişisel verileriniz pazarlama amacıyla üçüncü kişilere satılmaz veya kiralanmaz. Hizmetin sunulabilmesi için yalnızca aşağıdaki kategorilerde ve sınırlı ölçüde aktarım yapılır:</p>
<ul>
<li><strong>Ödeme kuruluşları:</strong> ödemenin alınabilmesi amacıyla, seçtiğiniz ödeme yöntemine göre ilgili ödeme hizmeti sağlayıcısına,</li>
<li><strong>Barındırma ve altyapı sağlayıcıları:</strong> sitenin ve veritabanının çalışabilmesi amacıyla,</li>
<li><strong>Yetkili kamu kurum ve kuruluşları:</strong> yalnızca mevzuattan doğan bir talep veya yükümlülük bulunması hâlinde.</li>
</ul>

<h3>6. Toplama Yöntemi</h3>
<p>Kişisel verileriniz; üyelik ve sipariş formları, iletişim formu, destek talepleri ve site kullanımınız sırasında oluşan teknik kayıtlar aracılığıyla elektronik ortamda toplanır.</p>

<h3>7. Saklama Süresi</h3>
<p>Verileriniz, işlendikleri amaç için gerekli olan süre boyunca ve ilgili mevzuatta öngörülen zamanaşımı ile saklama süreleri boyunca muhafaza edilir. Bu sürelerin sona ermesinin ardından veriler silinir, yok edilir veya anonim hâle getirilir.</p>

<h3>8. KVKK'nın 11. Maddesi Kapsamındaki Haklarınız</h3>
<p>Veri sahibi olarak; kişisel verinizin işlenip işlenmediğini öğrenme, işlenmişse buna ilişkin bilgi talep etme, işlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme, yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri bilme, eksik veya yanlış işlenmişse düzeltilmesini isteme, silinmesini veya yok edilmesini isteme, bu işlemlerin verilerin aktarıldığı üçüncü kişilere bildirilmesini isteme, münhasıran otomatik sistemlerle analiz edilmesi suretiyle aleyhinize bir sonucun ortaya çıkmasına itiraz etme ve kanuna aykırı işleme sebebiyle zarara uğramanız hâlinde zararın giderilmesini talep etme haklarına sahipsiniz.</p>
<p>Başvurularınızı ${email} adresine iletebilirsiniz. Talebiniz, niteliğine göre en geç <strong>otuz gün</strong> içinde ücretsiz olarak sonuçlandırılır.</p>

${contactBlock}`,
  },
  {
    pageType: "privacy",
    title: "Gizlilik Politikası",
    metaDescription: `${siteName} gizlilik politikası: hangi bilgileri topladığımız, nasıl kullandığımız, kimlerle paylaştığımız ve verilerinizi nasıl koruduğumuz.`,
    content: `<h2>Gizlilik Politikası</h2>
<p>${siteName} olarak gizliliğinize önem veriyoruz. Bu politika, sitemizi kullandığınızda hangi bilgileri topladığımızı, bu bilgileri neden ve nasıl kullandığımızı açıklar.</p>

<h3>1. Topladığımız Bilgiler</h3>
<ul>
<li><strong>Siz verdiğiniz için:</strong> üyelik veya sipariş sırasında girdiğiniz ad, e-posta adresi ve varsa telefon numarası; iletişim formuna yazdığınız mesajın içeriği.</li>
<li><strong>Otomatik olarak:</strong> IP adresiniz, oturum bilgisi, giriş kayıtları ve sitede ziyaret ettiğiniz sayfalara ilişkin genel kullanım verileri.</li>
<li><strong>Sipariş sürecinde:</strong> sipariş kodu, satın aldığınız ürün, tutar ve ödeme durumu.</li>
</ul>

<h3>2. Ödeme Bilgileriniz</h3>
<p>Kart numaranız, son kullanma tarihiniz ve güvenlik kodunuz <strong>sitemize hiçbir zaman girilmez ve tarafımızca saklanmaz</strong>. Ödeme adımında lisanslı ödeme kuruluşunun kendi güvenli sayfasına yönlendirilirsiniz; bize yalnızca işlemin başarılı olup olmadığı bilgisi döner. Kripto para ile ödemede ise cüzdan adresiniz dışında bir bilgi tarafımıza iletilmez.</p>

<h3>3. Bilgilerinizi Nasıl Kullanıyoruz</h3>
<ul>
<li>Siparişinizi oluşturmak, ödemenizi doğrulamak ve ürününüzü teslim etmek,</li>
<li>Sipariş takibi yapabilmenizi ve destek talebi açabilmenizi sağlamak,</li>
<li>Garanti kapsamındaki taleplerinizi değerlendirmek,</li>
<li>Kötüye kullanımı ve dolandırıcılığı tespit ederek engellemek,</li>
<li>Yasal yükümlülüklerimizi yerine getirmek.</li>
</ul>
<p>Onayınız olmadan size pazarlama amaçlı toplu e-posta göndermeyiz.</p>

<h3>4. Bilgilerin Paylaşımı</h3>
<p>Kişisel bilgilerinizi satmıyor, kiralamıyor veya reklam amacıyla üçüncü taraflarla paylaşmıyoruz. Paylaşım yalnızca hizmetin sunulması için zorunlu olan ödeme kuruluşları ve barındırma sağlayıcılarıyla, ya da mevzuattan doğan bir talep hâlinde yetkili kamu kurumlarıyla sınırlıdır.</p>

<h3>5. Güvenlik</h3>
<p>Site baştan sona HTTPS üzerinden sunulur. Parolalarınız veritabanında düz metin olarak tutulmaz; yalnızca geri döndürülemeyen özet (hash) değerleri saklanır. Bu nedenle parolanızı biz dahi göremeyiz; unutmanız hâlinde yapılabilecek tek şey sıfırlamaktır.</p>

<h3>6. Çerezler</h3>
<p>Oturumunuzun ve sepetinizin çalışabilmesi için zorunlu çerezler kullanılır. Çerezlerin türleri ve bunları nasıl yönetebileceğiniz Çerez Politikası sayfamızda ayrıntılı olarak açıklanmıştır.</p>

<h3>7. Saklama ve Silme</h3>
<p>Bilgileriniz, hesabınız aktif olduğu sürece ve ilgili mevzuatın öngördüğü saklama süreleri boyunca tutulur. Hesabınızın ve verilerinizin silinmesini talep etmek için ${email} adresine yazmanız yeterlidir.</p>

<h3>8. Haklarınız</h3>
<p>Kişisel verilerinize erişme, düzeltme, silme ve işlenmesine itiraz etme haklarınıza ilişkin ayrıntılı bilgi KVKK Aydınlatma Metni sayfamızda yer almaktadır.</p>

<h3>9. Değişiklikler</h3>
<p>Bu politika zaman zaman güncellenebilir. Güncel sürüm her zaman bu sayfada yayımlanır ve sayfanın sonundaki tarih güncellenir.</p>

${contactBlock}`,
  },
  {
    pageType: "terms",
    title: "Kullanım Şartları",
    metaDescription: `${siteName} kullanım şartları: hizmet kapsamı, üyelik, sipariş ve teslimat kuralları, tarafların yükümlülükleri ve sorumluluk sınırları.`,
    content: `<h2>Kullanım Şartları</h2>
<p>Bu metin, ${siteName} (${url}) internet sitesinin kullanımına ilişkin koşulları düzenler. Siteyi kullanarak ve sipariş vererek aşağıdaki şartları kabul etmiş sayılırsınız.</p>

<h3>1. Taraflar ve Tanımlar</h3>
<p><strong>Site:</strong> ${url} adresinde yayımlanan internet sitesi. <strong>Kullanıcı:</strong> siteyi ziyaret eden veya sipariş veren gerçek ya da tüzel kişi. <strong>Ürün:</strong> site üzerinden satışa sunulan, elektronik ortamda anında teslim edilen dijital hesap ve erişim bilgileri.</p>

<h3>2. Hizmetin Kapsamı</h3>
<p>${siteName}, sosyal medya ve dijital platformlara ait hesapların satışını yapar. Satışa sunulan her ürünün özellikleri, açılış tarihi, doğrulama durumu, garanti süresi ve fiyatı ilgili ürün sayfasında belirtilir. Sipariş vermeden önce ürün sayfasındaki bilgileri okumak kullanıcının sorumluluğundadır.</p>

<h3>3. Üyelik ve Misafir Alışveriş</h3>
<p>Sipariş vermek için üyelik zorunlu değildir; misafir olarak da alışveriş yapabilirsiniz. Misafir siparişlerinde sipariş bilgilerinize sipariş kodunuz ve ödeme sırasında girdiğiniz e-posta adresiyle erişirsiniz. <strong>Sipariş kodunuzu saklamanız gerekir</strong>, çünkü misafir siparişlerinde kodun yeniden gönderilebileceği bir üyelik kaydı bulunmaz.</p>
<p>Üye olmanız hâlinde hesap bilgilerinizin ve parolanızın gizliliğinden siz sorumlusunuz. Hesabınız üzerinden gerçekleştirilen işlemler size ait kabul edilir.</p>

<h3>4. Sipariş, Fiyat ve Ödeme</h3>
<p>Sitede yer alan fiyatlar Türk Lirası cinsindendir ve önceden haber verilmeksizin değiştirilebilir; ancak siparişinizi tamamladığınız andaki fiyat sizin için bağlayıcıdır. Ödemeler kredi/banka kartı veya kripto para ile, lisanslı ödeme kuruluşlarının güvenli altyapısı üzerinden alınır.</p>
<p>Stokta bulunmayan bir ürün için ödeme alınmışsa, tutar iade edilir.</p>

<h3>5. Teslimat</h3>
<p>Ürünler dijitaldir ve ödemenin onaylanmasının ardından <strong>anında</strong> teslim edilir. Hesap bilgileriniz sipariş kaydınıza işlenir; sipariş takip sayfasından görüntüleyebilir veya metin dosyası olarak indirebilirsiniz. Fiziki bir gönderim söz konusu değildir.</p>

<h3>6. Kullanıcı Yükümlülükleri</h3>
<ul>
<li>Satın aldığınız hesapları yürürlükteki mevzuata ve ilgili platformun kullanım koşullarına uygun biçimde kullanmakla yükümlüsünüz.</li>
<li>Hesapları dolandırıcılık, spam, yanıltıcı içerik veya herhangi bir hukuka aykırı faaliyet için kullanamazsınız.</li>
<li>Siteye otomatik araçlarla aşırı istek göndermek, güvenlik önlemlerini aşmaya çalışmak veya sistemin işleyişini bozmaya yönelik davranışlarda bulunmak yasaktır.</li>
<li>Teslim aldığınız hesap bilgilerini üçüncü kişilerle paylaşmanız hâlinde doğacak sonuçlardan siz sorumlusunuz.</li>
</ul>

<h3>7. Teslimat Sonrası Sorumluluk</h3>
<p>Teslimattan sonra hesabın güvenliği kullanıcının sorumluluğundadır. Hesabı teslim aldıktan sonra parolayı, kurtarma e-postasını ve telefon numarasını değiştirmeniz önerilir. İlgili platformun kurallarına aykırı kullanım sonucu oluşan kapanma ve kısıtlamalar garanti kapsamı dışındadır.</p>

<h3>8. Sorumluluğun Sınırı</h3>
<p>${siteName}, satışa sunduğu hesapların ürün sayfasında belirtilen özelliklere uygun olarak teslim edilmesinden sorumludur. Üçüncü taraf platformların kendi politikalarında yapacağı değişikliklerden, bu platformların kullanıcı hesabına uygulayacağı yaptırımlardan veya mücbir sebeplerden doğan sonuçlardan sorumluluk kabul edilmez.</p>

<h3>9. Fikri Mülkiyet</h3>
<p>Sitede yer alan tasarım, metin, görsel ve yazılım unsurları ${siteName}'e aittir. İzinsiz kopyalanamaz, çoğaltılamaz veya ticari amaçla kullanılamaz.</p>

<h3>10. İade ve Cayma Hakkı</h3>
<p>Anında teslim edilen dijital ürünlerde cayma hakkına ilişkin istisna ve garanti koşulları İade ve İptal Politikası sayfamızda düzenlenmiştir.</p>

<h3>11. Değişiklik ve Yürürlük</h3>
<p>${siteName}, bu şartları tek taraflı olarak güncelleyebilir. Güncel metin bu sayfada yayımlandığı anda yürürlüğe girer. Siteyi kullanmaya devam etmeniz güncel şartları kabul ettiğiniz anlamına gelir.</p>

<h3>12. Uyuşmazlıkların Çözümü</h3>
<p>Bu şartlardan doğabilecek uyuşmazlıklarda Türk hukuku uygulanır ve İstanbul mahkemeleri ile icra daireleri yetkilidir. Tüketici sıfatını haiz kullanıcılar, parasal sınırlar dâhilinde ikametgâhlarının bulunduğu yerdeki Tüketici Hakem Heyetlerine de başvurabilir.</p>

${contactBlock}`,
  },
  {
    pageType: "cookies",
    title: "Çerez Politikası",
    metaDescription: `${siteName} çerez politikası: hangi çerezleri neden kullandığımız, hangileri zorunlu ve çerez tercihlerinizi nasıl yönetebileceğiniz.`,
    content: `<h2>Çerez Politikası</h2>
<p>Bu politika, ${siteName} (${url}) üzerinde hangi çerezlerin kullanıldığını ve bu tercihleri nasıl yönetebileceğinizi açıklar.</p>

<h3>1. Çerez Nedir?</h3>
<p>Çerez, bir web sitesini ziyaret ettiğinizde tarayıcınıza kaydedilen küçük bir metin dosyasıdır. Çerezler, oturumunuzun açık kalması veya sepetinizin siz sayfalar arasında gezinirken korunması gibi temel işlevleri mümkün kılar.</p>

<h3>2. Kullandığımız Çerez Türleri</h3>
<ul>
<li><strong>Zorunlu çerezler:</strong> Oturum açma, sepet içeriğinin korunması ve güvenlik kontrolleri için gereklidir. Bu çerezler olmadan site çalışmaz ve devre dışı bırakılamaz.</li>
<li><strong>Tercih çerezleri:</strong> Çerez bildirimine verdiğiniz yanıt gibi seçimlerinizi hatırlar; böylece aynı bildirimi her ziyarette görmezsiniz.</li>
<li><strong>Ölçüm ve performans çerezleri:</strong> Hangi sayfaların ziyaret edildiğini ve sayfaların ne kadar hızlı yüklendiğini toplu ve anonim biçimde görmemizi sağlar. Bu veriler tek tek kullanıcıları tanımlamak için kullanılmaz.</li>
</ul>
<p>Sitemizde reklam ağlarına ait üçüncü taraf takip çerezleri kullanılmamaktadır.</p>

<h3>3. Çerezleri Nasıl Yönetirsiniz?</h3>
<p>Tarayıcınızın ayarlar bölümünden çerezleri görüntüleyebilir, silebilir veya engelleyebilirsiniz. İlgili bölüm Chrome'da "Gizlilik ve güvenlik", Safari'de "Gizlilik", Firefox'ta "Gizlilik ve Güvenlik" başlığı altındadır.</p>
<p>Zorunlu çerezleri engellemeniz hâlinde oturum açma ve sepete ürün ekleme gibi işlevlerin çalışmayacağını hatırlatırız.</p>

<h3>4. Değişiklikler</h3>
<p>Bu politika, kullanılan çerezlerde değişiklik olması hâlinde güncellenir ve güncel sürüm bu sayfada yayımlanır.</p>

${contactBlock}`,
  },
  {
    pageType: "refund",
    title: "İade ve İptal Politikası",
    metaDescription: `${siteName} iade ve iptal politikası: anında teslim edilen dijital ürünlerde cayma hakkı istisnası, garanti kapsamı ve iade başvuru süreci.`,
    content: `<h2>İade ve İptal Politikası</h2>
<p>Bu politika, ${siteName} (${url}) üzerinden satın alınan dijital ürünlerde iptal, iade ve garanti koşullarını düzenler.</p>

<h3>1. Cayma Hakkına İlişkin Yasal İstisna</h3>
<p>Mesafeli Sözleşmeler Yönetmeliği'nin 15. maddesi uyarınca, <strong>elektronik ortamda anında ifa edilen hizmetler ve tüketiciye anında teslim edilen gayrimaddi mallara ilişkin sözleşmelerde cayma hakkı bulunmamaktadır.</strong></p>
<p>Sitemizde satılan hesap bilgileri, ödeme onaylandığı anda elektronik ortamda teslim edildiği için bu istisna kapsamındadır. Bu nedenle teslim edilmiş ve görüntülenmiş bir siparişte 14 günlük cayma hakkı işletilemez. Bu düzenleme mevzuattan kaynaklanmaktadır.</p>

<h3>2. Teslimat Öncesi İptal</h3>
<p>Ödemeniz alınmış ancak ürün henüz teslim edilmemişse siparişinizi iptal edebilirsiniz. Stok yetersizliği nedeniyle teslimat yapılamayan siparişlerde ödemeniz <strong>eksiksiz olarak iade edilir</strong>; bu durumda iade talebinde bulunmanıza gerek yoktur, süreci biz başlatırız.</p>

<h3>3. Garanti Kapsamı</h3>
<p>Her ürün, ilgili ürün sayfasında belirtilen <strong>garanti süresi</strong> boyunca kapsam altındadır. Garanti, hesabın teslim anındaki durumuyla ilgili sorunları kapsar. Aşağıdaki durumlarda ürününüz değiştirilir veya bedeli iade edilir:</p>
<ul>
<li>Teslim edilen bilgilerle hesaba giriş yapılamaması,</li>
<li>Hesabın teslim anında ürün sayfasında belirtilen özellikleri taşımaması,</li>
<li>Hesabın teslimattan önce kapatılmış veya kısıtlanmış olması.</li>
</ul>

<h3>4. Garanti Kapsamı Dışındaki Durumlar</h3>
<ul>
<li>Teslimattan sonra parola, kurtarma e-postası veya telefon numarasının değiştirilmesi sonucu erişimin kaybedilmesi,</li>
<li>İlgili platformun kullanım koşullarına aykırı kullanım (spam, yanıltıcı içerik, toplu mesaj gönderimi vb.) nedeniyle hesabın kapatılması veya kısıtlanması,</li>
<li>Hesap bilgilerinin üçüncü kişilerle paylaşılması sonucu oluşan sorunlar,</li>
<li>Ürün sayfasında belirtilen garanti süresinin dolmasından sonra ortaya çıkan durumlar,</li>
<li>Platformun kendi politikalarında sonradan yaptığı değişikliklerden kaynaklanan kısıtlamalar.</li>
</ul>

<h3>5. Başvuru Süreci</h3>
<p>Garanti veya iade talebinizi, <strong>sipariş kodunuzu belirterek</strong> ${email} adresine ya da ${telegram} Telegram adresine iletin. Sorunu gösteren bir ekran görüntüsü eklemeniz değerlendirmeyi hızlandırır.</p>
<p>Talebiniz en geç <strong>3 iş günü</strong> içinde incelenir ve sonucu tarafınıza bildirilir.</p>

<h3>6. İade Şekli ve Süresi</h3>
<p>İadesi onaylanan tutar, ödemeyi gerçekleştirdiğiniz yönteme geri yapılır. Kart ile yapılan ödemelerde tutarın hesabınıza yansıması, bankanızın işlem süresine bağlı olarak <strong>7 ile 14 iş günü</strong> arasında sürebilir. Kripto para ile yapılan ödemelerde iade, ödemenin yapıldığı ağ üzerinden aynı cüzdan adresine gönderilir.</p>
<p>Dilerseniz iade yerine, eş değer bir ürünle değişim de yapılabilir.</p>

${contactBlock}`,
  },
  {
    pageType: "about",
    title: "Hakkımızda",
    metaDescription: `${foundedYear} yılından bu yana doğrulanmış sosyal medya hesapları sunan ${siteName} hakkında: ne yaptığımız, nasıl çalıştığımız ve teslimat güvencemiz.`,
    content: `<h2>Hakkımızda</h2>
<p><strong>${siteName}</strong>, ${foundedYear} yılından bu yana doğrulanmış sosyal medya ve dijital platform hesaplarının satışını yapan bir dijital ürün platformudur. Amacımız, reklam veren işletmelerin, ajansların ve içerik üreticilerinin ihtiyaç duyduğu hesaplara güvenli ve hızlı biçimde ulaşmasını sağlamaktır.</p>

<h3>Ne Sunuyoruz?</h3>
<p>Kataloğumuzda Facebook, Instagram, TikTok, Telegram, X (Twitter) ve Business Manager hesaplarının yanı sıra Gmail, Outlook ve Hotmail mail hesapları yer alır. Her ürünün açılış tarihi, kimlik doğrulama durumu, kayıtlı olduğu ülke ve garanti süresi ilgili ürün sayfasında açıkça belirtilir. Böylece hangi hesabın hangi kullanım amacına uygun olduğunu satın almadan önce görebilirsiniz.</p>

<h3>Nasıl Çalışıyoruz?</h3>
<ul>
<li><strong>Anında teslimat:</strong> Ödemeniz onaylandığı anda hesap bilgileri siparişinize işlenir. Bekleme veya manuel onay süreci yoktur.</li>
<li><strong>Üyeliksiz alışveriş:</strong> Kayıt olmadan, yalnızca e-posta adresinizle sipariş verebilirsiniz.</li>
<li><strong>Güvenli ödeme:</strong> Kredi/banka kartı ve kripto para seçenekleri, lisanslı ödeme kuruluşlarının altyapısı üzerinden sunulur. Kart bilgileriniz bize hiçbir aşamada ulaşmaz.</li>
<li><strong>Garanti:</strong> Her ürün, sayfasında yazan süre boyunca teslim anındaki durumuyla ilgili sorunlara karşı kapsam altındadır.</li>
<li><strong>Şeffaf stok:</strong> Ürün sayfasında gördüğünüz stok adedi gerçek zamanlıdır; stokta görünen ürün anında teslim edilebilir demektir.</li>
</ul>

<h3>Kimlere Hizmet Veriyoruz?</h3>
<p>Reklam kampanyası yürüten dijital pazarlama ajansları, kendi ürününü tanıtan e-ticaret satıcıları, topluluk yöneten içerik üreticileri ve birden fazla marka hesabını tek panelden yöneten ekipler başlıca kullanıcılarımızdır.</p>

<h3>Destek</h3>
<p>Satın alma öncesi ve sonrası sorularınız için WhatsApp, Telegram ve e-posta kanallarımız açıktır. Satın alma sonrası bir sorun bildirecekseniz sipariş kodunuzu paylaşmanız süreci belirgin biçimde hızlandırır.</p>

${contactBlock}`,
  },
];

async function main(): Promise<void> {
  for (const page of PAGES) {
    const result = await prisma.legalPage.updateMany({
      where: { pageType: page.pageType },
      data: {
        title: page.title,
        metaDescription: page.metaDescription,
        content: page.content,
        isActive: true,
      },
    });

    const status = result.count === 0 ? "BULUNAMADI" : "guncellendi";
    console.log(
      `[${page.pageType}] ${status} — ${page.content.length} karakter — "${page.title}"`,
    );
  }
}

main()
  .catch((error: unknown) => {
    console.error(error);
    process.exitCode = 1;
  })
  .finally(() => {
    void prisma.$disconnect();
  });
