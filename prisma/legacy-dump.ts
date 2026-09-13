/**
 * Minimal reader for the archived phpMyAdmin dump (legacy/businesshesap.sql).
 * Only the content tables (faqs, legal_pages, settings) are still valid; the
 * catalog rows in that dump are July 2025 demo data and must not be imported.
 */

export type DumpRow = Readonly<Record<string, string | null>>;

const QUOTE = "'";
const ESCAPE = "\\";

const ESCAPE_MAP: Readonly<Record<string, string>> = {
  n: "\n",
  r: "\r",
  t: "\t",
  "0": "\0",
  Z: "\u001a",
  b: "\b",
};

/** Reads a single-quoted MySQL string starting at the opening quote. */
function readQuoted(sql: string, start: number): { value: string; next: number } {
  let value = "";
  let index = start + 1;

  while (index < sql.length) {
    const char = sql[index];

    if (char === ESCAPE) {
      const escaped = sql[index + 1];
      value += ESCAPE_MAP[escaped] ?? escaped;
      index += 2;
      continue;
    }

    if (char === QUOTE) {
      // Doubled quote ('') is a literal quote, not a terminator.
      if (sql[index + 1] === QUOTE) {
        value += QUOTE;
        index += 2;
        continue;
      }
      return { value, next: index + 1 };
    }

    value += char;
    index += 1;
  }

  throw new Error("Unterminated string literal in dump");
}

function readColumns(sql: string, start: number): { columns: string[]; next: number } {
  const open = sql.indexOf("(", start);
  const close = sql.indexOf(")", open);

  if (open === -1 || close === -1) {
    throw new Error("Malformed INSERT column list in dump");
  }

  const columns = sql
    .slice(open + 1, close)
    .split(",")
    .map((column) => column.trim().replace(/`/g, ""));

  return { columns, next: close + 1 };
}

/** Parses every row of the first `INSERT INTO <table>` statement in the dump. */
export function readDumpTable(sql: string, table: string): DumpRow[] {
  const marker = `INSERT INTO \`${table}\` (`;
  const markerIndex = sql.indexOf(marker);

  if (markerIndex === -1) {
    return [];
  }

  const { columns, next: afterColumns } = readColumns(sql, markerIndex);
  const valuesIndex = sql.indexOf("VALUES", afterColumns);

  if (valuesIndex === -1) {
    throw new Error(`Missing VALUES clause for table ${table}`);
  }

  const rows: DumpRow[] = [];
  let index = valuesIndex + "VALUES".length;
  let values: (string | null)[] = [];
  let raw = "";
  let insideTuple = false;

  const pushValue = (): void => {
    const trimmed = raw.trim();
    if (trimmed.length > 0) {
      values.push(trimmed === "NULL" ? null : trimmed);
    }
    raw = "";
  };

  const pushRow = (): void => {
    pushValue();
    const row: Record<string, string | null> = {};
    columns.forEach((column, position) => {
      row[column] = values[position] ?? null;
    });
    rows.push(row);
    values = [];
  };

  while (index < sql.length) {
    const char = sql[index];

    if (char === QUOTE) {
      const { value, next } = readQuoted(sql, index);
      values.push(value);
      raw = "";
      index = next;
      continue;
    }

    if (char === "(") {
      insideTuple = true;
      index += 1;
      continue;
    }

    if (char === ")" && insideTuple) {
      pushRow();
      insideTuple = false;
      index += 1;
      continue;
    }

    if (char === "," && insideTuple) {
      pushValue();
      index += 1;
      continue;
    }

    if (char === ";" && !insideTuple) {
      break;
    }

    if (insideTuple) {
      raw += char;
    }

    index += 1;
  }

  return rows;
}

/** Detects UTF-8 bytes that were stored as latin1 (e.g. "PolitikasÄ±"). */
const MOJIBAKE_PATTERN = /[\u00c2-\u00c5][\u0080-\u00bf]/;

export function repairMojibake(value: string): string {
  if (!MOJIBAKE_PATTERN.test(value)) {
    return value;
  }

  const decoded = Buffer.from(value, "latin1").toString("utf8");
  return decoded.includes("\ufffd") ? value : decoded;
}

export function toInt(value: string | null, fallback: number): number {
  if (value === null) {
    return fallback;
  }
  const parsed = Number.parseInt(value, 10);
  return Number.isNaN(parsed) ? fallback : parsed;
}

export function toBoolean(value: string | null, fallback: boolean): boolean {
  if (value === null) {
    return fallback;
  }
  return value === "1" || value.toLowerCase() === "true";
}
