import { createClient } from "jsr:@supabase/supabase-js@2";
import { renderWeeklyEmailHtml } from "../_shared/bookshelf-weekly-email.ts";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type, x-admin-secret",
  "Access-Control-Allow-Methods": "POST, OPTIONS",
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

  const sampleType = String(body.sampleType || body.sample_type || "free_personalized").trim();
  const weekOf = String(body.weekOf || body.week_of || "").trim();

  const supabase = createClient(supabaseUrl, supabaseServiceRoleKey, {
    auth: { persistSession: false, autoRefreshToken: false },
  });

  try {
    let campaignQuery = supabase
      .from("bookshelf_weekly_campaigns")
      .select("id,week_of,status,scheduled_send_at,notes");

    if (weekOf) {
      campaignQuery = campaignQuery.eq("week_of", weekOf);
    } else {
      campaignQuery = campaignQuery.order("week_of", { ascending: false }).order("created_at", { ascending: false }).limit(1);
    }

    const { data: campaignRows, error: campaignError } = await campaignQuery.limit(1);
    if (campaignError) throw campaignError;
    const campaign = campaignRows?.[0];
    if (!campaign?.id) {
      return jsonResponse(404, { ok: false, error: "No weekly campaign found." });
    }

    const { data: sampleRows, error: sampleError } = await supabase
      .from("bookshelf_weekly_preview_samples")
      .select("sample_type,subject_line,preview_text,intro_copy,latest_books,recommended_book,support_link,newsletter_sneak_peek,html_preview,metadata")
      .eq("campaign_id", campaign.id)
      .eq("sample_type", sampleType)
      .limit(1);

    if (sampleError) throw sampleError;
    const sample = sampleRows?.[0];
    if (!sample) {
      return jsonResponse(404, { ok: false, error: `No preview sample found for ${sampleType}.` });
    }

    const html = sample.html_preview || renderWeeklyEmailHtml(sample as any, {
      emailLabel: sampleType.replaceAll("_", " "),
      supportLabel: "go deeper",
      showTease: sampleType === "paid_personalized",
    });

    if (!sample.html_preview) {
      await supabase
        .from("bookshelf_weekly_preview_samples")
        .update({ html_preview: html })
        .eq("campaign_id", campaign.id)
        .eq("sample_type", sampleType);
    }

    return jsonResponse(200, {
      ok: true,
      weekOf: campaign.week_of,
      sampleType,
      subject: sample.subject_line || "",
      previewText: sample.preview_text || "",
      html,
      sample,
    });
  } catch (error) {
    return jsonResponse(500, {
      ok: false,
      error: error instanceof Error ? error.message : String(error),
    });
  }
});
