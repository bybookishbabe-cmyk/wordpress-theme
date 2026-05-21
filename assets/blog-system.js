/* ======================
BLOG SYSTEM JS
Handles:
- Share button
- Shelf saving
- Scroll popups
- Signoff animation
====================== */

document.addEventListener("DOMContentLoaded", function () {

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
const preview = document.getElementById("bbbBookPreview");
const previewClose = document.getElementById("bbbPreviewClose");
const previewShareButton = document.getElementById("bbbPreviewShare");
let currentPreviewShare = null;
let currentPreviewBook = null;
let previewScrollY = 0;

function blogReaderIsSociety(){
  return Boolean(window.BBBReaderAccount && window.BBBReaderAccount.isSociety);
}

function lockPreviewScroll(){
  previewScrollY = window.scrollY || document.documentElement.scrollTop || 0;
  document.body.classList.add("bbb-book-preview-open");
  document.body.style.top = `-${previewScrollY}px`;
  document.body.style.position = "fixed";
  document.body.style.width = "100%";
}

function unlockPreviewScroll(){
  document.body.classList.remove("bbb-book-preview-open");
  document.body.style.position = "";
  document.body.style.top = "";
  document.body.style.width = "";
  window.scrollTo(0, previewScrollY || 0);
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
const amazon = card.dataset.amazon;
const bookshop = card.dataset.bookshop;
const newsletter = card.dataset.newsletter;
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
  title: title || "",
  author: author || "",
  cover: cover || "",
  amazon: amazon || "",
  bookshop: bookshop || "",
  newsletter: newsletter || "",
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
const hasKu = String(ku || "").trim() !== "";
const kuState = String(ku || "").toLowerCase().trim() === "true";
if (!preview) return;

const titleEl = preview.querySelector("[data-mtitle]");
const authorEl = preview.querySelector("[data-mauthor]");
const coverEl = preview.querySelector("[data-mcover]");
const amazonEl = preview.querySelector("[data-amazon-btn]");
const shopEl = preview.querySelector("[data-bookshop-btn]");
const newsletterEl = preview.querySelector("[data-newsletter-btn]");
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

if (titleEl) titleEl.textContent = title || "";
if (authorEl) authorEl.textContent = author ? "by " + author : "";
if (coverEl) {
  coverEl.src = cover || "";
  coverEl.alt = title || "";
}
if (miniEl) miniEl.textContent = mini ? "quick summary: " + mini : "";
if (tropesEl) {
  const modalTropes = tropesDisplay || tropes || "";
  tropesEl.textContent = modalTropes ? "tropes: " + modalTropes : "";
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
    const seriesHref = series ? `/pages/series?series=${encodeURIComponent(series || "")}` : "#";
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
  amazonEl.hidden = !amazon;
}

if (shopEl) {
  shopEl.classList.remove("sss-lib__mbtn--ghost");
  shopEl.classList.add("sss-lib__mbtn--bookshop");
  shopEl.href = bookshop || "#";
  shopEl.hidden = !bookshop;
}

if (newsletterEl) {
  const showNewsletter = blogReaderIsSociety() && newsletter;
  newsletterEl.href = showNewsletter ? newsletter : "#";
  newsletterEl.hidden = !showNewsletter;
}

if (kuEl) {
  if (hasKu) {
    kuEl.hidden = false;
    kuEl.classList.toggle("is-yes", kuState);
    kuEl.classList.toggle("is-no", !kuState);
    kuEl.textContent = `${kuState ? "✓" : "✕"} on kindle unlimited: ${kuState ? "yes" : "no"}`;
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
  previewShareButton.textContent = "📲";
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
        previewShareButton.textContent = "✓";

        setTimeout(() => {
          previewShareButton.textContent = "📲";
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
