export type WeeklySample = {
  subject_line?: string;
  preview_text?: string;
  intro_copy?: string;
  latest_books?: Array<Record<string, unknown>>;
  recommended_book?: Record<string, unknown>;
  support_link?: Record<string, unknown>;
  newsletter_sneak_peek?: Record<string, unknown>;
};

export type RenderOptions = {
  emailLabel?: string;
  supportLabel?: string;
  showTease?: boolean;
};

function escapeHtml(value: unknown) {
  return String(value || "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

export function renderWeeklyEmailHtml(sample: WeeklySample = {}, options: RenderOptions = {}) {
  const recommended = (sample.recommended_book || {}) as Record<string, unknown>;
  const support = (sample.support_link || {}) as Record<string, unknown>;
  const latestBooks = Array.isArray(sample.latest_books) ? sample.latest_books.slice(0, 3) : [];
  const tease = (sample.newsletter_sneak_peek || {}) as Record<string, unknown>;
  const starterLinks = Array.isArray(support.links) ? support.links.slice(0, 6) : [];

  function htmlBookList() {
    if (!latestBooks.length) return "";
    return latestBooks.map((book) => {
      const title = String(book.book_title || book.title || "");
      const author = String(book.author || "");
      const cover = String(book.cover || "");
      return [
        "<tr>",
        '<td style="padding:0 0 12px;">',
        '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#171515;border:1px solid rgba(255,255,255,0.08);border-radius:8px;">',
        "<tr>",
        '<td style="width:68px;padding:10px;vertical-align:top;">',
        cover
          ? `<img src="${escapeHtml(cover)}" alt="${escapeHtml(title)}" width="48" style="display:block;width:48px;height:68px;object-fit:cover;border-radius:6px;">`
          : '<div style="width:48px;height:68px;line-height:68px;text-align:center;border-radius:6px;background:#201a1d;color:#ff8ac7;font-size:18px;">♡</div>',
        "</td>",
        '<td style="padding:12px 12px 12px 0;vertical-align:middle;">',
        `<div style="color:#ffffff;font-size:16px;line-height:1.35;font-weight:700;">${escapeHtml(title || "saved book")}</div>`,
        author ? `<div style="margin-top:4px;color:#b7b1ae;font-size:13px;line-height:1.45;">${escapeHtml(author)}</div>` : "",
        "</td>",
        "</tr>",
        "</table>",
        "</td>",
        "</tr>",
      ].join("");
    }).join("");
  }

  function htmlSupport() {
    if (starterLinks.length) {
      return starterLinks.map((item: any) => {
        return [
          "<tr>",
          '<td style="padding:0 0 10px;">',
          `<div style="color:#ffffff;font-size:15px;line-height:1.4;font-weight:700;">${item.emoji ? `${escapeHtml(item.emoji)} ` : ""}${escapeHtml(item.title || "")}</div>`,
          item.description ? `<div style="margin-top:4px;color:#cfc8c5;font-size:14px;line-height:1.6;">${escapeHtml(item.description)}</div>` : "",
          item.url ? `<div style="margin-top:6px;"><a href="${escapeHtml(item.url)}" style="color:#ff8ac7;text-decoration:underline;">open link</a></div>` : "",
          "</td>",
          "</tr>",
        ].join("");
      }).join("");
    }
    if (support.title) {
      return [
        "<tr><td>",
        `<div style="color:#ffffff;font-size:16px;line-height:1.4;font-weight:700;">${escapeHtml(support.title)}</div>`,
        support.description ? `<div style="margin-top:4px;color:#cfc8c5;font-size:14px;line-height:1.6;">${escapeHtml(support.description)}</div>` : "",
        support.url
          ? `<div style="margin-top:12px;"><a href="${escapeHtml(support.url)}" style="display:inline-block;min-height:38px;line-height:38px;padding:0 16px;border-radius:999px;background:#ff8ac7;color:#140d11;font-size:12px;letter-spacing:0.14em;text-transform:uppercase;font-weight:800;text-decoration:none;">open it</a></div>`
          : "",
        "</td></tr>",
      ].join("");
    }
    return '<tr><td style="color:#b7b1ae;font-size:14px;line-height:1.6;">supporting click not set yet.</td></tr>';
  }

  return [
    "<!doctype html>",
    '<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>',
    '<body style="margin:0;padding:0;background:#060606;color:#f7f3ee;font-family:Georgia, serif;">',
    '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#060606;">',
    '<tr><td align="center" style="padding:32px 16px;">',
    '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;max-width:640px;background:#0f0d0d;border:1px solid rgba(255,255,255,0.08);border-radius:12px;overflow:hidden;">',
    '<tr><td style="padding:18px 24px;border-bottom:1px solid rgba(255,255,255,0.08);background:linear-gradient(180deg, rgba(255,138,199,0.08), rgba(255,138,199,0));">',
    '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>',
    '<td style="color:#ffffff;font-size:18px;line-height:1.2;font-weight:700;">bybookishbabe</td>',
    `<td align="right" style="color:#ffb8dc;font-size:11px;line-height:1;letter-spacing:0.14em;text-transform:uppercase;font-weight:700;">${escapeHtml(options.emailLabel || "email preview")}</td>`,
    "</tr></table>",
    "</td></tr>",
    '<tr><td style="padding:24px;">',
    `<div style="color:#ffffff;font-size:28px;line-height:1.2;font-weight:700;">${escapeHtml(sample.subject_line || "")}</div>`,
    sample.preview_text ? `<div style="margin-top:8px;color:#b7b1ae;font-size:14px;line-height:1.6;">${escapeHtml(sample.preview_text)}</div>` : "",
    sample.intro_copy ? `<div style="margin-top:20px;color:#e7e1de;font-size:16px;line-height:1.7;">${escapeHtml(sample.intro_copy)}</div>` : "",
    latestBooks.length ? '<div style="margin-top:24px;color:#ff8ac7;font-size:11px;line-height:1;letter-spacing:0.16em;text-transform:uppercase;font-weight:700;">your latest shelf picks</div>' : "",
    latestBooks.length ? `<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:14px;border-collapse:collapse;">${htmlBookList()}</table>` : "",
    (recommended.title || recommended.book_title)
      ? [
        '<div style="margin-top:24px;padding:18px;border:1px solid rgba(255,255,255,0.08);border-radius:10px;background:#151313;">',
        '<div style="color:#ff8ac7;font-size:11px;line-height:1;letter-spacing:0.16em;text-transform:uppercase;font-weight:700;">your next read</div>',
        `<div style="margin-top:10px;color:#ffffff;font-size:24px;line-height:1.2;font-weight:700;">${escapeHtml(recommended.title || recommended.book_title || "")}</div>`,
        recommended.author ? `<div style="margin-top:6px;color:#b7b1ae;font-size:14px;line-height:1.5;">${escapeHtml(recommended.author)}</div>` : "",
        recommended.reason ? `<div style="margin-top:10px;color:#e7e1de;font-size:15px;line-height:1.7;">${escapeHtml(recommended.reason)}</div>` : "",
        recommended.url
          ? `<div style="margin-top:14px;"><a href="${escapeHtml(recommended.url)}" style="display:inline-block;min-height:40px;line-height:40px;padding:0 18px;border-radius:999px;background:#ff8ac7;color:#140d11;font-size:12px;letter-spacing:0.14em;text-transform:uppercase;font-weight:800;text-decoration:none;">open in the library</a></div>`
          : "",
        "</div>",
      ].join("")
      : "",
    '<div style="margin-top:24px;padding:18px;border:1px solid rgba(255,255,255,0.08);border-radius:10px;background:#151313;">',
    `<div style="color:#ff8ac7;font-size:11px;line-height:1;letter-spacing:0.16em;text-transform:uppercase;font-weight:700;">${escapeHtml(options.supportLabel || "go deeper")}</div>`,
    `<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;border-collapse:collapse;">${htmlSupport()}</table>`,
    "</div>",
    options.showTease
      ? [
        '<div style="margin-top:24px;padding:18px;border:1px solid rgba(255,138,199,0.24);border-radius:10px;background:rgba(255,138,199,0.08);">',
        '<div style="color:#ff8ac7;font-size:11px;line-height:1;letter-spacing:0.16em;text-transform:uppercase;font-weight:700;">inside the society</div>',
        `<div style="margin-top:10px;color:#ffffff;font-size:20px;line-height:1.3;font-weight:700;">${escapeHtml(tease.title || "waiting for your friday-morning note")}</div>`,
        tease.body ? `<div style="margin-top:8px;color:#f2e9ee;font-size:15px;line-height:1.7;">${escapeHtml(tease.body)}</div>` : "",
        "</div>",
      ].join("")
      : "",
    "</td></tr>",
    "</table>",
    "</td></tr>",
    "</table>",
    "</body></html>",
  ].join("");
}
