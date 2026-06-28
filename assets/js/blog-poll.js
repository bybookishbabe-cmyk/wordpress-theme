(function () {
  var config = window.BBBBlogPollData || {};
  var endpoint = config.endpoint || "";

  function voterId() {
    var key = "bbb_blog_poll_voter";
    try {
      var existing = window.localStorage.getItem(key);
      if (existing) return existing;
      var next = "poll_" + Date.now().toString(36) + "_" + Math.random().toString(36).slice(2);
      window.localStorage.setItem(key, next);
      return next;
    } catch (error) {
      return "poll_fallback_" + Math.random().toString(36).slice(2);
    }
  }

  function optionKeys(root) {
    try {
      return JSON.parse(root.dataset.options || "[]");
    } catch (error) {
      return [];
    }
  }

  function optionConfig(root) {
    try {
      return JSON.parse(root.dataset.optionConfig || "[]");
    } catch (error) {
      return [];
    }
  }

  function request(root, method, option) {
    var body = {
      poll_key: root.dataset.pollKey || "",
      post_id: root.dataset.postId || "",
      question: root.dataset.question || "",
      options: optionKeys(root),
      option_config: optionConfig(root)
    };
    if (option) {
      body.option = option;
      body.voter_id = voterId();
    }

    var url = endpoint;
    var fetchOptions = {
      method: method,
      headers: {
        "Content-Type": "application/json"
      }
    };

    if (config.nonce) {
      fetchOptions.headers["X-WP-Nonce"] = config.nonce;
    }

    if (method === "GET") {
      var params = new URLSearchParams({
        poll_key: body.poll_key,
        post_id: body.post_id
      });
      body.options.forEach(function (key) {
        params.append("options[]", key);
      });
      params.append("question", body.question);
      params.append("option_config", JSON.stringify(body.option_config));
      url += "?" + params.toString();
    } else {
      fetchOptions.body = JSON.stringify(body);
    }

    return fetch(url, fetchOptions).then(function (response) {
      if (!response.ok) throw new Error("poll request failed");
      return response.json();
    });
  }

  function render(root, data) {
    var total = Number(data.total || 0);
    var selected = data.selected || window.localStorage.getItem("bbb_blog_poll_selected_" + root.dataset.pollKey);
    var status = root.querySelector("[data-poll-status]");
    var canShowResults = Boolean(selected);

    root.classList.toggle("has-results", total > 0 && canShowResults);
    root.querySelectorAll("[data-poll-option]").forEach(function (button) {
      var key = button.dataset.pollOption || "";
      var percent = data.percent && typeof data.percent[key] !== "undefined" ? Number(data.percent[key]) : 0;
      var count = data.counts && typeof data.counts[key] !== "undefined" ? Number(data.counts[key]) : 0;
      var bar = button.querySelector(".bbb-blog-poll__bar");
      var label = button.querySelector(".bbb-blog-poll__percent");

      button.classList.toggle("is-selected", key === selected);
      if (bar) bar.style.width = canShowResults ? percent + "%" : "0";
      if (label) label.textContent = canShowResults ? percent + "%" : "";
      button.setAttribute("aria-label", canShowResults ? button.innerText.trim() + ", " + count + " votes" : button.innerText.trim());
    });

    if (status) {
      status.textContent = canShowResults ? "results are in." : "Vote to see the results.";
    }
  }

  function selectedButton(root, option) {
    if (!option) return null;
    try {
      return root.querySelector('[data-poll-option="' + window.CSS.escape(option) + '"]');
    } catch (error) {
      return root.querySelector('[data-poll-option="' + option.replace(/"/g, '\\"') + '"]');
    }
  }

  function celebrate(root, option) {
    var button = selectedButton(root, option);
    if (!button) return;

    button.classList.remove("is-celebrating");
    void button.offsetWidth;
    button.classList.add("is-celebrating");
    window.setTimeout(function () {
      button.classList.remove("is-celebrating");
    }, 1300);

    if (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

    var layer = document.createElement("div");
    layer.className = "bbb-blog-poll__confetti";
    layer.setAttribute("aria-hidden", "true");

    var colors = ["#f7a4c8", "#ffd166", "#9ee6cf", "#bda7ff", "#ffffff"];
    for (var i = 0; i < 28; i++) {
      var piece = document.createElement("span");
      piece.style.left = Math.round(Math.random() * 100) + "%";
      piece.style.setProperty("--confetti-color", colors[i % colors.length]);
      piece.style.setProperty("--confetti-drift", Math.round((Math.random() * 80) - 40) + "px");
      piece.style.setProperty("--confetti-rotate", Math.round((Math.random() * 520) + 120) + "deg");
      piece.style.animationDelay = (Math.random() * 0.22).toFixed(2) + "s";
      piece.style.animationDuration = (0.9 + Math.random() * 0.55).toFixed(2) + "s";
      layer.appendChild(piece);
    }

    root.appendChild(layer);
    window.setTimeout(function () {
      layer.remove();
    }, 1800);
  }

  function init(root) {
    if (!endpoint || root.dataset.pollReady === "true") return;
    root.dataset.pollReady = "true";

    root.querySelectorAll("[data-poll-option]").forEach(function (button) {
      button.addEventListener("click", function () {
        var option = button.dataset.pollOption || "";
        var status = root.querySelector("[data-poll-status]");
        if (status) status.textContent = "Saving your vote...";

        request(root, "POST", option)
          .then(function (data) {
            try {
              window.localStorage.setItem("bbb_blog_poll_selected_" + root.dataset.pollKey, option);
            } catch (error) {}
            render(root, data);
            celebrate(root, option);
          })
          .catch(function () {
            if (status) status.textContent = "The poll is being stubborn. Try again in a second.";
          });
      });
    });

    request(root, "GET").then(function (data) {
      render(root, data);
    }).catch(function () {});
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-bbb-blog-poll]").forEach(init);
  });
})();
