"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import {
  addToCart,
  removeFromCart,
  setCartQuantity,
  clearCart,
  type CartMutationResult,
} from "@/lib/shop/cart";
import {
  CART_PATH,
  CHECKOUT_PATH,
  MAX_ITEM_QUANTITY,
  MIN_ITEM_QUANTITY,
} from "@/lib/shop/constants";

function readQuantity(formData: FormData): number {
  const parsed = Number.parseInt(String(formData.get("quantity") ?? ""), 10);

  if (Number.isNaN(parsed)) {
    return MIN_ITEM_QUANTITY;
  }

  return Math.min(Math.max(parsed, 0), MAX_ITEM_QUANTITY);
}

export async function addToCartAction(
  _previousState: CartMutationResult,
  formData: FormData,
): Promise<CartMutationResult> {
  const accountId = Number.parseInt(String(formData.get("accountId") ?? ""), 10);

  if (Number.isNaN(accountId)) {
    return { error: "Ürün seçilemedi.", success: null };
  }

  const quantity = Math.max(readQuantity(formData), MIN_ITEM_QUANTITY);
  const result = await addToCart(accountId, quantity);

  revalidatePath(CART_PATH);

  if (result.error === null && formData.get("goToCheckout") === "1") {
    redirect(CHECKOUT_PATH);
  }

  return result;
}

export async function updateCartItemAction(formData: FormData): Promise<void> {
  const accountId = Number.parseInt(String(formData.get("accountId") ?? ""), 10);

  if (Number.isNaN(accountId)) {
    return;
  }

  await setCartQuantity(accountId, readQuantity(formData));
  revalidatePath(CART_PATH);
}

export async function removeCartItemAction(formData: FormData): Promise<void> {
  const accountId = Number.parseInt(String(formData.get("accountId") ?? ""), 10);

  if (Number.isNaN(accountId)) {
    return;
  }

  await removeFromCart(accountId);
  revalidatePath(CART_PATH);
}

export async function clearCartAction(): Promise<void> {
  await clearCart();
  revalidatePath(CART_PATH);
}
