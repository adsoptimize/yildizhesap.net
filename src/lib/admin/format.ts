const TRY_FORMATTER = new Intl.NumberFormat("tr-TR", {
  style: "currency",
  currency: "TRY",
  maximumFractionDigits: 2,
});

const NUMBER_FORMATTER = new Intl.NumberFormat("tr-TR");

const DATE_TIME_FORMATTER = new Intl.DateTimeFormat("tr-TR", {
  dateStyle: "short",
  timeStyle: "short",
});

const DATE_FORMATTER = new Intl.DateTimeFormat("tr-TR", { dateStyle: "medium" });

export function formatCurrency(value: string | number): string {
  return TRY_FORMATTER.format(Number(value));
}

export function formatNumber(value: number): string {
  return NUMBER_FORMATTER.format(value);
}

export function formatDateTime(value: Date | null): string {
  return value === null ? "-" : DATE_TIME_FORMATTER.format(value);
}

export function formatDate(value: Date | null): string {
  return value === null ? "-" : DATE_FORMATTER.format(value);
}
