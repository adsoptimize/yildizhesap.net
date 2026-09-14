/**
 * Enriches thin (<200 char) product descriptions with SEO-friendly copy.
 *
 * Only touches active products whose description is shorter than
 * `MIN_DESCRIPTION_LENGTH`. Longer descriptions (scraped from businesshesap)
 * are preserved. Each product gets a bespoke description derived from its
 * title / platform / accountType / location metadata so Google does not see
 * duplicate content. Uses deterministic variation (based on product id) to
 * keep neighboring products textually distinct.
 *
 * Run:
 *   set -a && . ./.env.local && set +a
 *   npx tsx scripts/enrich-thin-descriptions.ts --dry-run   # preview
 *   npx tsx scripts/enrich-thin-descriptions.ts             # apply changes
 */

import { PrismaClient } from "@prisma/client";

const prisma = new PrismaClient();

const MIN_DESCRIPTION_LENGTH = 200;
const DRY_RUN = process.argv.includes("--dry-run");
const PREVIEW_COUNT = 5;

type ProductRow = {
  id: number;
  title: string;
  seoSlug: string | null;
  description: string | null;
  platform: string;
  accountType: string;
  price: unknown;
  location: string | null;
  warrantyDays: number;
  category: { name: string; seoSlug: string | null };
};

// ---------- helpers ---------------------------------------------------------

/** Removes trailing "| ..." suffix from titles and normalises whitespace. */
function cleanTitle(title: string): string {
  return title.replace(/\s*\|\s*.*$/, "").trim();
}

/**
 * Strips a trailing "Satın Al" from a title so we can compose sentences that
 * end with "satın alın" / "satın alabilirsiniz" without double-verb glitches.
 */
function titleWithoutSatinAl(title: string): string {
  return cleanTitle(title).replace(/\s+Sat[ıi]n\s+Al\.?$/i, "").trim();
}

function formatPrice(raw: unknown): string {
  const value = Number(raw);
  return Number.isFinite(value)
    ? `${value.toLocaleString("tr-TR")}₺`
    : "uygun fiyat";
}

// ---------- content generation ---------------------------------------------

/** Pulls a Y-Y year range or a single year (e.g. "2010-2023" or "2015"). */
function extractYearInfo(text: string): string | null {
  const range = text.match(/(20\d{2})\s*[-–]\s*(20\d{2})/);
  if (range !== null) {
    return `${range[1]}-${range[2]} yılları arasında`;
  }
  const single = text.match(/20\d{2}/);
  return single !== null ? `${single[0]} yılında` : null;
}

/** Extracts a country adjective from the product title, if present. */
function extractCountry(text: string): string | null {
  const map: Readonly<Record<string, string>> = {
    vietnam: "Vietnam",
    poland: "Polonya",
    taiwan: "Tayvan",
    usa: "ABD",
    "u\\.?s\\.?a\\.?": "ABD",
    uk: "İngiltere",
    india: "Hindistan",
    türkiye: "Türkiye",
    turk: "Türkiye",
  };
  for (const [pattern, label] of Object.entries(map)) {
    if (new RegExp(pattern, "i").test(text)) return label;
  }
  return null;
}

/** Detects which product family the row belongs to. */
type ProductFamily =
  | "aged_facebook"
  | "verified_facebook"
  | "marketplace"
  | "business_manager"
  | "ads_account"
  | "turkish_facebook"
  | "foreign_facebook"
  | "instagram_followers"
  | "instagram_posts"
  | "instagram_verified"
  | "telegram"
  | "twitter"
  | "tiktok"
  | "mail"
  | "generic";

function detectFamily(product: ProductRow): ProductFamily {
  const t = `${product.title} ${product.accountType}`.toLowerCase();
  const platform = product.platform.toLowerCase();
  if (platform === "instagram") {
    if (/tanıtım|tanitim|onay/.test(t)) return "instagram_verified";
    if (/gönderi|gonderi|gönderili|gonderili/.test(t)) return "instagram_posts";
    return "instagram_followers";
  }
  if (platform === "telegram") return "telegram";
  if (platform === "twitter" || /twitter|x\s/i.test(t)) return "twitter";
  if (platform === "tiktok") return "tiktok";
  if (/mail|gmail|outlook|hotmail/.test(platform + t)) return "mail";
  if (/marketplace/.test(t)) return "marketplace";
  if (/business\s*manager|bm\b/.test(t)) return "business_manager";
  if (/reklam\s*hesab|ads\b/.test(t)) return "ads_account";
  if (/türk\b|turk\b|türk\s*face|1\.paket|2\.paket/.test(t)) return "turkish_facebook";
  if (/yabancı|yabanci|foreign|country|usa|uk|poland|taiwan|india|vietnam/.test(t)) {
    return "foreign_facebook";
  }
  if (/eski|old|aged|reinstated|süper/.test(t)) return "aged_facebook";
  if (/kimlik|verified|doğru|onay/.test(t)) return "verified_facebook";
  return "generic";
}

/** Family-specific features paragraph (2-4 sentences). */
function familyFeatures(family: ProductFamily, product: ProductRow): string {
  // Slug carries locked SEO metadata (year, country, package variant) that the
  // title/accountType may lose. Combine everything so we can extract detail.
  const signalText = `${product.title} ${product.accountType} ${product.seoSlug ?? ""}`;
  const yearInfo = extractYearInfo(signalText);
  const country = extractCountry(signalText);
  // Two-variant switch so neighboring products with the same family don't
  // repeat verbatim. Deterministic by product id.
  const alt = pickVariant(product.id, 2) === 0;

  switch (family) {
    case "aged_facebook":
      if (alt) {
        return [
          `Facebook algoritmasının "aged account" (yaşlı hesap) etiketiyle işaretlediği bu profil ${yearInfo ?? "yıllar önce"} açılmıştır.`,
          "Yeni açılan hesaplara göre çok daha yüksek güven puanına sahiptir; sürekli oturum açma, arkadaş ekleme ve reklam yayınlama işlemlerinde bloke ihtimali dramatik olarak düşer.",
          country !== null
            ? `${country} lokasyonundan aktive edilmiş, IP tutarlılığı korunmuş olarak teslim edilir.`
            : "Hesabın giriş IP geçmişi ve cihaz izleri tutarlı bir şekilde kurulmuştur.",
        ].join(" ");
      }
      return [
        `Bu hesap ${yearInfo ?? "uzun süre önce"} oluşturulmuş, Meta tarafından "aged" (yaşlı) olarak sınıflandırılan bir Facebook profilidir.`,
        "Business Manager kurulumu, reklam yayını ve limit aşımı içeren işlemlerde yeni hesaplara kıyasla önemli avantaj sağlar.",
        country !== null
          ? `Coğrafi profili ${country} olarak tutarlıdır; VPN dönüşümlerine dayanıklıdır.`
          : "Cihaz parmak izi ve tarayıcı geçmişi Meta'nın güven skorunu yükseltecek şekilde ayarlanmıştır.",
      ].join(" ");

    case "verified_facebook":
      if (alt) {
        return [
          "Meta'nın kimlik doğrulama sistemine gerçek belge ile kayıt yapılmış olan hesap, reklam politikası kısıtlamalarını aşabilir.",
          "İki adımlı doğrulama (2FA) hazır, güvenlik soruları tanımlı, kurtarma e-postası eklenmiş olarak teslim edilir.",
          "Kritik pazarlama kampanyalarında hesap bloke riski minimum, harcama limiti yükseltme olasılığı maksimumdur.",
        ].join(" ");
      }
      return [
        "Hesap kimlik onayı yapılmış Facebook profilidir; bu sayede yüksek harcamalı reklam kampanyaları güvenle yönetilebilir.",
        "Güvenlik katmanı olarak 2FA, yedek telefon ve kurtarma e-postası kurulmuştur.",
        "Meta'nın yıllar içinde artan doğrulama zorunluluklarına uyumludur; reklam hesabı disable riski normal hesaplara göre çok daha düşüktür.",
      ].join(" ");

    case "marketplace":
      return [
        "Facebook Marketplace üzerinde ilan yayınlama, ürün satışı ve mesajlaşma özellikleri tam açık şekilde teslim edilir.",
        "İlan kısıtlaması bulunmayan, geçmişinde satış aktivitesi olan veya olabilecek profil güveniyle kurulmuştur.",
        "Marketplace kısıtlaması nedeniyle mağdur olan satıcılar için hızlı çözümdür.",
      ].join(" ");

    case "business_manager":
      return [
        "Business Manager (BM) yapısı hazır kurulmuş, reklam hesabı bağlanabilir, harcama limiti tanımlanabilir durumdadır.",
        "Meta reklam politikalarına uyumlu, ödeme yöntemi eklenebilen, sayfa yönetimi yapılabilir bir Ads yapısı sunar.",
        "BM üzerinden Instagram sayfa bağlama, WhatsApp Business entegrasyonu ve piksel yönetimi sorunsuz gerçekleşir.",
      ].join(" ");

    case "ads_account":
      return [
        "Reklam hesabı günlük harcama limiti tanımlanmış, ödeme yöntemi eklenmeye hazır ve reklam yayınlamaya uygun durumdadır.",
        "Meta reklam politika ihlali olmayan temiz bir geçmişe sahip, hedefleme ve dönüşüm optimizasyonu için uygundur.",
        "E-ticaret, lead generation ve marka kampanyalarında güvenle kullanılabilir.",
      ].join(" ");

    case "turkish_facebook":
      return [
        "Türkiye kaynaklı IP ve telefon numarasıyla oluşturulmuş, Türk kullanıcı davranış profiline uygun bir hesaptır.",
        "Türkiye pazarında yerel dilde reklam yayınlamak, Türk arkadaş çevresine ulaşmak veya lokal satış yapmak için idealdir.",
        "Türk kimliği ile ek doğrulama talep edildiğinde uyumlu şekilde ilerleyebilir.",
      ].join(" ");

    case "foreign_facebook":
      return [
        country !== null
          ? `Hesap ${country} lokasyonundan açılmış olup uluslararası kampanyalar ve ${country} pazarı için uygun coğrafi profile sahiptir.`
          : "Uluslararası bir lokasyondan açılmış, farklı pazarlarda kullanım için uygun coğrafi profile sahiptir.",
        "Global reklam kampanyaları, dropshipping mağazaları ve çok dilli marketing operasyonları için tercih edilir.",
        "IP ve telefon doğrulaması tutarlı şekilde yapıldığı için ülke değişimi kısıtlamalarına takılmaz.",
      ].join(" ");

    case "instagram_followers":
      return [
        "Gerçek görünümlü takipçilere sahip Instagram hesabı; organik büyüme, marka etkileşimi ve sponsorlu içerik için uygundur.",
        "Hesap profili doldurulmuş, biyografi kurulmuş ve profil fotoğrafı eklenmiş halde teslim edilir.",
        "Reklam yayınlamak veya influencer profili olarak konumlanmak için idealdir.",
      ].join(" ");

    case "instagram_posts":
      return [
        "Gönderi paylaşımı yapılmış, düzenli bir içerik akışına sahip Instagram hesabıdır.",
        "Post feed'i düzenlenmiş, hashtag stratejisiyle içerik yerleştirilmiş şekilde gelir; hemen üzerine paylaşım yapmaya devam edebilirsiniz.",
        "İçerik üretimi yapmadan aktif görünen bir profil elde etmek isteyen kullanıcılar için idealdir.",
      ].join(" ");

    case "instagram_verified":
      return [
        "Instagram tanıtım/reklam onayına sahip özel bir hesap türüdür; sponsorlu paylaşımlar ve markalarla iş birlikleri için uygundur.",
        "Reklam pikselleri, mağaza kurulumu (Instagram Shopping) ve profesyonel içerik üreticisi araçlarını kullanabilirsiniz.",
        "Instagram algoritmasında yüksek görünürlük skoruyla teslim edilir.",
      ].join(" ");

    case "telegram":
      return [
        "Telegram hesabı SMS ile telefon doğrulaması yapılmış, kanal kurma ve mesajlaşma kısıtlaması olmadan teslim edilir.",
        "Kripto para toplulukları, sinyal kanalları ve müşteri destek botları için uygundur.",
        "İki adımlı şifre eklenebilir, oturum güvenliği yüksek düzeyde korunur.",
      ].join(" ");

    case "twitter":
      return [
        "X (Twitter) hesabı gerçek e-posta ve telefon doğrulamasına sahip, tweet atma ve DM gönderme kısıtlaması bulunmayan bir profildir.",
        "Marka takibi, kampanya paylaşımı ve crypto/finans içerik yayını için uygundur.",
      ].join(" ");

    case "tiktok":
      return [
        "TikTok hesabı içerik paylaşımı, reklam yayınlama ve TikTok Shop entegrasyonu için hazır durumdadır.",
        "Algoritma güveni yüksek, video görüntülenme oranı yeni açılan hesaplara göre çok daha stabildir.",
      ].join(" ");

    case "mail":
      return [
        "E-posta hesabı IMAP/POP3 erişimine açık, sosyal medya kayıtlarında güvenle kullanılabilecek şekilde teslim edilir.",
        "Recovery bilgileri (yedek e-posta / telefon) size verilir, hesap üzerinde tam kontrol sağlanır.",
      ].join(" ");

    case "generic":
    default:
      return [
        "Hesap gerçek kullanıcı profiliyle oluşturulmuş, tüm doğrulama süreçleri tamamlanmış olarak teslim edilir.",
        "Sosyal medya kampanyaları, reklam yayını ve topluluk yönetimi için güvenle kullanılabilir.",
      ].join(" ");
  }
}

/** Deterministic 0..(n-1) pick based on product id — keeps variations stable. */
function pickVariant(id: number, n: number): number {
  return Math.abs(id) % n;
}

// Opening sentence pool — one is chosen per product based on id parity.
// All templates receive the title WITHOUT the trailing "Satın Al" suffix so
// composed sentences don't produce "…Satın Al satın almak…" duplication.
const OPENING_TEMPLATES: readonly ((c: {
  clean: string;
  category: string;
  platform: string;
  price: string;
}) => string)[] = [
  ({ clean, category, platform, price }) =>
    `${clean} arıyorsanız YıldızHesap doğru adres. Kategori: ${category.toLocaleLowerCase("tr-TR")}. Platform: ${platform}. Fiyat: ${price}. Ödemeden hemen sonra otomatik teslim.`,
  ({ clean, category, platform, price }) =>
    `${clean} ürünümüz ${category.toLocaleLowerCase("tr-TR")} kategorisinin öne çıkan ${platform} hesaplarından biridir. Şu anda ${price} fiyattan stokta yer alıyor ve anında teslimat garantisiyle sunuluyor.`,
  ({ clean, category, platform, price }) =>
    `${clean} satın almak isteyen kullanıcılar için hazırlanmış bu ${platform} hesabı ${price} fiyatla listelenmiştir. ${category} kategorisinde talep gören popüler bir üründür.`,
  ({ clean, category, platform, price }) =>
    `YıldızHesap kataloğunun ${category.toLocaleLowerCase("tr-TR")} bölümünde yer alan bu ${platform} hesabını (${clean.toLocaleLowerCase("tr-TR")}) sadece ${price} fiyatla ve anında teslimatla satın alabilirsiniz.`,
];

// Closing paragraph pool — mixes trust signals + call-to-action variations.
const CLOSING_TEMPLATES: readonly ((c: {
  categoryLc: string;
  accountTypeLc: string;
  cleanNoBuy: string;
  warranty: number;
}) => string)[] = [
  ({ categoryLc, cleanNoBuy, warranty }) =>
    `Ödeme için kredi kartı (Shopier güvenli ödeme) veya kripto para (Cryptomus – USDT/BTC/ETH) yöntemlerinden birini kullanabilirsiniz. Tüm işlemler SSL şifreli, KVKK uyumlu ve fatura kayıtlıdır. Teslimatın ardından ${warranty} gün boyunca geçerli olan garanti kapsamında ${cleanNoBuy.toLocaleLowerCase("tr-TR")} ürününü ${categoryLc} kategorisinden bugün satın alabilirsiniz.`,
  ({ categoryLc, accountTypeLc, warranty }) => {
    // "eski hesap" + "hesabınızda" tekrarı olmasın: accountType zaten "hesap"
    // ile bitiyorsa doğrudan "türünüzde" ekle.
    const label = /hesap\s*$/i.test(accountTypeLc)
      ? `${accountTypeLc} türünüzde`
      : `${accountTypeLc} hesabınızda`;
    return `Kredi kartı, banka havalesi (Shopier üzerinden) veya kripto para ile ödeme yapabilirsiniz. Cryptomus altyapısı USDT, BTC ve ETH gibi popüler kripto varlıkları destekler. ${warranty} günlük garanti süresi boyunca ${label} yaşanabilecek Meta / platform kaynaklı sorunlar için ücretsiz destek verilir. ${categoryLc} kategorisinden ihtiyacınız olan hesabı hemen sepete ekleyin.`;
  },
  ({ cleanNoBuy, warranty }) =>
    `Alışveriş sonrası ${warranty} gün boyunca garanti kapsamında olan ${cleanNoBuy.toLocaleLowerCase("tr-TR")} ürününde yaşanabilecek erişim, doğrulama veya bloke sorunları için 7/24 canlı destek ekibimiz devreye girer. Shopier (kredi kartı) veya Cryptomus (kripto para) altyapıları ile güvenli ödeme yapabilir, hesabınızı hemen kullanmaya başlayabilirsiniz.`,
  ({ categoryLc, accountTypeLc, cleanNoBuy, warranty }) =>
    `Türkiye'nin güvenilir hesap satış platformu YıldızHesap ile ${categoryLc} bölümündeki ${accountTypeLc} tipinde ${cleanNoBuy.toLocaleLowerCase("tr-TR")} ürününü kolayca satın alın. Ödeme adımından sonra hesap bilgileri panelinizde açılır, ${warranty} gün garanti kapsamındadır ve teknik destek ekibimiz her adımda yanınızdadır.`,
];

/**
 * Composes a bespoke 500-1000 char description that mentions the product
 * uniquely and avoids obvious duplicate content across the catalog.
 */
function buildDescription(product: ProductRow): string {
  // `cleanNoBuy` is used everywhere; `cleanTitle` kept as helper for future
  // callers but not needed here.
  const cleanNoBuy = titleWithoutSatinAl(product.title);
  const category = product.category.name;
  const categoryLc = category.toLocaleLowerCase("tr-TR");
  const platform = product.platform;
  const accountType = product.accountType || "hesap";
  const accountTypeLc = accountType.toLocaleLowerCase("tr-TR");
  const price = formatPrice(product.price);
  const warranty = product.warrantyDays || 30;
  const family = detectFamily(product);

  // Different formulas per slot so neighboring ids don't align on the same
  // opening+closing pair.
  const openingIndex = pickVariant(product.id, OPENING_TEMPLATES.length);
  const closingIndex = pickVariant(
    product.id * 3 + 7,
    CLOSING_TEMPLATES.length,
  );

  const opening = OPENING_TEMPLATES[openingIndex]({
    // Strip trailing "Satın Al" so opening sentences read naturally.
    clean: cleanNoBuy,
    category,
    platform,
    price,
  });
  const features = familyFeatures(family, product);
  // Slug-derived micro-detail (year / country / package #) appended to the
  // features paragraph so products that share a family stay textually distinct.
  const signalText = `${product.title} ${product.accountType} ${product.seoSlug ?? ""}`;
  const yearInfo = extractYearInfo(signalText);
  const country = extractCountry(signalText);
  const packageMatch = (product.seoSlug ?? "").match(/paket[-\s]?(\d+)/i);
  const microDetails: string[] = [];
  // Year is already surfaced by familyFeatures for aged/verified Facebook —
  // only add country / package / 2FA cues here to avoid the same fact twice.
  if (country !== null && !family.includes("foreign") && !family.includes("aged")) {
    microDetails.push(`Hesap doğrulaması ${country} ülkesi kaynaklı yapılmıştır.`);
  }
  if (packageMatch !== null) {
    microDetails.push(
      `Bu, ${packageMatch[1]} numaralı özel paket sürümüdür — farklı numaralı paketler farklı özellik kombinasyonlarına sahiptir.`,
    );
  }
  if (/2fa|2\s*ad[ıi]ml[ıi]/i.test(signalText)) {
    microDetails.push("Hesapta 2FA (iki adımlı doğrulama) aktif olarak teslim edilir.");
  }
  // Explicit "package #" suffix hint for aged products so identical family
  // outputs still differ by slug-derived context (year is baked into features).
  if (microDetails.length === 0 && yearInfo !== null && family !== "aged_facebook") {
    microDetails.push(`Bu paket ${yearInfo} açılan hesapları içerir.`);
  }
  const featuresBlock =
    microDetails.length === 0 ? features : `${features} ${microDetails.join(" ")}`;
  const delivery = `Ödeme onaylandığı anda hesap bilgileri (kullanıcı adı, şifre, kayıtlı e-posta ve gerektiğinde 2FA kodu) hesabım panelinizde listelenir ve ${platform} .txt formatında güvenli şekilde indirilebilir. Manuel bir bekleme süresi yoktur; süreç tamamen otomatik ilerler.`;
  const closing = CLOSING_TEMPLATES[closingIndex]({
    categoryLc,
    accountTypeLc,
    cleanNoBuy,
    warranty,
  });

  return [opening, featuresBlock, delivery, closing].join("\n\n");
}

// ---------- main ------------------------------------------------------------

async function main(): Promise<void> {
  const rows = await prisma.account.findMany({
    where: { status: "active" },
    select: {
      id: true,
      title: true,
      seoSlug: true,
      description: true,
      platform: true,
      accountType: true,
      price: true,
      location: true,
      warrantyDays: true,
      category: { select: { name: true, seoSlug: true } },
    },
    orderBy: { id: "asc" },
  });

  const thin = rows.filter(
    (r) => (r.description ?? "").length < MIN_DESCRIPTION_LENGTH,
  );

  console.log(`Total active: ${rows.length}`);
  console.log(`Thin (<${MIN_DESCRIPTION_LENGTH} char): ${thin.length}`);
  console.log(`Mode: ${DRY_RUN ? "DRY RUN (no writes)" : "LIVE APPLY"}\n`);

  if (thin.length === 0) {
    console.log("Nothing to enrich. Exiting.");
    return;
  }

  if (DRY_RUN) {
    for (const product of thin.slice(0, PREVIEW_COUNT)) {
      const description = buildDescription(product);
      console.log(`\n══════════════════════════════════════════════════════`);
      console.log(`ID ${product.id}: ${product.title}`);
      console.log(`Category: ${product.category.name}`);
      console.log(`Old length: ${(product.description ?? "").length} chars`);
      console.log(`New length: ${description.length} chars`);
      console.log(`──────────────────────────────────────────────────────`);
      console.log(description);
    }
    console.log(
      `\n${thin.length - PREVIEW_COUNT} more products would be enriched. Re-run without --dry-run to apply.`,
    );
    return;
  }

  let updated = 0;
  for (const product of thin) {
    const description = buildDescription(product);
    await prisma.account.update({
      where: { id: product.id },
      data: { description },
    });
    updated += 1;
    console.log(`✓ ${product.id}: ${product.title} (${description.length} chars)`);
  }

  console.log(`\n✅ ${updated} product(s) enriched.`);
}

main()
  .catch((error) => {
    console.error(error);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
