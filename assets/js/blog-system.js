/* ======================
BLOG SYSTEM JS
Handles:
- Share button
- Shelf saving
- Scroll popups
- Signoff animation
====================== */

document.addEventListener("DOMContentLoaded", function () {

document.addEventListener("click", function (event) {
  const target = event.target.closest("[data-trope-url]");
  if (!target) return;

  const url = target.getAttribute("data-trope-url");
  if (!url || url === "#") return;

  event.preventDefault();
  event.stopPropagation();
  window.location.href = url;
});

document.addEventListener("keydown", function (event) {
  if (event.key !== "Enter" && event.key !== " ") return;

  const target = event.target.closest("[data-trope-url]");
  if (!target) return;

  const url = target.getAttribute("data-trope-url");
  if (!url || url === "#") return;

  event.preventDefault();
  event.stopPropagation();
  window.location.href = url;
});

/* ======================
SHARE BUTTON
====================== */

const shareBtn = document.getElementById("bbbSharePost");

if (shareBtn) {

  shareBtn.addEventListener("click", function () {

    if (navigator.share) {

      navigator.share({
        title: document.title,
        text: "you need to read this romance 👀",
        url: window.location.href
      });

    } else {

      navigator.clipboard.writeText(window.location.href);

      shareBtn.innerHTML = "✓";

      setTimeout(() => {
        shareBtn.innerHTML = "📲";
      }, 2000);

    }

  });

}

/* ======================
READING SHELF (LOCAL STORAGE)
====================== */

function getShelf() {
  try {
    return JSON.parse(localStorage.getItem("sssMyShelf")) || [];
  } catch (e) {
    return [];
  }
}

function setShelf(data) {
  localStorage.setItem("sssMyShelf", JSON.stringify(data));
  localStorage.setItem("sssShelf", JSON.stringify(data));
  document.dispatchEvent(new CustomEvent("sss:bookshelf-updated", {
    detail: { count: Array.isArray(data) ? data.length : 0 }
  }));
}

function syncInlineBlogHearts() {
  const shelf = getShelf();

  document.querySelectorAll("[data-book-heart]").forEach(function (heart) {
    const exists = shelf.find(function (book) {
      return book.title === heart.dataset.title;
    });

    if (exists) {
      heart.classList.add("saved");
      heart.innerHTML = "♥";
    } else {
      heart.classList.remove("saved");
      heart.innerHTML = "♡";
    }
  });
}

const hearts = document.querySelectorAll("[data-book-heart]");
const popup = document.getElementById("bbbShelfPopup");

hearts.forEach(function (heart) {

  heart.addEventListener("click", function (e) {

    e.stopPropagation();

    const book = {
      title: heart.dataset.title,
      author: heart.dataset.author,
      cover: heart.dataset.cover,
      amazon: heart.dataset.amazon,
      bookshop: heart.dataset.bookshop
    };

    let shelf = getShelf();

    const exists = shelf.find(b => b.title === book.title);

    if (!exists) {

      shelf.push(book);

      setShelf(shelf);
      document.dispatchEvent(new CustomEvent("bbb:shelf-saved", {
        detail: {
          count: shelf.length,
          bookTitle: book.title || "",
          book: book,
          source: document.body.dataset.template || "blog"
        }
      }));

      heart.classList.add("saved");
      heart.innerHTML = "♥";

      if (popup) {

        popup.style.display = "block";

        setTimeout(() => {
          popup.style.display = "none";
        }, 4000);

      }

    } else {

      shelf = shelf.filter(function(item){
        return item.title !== book.title;
      });

      setShelf(shelf);
      document.dispatchEvent(new CustomEvent("bbb:shelf-unsaved", {
        detail: {
          count: shelf.length,
          bookTitle: book.title || "",
          book: book,
          source: document.body.dataset.template || "blog"
        }
      }));

      heart.classList.remove("saved");
      heart.innerHTML = "♡";

    }

  });

});

/* ======================
RESTORE SAVED HEARTS
====================== */

syncInlineBlogHearts();

/* ======================
GUIDE POPUP ON SCROLL
====================== */

const guidePopup = document.getElementById("bbbGuidePopup");

if (guidePopup) {

  let popupShown = false;

  window.addEventListener("scroll", () => {

    const scrollPosition = window.scrollY;
    const pageHeight = document.body.scrollHeight - window.innerHeight;
    const scrollPercent = scrollPosition / pageHeight;

    if (scrollPercent > 0.25 && !popupShown) {

      popupShown = true;

      guidePopup.style.display = "block";

      setTimeout(() => {

        guidePopup.style.opacity = "0";

        setTimeout(() => {
          guidePopup.style.display = "none";
        }, 300);

      }, 5000);

    }

  });

}

/* ======================
SIGNOFF ANIMATION
====================== */

const signoff = document.querySelector(".bbb-signoff");

if (signoff) {

  const observer = new IntersectionObserver(entries => {

    entries.forEach(entry => {

      if (entry.isIntersecting) {

        signoff.style.transform = "translateY(0)";
        signoff.style.opacity = "1";

      }

    });

  }, {
    threshold: 0.3
  });

  signoff.style.opacity = "0";
  signoff.style.transform = "translateY(40px)";
  signoff.style.transition = "all .6s ease";

  observer.observe(signoff);

}

});

/* ======================
BOOK PREVIEW POPUP
====================== */
const previewCards = document.querySelectorAll("[data-book-preview]");
const preview = document.querySelector(".sss-lib__modal:not([data-quiz-modal])");
const previewClose = preview ? preview.querySelector("[data-close].sss-lib__x") : null;
const previewShareButton = preview ? preview.querySelector("[data-modal-share-btn]") : null;
let currentPreviewShare = null;
let currentPreviewBook = null;
let previewScrollY = 0;

function setPreviewShareButton(icon, label){
  if (!previewShareButton) return;
  const iconEl = previewShareButton.querySelector("[data-modal-share-label]") ? previewShareButton.querySelector(".sss-lib__mshareIcon") : null;
  const labelEl = previewShareButton.querySelector("[data-modal-share-label]");
  if (iconEl) iconEl.textContent = icon;
  if (labelEl) labelEl.textContent = label;
  if (!iconEl && !labelEl) previewShareButton.textContent = icon;
}

function lockPreviewScroll(){
  previewScrollY = window.scrollY || document.documentElement.scrollTop || 0;
  document.body.classList.add("bbb-book-preview-open");
  document.documentElement.style.overflow = "hidden";
  document.body.style.overflow = "hidden";
}

function unlockPreviewScroll(){
  document.body.classList.remove("bbb-book-preview-open");
  document.documentElement.style.overflow = "";
  document.body.style.overflow = "";
}

function openBookPreview(){
  if (!preview) return;
  preview.style.display = "flex";
  preview.hidden = false;
  preview.setAttribute("aria-hidden", "false");
  lockPreviewScroll();
}

function closeBookPreview(){
  if (!preview || preview.hidden) return;
  preview.style.display = "none";
  preview.hidden = true;
  preview.setAttribute("aria-hidden", "true");
  unlockPreviewScroll();
}

function getBlogShelf(){
  try {
    return JSON.parse(localStorage.getItem("sssMyShelf")) || [];
  } catch (e) {
    return [];
  }
}

function setBlogShelf(data){
  localStorage.setItem("sssMyShelf", JSON.stringify(data));
  localStorage.setItem("sssShelf", JSON.stringify(data));
  document.dispatchEvent(new CustomEvent("sss:bookshelf-updated", {
    detail: { count: Array.isArray(data) ? data.length : 0 }
  }));
}

function getPreviewBookKey(book){
  if (!book) return "";
  return String(book.handle || book.title || "").trim().toLowerCase();
}

function isPreviewBookSaved(book){
  const key = getPreviewBookKey(book);
  if (!key) return false;

  return getBlogShelf().some(function(item){
    const itemKey = String(item.handle || item.title || "").trim().toLowerCase();
    return itemKey && itemKey === key;
  });
}

function syncPreviewInlineHearts(book, saved){
  const key = getPreviewBookKey(book);
  if (!key) return;

  document.querySelectorAll("[data-book-preview]").forEach(function(card){
    const cardKey = String(card.dataset.handle || card.dataset.title || "").trim().toLowerCase();
    if (!cardKey || cardKey !== key) return;

    const heart = card.querySelector("[data-blog-heart]");
    if (!heart) return;

    heart.classList.toggle("is-saved", saved);
    const icon = heart.querySelector(".article-book-card__heartIcon");
    const label = heart.querySelector(".article-book-card__heartLabel");
    if (icon) icon.textContent = saved ? "♥" : "♡";
    if (label) label.textContent = saved ? "saved" : "save";
    heart.setAttribute("aria-label", saved ? "remove from your bookshelf" : "save to your bookshelf");
  });
}

function syncPreviewModalHeart(){
  const heart = preview ? preview.querySelector("[data-modal-heart]") : null;
  if (!heart || !currentPreviewBook) return;

  const saved = isPreviewBookSaved(currentPreviewBook);
  heart.classList.toggle("is-saved", saved);
  const icon = heart.querySelector("[data-heart-icon]") || heart.querySelector(".preview-heartIcon");
  const label = heart.querySelector("[data-heart-label]") || heart.querySelector(".preview-heartLabel");
  if (icon) icon.textContent = saved ? "♥" : "♡";
  if (label) label.textContent = saved ? "saved" : "save";
  heart.setAttribute("aria-label", saved ? "remove from your bookshelf" : "save to your bookshelf");
}

previewCards.forEach(card => {

card.addEventListener("click", function(){

const title = card.dataset.title;
const author = card.dataset.author;
const cover = card.dataset.cover;
const url = card.dataset.url || (card.dataset.handle ? "/books/" + encodeURIComponent(card.dataset.handle) + "/" : "");
const amazon = card.dataset.amazon;
const bookshop = card.dataset.bookshop;
const spice = card.dataset.spice;
const tropes = card.dataset.tropes;
const tropesDisplay = card.dataset.tropesDisplay;
const mini = card.dataset.mini;
const why = card.dataset.why;
const ku = card.dataset.ku;
const series = card.dataset.series;
const seriesName = card.dataset.seriesName;
const seriesNumber = card.dataset.seriesNumber;
const shareUrl = window.location.origin + window.location.pathname + "?book=" + encodeURIComponent(title || "");
currentPreviewBook = {
  handle: card.dataset.handle || "",
  url: url || "",
  title: title || "",
  author: author || "",
  cover: cover || "",
  amazon: amazon || "",
  bookshop: bookshop || "",
  spice: spice || "",
  tropes: tropes || "",
  tropesDisplay: tropesDisplay || "",
  mini: mini || "",
  why: why || "",
  ku: ku || "",
  series: series || "",
  seriesName: seriesName || "",
  seriesNumber: seriesNumber || ""
};
const kuState = String(ku || "").toLowerCase().trim() === "true";
if (!preview) return;

const titleEl = preview.querySelector("[data-mtitle]");
const authorEl = preview.querySelector("[data-mauthor]");
const coverEl = preview.querySelector("[data-mcover]");
const kuButtonEl = preview.querySelector("[data-ku-btn]");
const amazonEl = preview.querySelector("[data-amazon-btn]");
const shopEl = preview.querySelector("[data-bookshop-btn]");
const miniEl = preview.querySelector("[data-mmini]");
const tropesEl = preview.querySelector("[data-mtropes]");
const whyEl = preview.querySelector("[data-mwhy]");
const kuEl = preview.querySelector("[data-mku]");
const seriesEl = preview.querySelector("[data-mseries]");
const seriesOrderEl = preview.querySelector("[data-mseries-order]");
const standaloneEl = preview.querySelector("[data-mstandalone]");
const tensionEl = preview.querySelector("[data-mtension]");
const damageEl = preview.querySelector("[data-mdamage]");
const yearningEl = preview.querySelector("[data-myearning]");
const boyfriendEl = preview.querySelector("[data-mboyfriend]");
const rereadEl = preview.querySelector("[data-mreread]");
let spiceEl = preview.querySelector("[data-mspice]");
const modalHeart = preview.querySelector("[data-modal-heart]");
const modalFullLink = preview.querySelector("[data-modal-full-link]");

function ensurePreviewSpiceBadge(){
  if (spiceEl || !preview) return spiceEl;
  const coverFrame = preview.querySelector(".sss-lib__mcoverFrame");
  if (!coverFrame) return null;
  spiceEl = document.createElement("div");
  spiceEl.className = "sss-lib__floatSpice sss-lib__mspice";
  spiceEl.setAttribute("data-mspice", "");
  spiceEl.hidden = true;
  coverFrame.appendChild(spiceEl);
  return spiceEl;
}

function previewEscape(value){
  return String(value || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function previewTropeNameWithoutEmoji(tropeName){
  const raw = String(tropeName || "").trim();
  const lower = raw.toLowerCase();
  const knownTropes = [
    "touch her and die",
    "why choose",
    "who did this to you",
    "mafia romance",
    "slow burn",
    "enemies to lovers",
    "fated mates"
  ];
  for (let i = 0; i < knownTropes.length; i += 1) {
    if (lower.indexOf(knownTropes[i]) !== -1) return knownTropes[i];
  }
  return raw.replace(/^[^a-z0-9]+/i, "").trim();
}

function previewTropeCustomKey(tropeName){
  const name = previewTropeNameWithoutEmoji(tropeName);
  const key = name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
  const haystack = (key + " " + name.toLowerCase()).replace(/\s+/g, " ");
  const aliases = [
    ["mafia-romance", ["mafia"]],
    ["slow-burn", ["slow burn", "slow-burn"]],
    ["enemies-to-lovers", ["enemies to lovers", "enemies-to-lovers"]],
    ["friends-to-lovers", ["friends to lovers", "friends-to-lovers"]],
    ["he-falls-first", ["he falls first", "he-falls-first", "falls first"]],
    ["billionaire-romance", ["billionaire romance", "billionaire-romance", "billionaire"]],
    ["stalker-romance", ["stalker romance", "stalker-romance", "stalker"]],
    ["dystopian-romance", ["dystopian romance", "dystopian-romance"]],
    ["sports-romance", ["sports romance", "sports-romance", "sports"]],
    ["bully-romance", ["bully romance", "bully-romance", "bully"]],
    ["forced-proximity", ["forced proximity", "forced-proximity"]],
    ["villain-gets-the-girl", ["villain gets the girl", "villain-gets-the-girl", "villain romance"]],
    ["historical-romance", ["historical romance", "historical-romance"]],
    ["bodyguard-romance", ["bodyguard romance", "bodyguard-romance", "bodyguard"]],
    ["opposites-attract", ["opposites attract", "opposites-attract"]],
    ["marriage-of-convenience", ["marriage of convenience", "marriage-of-convenience"]],
    ["found-family", ["found family", "found-family"]],
    ["dark-academia", ["dark academia", "dark-academia"]],
    ["captor-x-captive", ["captor x captive", "captor-x-captive", "captor captive", "captor", "captive"]],
    ["boss-x-employee", ["boss x employee", "boss-x-employee", "boss employee"]],
    ["age-gap", ["age gap", "age-gap"]],
    ["trauma-bonding", ["trauma bonding", "trauma-bonding"]],
    ["baseball-romance", ["baseball romance", "baseball-romance", "baseball"]],
    ["hockey-romance", ["hockey romance", "hockey-romance", "hockey"]],
    ["contemporary-romance", ["contemporary romance", "contemporary-romance"]],
    ["dark-romance", ["dark romance", "dark-romance"]],
    ["forbidden-love", ["forbidden love", "forbidden-love", "forbidden romance"]],
    ["step-siblings", ["step siblings", "step-siblings", "stepsiblings"]],
    ["nanny", ["nanny romance", "nanny"]],
    ["single-dad", ["single dad", "single-dad"]],
    ["small-town", ["small town", "small-town"]],
    ["grumpy-x-sunshine", ["grumpy x sunshine", "grumpy-x-sunshine", "grumpy sunshine"]],
    ["one-bed", ["one bed", "one-bed"]],
    ["brothers-best-friend", ["brother best friend", "brothers best friend", "brother's best friend", "brothers-best-friend", "brother-s-best-friend"]],
    ["second-chance", ["second chance", "second-chance"]],
    ["fake-dating", ["fake dating", "fake-dating"]],
    ["fated-mates", ["fated mates", "fated-mates"]],
    ["who-did-this-to-you", ["who did this to you", "who-did-this-to-you"]],
    ["touch-her-and-die", ["touch her and die", "touch-her-and-die"]],
    ["why-choose", ["why choose", "why-choose"]],
    ["paranormal-romance", ["paranormal romance", "paranormal-romance", "paranormal"]],
    ["romantasy", ["romantasy", "fantasy romance"]]
  ];
  for (let i = 0; i < aliases.length; i += 1) {
    for (let j = 0; j < aliases[i][1].length; j += 1) {
      if (haystack.indexOf(aliases[i][1][j]) !== -1) return aliases[i][0];
    }
  }
  return "";
}

function previewTropesHtml(value){
  const tropesList = String(value || "").split(",").map((trope) => trope.trim()).filter(Boolean);
  if (!tropesList.length) return "";

  return "tropes: " + tropesList.map((trope) => {
    const name = previewTropeNameWithoutEmoji(trope);
    const key = name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
    const customKey = previewTropeCustomKey(trope);
    if (customKey) {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/' + customKey + '.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name);
    }
    if (key === "mafia" || key === "mafia-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/mafia-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "mafia romance");
    }
    if (key === "slow-burn") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/slow-burn.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "slow burn");
    }
    if (key === "enemies-to-lovers") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/enemies-to-lovers.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "enemies to lovers");
    }
    if (key === "friends-to-lovers") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/friends-to-lovers.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "friends to lovers");
    }
    if (key === "he-falls-first" || key === "falls-first") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/he-falls-first.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "he falls first");
    }
    if (key === "billionaire-romance" || key === "billionaire") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/billionaire-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "billionaire romance");
    }
    if (key === "stalker-romance" || key === "stalker") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/stalker-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "stalker romance");
    }
    if (key === "dystopian-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/dystopian-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "dystopian romance");
    }
    if (key === "sports-romance" || key === "sports") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/sports-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "sports romance");
    }
    if (key === "bully-romance" || key === "bully") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/bully-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "bully romance");
    }
    if (key === "forced-proximity") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/forced-proximity.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "forced proximity");
    }
    if (key === "villain-gets-the-girl" || key === "villain-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/villain-gets-the-girl.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "villain gets the girl");
    }
    if (key === "historical-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/historical-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "historical romance");
    }
    if (key === "bodyguard-romance" || key === "bodyguard") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/bodyguard-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "bodyguard romance");
    }
    if (key === "opposites-attract") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/opposites-attract.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "opposites attract");
    }
    if (key === "marriage-of-convenience") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/marriage-of-convenience.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "marriage of convenience");
    }
    if (key === "found-family") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/found-family.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "found family");
    }
    if (key === "dark-academia" || key === "dark-academia-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/dark-academia.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "dark academia");
    }
    if (key === "captor-x-captive" || key === "captor-captive-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/captor-x-captive.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "captor x captive");
    }
    if (key === "boss-x-employee" || key === "boss-employee") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/boss-x-employee.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "boss x employee");
    }
    if (key === "age-gap") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/age-gap.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "age gap");
    }
    if (key === "trauma-bonding") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/trauma-bonding.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "trauma bonding");
    }
    if (key === "baseball-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/baseball-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "baseball romance");
    }
    if (key === "hockey-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/hockey-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "hockey romance");
    }
    if (key === "contemporary-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/contemporary-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "contemporary romance");
    }
    if (key === "dark-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/dark-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "dark romance");
    }
    if (key === "forbidden-love" || key === "forbidden-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/forbidden-love.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "forbidden love");
    }
    if (key === "step-siblings" || key === "stepsiblings") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/step-siblings.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "step siblings");
    }
    if (key === "nanny" || key === "nanny-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/nanny.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "nanny");
    }
    if (key === "single-dad" || key === "single-dad-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/single-dad.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "single dad");
    }
    if (key === "small-town" || key === "small-town-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/small-town.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "small town");
    }
    if (key === "grumpy-x-sunshine" || key === "grumpy-sunshine") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/grumpy-x-sunshine.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape("grumpy x sunshine");
    }
    if (key === "one-bed") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/one-bed.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "one bed");
    }
    if (key === "brothers-best-friend" || key === "brother-s-best-friend") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/brothers-best-friend.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "brother's best friend");
    }
    if (key === "second-chance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/second-chance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "second chance");
    }
    if (key === "fake-dating" || key === "fake-dating-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/fake-dating.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "fake dating");
    }
    if (key === "fated-mates") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/fated-mates.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "fated mates");
    }
    if (key === "who-did-this-to-you") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/who-did-this-to-you.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "who did this to you");
    }
    if (key === "touch-her-and-die") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/touch-her-and-die.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "touch her and die");
    }
    if (key === "why-choose") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/why-choose.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "why choose");
    }
    if (key === "paranormal" || key === "paranormal-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/paranormal-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "paranormal romance");
    }
    if (key === "romantasy" || key === "fantasy-romance") {
      return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/romantasy.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + previewEscape(name || "romantasy");
    }
    return previewEscape(trope);
  }).join(", ");
}

if (titleEl) titleEl.textContent = title || "";
if (authorEl) authorEl.textContent = author ? "by " + author : "";
if (modalFullLink) {
  if (url) {
    modalFullLink.href = url;
    modalFullLink.hidden = false;
  } else {
    modalFullLink.hidden = true;
    modalFullLink.removeAttribute("href");
  }
}
if (coverEl) {
  coverEl.src = cover || "";
  coverEl.alt = title || "";
}
if (miniEl) miniEl.textContent = mini ? "quick summary: " + mini : "";
if (tropesEl) {
  const modalTropes = tropesDisplay || tropes || "";
  tropesEl.innerHTML = previewTropesHtml(modalTropes);
}
if (whyEl) whyEl.textContent = why || "";
if (tensionEl) tensionEl.textContent = "";
if (damageEl) damageEl.textContent = "";
if (yearningEl) yearningEl.textContent = "";
if (boyfriendEl) boyfriendEl.textContent = "";
if (rereadEl) rereadEl.textContent = spice ? "🌶 " + spice + "/5 spice" : "";
const modalSpiceEl = ensurePreviewSpiceBadge();
if (modalSpiceEl) {
  const spiceCount = parseInt(spice, 10) || 0;
  modalSpiceEl.textContent = spiceCount > 0 ? Array(spiceCount + 1).join("🌶") : "";
  modalSpiceEl.hidden = spiceCount <= 0;
}

if (seriesEl) {
  if (seriesName) {
    const seriesHref = series ? `/series/${encodeURIComponent(series || "")}/` : "#";
    seriesEl.hidden = false;
    seriesEl.innerHTML = `<a href="${seriesHref}" class="sss-lib__seriesLink">${seriesName} series →</a>`;
  } else {
    seriesEl.hidden = true;
    seriesEl.innerHTML = "";
  }
}

if (seriesOrderEl) {
  seriesOrderEl.textContent = seriesNumber ? "book " + seriesNumber : "";
}

if (standaloneEl) {
  standaloneEl.textContent = seriesNumber === "1" ? "⚠ highly recommend starting the series from book 1" : "";
}

if (amazonEl) {
  amazonEl.classList.add("sss-lib__mbtn--amazon");
  amazonEl.href = amazon || "#";
  amazonEl.innerHTML = kuState ? "buy on amazon <span>· own it forever</span>" : "buy on amazon";
  amazonEl.classList.remove("sss-lib__mbtn--primary");
  amazonEl.hidden = !amazon;
}

if (kuButtonEl) {
  kuButtonEl.href = amazon || "#";
  kuButtonEl.hidden = !amazon || !kuState;
}

if (shopEl) {
  shopEl.classList.remove("sss-lib__mbtn--ghost");
  shopEl.classList.add("sss-lib__mbtn--bookshop");
  shopEl.href = bookshop || "#";
  shopEl.innerHTML = "prefer indie? bookshop.org →";
  shopEl.hidden = !bookshop;
}

if (kuEl) {
  if (kuState) {
    kuEl.hidden = false;
    kuEl.classList.add("is-yes");
    kuEl.classList.remove("is-no");
    kuEl.textContent = "included in your kindle unlimited subscription — no extra cost";
  } else {
    kuEl.hidden = true;
    kuEl.textContent = "";
    kuEl.classList.remove("is-yes", "is-no");
  }
}

if (window.sssRenderModalBookStatus) {
  window.sssRenderModalBookStatus(preview, currentPreviewBook);
} else {
  syncPreviewModalHeart();
}

currentPreviewShare = {
  title: title || document.title,
  author: author || "",
  url: shareUrl
};

if (previewShareButton) {
  setPreviewShareButton("📲", "share");
}

openBookPreview();
preview.__currentBook = currentPreviewBook;

});

});

if (previewClose) {
  previewClose.addEventListener("click", () => {
    closeBookPreview();
  });
}

if (preview) {
  preview.addEventListener("click", function(event){
    if (event.target.closest("[data-close]")) {
      closeBookPreview();
    }
  });
}

document.addEventListener("keydown", function(event){
  if (event.key === "Escape") closeBookPreview();
});

if (preview) {
  preview.addEventListener("click", function(event){
    const heart = event.target.closest("[data-modal-heart]");
    if (!heart || !currentPreviewBook) return;

    event.preventDefault();
    event.stopPropagation();

    const key = getPreviewBookKey(currentPreviewBook);
    if (!key) return;

    let shelf = getBlogShelf();
    const exists = shelf.some(function(item){
      const itemKey = String(item.handle || item.title || "").trim().toLowerCase();
      return itemKey && itemKey === key;
    });

    if (exists){
      shelf = shelf.filter(function(item){
        const itemKey = String(item.handle || item.title || "").trim().toLowerCase();
        return itemKey !== key;
      });
      document.dispatchEvent(new CustomEvent("bbb:shelf-unsaved", {
        detail: {
          count: shelf.length,
          bookTitle: currentPreviewBook.title || "",
          bookHandle: currentPreviewBook.handle || "",
          book: currentPreviewBook,
          source: document.body.dataset.template || "blog"
        }
      }));
    } else {
      shelf.push(currentPreviewBook);
      document.dispatchEvent(new CustomEvent("bbb:shelf-saved", {
        detail: {
          count: shelf.length,
          bookTitle: currentPreviewBook.title || "",
          bookHandle: currentPreviewBook.handle || "",
          book: currentPreviewBook,
          source: document.body.dataset.template || "blog"
        }
      }));
    }

    setBlogShelf(shelf);
    syncPreviewInlineHearts(currentPreviewBook, !exists);
    syncPreviewModalHeart();
  });
}

document.addEventListener("sss:bookshelf-updated", function(){
  syncInlineBlogHearts();
  syncPreviewModalHeart();
});

window.addEventListener("storage", function(event){
  if (event.key !== "sssMyShelf" && event.key !== "sssShelf") return;
  syncInlineBlogHearts();
  syncPreviewModalHeart();
});

if (previewShareButton) {
  previewShareButton.addEventListener("click", function (event) {
    event.preventDefault();
    event.stopPropagation();

    if (!currentPreviewShare) return;

    const shareTitle = currentPreviewShare.title || document.title;
    const shareAuthor = currentPreviewShare.author || "";
    const shareUrl = currentPreviewShare.url || window.location.href;
    const shareText = shareAuthor
      ? `next book club read: ${shareTitle} by ${shareAuthor}`
      : "share this book with your book bestie";

    if (navigator.share) {
      navigator.share({
        title: shareTitle,
        text: shareText,
        url: shareUrl
      }).catch(function () {});
      return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(shareUrl).then(function () {
        setPreviewShareButton("✓", "copied");

        setTimeout(() => {
          setPreviewShareButton("📲", "share");
        }, 1600);
      }).catch(function () {});
    }
  });
}

/* ======================
SCROLL REVEAL
====================== */

const revealItems = document.querySelectorAll(".js-scroll-reveal");

if(revealItems.length){

const observer = new IntersectionObserver(entries => {

entries.forEach(entry => {

if(entry.isIntersecting){

entry.target.classList.add("is-visible");

}

});

},{
threshold:0.2
});

revealItems.forEach(el => observer.observe(el));

}

/* ======================
BACK TO TOP
====================== */

if (document.body.classList.contains("single-post") && !document.getElementById("bbbBackToTop")) {
  const backToTop = document.createElement("button");
  backToTop.type = "button";
  backToTop.id = "bbbBackToTop";
  backToTop.className = "bbb-back-to-top";
  backToTop.setAttribute("aria-label", "back to top");
  backToTop.innerHTML = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V5"></path><path d="M6 11l6-6 6 6"></path></svg>';
  document.body.appendChild(backToTop);

  const syncBackToTop = () => {
    backToTop.classList.toggle("is-visible", window.scrollY > 520);
  };

  backToTop.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  window.addEventListener("scroll", syncBackToTop, { passive: true });
  syncBackToTop();
}
