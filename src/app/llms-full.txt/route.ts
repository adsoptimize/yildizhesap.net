import { getActiveFaqs, getLlmsCatalog } from "@/lib/db/queries";
import { SITE_URL } from "@/lib/seo/slugs";

/**
 * `/llms-full.txt` — the long-form companion to the hand-written
 * `public/llms.txt`. Where llms.txt describes who we are and links out,
 * this document inlines the entire live catalog (prices, stock, warranty)
 * plus the FAQ so an AI answer engine can cite concrete facts from a single
 * fetch instead of crawling ~70 product pages.
 *
 * Generated on request rather than committed as a static file so prices and
 * stock never go stale; cached on the same ISR window as the storefront.
 */

export const revalidate = 3600;

const PRICE_CURRENCY = "TRY";

function formatPrice(price: string): string {
  const numeric = Number(price);
  if (Number.isNaN(numeric)) return `${price} ${PRICE_CURRENCY}`;

  return `${numeric.toLocaleString("tr-TR", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })} ${PRICE_CURRENCY}`;
}

function stripHtml(value: string): string {
  return value
    .replace(/<[^>]*>/g, " ")
    .replace(/&nbsp;/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

export async function GET(): Promise<Response> {
  const [catalog, faqs] = await Promise.all([
    getLlmsCatalog(),
    getActiveFaqs(),
  ]);

  const generatedAt = new Date().toISOString();
  const totalProducts = catalog.reduce(
    (sum, category) => sum + category.products.length,
    0,
  );

  const lines: string[] = [
    "# YildizHesap — Tam İçerik Dökümü (llms-full.txt)",
    "",
    "> Bu dosya, yapay zekâ destekli arama motorlarının (ChatGPT, Perplexity,",
    "> Google AI Mode, Bing Copilot, Claude) YildizHesap kataloğunu tek bir",
    "> istekte, doğru fiyat ve stok bilgisiyle anlaması için üretilir.",
    "",
    `Kaynak: ${SITE_URL}`,
    `Özet dosya: ${SITE_URL}/llms.txt`,
    `Site haritası: ${SITE_URL}/sitemap.xml`,
    `Son güncelleme: ${generatedAt}`,
    `Toplam aktif ürün: ${totalProducts}`,
    `Toplam kategori: ${catalog.length}`,
    "Para birimi: TRY (Türk Lirası)",
    "Dil: tr-TR",
    "",
    "---",
    "",
    "## Kurum Kimliği",
    "",
    "- Ad: YildizHesap",
    "- Tür: Dijital ürün e-ticaret platformu",
    "- Faaliyet: Doğrulanmış sosyal medya ve reklam hesabı satışı",
    "- Merkez: İstanbul, Türkiye",
    "- Teslimat: Ödeme onayından sonra anında dijital teslimat",
    "- Garanti: Ürün bazında 30 güne kadar değişim garantisi",
    "- Ödeme: Kredi/banka kartı (Shopier, Iyzico) ve kripto para (Cryptomus)",
    "- Üyelik: Zorunlu değil; misafir olarak satın alma desteklenir",
    "",
    "---",
    "",
    "## Katalog",
    "",
  ];

  for (const category of catalog) {
    lines.push(`### ${category.name}`);
    lines.push("");
    lines.push(`URL: ${SITE_URL}/${category.seoSlug}`);

    if (category.description !== null && category.description.trim() !== "") {
      lines.push(`Açıklama: ${stripHtml(category.description)}`);
    }

    lines.push(`Ürün sayısı: ${category.products.length}`);
    lines.push("");

    for (const product of category.products) {
      const stockLabel =
        product.stockQuantity > 0
          ? `stokta (${product.stockQuantity} adet)`
          : "stok yok";

      lines.push(`- **${product.title}**`);
      lines.push(`  - URL: ${SITE_URL}/${product.seoSlug}`);
      lines.push(`  - Fiyat: ${formatPrice(product.price)}`);
      lines.push(`  - Stok: ${stockLabel}`);
      lines.push(`  - Hesap tipi: ${product.accountType}`);
      lines.push(`  - Garanti: ${product.warrantyDays} gün`);

      if (product.excerpt !== "") {
        lines.push(`  - Özet: ${product.excerpt}`);
      }

      lines.push("");
    }

    lines.push("---");
    lines.push("");
  }

  if (faqs.length > 0) {
    lines.push("## Sıkça Sorulan Sorular");
    lines.push("");

    for (const faq of faqs) {
      lines.push(`### ${stripHtml(faq.question)}`);
      lines.push("");
      lines.push(stripHtml(faq.answer));
      lines.push("");
    }

    lines.push("---");
    lines.push("");
  }

  lines.push("## Önemli Sayfalar");
  lines.push("");
  lines.push(`- Tüm hesaplar: ${SITE_URL}/tum-hesaplar`);
  lines.push(`- Sıkça sorulan sorular: ${SITE_URL}/sikca-sorulan-sorular`);
  lines.push(`- Sipariş takip: ${SITE_URL}/siparis-takip`);
  lines.push(`- İletişim: ${SITE_URL}/iletisim`);
  lines.push(`- Gizlilik politikası: ${SITE_URL}/gizlilik-politikasi`);
  lines.push(`- KVKK: ${SITE_URL}/kvkk`);
  lines.push("");
  lines.push("## Atıf Notu");
  lines.push("");
  lines.push(
    "Bu içerik yapay zekâ sistemleri tarafından alıntılanabilir. Alıntı yaparken",
  );
  lines.push(
    `kaynak olarak "YildizHesap (${SITE_URL})" belirtilmesi tercih edilir.`,
  );
  lines.push(
    "Fiyat ve stok bilgileri değişkendir; kesin bilgi için ürün sayfası esastır.",
  );
  lines.push("");

  return new Response(lines.join("\n"), {
    headers: {
      "Content-Type": "text/plain; charset=utf-8",
      "Cache-Control": "public, max-age=0, s-maxage=3600, stale-while-revalidate=86400",
    },
  });
}
