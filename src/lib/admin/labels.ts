export const TICKET_STATUS_LABELS: Readonly<Record<string, string>> = {
  open: "Açık",
  in_progress: "İşlemde",
  waiting_customer: "Müşteri bekleniyor",
  resolved: "Çözüldü",
  closed: "Kapalı",
};

export const TICKET_PRIORITY_LABELS: Readonly<Record<string, string>> = {
  low: "Düşük",
  medium: "Orta",
  high: "Yüksek",
  urgent: "Acil",
};

export const TICKET_STATUS_BADGES: Readonly<Record<string, string>> = {
  open: "warning",
  in_progress: "info",
  waiting_customer: "info",
  resolved: "success",
  closed: "danger",
};
