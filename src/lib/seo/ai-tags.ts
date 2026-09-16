/**
 * Non-standard meta tags read by AI answer engines (ChatGPT, Perplexity,
 * Google's AI Mode, Bing Copilot). They are additive hints that summarise
 * the page's intent, audience, and topical scope. Legacy PHP emitted these
 * for every page — we replicate the behaviour here.
 *
 * Usage from a `generateMetadata()`:
 *
 *   return { ..., other: aiMeta({ intent: "...", audience: "...", ... }) };
 */

export type AiMeta = {
  intent: string;
  audience?: string;
  summary?: string;
  category?: string;
  contentTone?: string;
  topicTags?: string;
  visualContent?: string;
  readerInterest?: string;
};

export function aiMeta(input: AiMeta): Readonly<Record<string, string>> {
  const output: Record<string, string> = {
    "ai-intent": input.intent,
  };

  if (input.audience !== undefined) output["audience"] = input.audience;
  if (input.summary !== undefined) output["summary"] = input.summary;
  if (input.category !== undefined) output["category"] = input.category;
  if (input.contentTone !== undefined)
    output["content-tone"] = input.contentTone;
  if (input.topicTags !== undefined) output["topic-tags"] = input.topicTags;
  if (input.visualContent !== undefined)
    output["visual-content"] = input.visualContent;
  if (input.readerInterest !== undefined)
    output["reader-interest"] = input.readerInterest;

  return output;
}

/** Home defaults — locked to the legacy site copy. */
export const HOME_AI_META: AiMeta = {
  intent:
    "Facebook reklam hesabı satın al ile sosyal medya hesaplarını güvenli ve hızlı şekilde edinmek isteyen kullanıcıları bilgilendirme",
  audience:
    "sosyal medya kullanıcıları, dijital pazarlamacılar, e-ticaret girişimcileri",
  summary:
    "YildizHesap, doğrulanmış Facebook, Instagram, TikTok, Telegram ve Business Manager hesaplarını anında dijital teslimatla satan Türkiye merkezli e-ticaret platformudur.",
  category: "sosyal medya hesap satışı, dijital hesap pazarı",
  contentTone: "profesyonel, satış odaklı, güven veren",
  topicTags:
    "facebook hesap satın al, business manager, instagram hesap, doğrulanmış hesap, hızlı teslimat",
  visualContent: "hesap kategorileri, logo, güvenlik rozetleri",
  readerInterest: "güvenli hesap satın alma, hızlı teslimat, 30 gün garanti",
};

export const ALL_ACCOUNTS_AI_META: AiMeta = {
  intent:
    "premium sosyal medya hesap kataloğunu inceleyip doğrulanmış Facebook, Instagram, Business Manager, TikTok, Telegram ve mail hesaplarını satın almak isteyen kullanıcıları bilgilendirme",
  audience:
    "reklam veren, sosyal medya yöneticisi, dijital pazarlamacı, e-ticaret girişimcisi",
  summary:
    "Doğrulanmış premium hesapların tüm kategorilerdeki fiyat, stok ve garanti bilgileri.",
  category: "sosyal medya hesap satışı",
  topicTags:
    "premium hesap, doğrulanmış hesap, hızlı teslimat, 30 gün garanti, kategori",
};

/**
 * Static informational pages. Keyed by SEO slug so `generateMetadata()` can
 * look one up directly. Legal pages share a single preset because their
 * intent is identical from an answer-engine perspective.
 */
export const STATIC_PAGE_AI_META: Readonly<Record<string, AiMeta>> = {
  "sikca-sorulan-sorular": {
    intent:
      "hesap satın alma, teslimat süresi, garanti kapsamı, ödeme yöntemleri ve iade koşulları hakkındaki soruları yanıtlama",
    audience:
      "hesap satın almayı düşünen kullanıcılar, ilk kez alışveriş yapanlar, reklam verenler",
    summary:
      "YildizHesap üzerinden hesap satın alma süreci, anında dijital teslimat, 30 gün garanti, ödeme yöntemleri ve destek kanalları hakkında resmi soru-cevap listesi.",
    category: "destek, sıkça sorulan sorular, satın alma rehberi",
    contentTone: "bilgilendirici, net, güven veren",
    topicTags:
      "hesap satın alma, teslimat süresi, garanti, ödeme yöntemleri, iade, destek",
    readerInterest:
      "satın alma adımları, teslimat hızı, garanti şartları, ödeme güvenliği",
  },
  hizmetler: {
    intent:
      "YildizHesap'ın sunduğu hesap kategorilerini ve hizmet kapsamını tanıtma",
    audience:
      "dijital pazarlama ajansları, reklam verenler, e-ticaret girişimcileri",
    summary:
      "Facebook, Instagram, Business Manager, TikTok, Telegram ve mail hesapları dahil tüm hizmet kategorilerinin kapsamı ve kullanım alanları.",
    category: "hizmet kataloğu, hesap kategorileri",
    contentTone: "profesyonel, kurumsal",
    topicTags:
      "hesap kategorileri, business manager, reklam hesabı, sosyal medya hesabı, hizmetler",
    readerInterest: "hangi hesap türleri sunuluyor, kullanım alanları",
  },
  iletisim: {
    intent:
      "YildizHesap destek ekibine WhatsApp, Telegram, e-posta veya iletişim formu üzerinden ulaşma yollarını gösterme",
    audience: "mevcut müşteriler, satın alma öncesi soru soran kullanıcılar",
    summary:
      "YildizHesap 7/24 destek kanalları: WhatsApp, Telegram, e-posta ve web iletişim formu. Çalışma saatleri ve yanıt süreleri dahil.",
    category: "iletişim, müşteri desteği",
    contentTone: "yardımcı, erişilebilir",
    topicTags: "iletişim, canlı destek, whatsapp, telegram, e-posta, 7/24",
    readerInterest: "destek kanalları, yanıt süresi, çalışma saatleri",
  },
  "siparis-takip": {
    intent:
      "sipariş kodu ve e-posta ile misafir siparişinin durumunu ve teslim edilen hesap bilgilerini sorgulama",
    audience: "satın alma yapmış müşteriler, misafir alıcılar",
    summary:
      "Üyelik olmadan yapılan siparişlerin durumu sipariş kodu ve e-posta adresi ile sorgulanabilir; teslim edilen hesap bilgileri bu sayfadan indirilir.",
    category: "sipariş takibi, satış sonrası",
    contentTone: "işlevsel, açık",
    topicTags: "sipariş takip, misafir sipariş, sipariş kodu, teslimat durumu",
    readerInterest: "siparişim nerede, hesap bilgilerime nasıl ulaşırım",
  },
};

/** Utility: build an AI intent for a legal / policy page. */
export function legalAiMeta(input: { title: string }): AiMeta {
  const { title } = input;
  return {
    intent: `YildizHesap ${title.toLowerCase()} metnini yasal referans olarak sunma`,
    audience: "müşteriler, denetleyiciler, yasal inceleme yapanlar",
    summary: `${title} — YildizHesap'ın yürürlükteki resmi politika metni.`,
    category: "yasal bilgilendirme, politika",
    contentTone: "resmi, hukuki",
    topicTags: `${title.toLowerCase()}, yasal metin, politika, kvkk, gizlilik`,
    readerInterest: "hak ve yükümlülükler, veri işleme, iade koşulları",
  };
}

/** Utility: build an AI intent for a product detail page. */
export function productAiMeta(input: {
  title: string;
  categoryName: string;
}): AiMeta {
  const { title, categoryName } = input;
  return {
    intent: `${title} satın almak isteyen kullanıcılar için ${categoryName.toLowerCase()} kategorisinde doğrulanmış hesap sunumu`,
    audience:
      "dijital pazarlamacılar, sosyal medya yöneticileri, hesap arayan kullanıcılar",
    summary: `${title} - premium doğrulanmış hesap, anında dijital teslimat, 30 gün garanti.`,
    category: `${categoryName}, hesap satışı`,
    contentTone: "profesyonel, satış odaklı",
    topicTags: `${title.toLowerCase()}, doğrulanmış hesap, premium hesap`,
    visualContent: "hesap detay görseli, özellik tablosu",
    readerInterest: `${title} özellikleri, fiyat, teslimat, garanti`,
  };
}

/** Utility: build an AI intent for a category page. */
export function categoryAiMeta(input: {
  categoryName: string;
}): AiMeta {
  const { categoryName } = input;
  return {
    intent: `${categoryName} satın almak isteyen kullanıcılar için doğrulanmış hesap seçenekleri`,
    audience:
      "dijital pazarlamacılar, sosyal medya yöneticileri, reklam verenler",
    summary: `${categoryName} kategorisinde tüm doğrulanmış hesap seçenekleri, fiyatları ve stok durumları.`,
    category: `${categoryName}, hesap satışı`,
    topicTags: `${categoryName.toLowerCase()}, hesap satın al, doğrulanmış hesap`,
  };
}
