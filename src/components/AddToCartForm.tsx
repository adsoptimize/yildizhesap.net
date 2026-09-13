"use client";

import Link from "next/link";
import { useActionState } from "react";
import { addToCartAction } from "@/app/(storefront)/sepet/actions";
import { INITIAL_CART_STATE } from "@/lib/shop/form-state";
import { CART_PATH, MAX_ITEM_QUANTITY } from "@/lib/shop/constants";

type AddToCartFormProps = {
  accountId: number;
  availableStock: number;
  /** Product detail pages show a quantity picker and a buy-now button. */
  variant: "detail" | "compact";
};

export function AddToCartForm({
  accountId,
  availableStock,
  variant,
}: AddToCartFormProps) {
  const [state, formAction, pending] = useActionState(
    addToCartAction,
    INITIAL_CART_STATE,
  );

  if (availableStock <= 0) {
    return (
      <div className="shop-alert info">
        Bu hesap şu anda tükendi. Stok girildiğinde tekrar satışa açılacak.
      </div>
    );
  }

  const maxQuantity = Math.min(MAX_ITEM_QUANTITY, availableStock);

  if (variant === "compact") {
    return (
      <form action={formAction}>
        <input type="hidden" name="accountId" value={accountId} />
        <input type="hidden" name="quantity" value={1} />
        <button type="submit" className="shop-btn accent" disabled={pending}>
          <i className="fas fa-cart-plus" /> {pending ? "Ekleniyor..." : "Sepete Ekle"}
        </button>
      </form>
    );
  }

  return (
    <form action={formAction}>
      {state.error === null ? null : (
        <div className="shop-alert error">{state.error}</div>
      )}
      {state.success === null ? null : (
        <div className="shop-alert success">
          {state.success}{" "}
          <Link href={CART_PATH} className="shop-link">
            Sepete git
          </Link>
        </div>
      )}

      <input type="hidden" name="accountId" value={accountId} />

      <div className="quantity-selector">
        <label htmlFor={`quantity-${accountId}`}>Adet:</label>
        <div className="shop-qty">
          <input
            id={`quantity-${accountId}`}
            name="quantity"
            type="number"
            min={1}
            max={maxQuantity}
            defaultValue={1}
          />
          <span className="shop-hint">Stok: {availableStock}</span>
        </div>
      </div>

      <div className="purchase-buttons">
        <button
          type="submit"
          name="goToCheckout"
          value="1"
          className="btn-buy-now"
          disabled={pending}
        >
          <i className="fas fa-bolt" /> Hemen Satın Al
        </button>
        <button type="submit" className="btn-add-cart" disabled={pending}>
          <i className="fas fa-cart-plus" /> Sepete Ekle
        </button>
      </div>
    </form>
  );
}
