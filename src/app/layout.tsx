import type { Metadata, Viewport } from "next";
import { Montserrat, Poppins } from "next/font/google";
import { Analytics } from "@vercel/analytics/next";
import { SpeedInsights } from "@vercel/speed-insights/next";
import { HOME_META, SITE_NAME, SITE_URL } from "@/lib/seo/meta";
import "./globals.css";

/**
 * Self-hosted at build time instead of linked from fonts.googleapis.com.
 *
 * The CDN version cost two render-blocking round trips to third-party origins
 * before any text could paint; serving the files from our own domain removes
 * both and measurably improves LCP, which feeds Core Web Vitals.
 *
 * `latin-ext` is required, not optional: the Turkish glyphs ğ, ş, ı and İ live
 * there, and omitting it would fall back to a system font mid-word.
 *
 * The weights match what /public/css/style.css actually uses. Adding more
 * would ship unused font files.
 */
const poppins = Poppins({
  subsets: ["latin", "latin-ext"],
  weight: ["400", "500", "600", "700", "800"],
  display: "swap",
  variable: "--font-poppins",
});

/** Headings only (h1–h6 in the legacy stylesheet), hence the heavy weights. */
const montserrat = Montserrat({
  subsets: ["latin", "latin-ext"],
  weight: ["700", "800", "900"],
  display: "swap",
  variable: "--font-montserrat",
});

/**
 * Brand theme color drives the mobile browser chrome (Android address bar,
 * iOS PWA status bar) and is a light-weight trust signal for mobile SERPs.
 * Matches the orange used across the storefront light theme.
 */
export const viewport: Viewport = {
  themeColor: "#EE5A24",
  width: "device-width",
  initialScale: 1,
};

export const metadata: Metadata = {
  metadataBase: new URL(SITE_URL),
  title: {
    default: HOME_META.title,
    template: `%s | YildizHesap`,
  },
  description: HOME_META.description,
  openGraph: {
    type: "website",
    locale: "tr_TR",
    siteName: SITE_NAME,
    title: HOME_META.title,
    description: HOME_META.description,
    url: SITE_URL,
    images: [{ url: "/images/logo.png" }],
  },
  twitter: {
    card: "summary_large_image",
    title: HOME_META.title,
    description: HOME_META.description,
    // Explicit dimensions so X/Twitter renders the large card instead of
    // falling back to the small summary layout.
    images: [
      {
        url: "/images/logo.png",
        width: 1200,
        height: 630,
        alt: SITE_NAME,
      },
    ],
  },
  robots: {
    index: true,
    follow: true,
  },
  icons: {
    icon: "/images/favicon.png",
  },
  other: {
    // Explicit AI opt-in. Not a ratified standard, but GPTBot/ClaudeBot
    // operators document reading these alongside robots.txt.
    "ai-training": "allow",
    "content-language": "tr-TR",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="tr-TR"
      className={`${poppins.variable} ${montserrat.variable}`}
    >
      <head>
        <link
          rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        />
        <link rel="stylesheet" href="/css/style.css" />
      </head>
      <body>
        {children}
        <Analytics />
        <SpeedInsights />
      </body>
    </html>
  );
}
