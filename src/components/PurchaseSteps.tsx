/**
 * Visible counterpart to the `HowTo` JSON-LD in `structured-data.ts`.
 *
 * Structured data must reflect content the visitor can actually see —
 * markup describing steps that appear nowhere on the page is a spam signal.
 * Keep the step titles here in sync with `howToPurchaseStructuredData()`.
 */

const STEPS = [
  {
    icon: "fas fa-layer-group",
    title: "Hesap kategorisini seçin",
    text: "Facebook, Instagram, Business Manager, TikTok, Telegram veya mail kategorilerinden ihtiyacınıza uygun olanı açın. Fiyat ve stok filtreleriyle daraltabilirsiniz.",
  },
  {
    icon: "fas fa-cart-plus",
    title: "Ürünü sepete ekleyin",
    text: "Detay sayfasında adet seçip sepete ekleyin. Stok bilgisi gerçek zamanlıdır; stokta görünen adet anında teslim edilebilir.",
  },
  {
    icon: "fas fa-user-check",
    title: "Bilgilerinizi girin",
    text: "Üyelik zorunlu değil. Misafir olarak da satın alabilirsiniz; sipariş bilgilerinin gönderileceği e-posta adresi yeterlidir.",
  },
  {
    icon: "fas fa-credit-card",
    title: "Ödemeyi tamamlayın",
    text: "Kredi/banka kartı veya kripto para ile ödeyin. Ödeme, sağlayıcının güvenli altyapısında gerçekleşir; kart bilgileri bizde saklanmaz.",
  },
  {
    icon: "fas fa-bolt",
    title: "Hesap bilgilerini anında alın",
    text: "Ödeme onaylandığı anda hesap bilgileri siparişinize işlenir. Sipariş takip sayfasından görüntüleyebilir veya metin dosyası olarak indirebilirsiniz.",
  },
] as const;

export function PurchaseSteps() {
  return (
    <section className="purchase-steps" aria-labelledby="purchase-steps-title">
      <div className="section-title" style={{ marginBottom: 24 }}>
        <h2 id="purchase-steps-title">Nasıl Satın Alınır?</h2>
        <p>Siparişten teslimata kadar tüm süreç ortalama 5 dakika sürer.</p>
      </div>

      <ol className="purchase-steps__list">
        {STEPS.map((step, index) => (
          <li key={step.title}>
            <span className="purchase-steps__num">{index + 1}</span>
            <div className="purchase-steps__body">
              <h3>
                <i className={step.icon} aria-hidden="true" /> {step.title}
              </h3>
              <p>{step.text}</p>
            </div>
          </li>
        ))}
      </ol>
    </section>
  );
}
