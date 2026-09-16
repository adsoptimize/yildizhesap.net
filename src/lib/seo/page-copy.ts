/**
 * Long-form intro copy rendered underneath each category's product grid.
 *
 * Category pages were 125-152 words each, essentially a grid with a one-line
 * description, which is why Search Console left most of them out of the index
 * as "crawled - currently not indexed". This copy is deliberately specific per
 * category rather than templated: near-identical text across eleven pages
 * would reproduce the duplicate-content problem it is meant to solve.
 *
 * Keyed by `category.id` so a slug change never silently drops the copy.
 */

export type CategoryCopy = {
  heading: string;
  paragraphs: readonly string[];
};

export const CATEGORY_COPY: Readonly<Record<number, CategoryCopy>> = {
  7: {
    heading: "Doğrulanmış ana hesaplar hakkında bilmeniz gerekenler",
    paragraphs: [
      "Ana hesap, bir kullanıcının reklam hesabı, Business Manager varlığı ve sayfalarının bağlı olduğu kök Facebook profilidir. Bu profil kısıt aldığında ona bağlı tüm varlıklar aynı anda çalışamaz hâle geldiği için, ana hesabın kimlik doğrulamasından geçmiş olması reklam yürüten ekipler açısından tek başına en belirleyici kriterdir.",
      "Buradaki hesaplar resmi kimlik belgesiyle doğrulanmış, doğrulama kaydı Meta tarafında tamamlanmış profillerdir. Doğrulanmış bir ana hesap, yeni bir reklam hesabı açarken veya ödeme yöntemi eklerken karşınıza çıkan ek güvenlik adımlarını büyük ölçüde ortadan kaldırır ve inceleme süreçlerinde itiraz hakkınızın işletilme olasılığını artırır.",
      "Teslimattan sonra yapılması gereken ilk iş, kurtarma e-posta adresini ve telefon numarasını kendi bilgilerinizle değiştirmektir. Oturumu yeni bir cihazda açarken, hesabın daha önce kullanıldığı ülkeye yakın bir IP kullanmak ilk giriş sırasında doğrulama istenme ihtimalini düşürür.",
    ],
  },
  8: {
    heading: "Dirençli hesap ne demek?",
    paragraphs: [
      "Dirençli hesap, geçmişte en az bir kez kısıtlama almış ancak itiraz veya inceleme süreci sonunda yeniden açılmış hesaptır. Sektörde bu hesaplar tercih edilir, çünkü bir kez incelemeden geçip geri kazanılmış olmaları Meta tarafında hesabın gerçek bir kullanıcıya ait olduğunun teyit edildiği anlamına gelir.",
      "Pratikte bu, hesabın ikinci bir incelemeye girmesi hâlinde tamamen kapanmak yerine tekrar değerlendirilme ihtimalinin daha yüksek olması demektir. Yoğun reklam harcaması yapan veya sık kampanya değiştiren hesaplarda bu dayanıklılık, hiç kısıt görmemiş taze bir hesabın sunduğu temiz geçmişten daha değerli olabilir.",
      "Dirençli hesaplarda dikkat edilmesi gereken nokta, kısıtın hangi sebeple geldiğidir. Ödeme kaynaklı bir kısıttan dönmüş hesap ile içerik politikası kaynaklı bir kısıttan dönmüş hesabın davranışı farklıdır; ürün açıklamalarında bu bilgi belirtildiğinde kullanım planınızı buna göre yapmanızı öneririz.",
    ],
  },
  12: {
    heading: "Eski tarihli (aged) hesaplar neden daha değerli?",
    paragraphs: [
      "Hesap yaşı, platformların güven skorunu belirleyen en eski ve en istikrarlı sinyallerden biridir. 2008-2015 aralığında açılmış bir Facebook hesabı, arkasında yıllara yayılan gerçek bir etkileşim geçmişi taşıdığı için otomatik spam filtrelerinde yeni hesaplara kıyasla çok daha az takılır.",
      "Bu farkın en somut karşılığı reklam tarafındadır. Eski hesaplar genellikle daha yüksek günlük harcama limitiyle başlar, ödeme yöntemi eklerken daha az doğrulama ister ve yeni bir kampanya yayına alırken onay süreleri daha kısadır. Aynı bütçeyi yeni açılmış bir hesapla harcamak çoğu zaman kademeli limit artışı beklemeyi gerektirir.",
      "Satın alırken hesabın açılış yılı kadar, o yıldan bu yana kesintisiz kullanımda olup olmadığına da bakın. Uzun süre atıl kalmış çok eski bir hesap, ilk girişte kimlik doğrulaması isteyebilir; listelerimizde açılış aralığı ve hesabın durumu ürün başlığında ayrıca belirtilir.",
    ],
  },
  13: {
    heading: "Business Manager hesapları ne işe yarar?",
    paragraphs: [
      "Business Manager (güncel adıyla Meta Business Suite), sayfaları, reklam hesaplarını, pikselleri ve ürün kataloglarını tek çatı altında toplayan yönetim panelidir. Bir ajans veya birden fazla markayı yöneten ekip için, varlıkları kişisel profilden ayırmanın ve ekip üyelerine rol bazlı yetki vermenin tek resmî yolu budur.",
      "Hazır bir Business Manager satın almanın temel avantajı limit ve doğrulama sürecini atlamaktır. Sıfırdan açılan bir BM genellikle düşük harcama limitiyle başlar ve işletme doğrulaması tamamlanana kadar reklam hesabı açma sayısı kısıtlıdır. Doğrulanmış bir BM ise ilk günden itibaren tanımlı limitiyle çalışmaya başlar.",
      "Bu kategorideki ürünlerde bağlı reklam hesabı sayısı, günlük harcama limiti ve doğrulama durumu ayrı ayrı belirtilir. Satın aldıktan sonra kendi kullanıcınızı yönetici olarak ekleyip iki adımlı doğrulamayı etkinleştirmeniz, hesabın kontrolünü kalıcı olarak devralmanız için yeterlidir.",
    ],
  },
  14: {
    heading: "Marketplace hesapları ve satış izni",
    paragraphs: [
      "Facebook Marketplace, ilan yayınlamak için hesabın belirli bir güven eşiğini geçmiş olmasını şart koşar. Yeni açılan veya bölgesel kısıt taşıyan profillerde Marketplace sekmesi hiç görünmez ya da görünür olsa bile ilan yayınlama yetkisi kapalı gelir.",
      "Bu kategorideki hesaplar Marketplace erişimi açık ve ilan yayınlamaya hazır durumda teslim edilir. İkinci el satış yapan, bölgesel ürün tanıtımı yürüten veya Marketplace üzerinden potansiyel müşteriye ulaşmak isteyen kullanıcılar için hesabın ilan geçmişi ve satıcı puanı doğrudan dönüşüme etki eder.",
      "İlk ilanınızı yayınlarken hesabın kayıtlı olduğu bölgeyle ilan bölgesinin tutarlı olmasına dikkat edin. Konum ile IP arasındaki büyük farklar, Marketplace tarafında ilanın incelemeye alınmasına yol açan en yaygın nedendir.",
    ],
  },
  15: {
    heading: "Facebook hesap satın alırken nelere dikkat etmeli?",
    paragraphs: [
      "Facebook hesap satın al aramasıyla karşınıza çıkan seçenekler arasındaki asıl fark fiyat değil, hesabın geçmişidir. Bir hesabın değerini belirleyen dört şey vardır: açılış yılı, kimlik doğrulamasından geçip geçmediği, kayıtlı olduğu ülke ve daha önce kısıt alıp almadığı. Listelerimizde bu dördü de ürün başlığında ve açıklamasında açıkça yazar.",
      "Kullanım amacınız seçimi doğrudan belirler. Yalnızca gruplara katılmak veya sayfa yönetmek istiyorsanız doğrulanmamış bir hesap yeterli olabilir. Reklam vereceksiniz veya Business Manager bağlayacaksanız kimlik doğrulaması yapılmış, tercihen eski tarihli bir hesap seçmelisiniz; aksi hâlde ilk harcamada limit duvarına çarparsınız.",
      "Teslimat sonrası ilk yirmi dört saat kritiktir. Hesabı aldığınız anda şifreyi, kurtarma e-postasını ve telefon numarasını değiştirin, iki adımlı doğrulamayı açın. İlk günlerde ani ve yoğun aktiviteden (toplu arkadaş ekleme, çok sayıda grup mesajı) kaçınmak, hesabın otomatik güvenlik kontrollerine takılmasını önler.",
    ],
  },
  16: {
    heading: "Instagram hesapları: takipçi sayısı ve tanıtım onayı",
    paragraphs: [
      "Instagram hesaplarında iki ayrı kriter fiyatı belirler. Birincisi takipçi sayısı ve bu takipçilerin etkileşim oranı; ikincisi hesabın tanıtım (promosyon) yayınlama yetkisinin açık olup olmadığıdır. Yüksek takipçili ama tanıtım yetkisi kapalı bir hesap, reklam yürütmek isteyen bir marka için sınırlı değer taşır.",
      "Tanıtım onaylı hesaplar, sponsorlu içerik yayınlayabilir ve Meta reklam panelinden doğrudan kampanya bağlanabilir. İçerik üreticisiyle iş birliği yapan markalar ve kendi ürününü tanıtan satıcılar için bu yetki, hesabın takipçi sayısından daha belirleyicidir.",
      "Satın aldıktan sonra profil adını ve biyografiyi tek seferde değil, birkaç güne yayarak değiştirin. Kısa sürede yapılan toptan profil değişiklikleri Instagram tarafında hesap devri sinyali olarak okunur ve erişim kısıtlamasına yol açabilir.",
    ],
  },
  20: {
    heading: "X (Twitter) hesapları hakkında",
    paragraphs: [
      "X hesaplarında onay, mavi tik aboneliğiyle karıştırılmamalıdır. Buradaki onaylı ifadesi, hesabın e-posta ve telefon doğrulamasının tamamlanmış, gönderi geçmişinin bulunduğu ve aktif bir platform kısıtı taşımadığı anlamına gelir. X Premium aboneliği hesapla birlikte gelmez, ayrıca satın alınan bir hizmettir.",
      "Doğrulaması tamamlanmış ve geçmişi olan bir X hesabı, yeni açılan hesaplara kıyasla belirgin biçimde daha stabildir. Yeni hesaplar günlük gönderi ve takip limitlerine hızla takılır, API erişiminde ek doğrulama ister ve otomatik spam filtrelerinde çok daha sık işaretlenir.",
      "Topluluk yönetimi, kripto ve oyun alanlarında duyuru hesabı olarak kullanacaksanız, hesabın açılış tarihini ve mevcut gönderi geçmişini ürün açıklamasından kontrol edin. Uzun süredir sessiz kalmış bir hesapta yayına ilk birkaç gün düşük tempoyla başlamak daha güvenlidir.",
    ],
  },
  21: {
    heading: "TikTok hesapları ne için kullanılır?",
    paragraphs: [
      "TikTok hesapları en çok TikTok Ads Manager üzerinden reklam yayınlamak, marka içeriği üretmek ve hesabın mevcut kitlesine doğrudan erişmek için tercih edilir. Platformun içerik dağıtım algoritması hesap geçmişine duyarlı olduğundan, yayın geçmişi olan bir hesap ilk videodan itibaren daha geniş erişim alır.",
      "Eski tarihli TikTok hesapları yeni açılanlara göre daha az kısıt alır ve reklam onay süreçleri daha hızlı ilerler. Yeni bir hesapla reklam vermeye başladığınızda karşınıza çıkan ödeme doğrulama ve harcama limiti adımları, geçmişi olan bir hesapta çoğunlukla atlanır.",
      "Hesap seçerken ülke ayarı önemlidir: hesabın kayıtlı olduğu bölge, içeriğinizin ilk gösterileceği kitleyi belirler. Hedef kitleniz Türkiye ise Türkiye kayıtlı, yurt dışıysa ilgili ülkeye kayıtlı hesap seçmek erişim açısından fark yaratır.",
    ],
  },
  22: {
    heading: "Mail hesapları neden ayrıca gerekir?",
    paragraphs: [
      "Gmail, Outlook ve Hotmail hesapları çoğunlukla tek başına değil, bir sosyal medya hesabının tamamlayıcısı olarak alınır. Satın aldığınız Facebook veya Instagram hesabının kurtarma adresini kendi kontrolünüzdeki bir maile taşımadan, hesabın kontrolünü gerçekten devralmış sayılmazsınız.",
      "İkinci yaygın kullanım, reklam panellerinde birbirinden bağımsız kimlikler oluşturmaktır. Aynı mail adresine bağlı birden fazla reklam hesabı, biri kısıt aldığında diğerlerinin de incelemeye alınmasına yol açabilir; ayrı mail adresleri bu zinciri kırar.",
      "Buradaki hesaplar telefon doğrulaması tamamlanmış olarak teslim edilir. Bu önemlidir, çünkü doğrulanmamış bir mail adresini Facebook'a kurtarma adresi olarak eklemek istediğinizde platform ek doğrulama ister ve süreç tıkanır.",
    ],
  },
  27: {
    heading: "Telegram hesapları ve kullanım alanları",
    paragraphs: [
      "Telegram hesapları kanal ve grup yönetimi, bot kurulumu ve topluluk üzerinden duyuru yapmak için kullanılır. Platform kayıt için telefon numarası zorunlu tuttuğundan, numara doğrulaması tamamlanmış hazır bir hesap, numara tedarik etme adımını tamamen ortadan kaldırır.",
      "Kanal kuracaksanız hesabın yaşı önem taşır. Yeni açılmış Telegram hesapları ilk günlerde toplu kullanıcı ekleme ve çok sayıda gruba katılma konusunda sıkı limitlere tabidir; bu limitler spam şikâyeti almadan kanal büyütmeyi zorlaştırır.",
      "Teslimattan sonra hesabın iki adımlı doğrulama şifresini kendiniz belirleyin ve aktif oturumlar listesinden tanımadığınız oturumları kapatın. Telegram oturum yönetimini kullanıcıya açık şekilde sunduğu için bu iki adım hesabı tam olarak devralmanız açısından yeterlidir.",
    ],
  },
};

/**
 * Intro copy for the two static pages that crawled thinnest: `/siparis-takip`
 * at 27 words and `/iletisim` at 70. Both are functional pages built around a
 * form, so without this they carry almost no indexable text.
 */
export const STATIC_PAGE_COPY: Readonly<Record<string, CategoryCopy>> = {
  "siparis-takip": {
    heading: "Sipariş takibi nasıl çalışır?",
    paragraphs: [
      "Ödemeniz onaylandığı anda siparişinize benzersiz bir sipariş kodu atanır ve satın aldığınız hesap bilgileri bu koda işlenir. Yukarıdaki forma sipariş kodunuzu ve ödeme sırasında girdiğiniz e-posta adresini yazarak siparişinize üye olmadan da ulaşabilirsiniz; iki bilginin birlikte doğrulanması, bilgilerinize sizden başka kimsenin erişememesini sağlar.",
      "Sipariş detay ekranında hesap bilgilerini doğrudan görüntüleyebilir veya tek tuşla metin dosyası olarak indirebilirsiniz. Hesabınızda iki adımlı doğrulama tanımlıysa, doğrulama kodunu aynı ekrandaki üreticiden alabilirsiniz; gizli anahtar tarayıcınızdan hiç çıkmadığı için kod cihazınızda üretilir.",
      "Üye olarak satın aldıysanız siparişleriniz hesabım bölümünde kalıcı olarak listelenir ve sipariş kodu girmenize gerek kalmaz. Misafir olarak aldıysanız sipariş kodunuzu saklamanız gerekir, çünkü kodun yeniden gönderilebileceği bir üyelik kaydı bulunmaz.",
      "Ödemeniz görünmüyorsa önce bankanızın veya kripto ağının onay süresini bekleyin; kart ödemeleri genellikle saniyeler içinde, kripto ödemeleri ağ yoğunluğuna göre birkaç dakikada onaylanır. Onay geldiği hâlde siparişiniz görünmüyorsa iletişim sayfasındaki kanallardan sipariş kodunuzla bize ulaşın.",
    ],
  },
  iletisim: {
    heading: "Destek almadan önce",
    paragraphs: [
      "Satın alma öncesi sorularınız için WhatsApp ve Telegram en hızlı kanallardır; hangi hesabın kullanım amacınıza uyduğundan emin değilseniz, ne yapmak istediğinizi yazmanız yeterli olur, uygun kategoriyi biz yönlendiririz.",
      "Satın alma sonrası bir sorun bildirecekseniz mesajınıza sipariş kodunuzu eklemeniz süreci belirgin biçimde kısaltır. Hesapla ilgili bir hata alıyorsanız hata ekranının görüntüsünü ve hangi adımda karşılaştığınızı da paylaşın; bu iki bilgi çoğu durumda ilk yanıtta çözüme ulaşmamızı sağlar.",
      "Teslim edilen hesaplar ürün sayfasında belirtilen garanti süresi boyunca kapsam altındadır. Garanti, teslimat anındaki durumuyla ilgili sorunları kapsar; teslimattan sonra hesap bilgilerinin değiştirilmesi veya platform kurallarına aykırı kullanım sonucu oluşan kısıtlamalar kapsam dışındadır.",
    ],
  },
};
