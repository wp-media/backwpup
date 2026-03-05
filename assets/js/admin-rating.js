document.addEventListener("click", function (e) {
  const btn = e.target.closest(".notice-backwpup_rate .bwu-leave-review");
  if (!btn) return;

  let notice = btn.closest("[data-notice-id]");
  let noticeId = notice ? notice.getAttribute("data-notice-id") : null;
  if (!notice && window.BackWPupRating && BackWPupRating.notice_id) {
    noticeId = BackWPupRating.notice_id;
  }
  if (!notice && noticeId) {
    notice = document.getElementById(noticeId);
  }

  if (notice) {
    notice.style.transition = "opacity 180ms ease";
    notice.style.opacity = "0.2";
    window.setTimeout(function () {
      if (notice && notice.parentNode) {
        notice.parentNode.removeChild(notice);
      }
    }, 200);
  }

  if (!window.BackWPupRating) return;

  const body = new URLSearchParams();
  body.append("action", "backwpup_rating_notice_leave_review");
  body.append("_ajax_nonce", BackWPupRating.nonce);

  if (navigator.sendBeacon) {
    navigator.sendBeacon(BackWPupRating.ajax_url, body);
    return;
  }

  // Fallback (fire-and-forget).
  fetch(BackWPupRating.ajax_url, {
    method: "POST",
    body,
    credentials: "same-origin",
    keepalive: true,
  }).catch(function () {
    // Intentionally ignore failures; UI is already dismissed.
  });
});
