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
