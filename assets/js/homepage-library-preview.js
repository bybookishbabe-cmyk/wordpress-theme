async function loadPreviewTrending(){

  try {

    const { createClient } = supabase;

    const client = createClient(
      "https://efmrfxsmgbeikfgtrxjv.supabase.co",
      "sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk"
    );

    const { data } = await client
      .from("book_saves_recent_rollup")
      .select("book_title,saves_last_30_days,last_saved_at")
      .gt("saves_last_30_days", 0)
      .order("saves_last_30_days", { ascending: false })
      .order("last_saved_at", { ascending: false })
      .limit(5);

    const row = document.getElementById("sssPreviewTrending");
    if(!row) return;

    const sorted = (data || [])
      .map(function(item){
        return (item.book_title || "").toLowerCase().trim();
      })
      .filter(Boolean)
      .slice(0,5);

    window.sssPreviewTrending = sorted;

    document.dispatchEvent(new CustomEvent("sssPreviewReady"));

  } catch(err){

    console.log("Preview trending failed", err);

  }

}

document.addEventListener("sssPreviewReady", function(){

  const titles = window.sssPreviewTrending || [];
  if(!titles.length) return;

  const row = document.getElementById("sssPreviewTrending");
  if(!row) return;

  const cards = Array.from(row.querySelectorAll(".sss-lib__book"));

  const map = {};

  cards.forEach(card=>{
    const title = (card.dataset.title || "").toLowerCase().trim();
    if(title) map[title] = card;
  });

  const matchedCards = titles
    .map(title=>map[title])
    .filter(Boolean);

  if(!matchedCards.length) return;

  const matchedSet = new Set(matchedCards);
  const remainingCards = cards.filter(card=>!matchedSet.has(card));

  row.innerHTML = "";

  matchedCards
    .concat(remainingCards)
    .slice(0, 5)
    .forEach(card=>row.appendChild(card));

});

loadPreviewTrending();
