/** Same transliteration rules as legacy setup_seo_urls.php generateSlug(). */

const TURKISH_MAP: Readonly<Record<string, string>> = {
  Ç: "C",
  Ş: "S",
  Ğ: "G",
  Ü: "U",
  İ: "I",
  Ö: "O",
  ç: "c",
  ş: "s",
  ğ: "g",
  ü: "u",
  ö: "o",
  ı: "i",
};

export function slugify(value: string): string {
  return value
    .replace(/[ÇŞĞÜİÖçşğüöı]/g, (char) => TURKISH_MAP[char] ?? char)
    .toLowerCase()
    .replace(/<[^>]*>/g, "")
    .replace(/[^a-z0-9\s-]/g, "")
    .replace(/[\s-]+/g, "-")
    .replace(/^-+|-+$/g, "");
}
