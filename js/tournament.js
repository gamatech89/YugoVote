/**
 * Tournament Duel Arena JavaScript
 * Countdown timer, voting, and interactive features
 */

jQuery(document).ready(function ($) {
  const arena = $(".yuv-duel-arena");
  if (!arena.length) return;

  const endTime = parseInt(arena.data("end-time"));
  const matchId = arena.data("match-id");
  const hasVoted = arena.data("user-voted") === "true";

  // ========================================================================
  // COUNTDOWN TIMER
  // ========================================================================

  function updateTimer() {
    const now = Math.floor(Date.now() / 1000);
    const remaining = endTime - now;

    if (remaining <= 0) {
      $("#timer-hours").text("00");
      $("#timer-minutes").text("00");
      $("#timer-seconds").text("00");
      $(".yuv-vote-btn").prop("disabled", true).text("VREME ISTEKLO");
      return;
    }

    const hours = Math.floor(remaining / 3600);
    const minutes = Math.floor((remaining % 3600) / 60);
    const seconds = remaining % 60;

    $("#timer-hours").text(String(hours).padStart(2, "0"));
    $("#timer-minutes").text(String(minutes).padStart(2, "0"));
    $("#timer-seconds").text(String(seconds).padStart(2, "0"));
  }

  // Update timer every second
  if (!hasVoted) {
    updateTimer();
    setInterval(updateTimer, 1000);
  }

  // ========================================================================
  // VOTE BUTTON HANDLER
  // ========================================================================

  $(".yuv-vote-btn").on("click", function (e) {
    e.preventDefault();

    const btn = $(this);
    const itemId = btn.data("item-id");

    // Disable all vote buttons
    $(".yuv-vote-btn").prop("disabled", true);
    btn.html(
      '<span class="yuv-vote-icon">⏳</span><span class="yuv-vote-text">GLASANJE...</span>'
    );

    // Send AJAX vote
    $.ajax({
      url: yuvTournamentData.ajaxurl,
      type: "POST",
      data: {
        action: "yuv_cast_tournament_vote",
        _ajax_nonce: yuvTournamentData.nonce,
        match_id: matchId,
        item_id: itemId,
      },
      success: function (response) {
        if (response.success) {
          // Show success toast
          showToast("Tvoj glas je zabeležen!");

          // Redirect to next match if available, otherwise reload to show results
          setTimeout(function () {
            if (response.data.next_match_url) {
              window.location.href = response.data.next_match_url;
            } else {
              location.reload();
            }
          }, 1500);
        } else {
          alert(
            response.data.message || "Greška pri glasanju. Pokušaj ponovo."
          );
          $(".yuv-vote-btn").prop("disabled", false);
          btn.html(
            '<span class="yuv-vote-icon">⚡</span><span class="yuv-vote-text">GLASAJ</span>'
          );
        }
      },
      error: function (xhr, status, error) {
        alert("Greška pri glasanju. Pokušajte ponovo.");
        $(".yuv-vote-btn").prop("disabled", false);
        btn.html(
          '<span class="yuv-vote-icon">⚡</span><span class="yuv-vote-text">GLASAJ</span>'
        );
      },
    });
  });

  // ========================================================================
  // TOAST NOTIFICATION
  // ========================================================================

  function showToast(message) {
    const toast = $("#yuv-vote-toast");
    toast.find(".yuv-toast-message").text(message);
    toast.show();

    setTimeout(function () {
      toast.addClass("hiding");
      setTimeout(function () {
        toast.hide().removeClass("hiding");
      }, 400);
    }, 3000);
  }

  // ========================================================================
  // RESULT BARS ANIMATION (if already voted)
  // ========================================================================

  if (hasVoted) {
    setTimeout(function () {
      $(".yuv-result-bar").each(function () {
        const bar = $(this);
        const fill = bar.find(".yuv-bar-fill");
        const targetHeight = fill.css("height");

        // Start from 0 and animate to target height
        fill.css("height", "0");
        setTimeout(function () {
          fill.css("height", targetHeight);
        }, 100);
      });
    }, 500);
  }

  // ========================================================================
  // CONTENDER HOVER EFFECTS
  // ========================================================================

  $(".yuv-contender:not(.voted)")
    .on("mouseenter", function () {
      $(this).find(".yuv-contender-img").css("filter", "brightness(1.2)");
    })
    .on("mouseleave", function () {
      $(this).find(".yuv-contender-img").css("filter", "brightness(1)");
    });

  // Timeline section removed for better UX
});
