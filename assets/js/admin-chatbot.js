/**
 * BackWPup Admin Chatbot modal controller
 * - Fresh context per click (POST to contextEndpoint)
 * - Send FULL snapshot to iframe via URL query param (base64url JSON)
 *
 * Requirements:
 * - window.BackWPupChatbot = { url, contextEndpoint, nonce, i18n: { missingUrl } }
 * - Modal markup:
 *   #backwpup-chatbot-modal
 *   #backwpup-chatbot-iframe
 *   [data-backwpup-chatbot-close]
 *   Button/link:
 *   #backwpup-open-chatbot
 */

(function () {
  function getModal() {
    return document.getElementById("backwpup-chatbot-modal");
  }

  function getIframe() {
    return document.getElementById("backwpup-chatbot-iframe");
  }

  function openModal(url) {
    var modal = getModal();
    var iframe = getIframe();

    if (!modal) return;

    if (iframe && typeof url === "string") {
      iframe.src = url;
    }

    modal.classList.add("is-open");
  }

  function closeModal() {
    var modal = getModal();
    var iframe = getIframe();

    if (iframe) {
      iframe.src = "about:blank";
    }
    if (modal) {
      modal.classList.remove("is-open");
    }
  }

  function getMissingUrlMessage() {
    return (
      (window.BackWPupChatbot &&
        BackWPupChatbot.i18n &&
        BackWPupChatbot.i18n.missingUrl) ||
      "Chatbot URL missing."
    );
  }

  // Base64URL encode for UTF-8 strings
  function base64UrlEncode(str) {
    // UTF-8 encode -> binary string
    var utf8 = encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function (_, p1) {
      return String.fromCharCode(parseInt(p1, 16));
    });

    // btoa expects "binary string"
    var b64 = window.btoa(utf8);

    // base64url
    return b64.replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/g, "");
  }

  function buildIframeUrl(baseUrl, payload) {
    var u = new URL(baseUrl);

    var json = JSON.stringify(payload);
    var encoded = base64UrlEncode(json);

    u.searchParams.set("context", encoded);

    if (payload && payload.generated_at) {
      u.searchParams.set("generated_at", String(payload.generated_at));
    }

    return u.toString();
  }

  async function openChatbotWithContext() {
    if (!window.BackWPupChatbot || !BackWPupChatbot.url) {
      alert(getMissingUrlMessage());
      return;
    }

    if (!window.fetch) {
      alert("Your browser does not support fetch().");
      return;
    }

    if (typeof URL === "undefined") {
      alert("Your browser does not support URL().");
      return;
    }

    // Open modal early (keeps UX responsive)
    openModal("about:blank");

    try {
      // Fresh snapshot per click
      var res = await fetch(BackWPupChatbot.contextEndpoint, {
        method: "POST",
        headers: {
          "X-WP-Nonce": BackWPupChatbot.nonce,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({}),
      });

      if (!res.ok) {
        alert("Could not create chatbot context.");
        closeModal();
        return;
      }

      var data = await res.json();

      // Expected: { payload: {...} } or direct snapshot object
      // We'll support both to avoid breaking changes.
      var payload = data && data.payload ? data.payload : data;

      var iframeUrl = buildIframeUrl(BackWPupChatbot.url, payload);

      var iframe = getIframe();
      if (iframe) {
        iframe.src = iframeUrl;
      } else {
        openModal(iframeUrl);
      }
    } catch (err) {
      alert("Could not open chatbot.");
      closeModal();
    }
  }

  document.addEventListener("click", function (e) {
    // Open
    var openBtn =
      e.target &&
      e.target.closest &&
      e.target.closest("#backwpup-open-chatbot");

    if (openBtn) {
      e.preventDefault();
      openChatbotWithContext();
      return;
    }

    // Close (button inside modal, or overlay click)
    var closeBtn =
      e.target &&
      e.target.closest &&
      e.target.closest("[data-backwpup-chatbot-close]");
    var overlay = e.target && e.target.id === "backwpup-chatbot-modal";

    if (closeBtn || overlay) {
      e.preventDefault();
      closeModal();
    }
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeModal();
    }
  });
})();
