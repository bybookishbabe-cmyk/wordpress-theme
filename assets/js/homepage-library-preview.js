var bbbHomepageSupabasePromise = null;

function bbbLoadHomepageSupabase(){
  if (window.supabase && window.supabase.createClient) {
    return Promise.resolve(window.supabase);
  }

  if (bbbHomepageSupabasePromise) {
    return bbbHomepageSupabasePromise;
  }

  bbbHomepageSupabasePromise = new Promise(function(resolve, reject){
    var script = document.createElement("script");
    script.src = "https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2";
    script.async = true;
    script.onload = function(){
      resolve(window.supabase);
    };
    script.onerror = reject;
    document.head.appendChild(script);
  });

  return bbbHomepageSupabasePromise;
}

async function loadPreviewTrending(){

  try {

    await bbbLoadHomepageSupabase();

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

function queuePreviewTrendingLoad(){
  const row = document.getElementById("sssPreviewTrending");
  if(!row) return;

  if(!("IntersectionObserver" in window)){
    loadPreviewTrending();
    return;
  }

  const observer = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(!entry.isIntersecting) return;
      observer.disconnect();
      loadPreviewTrending();
    });
  }, { rootMargin: "450px 0px", threshold: 0.01 });

  observer.observe(row);
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

queuePreviewTrendingLoad();

function revealHomepageTrendingBooks(){
  const books = Array.from(document.querySelectorAll(".bbb-trending__book"));
  if(!books.length) return;

  function revealAll(){
    books.forEach(function(book){
      book.classList.add("is-visible");
    });
  }

  if(!("IntersectionObserver" in window)){
    revealAll();
    return;
  }

  const observer = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(!entry.isIntersecting) return;

      const index = books.indexOf(entry.target);
      window.setTimeout(function(){
        entry.target.classList.add("is-visible");
      }, Math.max(index, 0) * 120);
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.1 });

  books.forEach(function(book){
    observer.observe(book);
  });

  window.setTimeout(revealAll, 1200);
}

if(document.readyState === "loading"){
  document.addEventListener("DOMContentLoaded", revealHomepageTrendingBooks);
} else {
  revealHomepageTrendingBooks();
}

document.addEventListener("click", function(event){
  const target = event.target;
  if(!(target instanceof Element)) return;

  const home = target.closest(".bbb-home");
  if(!home) return;

  const seriesBadge = target.closest(".sss-lib__seriesBadge[data-series-url]");
  if(seriesBadge){
    event.preventDefault();
    event.stopPropagation();
    window.location.href = seriesBadge.getAttribute("data-series-url");
    return;
  }

  const card = target.closest(".sss-lib__book[data-url]");
  if(!card) return;

  event.preventDefault();
  window.location.href = card.getAttribute("data-url");
});
