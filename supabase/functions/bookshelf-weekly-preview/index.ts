import { createClient } from "jsr:@supabase/supabase-js@2";
import { renderWeeklyEmailHtml } from "../_shared/bookshelf-weekly-email.ts";

const DEFAULT_TIMEZONE = "America/Los_Angeles";
const DEFAULT_SEND_HOUR = 16;
const DEFAULT_SEND_MINUTE = 0;

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type, x-admin-secret",
  "Access-Control-Allow-Methods": "POST, OPTIONS",
};

const QUIZ_LINK = {
  title: "find your fictional match",
  description: "take the quiz if you want the next rec picked for you.",
  url: "https://bybookishbabe.com/pages/fictional-boyfriend-quiz",
  emoji: "💌",
};

const FORCED_PROXIMITY_URL = "https://bybookishbabe.com/pages/forced-proximity-romance-books";

const CLUSTER_SUPPORT: Record<string, Record<string, unknown>> = {
  stalker: {
    title: "browse all stalker romance books",
    description: "the full trope page for obsessive, dark, fully committed energy.",
    url: "https://bybookishbabe.com/pages/stalker-romance-books",
    emoji: "🔪",
  },
  dark: {
    title: "the dark romance guide with morally gray men",
    description: "everything that hits the same dangerous nerve.",
    url: "https://bybookishbabe.com/blogs/curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you",
    emoji: "🖤",
  },
  darkobsession: {
    title: "dark romance books with morally gray men",
    description: "a full list for readers who want more obsession, menace, and chemistry.",
    url: "https://bybookishbabe.com/blogs/curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you",
    emoji: "🗡️",
  },
  morallygraymen: {
    title: "villain gets the girl",
    description: "the trope page for readers who always root for the wrong man.",
    url: "https://bybookishbabe.com/pages/villain-gets-the-girl-romance-books",
    emoji: "😈",
  },
  sports: {
    title: "forced proximity romance books",
    description: "one click deeper into the chemistry-heavy side of sports romance.",
    url: FORCED_PROXIMITY_URL,
    emoji: "🏒",
  },
  romantasy: {
    title: "the ultimate romantasy reading guide",
    description: "a bigger-world guide for readers who want magic with their longing.",
    url: "https://bybookishbabe.com/blogs/curated-romance-guides/the-ultimate-romantasy-reading-guide",
    emoji: "🔮",
  },
  darkromantasy: {
    title: "dark romantasy",
    description: "more villainous fantasy romance for when pretty danger is the point.",
    url: "https://bybookishbabe.com/blogs/curated-romance-guides/dark-romantasy-books",
    emoji: "👑",
  },
  morallygrayfantasy: {
    title: "villain gets the girl",
    description: "the exact lane for fantasy readers who still want the dangerous man.",
    url: "https://bybookishbabe.com/pages/villain-gets-the-girl-romance-books",
    emoji: "🖤",
  },
  slowburn: {
    title: "slow burn romance books",
    description: "when the payoff needs to take its sweet, devastating time.",
    url: "https://bybookishbabe.com/pages/slow-burn-books",
    emoji: "⏳",
  },
  soft: {
    title: "small town romance books",
    description: "the softer lane when you want devotion with actual heart.",
    url: "https://bybookishbabe.com/pages/small-town-romance-books",
    emoji: "🌷",
  },
  default: QUIZ_LINK,
};

const CLUSTER_RECOMMENDATIONS: Record<string, Record<string, string>> = {
  stalker: {
    title: "Haunting Adeline",
    author: "H.D. Carlton",
    handle: "haunting-adeline",
    reason: "the stalker romance everyone measures the trope against.",
  },
  dark: {
    title: "Corrupt",
    author: "Penelope Douglas",
    handle: "corrupt",
    reason: "morally gray, dangerous, and exactly the wrong man in the right way.",
  },
  darkobsession: {
    title: "Lights Out",
    author: "Navessa Allen",
    handle: "lights-out",
    reason: "the dark, obsessive energy is there immediately and it commits hard.",
  },
  morallygraymen: {
    title: "Bad Bishop",
    author: "Raven Dark",
    handle: "bad-bishop",
    reason: "for when you want menace, power, and chemistry that should probably be illegal.",
  },
  sports: {
    title: "Breaking Point",
    author: "L.J. Shen",
    handle: "breaking-point",
    reason: "sports romance with the fast chemistry and tension-first payoff your shelf keeps picking.",
  },
  romantasy: {
    title: "One Dark Window",
    author: "Rachel Gillig",
    handle: "one-dark-window",
    reason: "bigger world, dangerous magic, and a romance that feels expensive.",
  },
  darkromantasy: {
    title: "The Ever King",
    author: "L.J. Andrews",
    handle: "the-ever-king",
    reason: "dark fantasy pull, villain energy, and the kind of obsession that reshapes a kingdom.",
  },
  morallygrayfantasy: {
    title: "The Ever King",
    author: "L.J. Andrews",
    handle: "the-ever-king",
    reason: "morally gray fantasy energy with a man you know is trouble and choose anyway.",
  },
  slowburn: {
    title: "Powerless",
    author: "Lauren Roberts",
    handle: "powerless",
    reason: "slow-burn tension and payoff that takes its sweet time before it wrecks you.",
  },
  soft: {
    title: "Done and Dusted",
    author: "Lyla Sage",
    handle: "done-and-dusted",
    reason: "tenderness, loyalty, and enough ache to make the softness memorable.",
  },
  default: {
    title: "open the library",
    author: "",
    handle: "",
    reason: "your shelf is still taking shape, so the library is the fastest way to find the right lane.",
  },
};

const EMPTY_SHELF_LINKS = [
  {
    title: "dark romance books",
    description: "start with the full dark list if you want obsessive, morally gray energy.",
    url: "https://bybookishbabe.com/pages/dark-romance-books",
    emoji: "🖤",
  },
  {
    title: "romantasy books",
    description: "bigger world, bigger feelings, and a little royal chaos.",
    url: "https://bybookishbabe.com/pages/romantasy-books",
    emoji: "🔮",
  },
  {
    title: "slow burn romance books",
    description: "for tension first, payoff later, and yearning that ruins your peace.",
    url: "https://bybookishbabe.com/pages/slow-burn-books",
    emoji: "⏳",
  },
  {
    title: "touch her and die",
    description: "the full trope page for possessive protection and dangerous devotion.",
    url: "https://bybookishbabe.com/pages/touch-her-and-die-books",
    emoji: "🔪",
  },
  {
    title: "the ultimate romantasy reading guide",
    description: "an editorial guide if you want one place to start fast.",
    url: "https://bybookishbabe.com/blogs/curated-romance-guides/the-ultimate-romantasy-reading-guide",
    emoji: "📖",
  },
  QUIZ_LINK,
];

const EMPTY_SHELF_PRIMARY = {
  title: "start with a lane that already sounds like you",
  description: "pick a trope, genre, or quiz below and let the shelf build itself from there.",
  url: "https://bybookishbabe.com/pages/library",
  emoji: "📚",
  links: EMPTY_SHELF_LINKS,
};

function jsonResponse(status: number, payload: unknown) {
  return new Response(JSON.stringify(payload, null, 2), {
    status,
    headers: {
      ...corsHeaders,
      "Content-Type": "application/json; charset=utf-8",
    },
  });
}

function normalize(value = "") {
  return String(value || "").trim().toLowerCase();
}

function uniqueBy<T>(array: T[], getKey: (item: T) => string) {
  const seen = new Set<string>();
  const output: T[] = [];
  for (const item of array) {
    const key = getKey(item);
    if (!key || seen.has(key)) continue;
    seen.add(key);
    output.push(item);
  }
  return output;
}

function parseTropes(value: unknown): string[] {
  if (Array.isArray(value)) {
    return value.map((entry) => String(entry || "").trim()).filter(Boolean);
  }
  if (value && typeof value === "object") {
    return Object.values(value as Record<string, unknown>).map((entry) => String(entry || "").trim()).filter(Boolean);
  }
  if (!value) return [];
  if (typeof value === "string") {
    const trimmed = value.trim();
    if (!trimmed) return [];
    try {
      const parsed = JSON.parse(trimmed);
      if (Array.isArray(parsed)) return parseTropes(parsed);
    } catch {
      // ignore
    }
    return trimmed.split(",").map((entry) => entry.trim()).filter(Boolean);
  }
  return [];
}

function toNumber(value: unknown) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

function getTimeParts(value: Date, timeZone: string) {
  const formatter = new Intl.DateTimeFormat("en-CA", {
    timeZone,
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  });
  const entries = formatter.formatToParts(value);
  const bag: Record<string, string> = {};
  for (const part of entries) {
    if (part.type !== "literal") bag[part.type] = part.value;
  }
  return bag;
}

function getLocalDateKey(value: Date, timeZone: string) {
  const parts = getTimeParts(value, timeZone);
  return `${parts.year}-${parts.month}-${parts.day}`;
}

function buildSendTimestamp(dateKey: string, timeZone: string, hour = DEFAULT_SEND_HOUR, minute = DEFAULT_SEND_MINUTE) {
  const [year, month, day] = String(dateKey || "").split("-").map((piece) => Number(piece));
  if (!year || !month || !day) return "";
  const utcBase = new Date(Date.UTC(year, month - 1, day, hour, minute, 0));
  const localMatch = new Date(utcBase);
  for (let i = 0; i < 4; i += 1) {
    const parts = getTimeParts(localMatch, timeZone);
    const currentMinutes = Number(parts.hour || 0) * 60 + Number(parts.minute || 0);
    const desiredMinutes = hour * 60 + minute;
    const deltaMinutes = desiredMinutes - currentMinutes;
    if (!deltaMinutes && `${parts.year}-${parts.month}-${parts.day}` === dateKey) break;
    localMatch.setUTCMinutes(localMatch.getUTCMinutes() + deltaMinutes);
  }
  return localMatch.toISOString();
}

function buildNewsletterSneakPeek({ title, body }: { title?: string; body?: string } = {}) {
  return {
    title: title || "this week inside the society",
    body: body || "add the paid sneak peek here on friday morning before you approve the send.",
  };
}

function normalizeSavedBook(book: Record<string, unknown>) {
  return {
    bookKey: String(book.book_key || ""),
    handle: String(book.book_handle || book.book_key || book.book_title || ""),
    title: String(book.book_title || ""),
    author: String(book.author || ""),
    cover: String(book.cover || ""),
    amazon: String(book.amazon || ""),
    bookshop: String(book.bookshop || ""),
    shelfName: String(book.shelf_name || ""),
    spiceLevel: toNumber(book.spice_level),
    darknessLevel: toNumber(book.darkness_level),
    tropes: uniqueBy(parseTropes(book.tropes), (item) => normalize(item)),
    savedAt: String(book.saved_at || ""),
  };
}

function inferCluster(books: ReturnType<typeof normalizeSavedBook>[]) {
  const context = books
    .flatMap((book) => [book.title, book.shelfName, ...(book.tropes || [])])
    .join(" ")
    .toLowerCase();

  if (context.includes("dark romantasy") || context.includes("ever king")) return "darkromantasy";
  if (context.includes("morally gray fantasy")) return "morallygrayfantasy";
  if (context.includes("stalker") || context.includes("obsession") || context.includes("obsessive")) return "stalker";
  if (context.includes("dark obsession")) return "darkobsession";
  if (context.includes("sport") || context.includes("hockey") || context.includes("football") || context.includes("athlete")) return "sports";
  if (context.includes("romantasy") || context.includes("fantasy") || context.includes("fae") || context.includes("fated mates") || context.includes("dragon") || context.includes("magic")) return "romantasy";
  if (context.includes("slow burn") || context.includes("yearning")) return "slowburn";
  if (context.includes("small town") || context.includes("friends to lovers") || context.includes("healing") || context.includes("second chance")) return "soft";
  if (context.includes("morally gray") || context.includes("villain gets the girl")) return "morallygraymen";
  if (context.includes("touch her and die") || context.includes("dark romance") || context.includes("captor") || context.includes("captive")) return "dark";
  return "default";
}

function chooseSupportLink(cluster: string, latestBooks: ReturnType<typeof normalizeSavedBook>[]) {
  if (cluster === "sports") {
    const hasFakeDating = latestBooks.some((book) => book.tropes.some((trope) => normalize(trope).includes("fake dating")));
    if (hasFakeDating) {
      return {
        title: "fake dating romance",
        description: "keep leaning into the chemistry that starts as a terrible idea.",
        url: "https://bybookishbabe.com/pages/fake-dating-romance-books",
        emoji: "💘",
      };
    }
  }
  return CLUSTER_SUPPORT[cluster] || CLUSTER_SUPPORT.default;
}

function buildRecommendedBook(cluster: string, latestBooks: ReturnType<typeof normalizeSavedBook>[]) {
  const sourceTitles = latestBooks.map((book) => normalize(book.title));
  let key = cluster;

  if (sourceTitles.some((title) => title.includes("fourth wing") || title.includes("iron flame"))) {
    key = "romantasy";
  } else if (sourceTitles.some((title) => title.includes("ever king"))) {
    key = "darkromantasy";
  } else if (sourceTitles.some((title) => title.includes("haunting adeline") || title.includes("lights out"))) {
    key = "stalker";
  }

  const rec = CLUSTER_RECOMMENDATIONS[key] || CLUSTER_RECOMMENDATIONS.default;
  return {
    ...rec,
    url: rec.handle
      ? `https://bybookishbabe.com/pages/library?book=${encodeURIComponent(rec.handle)}`
      : "https://bybookishbabe.com/pages/library",
  };
}

function buildSubjectLine(variant: string, cluster: string) {
  if (variant === "empty_shelf") return "your shelf is still empty. let’s fix that.";
  if (variant === "paid_personalized") return "your next read + a little society sneak peek";
  if (["romantasy", "darkromantasy", "morallygrayfantasy"].includes(cluster)) return "your next romantasy read is here";
  if (cluster === "sports") return "your next sports romance is here";
  if (["stalker", "dark", "darkobsession", "morallygraymen"].includes(cluster)) return "your next dangerous romance read is here";
  return "your next read based on your shelf";
}

function buildPreviewText(variant: string, cluster: string) {
  if (variant === "empty_shelf") return "your shelf is empty, so I picked the best places to start.";
  if (variant === "paid_personalized") return "latest 3 shelf picks, 1 rec, and this week’s society tease.";
  if (cluster === "sports") return "based on your last three saves, here’s the next romance with chemistry on impact.";
  if (["romantasy", "darkromantasy", "morallygrayfantasy"].includes(cluster)) {
    return "your shelf wants bigger worlds, sharp longing, and chaos that feels expensive.";
  }
  if (["stalker", "dark", "darkobsession", "morallygraymen"].includes(cluster)) {
    return "your shelf keeps choosing dangerous chemistry, so I leaned in.";
  }
  return "based on the last three books you saved, here’s where I’d send you next.";
}

function buildIntroCopy(variant: string, latestBooks: ReturnType<typeof normalizeSavedBook>[], cluster: string) {
  if (variant === "empty_shelf") {
    return "your shelf is still empty, which honestly just means we get to start at the fun part. pick a trope, genre, or quiz below and build from there.";
  }
  const titles = latestBooks.map((book) => book.title).filter(Boolean);
  const readableList = titles.length > 1 ? `${titles.slice(0, -1).join(", ")}, and ${titles[titles.length - 1]}` : titles[0] || "your latest saves";
  if (cluster === "sports") {
    return `you saved ${readableList}, so I pulled one more with the same chemistry-first, page-devouring energy.`;
  }
  if (["romantasy", "darkromantasy", "morallygrayfantasy"].includes(cluster)) {
    return `you saved ${readableList}, so I picked the next one with the same bigger-world longing and dangerous pull.`;
  }
  if (["stalker", "dark", "darkobsession", "morallygraymen"].includes(cluster)) {
    return `you saved ${readableList}, and the pattern is clear: you want obsession, danger, and devotion that should probably come with a warning label.`;
  }
  return `you saved ${readableList}, so here’s the next rec I’d hand you before your shelf changes its mind.`;
}

function buildPayload({
  subscriber,
  latestBooks,
  variant,
  cluster,
  recommendedBook,
  supportLink,
  newsletterSneakPeek,
}: {
  subscriber: { email_normalized?: string };
  latestBooks: ReturnType<typeof normalizeSavedBook>[];
  variant: string;
  cluster: string;
  recommendedBook: Record<string, unknown>;
  supportLink: Record<string, unknown>;
  newsletterSneakPeek: Record<string, unknown>;
}) {
  return {
    subject_line: buildSubjectLine(variant, cluster),
    preview_text: buildPreviewText(variant, cluster),
    intro_copy: buildIntroCopy(variant, latestBooks, cluster),
    latest_books: latestBooks,
    recommended_book: variant === "empty_shelf" ? {} : recommendedBook,
    support_link: variant === "empty_shelf" ? EMPTY_SHELF_PRIMARY : supportLink,
    newsletter_sneak_peek: variant === "paid_personalized" ? newsletterSneakPeek : {},
    metadata: {
      subscriber_email: subscriber.email_normalized || "",
      cluster,
    },
  };
}

Deno.serve(async (request) => {
  if (request.method === "OPTIONS") {
    return new Response("ok", { headers: corsHeaders });
  }

  if (request.method !== "POST") {
    return jsonResponse(405, { error: "Method not allowed. Use POST." });
  }

  const expectedSecret = Deno.env.get("BOOKSHELF_WEEKLY_ADMIN_SECRET") || "";
  if (expectedSecret) {
    const providedSecret = request.headers.get("x-admin-secret") || "";
    if (providedSecret !== expectedSecret) {
      return jsonResponse(401, { error: "Unauthorized" });
    }
  }

  const supabaseUrl = Deno.env.get("SUPABASE_URL") || "";
  const supabaseServiceRoleKey = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY") || "";
  const timeZone = Deno.env.get("TZ") || DEFAULT_TIMEZONE;

  if (!supabaseUrl || !supabaseServiceRoleKey) {
    return jsonResponse(500, {
      error: "Missing required environment variables.",
      required: ["SUPABASE_URL", "SUPABASE_SERVICE_ROLE_KEY"],
    });
  }

  let body: Record<string, unknown> = {};
  try {
    body = await request.json();
  } catch {
    body = {};
  }

  const now = body.now ? new Date(String(body.now)) : new Date();
  const weekOf = String(body.weekOf || "").trim() || getLocalDateKey(now, timeZone);
  const newsletterSneakPeek = buildNewsletterSneakPeek({
    title: String(body.teaseTitle || body.newsletterTeaseTitle || "").trim() || undefined,
    body: String(body.teaseBody || body.newsletterTeaseBody || "").trim() || undefined,
  });

  try {
    const supabase = createClient(supabaseUrl, supabaseServiceRoleKey, {
      auth: { persistSession: false },
    });

    const [{ data: subscribers, error: subscriberError }, { data: latestBooksRows, error: latestBooksError }] = await Promise.all([
      supabase
        .from("bookshelf_subscribers")
        .select("id,email,email_normalized,shopify_customer_id,customer_email,access_tier,society_key_used_at,weekly_email_opt_in,last_weekly_sent_at")
        .eq("weekly_email_opt_in", true)
        .order("subscribed_at", { ascending: true }),
      supabase
        .from("bookshelf_latest_three_books")
        .select("email_normalized,shopify_customer_id,customer_email,latest_books"),
    ]);

    if (subscriberError) throw subscriberError;
    if (latestBooksError) throw latestBooksError;

    const latestBooksMap = new Map(
      (latestBooksRows || []).map((row: any) => [
        normalize(row.email_normalized),
        (row.latest_books || []).map(normalizeSavedBook),
      ]),
    );

    const previewCandidates: Record<string, any> = {
      free_personalized: null,
      paid_personalized: null,
      empty_shelf: null,
    };
    const queueRows: any[] = [];

    for (const subscriber of subscribers || []) {
      const emailNormalized = normalize(subscriber.email_normalized || subscriber.email || subscriber.customer_email);
      if (!emailNormalized) continue;

      const latestBooks = latestBooksMap.get(emailNormalized) || [];
      const accessTier = subscriber.society_key_used_at ? "society" : "free";
      const variant = latestBooks.length ? (accessTier === "society" ? "paid_personalized" : "free_personalized") : "empty_shelf";
      const cluster = latestBooks.length ? inferCluster(latestBooks) : "default";
      const recommendedBook = latestBooks.length ? buildRecommendedBook(cluster, latestBooks) : {};
      const supportLink = chooseSupportLink(cluster, latestBooks);
      const payload = buildPayload({
        subscriber,
        latestBooks,
        variant,
        cluster,
        recommendedBook,
        supportLink,
        newsletterSneakPeek,
      });

      queueRows.push({
        subscriber_id: subscriber.id,
        email_normalized: emailNormalized,
        access_tier: accessTier,
        send_variant: variant,
        queue_status: "queued",
        latest_books: payload.latest_books,
        recommended_book: payload.recommended_book,
        support_link: payload.support_link,
        newsletter_sneak_peek: payload.newsletter_sneak_peek,
        payload,
        metadata: {
          cluster,
          generated_at: now.toISOString(),
        },
      });

      if (!previewCandidates[variant]) {
        previewCandidates[variant] = {
          sample_email: emailNormalized,
          access_tier: accessTier,
          payload,
        };
      }
    }

    if (!previewCandidates.paid_personalized) {
      const paidSource = previewCandidates.free_personalized || previewCandidates.empty_shelf;
      if (paidSource) {
        previewCandidates.paid_personalized = {
          sample_email: paidSource.sample_email,
          access_tier: "society",
          payload: {
            ...paidSource.payload,
            subject_line: buildSubjectLine("paid_personalized", paidSource.payload?.metadata?.cluster || "default"),
            preview_text: buildPreviewText("paid_personalized"),
            newsletter_sneak_peek: newsletterSneakPeek,
            metadata: {
              ...(paidSource.payload?.metadata || {}),
              preview_fallback: "generated_from_non_paid_sample",
            },
          },
        };
      }
    }

    const scheduledSendAt = buildSendTimestamp(weekOf, timeZone, DEFAULT_SEND_HOUR, DEFAULT_SEND_MINUTE);

    const { data: campaignRows, error: campaignError } = await supabase
      .from("bookshelf_weekly_campaigns")
      .upsert(
        {
          week_of: weekOf,
          status: "draft",
          preview_ready_at: now.toISOString(),
          scheduled_send_at: scheduledSendAt,
          free_subject: buildSubjectLine("free_personalized", previewCandidates.free_personalized?.payload?.metadata?.cluster || "default"),
          paid_subject: buildSubjectLine("paid_personalized", previewCandidates.paid_personalized?.payload?.metadata?.cluster || "default"),
          empty_subject: buildSubjectLine("empty_shelf", "default"),
          newsletter_tease_title: newsletterSneakPeek.title,
          newsletter_tease_body: newsletterSneakPeek.body,
          notes: String(body.notes || "").trim(),
          metadata: {
            subscriber_count: (subscribers || []).length,
            queued_count: queueRows.length,
            preview_generated_at: now.toISOString(),
          },
        },
        { onConflict: "week_of" },
      )
      .select("id,status,approved_by,notes");

    if (campaignError) throw campaignError;
    const campaign = campaignRows?.[0];
    if (!campaign?.id) throw new Error("Could not create or fetch weekly campaign.");

    const campaignId = campaign.id;

    const { error: deletePreviewError } = await supabase
      .from("bookshelf_weekly_preview_samples")
      .delete()
      .eq("campaign_id", campaignId);
    if (deletePreviewError) throw deletePreviewError;

    const previewRows = ["free_personalized", "paid_personalized", "empty_shelf"].map((sampleType) => {
      const sample = previewCandidates[sampleType];
      const payload = sample?.payload || buildPayload({
        subscriber: { email_normalized: "" },
        latestBooks: [],
        variant: sampleType,
        cluster: "default",
        recommendedBook: {},
        supportLink: QUIZ_LINK,
        newsletterSneakPeek,
      });

      const htmlPreview = renderWeeklyEmailHtml(payload as any, {
        emailLabel: sampleType.replaceAll("_", " "),
        supportLabel: "go deeper",
        showTease: sampleType === "paid_personalized",
      });

      return {
        campaign_id: campaignId,
        sample_type: sampleType,
        sample_email: sample?.sample_email || null,
        access_tier: sample?.access_tier || (sampleType === "paid_personalized" ? "society" : "free"),
        subject_line: payload.subject_line,
        preview_text: payload.preview_text,
        intro_copy: payload.intro_copy,
        latest_books: payload.latest_books,
        recommended_book: payload.recommended_book,
        support_link: payload.support_link,
        newsletter_sneak_peek: payload.newsletter_sneak_peek,
        html_preview: htmlPreview,
        metadata: payload.metadata,
      };
    });

    const { data: insertedPreviewRows, error: previewInsertError } = await supabase
      .from("bookshelf_weekly_preview_samples")
      .insert(previewRows)
      .select("sample_type");
    if (previewInsertError) throw previewInsertError;

    const { error: deleteQueueError } = await supabase
      .from("bookshelf_weekly_send_queue")
      .delete()
      .eq("campaign_id", campaignId);
    if (deleteQueueError) throw deleteQueueError;

    if (queueRows.length) {
      const { error: queueInsertError } = await supabase
        .from("bookshelf_weekly_send_queue")
        .insert(queueRows.map((row) => ({ ...row, campaign_id: campaignId })));
      if (queueInsertError) throw queueInsertError;
    }

    const previewPayload = {
      week_of: weekOf,
      run_week: weekOf,
      status: campaign.status || "draft",
      scheduled_send_at: scheduledSendAt,
      approved_by: campaign.approved_by || "",
      notes: campaign.notes || "",
      free_sample: previewCandidates.free_personalized?.payload || {},
      paid_sample: previewCandidates.paid_personalized?.payload || {},
      empty_shelf_sample: previewCandidates.empty_shelf?.payload || {},
    };

    return jsonResponse(200, {
      ok: true,
      weekOf,
      campaignId,
      subscriberCount: (subscribers || []).length,
      queuedCount: queueRows.length,
      previewSampleCount: insertedPreviewRows?.length || 0,
      scheduledSendAt,
      previewPayload,
    });
  } catch (error) {
    return jsonResponse(500, {
      ok: false,
      error: error instanceof Error ? error.message : String(error),
    });
  }
});
