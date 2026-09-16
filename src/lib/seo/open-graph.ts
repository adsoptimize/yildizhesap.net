import type { Metadata } from "next";
import { SITE_NAME, SITE_URL } from "./slugs";

/**
 * Per-page OpenGraph + Twitter card builder.
 *
 * Without this every route inherits the root layout's homepage card, so a
 * shared product link renders the generic homepage title. Answer engines and
 * social previews both read these tags, so keeping them page-specific is the
 * cheapest accuracy win available.
 */

const DEFAULT_IMAGE = "/images/mockup.png";
const LOGO_IMAGE = "/images/logo.png";

/** Twitter/X and Facebook both want a 1.91:1 card; these match our assets. */
const IMAGE_WIDTH = 1200;
const IMAGE_HEIGHT = 630;

export type OgInput = {
  title: string;
  description: string;
  /** Path without leading slash, e.g. `facebook-hesaplari`. */
  slug: string;
  /** `article` suits legal and policy pages. Defaults to `website`. */
  type?: "website" | "article";
  image?: string;
};

export function pageOpenGraph(input: OgInput): Pick<
  Metadata,
  "openGraph" | "twitter"
> {
  const { title, description, slug, type = "website", image } = input;
  const imageUrl = image ?? (type === "website" ? LOGO_IMAGE : DEFAULT_IMAGE);
  const url = slug === "" ? SITE_URL : `${SITE_URL}/${slug}`;

  const images = [
    {
      url: imageUrl,
      width: IMAGE_WIDTH,
      height: IMAGE_HEIGHT,
      alt: title,
    },
  ];

  return {
    openGraph: {
      type,
      locale: "tr_TR",
      siteName: SITE_NAME,
      title,
      description,
      url,
      images,
    },
    twitter: {
      card: "summary_large_image",
      title,
      description,
      images,
    },
  };
}

/**
 * Price and availability facts for a product page.
 *
 * Deliberately *not* modelled as OpenGraph `product:*` tags: Next.js renders
 * everything in `metadata.other` with a `name` attribute, while the OG
 * protocol requires `property`. Emitting `name="og:type"` would be ignored by
 * Facebook while conflicting with the real `property="og:type"` tag.
 *
 * Google already reads price and stock from the Product JSON-LD, so these
 * exist for the AI answer engines that parse the non-standard `name` meta set
 * alongside our other `ai-*` hints.
 */
export function productFactMeta(input: {
  priceTry: number;
  stockQuantity: number;
}): Readonly<Record<string, string>> {
  return {
    price: input.priceTry.toFixed(2),
    "price-currency": "TRY",
    availability: input.stockQuantity > 0 ? "in stock" : "out of stock",
    "stock-count": String(input.stockQuantity),
  };
}
