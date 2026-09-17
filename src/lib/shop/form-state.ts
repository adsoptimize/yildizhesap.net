/**
 * Form state shapes for the storefront server actions. They live outside the
 * "use server" modules because those files may only export async functions.
 */

export type CustomerFormState = {
  error: string | null;
  success: string | null;
};

export const INITIAL_CUSTOMER_FORM_STATE: CustomerFormState = {
  error: null,
  success: null,
};

export type CheckoutState = {
  error: string | null;
};

export const INITIAL_CHECKOUT_STATE: CheckoutState = { error: null };

export type CustomerLoginState = {
  error: string | null;
};

export const INITIAL_LOGIN_STATE: CustomerLoginState = { error: null };

export type RegisterState = {
  error: string | null;
};

export const INITIAL_REGISTER_STATE: RegisterState = { error: null };

export type CartFormState = {
  error: string | null;
  success: string | null;
};

export const INITIAL_CART_STATE: CartFormState = {
  error: null,
  success: null,
};

export type TrackedCredential = {
  id: number;
  username: string;
  password: string;
  email: string | null;
  emailPassword: string | null;
  totpSecret: string | null;
};

export type TrackedOrder = {
  orderCode: string;
  productName: string;
  quantity: number;
  totalPrice: string;
  status: string;
  deliveryStatus: string;
  paymentStatus: string | null;
  createdAt: string;
  credentials: TrackedCredential[];
  /**
   * Carried on the order so the tracking page can stay statically rendered:
   * the action already queries the database, the page would have to become
   * dynamic just to read this one setting.
   */
  telegramUsername: string | null;
};

export type TrackState = {
  error: string | null;
  order: TrackedOrder | null;
};

export const INITIAL_TRACK_STATE: TrackState = { error: null, order: null };
