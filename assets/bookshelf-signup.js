(function(){
  var SUPABASE_URL = "https://efmrfxsmgbeikfgtrxjv.supabase.co";
  var SUPABASE_KEY = "sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk";
  var SUBSCRIBERS_TABLE = "bookshelf_subscribers";
  var SAVED_BOOKS_TABLE = "bookshelf_saved_books";
  var STATUSES_TABLE = "bookshelf_book_statuses";
  var POPUP_STATE_KEY = "bbbBookshelfSignupState";
  var PENDING_SUBMISSION_KEY = "bbbBookshelfSignupPending";
  var TRIGGERS = [2, 5];

  var root = document.querySelector("[data-bbb-shelf-signup]");
  if (!root) return;

  var form = document.getElementById("BBBBookshelfSignupForm");
  var emailInput = document.getElementById("bbbShelfSignupEmail");
  var closeButtons = root.querySelectorAll("[data-bbb-shelf-close]");
  var supabaseClient = window.supabase && window.supabase.createClient
    ? window.supabase.createClient(SUPABASE_URL, SUPABASE_KEY)
    : null;

  function inDesignMode(){
    return !!(window.Shopify && window.Shopify.designMode);
  }

  function getShelfItems(){
    try {
      var primary = JSON.parse(localStorage.getItem("sssMyShelf") || "[]") || [];
      if (Array.isArray(primary) && primary.length) return primary;
      return JSON.parse(localStorage.getItem("sssShelf") || "[]") || [];
    } catch (err) {
      return [];
    }
  }

  function getState(){
    try {
      var parsed = JSON.parse(localStorage.getItem(POPUP_STATE_KEY) || "{}") || {};
      return {
        shownAt: Array.isArray(parsed.shownAt) ? parsed.shownAt : [],
        subscribed: parsed.subscribed === true,
        email: parsed.email || "",
        lastCount: Number(parsed.lastCount || 0)
      };
    } catch (err) {
      return { shownAt: [], subscribed: false, email: "", lastCount: 0 };
    }
  }

  function setState(next){
    var state = {
      shownAt: Array.isArray(next.shownAt) ? next.shownAt : [],
      subscribed: next.subscribed === true,
      email: next.email || "",
      lastCount: Number(next.lastCount || 0)
    };
    localStorage.setItem(POPUP_STATE_KEY, JSON.stringify(state));
    return state;
  }

  function getSessionId(){
    try {
      var existing = localStorage.getItem("sssAnalyticsSessionId");
      if (existing) return existing;
      var created = "sss-" + Date.now() + "-" + Math.random().toString(36).slice(2, 10);
      localStorage.setItem("sssAnalyticsSessionId", created);
      return created;
    } catch (err) {
      return "sss-" + Date.now() + "-" + Math.random().toString(36).slice(2, 10);
    }
  }

  function normalizeEmail(email){
    return String(email || "").trim().toLowerCase();
  }

  function getReaderAccount(){
    return window.BBBReaderAccount || {};
  }

  function getReaderCustomerId(){
    var account = getReaderAccount();
    return account && account.loggedIn && account.customerId ? String(account.customerId) : "";
  }

  function getReaderEmail(){
    var account = getReaderAccount();
    return account && account.loggedIn && account.email ? String(account.email) : "";
  }

  function getBookKey(book){
    if (!book) return "";
    return String(book.handle || book.title || "").trim().toLowerCase();
  }

  function getSubscriberEmail(){
    var state = getState();
    return normalizeEmail(state.email || getReaderEmail() || "");
  }

  async function ensureSubscriberRecord(emailNormalized){
    if (!supabaseClient || !emailNormalized) return;

    var state = getState();
    var account = getReaderAccount();
    var accountEmail = getReaderEmail();
    try {
      await supabaseClient.from(SUBSCRIBERS_TABLE).upsert([
        {
          email: state.email || accountEmail || emailNormalized,
          email_normalized: emailNormalized,
          wordpress_user_id: getReaderCustomerId() || null,
          shopify_customer_id: getReaderCustomerId() || null,
          customer_email: accountEmail || state.email || emailNormalized,
          account_status: account && account.loggedIn ? "logged_in" : "email_only",
          access_tier: account && account.isSociety ? "society" : "free",
          session_id: getSessionId(),
          source: account && account.loggedIn ? "wordpress_account" : "bookshelf_popup",
          last_synced_at: new Date().toISOString(),
          metadata: {
            shelf_count: getShelfItems().length,
            source_page: window.location.pathname,
            popup_triggers: state.shownAt || []
          }
        }
      ], {
        onConflict: "email_normalized"
      });
    } catch (err) {
      console.log("Bookshelf subscriber backfill failed", err);
    }
  }

  function getStoredStatuses(){
    try {
      return JSON.parse(localStorage.getItem("sssBookStatuses") || "{}") || {};
    } catch (err) {
      return {};
    }
  }

  function getBookSnapshot(book){
    if (!book) return null;
    return {
      handle: book.handle || book.bookHandle || "",
      title: book.title || book.bookTitle || "",
      author: book.author || "",
      cover: book.cover || "",
      amazon: book.amazon || "",
      bookshop: book.bookshop || "",
      spice: book.spice || "",
      darkness: book.darkness || "",
      tropes: book.tropes || "",
      tropesDisplay: book.tropesDisplay || ""
    };
  }

  function splitTropes(value){
    if (Array.isArray(value)) return value;
    return String(value || "")
      .split(",")
      .map(function(trope){ return trope.trim(); })
      .filter(Boolean);
  }

  async function syncSavedBookRecord(emailNormalized, book, isActive){
    if (!supabaseClient || !emailNormalized || !book) return;

    var key = getBookKey(book);
    if (!key) return;

    try {
      if (isActive){
        await supabaseClient.from(SAVED_BOOKS_TABLE).upsert([
          {
          email_normalized: emailNormalized,
          wordpress_user_id: getReaderCustomerId() || null,
          shopify_customer_id: getReaderCustomerId() || null,
          customer_email: getReaderEmail() || emailNormalized,
          book_key: key,
          book_handle: book.handle || null,
          book_title: book.title || "",
            author: book.author || null,
            cover: book.cover || null,
            amazon: book.amazon || null,
          bookshop: book.bookshop || null,
          spice_level: Number(book.spice || 0) || null,
          darkness_level: Number(book.darkness || 0) || null,
          tropes: splitTropes(book.tropesDisplay || book.tropes),
          source: getReaderCustomerId() ? "wordpress_account" : "site",
          is_active: true,
          removed_at: null
        }
        ], {
          onConflict: "email_normalized,book_key"
        });
      } else {
        await supabaseClient
          .from(SAVED_BOOKS_TABLE)
          .update({
            is_active: false,
            removed_at: new Date().toISOString()
          })
          .eq("email_normalized", emailNormalized)
          .eq("book_key", key);
      }
    } catch (err) {
      console.log("Bookshelf saved-book sync failed", err);
    }
  }

  async function syncAllStatuses(emailNormalized){
    if (!supabaseClient || !emailNormalized) return;

    var statuses = getStoredStatuses();
    var keys = Object.keys(statuses || {});

    try {
      await supabaseClient
        .from(STATUSES_TABLE)
        .delete()
        .eq("email_normalized", emailNormalized);

      if (!keys.length) return;

      var shelfItems = getShelfItems();
      var shelfMap = {};
      shelfItems.forEach(function(item){
        var key = getBookKey(item);
        if (key) shelfMap[key] = item;
      });

      await supabaseClient.from(STATUSES_TABLE).insert(
        keys.map(function(key){
          var book = shelfMap[key] || {};
          return {
          email_normalized: emailNormalized,
          wordpress_user_id: getReaderCustomerId() || null,
          shopify_customer_id: getReaderCustomerId() || null,
          customer_email: getReaderEmail() || emailNormalized,
          book_key: key,
          book_handle: book.handle || null,
          book_title: book.title || key,
          status: statuses[key],
          source: getReaderCustomerId() ? "wordpress_account" : "site",
          metadata: {}
        };
        })
      );
    } catch (err) {
      console.log("Bookshelf status sync failed", err);
    }
  }

  function getShelfRedirectUrl(sourcePage){
    return "/library/?shelf=open";
  }

  function queuePendingSubmission(email){
    var shelf = getShelfItems().map(function(book){
      return {
        handle: book.handle || "",
        title: book.title || "",
        author: book.author || "",
        cover: book.cover || "",
        amazon: book.amazon || "",
        bookshop: book.bookshop || ""
      };
    }).filter(function(book){
      return !!book.title;
    });

    var payload = {
      email: String(email || "").trim(),
      email_normalized: normalizeEmail(email),
      shelf: shelf,
      shelf_count: shelf.length,
      session_id: getSessionId(),
      source_page: window.location.pathname,
      redirect_url: getShelfRedirectUrl(window.location.pathname),
      submitted_at: new Date().toISOString()
    };

    sessionStorage.setItem(PENDING_SUBMISSION_KEY, JSON.stringify(payload));
  }

  async function trackPopupEvent(eventType, metadata){
    if (!supabaseClient || !eventType || inDesignMode()) return;

    try {
      await supabaseClient.from("site_events").insert([
        {
          session_id: getSessionId(),
          event_type: eventType,
          page_path: window.location.pathname,
          page_title: document.title,
          ui_location: "bookshelf_signup_popup",
          metadata: metadata || {}
        }
      ]);
    } catch (err) {
      console.log("Bookshelf popup tracking failed", err);
    }
  }

  async function syncPendingSubmission(){
    var raw = sessionStorage.getItem(PENDING_SUBMISSION_KEY);
    if (!raw) return;

    try {
      var pending = JSON.parse(raw);
      if (!pending || !pending.email_normalized) return;

      if (supabaseClient){
        var account = getReaderAccount();
        await supabaseClient.from(SUBSCRIBERS_TABLE).upsert([
          {
            email: pending.email,
            email_normalized: pending.email_normalized,
            wordpress_user_id: getReaderCustomerId() || null,
            shopify_customer_id: getReaderCustomerId() || null,
            customer_email: getReaderEmail() || pending.email,
            account_status: account && account.loggedIn ? "logged_in" : "email_only",
            access_tier: account && account.isSociety ? "society" : "free",
            session_id: pending.session_id,
            source: "bookshelf_popup",
            last_synced_at: new Date().toISOString(),
            metadata: {
              shelf_count: pending.shelf_count,
              source_page: pending.source_page,
              popup_triggers: getState().shownAt
            }
          }
        ], {
          onConflict: "email_normalized"
        });

        if (Array.isArray(pending.shelf) && pending.shelf.length){
          await supabaseClient.from(SAVED_BOOKS_TABLE).upsert(
            pending.shelf.map(function(book){
              return {
                email_normalized: pending.email_normalized,
                wordpress_user_id: getReaderCustomerId() || null,
                shopify_customer_id: getReaderCustomerId() || null,
                customer_email: getReaderEmail() || pending.email_normalized,
                book_key: getBookKey(book),
                book_handle: book.handle || null,
                book_title: book.title || "",
                author: book.author || null,
                cover: book.cover || null,
                amazon: book.amazon || null,
                bookshop: book.bookshop || null,
                spice_level: Number(book.spice || 0) || null,
                darkness_level: Number(book.darkness || 0) || null,
                tropes: splitTropes(book.tropesDisplay || book.tropes),
                source: "bookshelf_popup"
              };
            }).filter(function(book){
              return !!book.book_key;
            }),
            { onConflict: "email_normalized,book_key" }
          );
        }

        await syncAllStatuses(pending.email_normalized);
      }

      var state = getState();
      state.subscribed = true;
      state.email = pending.email;
      state.lastCount = pending.shelf_count || getShelfItems().length;
      setState(state);
      sessionStorage.removeItem(PENDING_SUBMISSION_KEY);

      window.setTimeout(function(){
        closeModal();
      }, 700);
    } catch (err) {
      console.log("Bookshelf signup sync failed", err);
    }
  }

  function openModal(triggerCount, options){
    options = options || {};
    var account = getReaderAccount();
    if (account && account.loggedIn && options.manual === true){
      window.location.href = account.bookshelfUrl || "/my-bookshelf/";
      return;
    }
    var state = getState();
    if (state.subscribed && options.manual !== true) return;
    if (inDesignMode() && options.manual !== true) return;

    if (typeof triggerCount === "number" && state.shownAt.indexOf(triggerCount) === -1){
      state.shownAt.push(triggerCount);
      state.shownAt.sort();
      setState(state);
    }

    root.classList.add("is-open");
    document.documentElement.classList.add("bbb-shelf-signup-open");
    document.body.classList.add("bbb-shelf-signup-open");
    if (emailInput) {
      window.setTimeout(function(){
        emailInput.focus();
      }, 40);
    }

    trackPopupEvent("bookshelf_signup_popup_shown", {
      trigger_count: triggerCount || null,
      shelf_count: getShelfItems().length
    });
  }

  window.BBBShelfSignup = window.BBBShelfSignup || {};
  window.BBBShelfSignup.open = function(){
    openModal(null, { manual: true });
  };

  function closeModal(){
    var wasOpen = root.classList.contains("is-open");
    root.classList.remove("is-open");
    document.documentElement.classList.remove("bbb-shelf-signup-open");
    document.body.classList.remove("bbb-shelf-signup-open");
    if (wasOpen){
      trackPopupEvent("bookshelf_signup_popup_dismissed", {
        shelf_count: getShelfItems().length
      });
    }
  }

  function maybeTrigger(count){
    var account = getReaderAccount();
    if (account && account.loggedIn) return;

    var state = getState();
    state.lastCount = Math.max(state.lastCount || 0, count || 0);
    setState(state);

    if (state.subscribed) return;

    var nextTrigger = TRIGGERS.find(function(value){
      return count >= value && state.shownAt.indexOf(value) === -1;
    });

    if (!nextTrigger) return;

    window.setTimeout(function(){
      openModal(nextTrigger);
    }, 650);
  }

  closeButtons.forEach(function(button){
    button.addEventListener("click", function(){
      closeModal();
    });
  });

  function bindManualOpenTriggers(){
    var triggers = document.querySelectorAll("[data-bbb-shelf-open]");
    triggers.forEach(function(trigger){
      if (trigger.dataset.bbbShelfBound === "true") return;
      trigger.dataset.bbbShelfBound = "true";
      trigger.addEventListener("click", function(event){
        event.preventDefault();
        openModal(null, { manual: true });
      });
    });
  }

  bindManualOpenTriggers();

  document.addEventListener("bbb:open-shelf-signup", function(){
    openModal(null, { manual: true });
  });

  document.addEventListener("keydown", function(event){
    if (event.key === "Escape" && root.classList.contains("is-open")){
      closeModal();
    }
  });

  document.addEventListener("bbb:shelf-saved", function(event){
    var detail = event.detail || {};
    maybeTrigger(Number(detail.count || getShelfItems().length || 0));
    var emailNormalized = getSubscriberEmail();
    if (!emailNormalized) return;

    ensureSubscriberRecord(emailNormalized);
    syncSavedBookRecord(emailNormalized, {
      handle: (detail.book && detail.book.handle) || detail.bookHandle || "",
      title: (detail.book && detail.book.title) || detail.bookTitle || "",
      author: detail.book && detail.book.author || "",
      cover: detail.book && detail.book.cover || "",
      amazon: detail.book && detail.book.amazon || "",
      bookshop: detail.book && detail.book.bookshop || "",
      spice: detail.book && detail.book.spice || "",
      darkness: detail.book && detail.book.darkness || "",
      tropes: detail.book && detail.book.tropes || "",
      tropesDisplay: detail.book && detail.book.tropesDisplay || ""
    }, true);
  });

  document.addEventListener("bbb:shelf-unsaved", function(event){
    var detail = event.detail || {};
    var emailNormalized = getSubscriberEmail();
    if (!emailNormalized) return;

    ensureSubscriberRecord(emailNormalized);
    syncSavedBookRecord(emailNormalized, {
      handle: (detail.book && detail.book.handle) || detail.bookHandle || "",
      title: (detail.book && detail.book.title) || detail.bookTitle || "",
      author: detail.book && detail.book.author || "",
      cover: detail.book && detail.book.cover || "",
      amazon: detail.book && detail.book.amazon || "",
      bookshop: detail.book && detail.book.bookshop || "",
      spice: detail.book && detail.book.spice || "",
      darkness: detail.book && detail.book.darkness || "",
      tropes: detail.book && detail.book.tropes || "",
      tropesDisplay: detail.book && detail.book.tropesDisplay || ""
    }, false);
  });

  document.addEventListener("sss:bookshelf-updated", function(event){
    var detail = event.detail || {};
    var state = getState();
    state.lastCount = Number(detail.count || getShelfItems().length || 0);
    setState(state);
  });

  document.addEventListener("sss:shelf-updated", function(event){
    var detail = event.detail || {};
    var state = getState();
    state.lastCount = Number(detail.count || getShelfItems().length || 0);
    setState(state);
  });

  document.addEventListener("bbb:book-status-changed", function(){
    var emailNormalized = getSubscriberEmail();
    if (!emailNormalized) return;
    ensureSubscriberRecord(emailNormalized);
    syncAllStatuses(emailNormalized);
  });

  if (form){
    form.addEventListener("submit", function(){
      if (!emailInput || !emailInput.value) return;
      queuePendingSubmission(emailInput.value);
      trackPopupEvent("bookshelf_signup_submitted", {
        shelf_count: getShelfItems().length
      });
    });
  }

  if (root.dataset.success === "true"){
    syncPendingSubmission();
  }
})();
