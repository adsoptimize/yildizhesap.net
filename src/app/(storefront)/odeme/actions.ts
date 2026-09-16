"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { getCurrentCustomer } from "@/lib/auth/customer";
import { getClientIp } from "@/lib/auth/session";
import { clearCart, getCart } from "@/lib/shop/cart";
import {
  CART_PATH,
  isPaymentMethod,
  PAYMENT_CANCEL_PATH,
  PAYMENT_SUCCESS_PATH,
  SHOPIER_FORM_PATH,
} from "@/lib/shop/constants";
import {
  attachPaymentProviderData,
  createPendingOrder,
  type BuyerInfo,
} from "@/lib/shop/orders";
import { consumeRateLimit, GUEST_CHECKOUT_RULE } from "@/lib/shop/rate-limit";
import { getAbsoluteUrl } from "@/lib/shop/site-url";
import type { CheckoutState } from "@/lib/shop/form-state";
import { createCryptomusInvoice } from "@/lib/payments/cryptomus";
import { initializeCheckoutForm } from "@/lib/payments/iyzico";
import {
  getCryptomusCredentials,
  getIyzicoCredentials,
  getShopierCredentials,
} from "@/lib/payments/settings";

const CRYPTO_INVOICE_CURRENCY = "TRY";
const CRYPTO_SETTLEMENT_CURRENCY = "USDT";
const SHOPIER_CURRENCY = "TRY";
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function readText(formData: FormData, key: string): string {
  return String(formData.get(key) ?? "").trim();
}

async function resolveBuyer(
  formData: FormData,
): Promise<{ buyer: BuyerInfo } | { error: string }> {
  const customer = await getCurrentCustomer();

  if (customer !== null) {
    const profile = await prisma.user.findUnique({
      where: { id: customer.id },
      select: { phone: true },
    });

    return {
      buyer: {
        userId: customer.id,
        fullName: `${customer.firstName} ${customer.lastName}`.trim(),
        email: customer.email,
        phone: profile?.phone ?? null,
      },
    };
  }

  const fullName = readText(formData, "guestName");
  const email = readText(formData, "guestEmail");
  const phone = readText(formData, "guestPhone");

  if (fullName === "") {
    return { error: "Ad soyad alanı zorunludur." };
  }

  if (!EMAIL_PATTERN.test(email)) {
    return { error: "Geçerli bir e-posta adresi girin." };
  }

  if (formData.get("agreeTerms") !== "on") {
    return { error: "Satış koşullarını onaylamanız gerekiyor." };
  }

  const ip = await getClientIp();
  const limit = await consumeRateLimit(ip, GUEST_CHECKOUT_RULE);

  if (!limit.allowed) {
    return { error: limit.message ?? "Çok fazla deneme yaptınız." };
  }

  return {
    buyer: {
      userId: null,
      fullName,
      email,
      phone: phone === "" ? null : phone,
    },
  };
}

export async function startCheckoutAction(
  _previousState: CheckoutState,
  formData: FormData,
): Promise<CheckoutState> {
  const method = readText(formData, "paymentMethod");

  if (!isPaymentMethod(method)) {
    return { error: "Ödeme yöntemi seçmelisiniz." };
  }

  const cart = await getCart();

  if (cart.items.length === 0) {
    return { error: "Sepetiniz boş." };
  }

  const buyerResult = await resolveBuyer(formData);

  if ("error" in buyerResult) {
    return { error: buyerResult.error };
  }

  // Credential lookup is method-specific to keep the union type narrow.
  const credentials = await (async () => {
    if (method === "cryptomus") return getCryptomusCredentials();
    if (method === "shopier") return getShopierCredentials();
    return getIyzicoCredentials();
  })();

  if (credentials === null) {
    return {
      error:
        "Bu ödeme yöntemi şu anda kullanılamıyor. Lütfen diğer yöntemi seçin veya bizimle iletişime geçin.",
    };
  }

  const order = await createPendingOrder({
    cart,
    buyer: buyerResult.buyer,
    paymentMethod: method,
    // Iyzico and Shopier both charge in TRY; only Cryptomus keeps a separate
    // settlement currency (USDT).
    currency:
      method === "cryptomus" ? CRYPTO_INVOICE_CURRENCY : SHOPIER_CURRENCY,
  });

  if (method === "shopier") {
    await clearCart();
    revalidatePath(CART_PATH);
    redirect(`${SHOPIER_FORM_PATH}/${order.orderCode}`);
  }

  if (method === "iyzico") {
    if (!("secretKey" in credentials)) {
      return { error: "Iyzico ödeme ayarları eksik." };
    }

    const callbackUrl = await getAbsoluteUrl("/api/webhooks/iyzico");
    const ip = await getClientIp();

    // Iyzico requires firstName/lastName separately. Fall back to sensible
    // defaults when the customer entered a single-word name.
    const nameParts = buyerResult.buyer.fullName
      .split(" ")
      .filter((part) => part !== "");
    const firstName =
      nameParts.length > 1
        ? nameParts.slice(0, -1).join(" ")
        : nameParts[0] ?? "Musteri";
    const lastName =
      nameParts.length > 1 ? (nameParts[nameParts.length - 1] ?? "") : "Kullanici";

    const initResult = await initializeCheckoutForm(credentials, {
      orderCode: order.orderCode,
      amount: order.totalPrice,
      buyer: {
        id: String(buyerResult.buyer.userId ?? `guest-${order.orderCode}`),
        firstName,
        lastName,
        email: buyerResult.buyer.email,
        phone: buyerResult.buyer.phone,
        ip,
      },
      basketItems: cart.items.map((item) => ({
        id: String(item.accountId),
        name: item.title,
        category: item.categoryName,
        price: item.lineTotal,
      })),
      callbackUrl,
    });

    if (initResult.status !== "success") {
      console.error("Iyzico ödeme başlatılamadı", initResult);
      return {
        error:
          initResult.errorMessage ??
          "Iyzico ödeme sayfası oluşturulamadı. Lütfen tekrar deneyin.",
      };
    }

    // Iyzico posts `token` back on the callback URL, so we don't need to
    // persist it separately. Storing the hosted-page URL keeps parity with
    // the Cryptomus flow for audit/retry surfaces in the admin panel.
    await attachPaymentProviderData(order.orderCode, {
      paymentUrl: initResult.paymentPageUrl,
    });

    await clearCart();
    revalidatePath(CART_PATH);
    redirect(initResult.paymentPageUrl);
  }

  if (!("merchantUuid" in credentials)) {
    return { error: "Kripto ödeme ayarları eksik." };
  }

  const [callbackUrl, successUrl, returnUrl] = await Promise.all([
    getAbsoluteUrl("/api/webhooks/cryptomus"),
    getAbsoluteUrl(`${PAYMENT_SUCCESS_PATH}?order=${order.orderCode}`),
    getAbsoluteUrl(`${PAYMENT_CANCEL_PATH}?order=${order.orderCode}`),
  ]);

  let paymentUrl: string;

  try {
    const invoice = await createCryptomusInvoice(credentials, {
      orderCode: order.orderCode,
      amount: order.totalPrice,
      currency: CRYPTO_INVOICE_CURRENCY,
      toCurrency: CRYPTO_SETTLEMENT_CURRENCY,
      callbackUrl,
      successUrl,
      returnUrl,
    });

    await attachPaymentProviderData(order.orderCode, {
      cryptomusUuid: invoice.uuid,
      paymentUrl: invoice.paymentUrl,
    });

    paymentUrl = invoice.paymentUrl;
  } catch (error: unknown) {
    console.error("Cryptomus ödeme oluşturulamadı", error);
    return {
      error:
        "Kripto ödeme sayfası oluşturulamadı. Lütfen tekrar deneyin veya kredi kartı ile ödeyin.",
    };
  }

  await clearCart();
  revalidatePath(CART_PATH);
  redirect(paymentUrl);
}
