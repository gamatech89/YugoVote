/**
 * Tournament Arena JavaScript - Random Access + Auto-Advance
 * Vote handler with auto-reload navigation
 */

jQuery(document).ready(function ($) {
  // FIX 1: Read from .yuv-duel-arena which has the data attributes
  const $duelArena = $(".yuv-duel-arena");
  if (!$duelArena.length) {
    console.error("YUV Tournament: .yuv-duel-arena not found");
    return;
  }

  const matchId = $duelArena.data("match-id");
  const tournamentId = $duelArena.data("tournament-id");
  const endTime = parseInt($duelArena.data("end-time"));
  const $arena = $(".yuv-arena-wrapper");
  const hasVoted = $arena.hasClass("yuv-show-results");
  
  // FIX 1: Debug logging
  console.log("YUV Tournament Init:", {
    matchId: matchId,
    tournamentId: tournamentId,
    endTime: endTime,
    hasVoted: hasVoted
  });
  
  if (!matchId) {
    console.error("YUV Tournament: match_id missing from .yuv-duel-arena!");
    return;
  }

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
  // VOTE BUTTON HANDLER - NEW SIMPLIFIED FLOW
  // ========================================================================

  $(".yuv-vote-btn").on("click", function (e) {
    e.preventDefault();

    const btn = $(this);
    const itemId = btn.data("item-id");
    const contender = btn.closest(".yuv-contender");

    // Disable all vote buttons
    $(".yuv-vote-btn").prop("disabled", true);
    btn.html(
      '<span class="yuv-vote-icon">⏳</span><span class="yuv-vote-text">GLASANJE...</span>'
    );

    // FIX 1: Prepare and log payload
    const payload = {
      action: "yuv_cast_tournament_vote",
      _ajax_nonce: yuvTournamentData.nonce,
      match_id: matchId,
      item_id: itemId,
    };
    
    console.log("YUV Tournament: Sending vote payload:", payload);
    
    if (!payload.match_id || !payload.item_id) {
      console.error("YUV Tournament: Invalid payload!", payload);
      alert("Greška: Nevažeći podaci. Osvežite stranicu.");
      $(".yuv-vote-btn").prop("disabled", false);
      btn.html('<span class="yuv-vote-icon">⚡</span><span class="yuv-vote-text">GLASAJ</span>');
      return;
    }

    // Send AJAX vote
    $.ajax({
      url: yuvTournamentData.ajaxurl,
      type: "POST",
      data: payload,
      success: function (response) {
        if (response.success) {
          // Add results state class
          arena.addClass("yuv-show-results");

          // Mark winner
          contender.addClass("is-winner");

          // Update percentages and vote counts from response
          if (response.data.results) {
            const results = response.data.results;

            $(".yuv-contender").each(function () {
              const $cont = $(this);
              const contId = $cont.data("contender-id");
              const result = results.find((r) => r.id == contId);

              if (result) {
                $cont.find(".yuv-percent").text(result.percent + "%");
                $cont
                  .find(".yuv-vote-count")
                  .text(result.votes.toLocaleString() + " glasova");
                $cont
                  .find(".yuv-result-bar")
                  .css("width", result.percent + "%");
              }
            });
          }

          // Show success toast
          showToast("Tvoj glas je zabeležen!");

          // Wait 2 seconds, then reload without params (auto-advance)
          setTimeout(function () {
            window.location.href = window.location.pathname;
          }, 2000);
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
        console.error("YUV Tournament: AJAX error:", {
          status: status,
          error: error,
          response: xhr.responseText
        });
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
    if (toast.length) {
      toast.find(".yuv-toast-message").text(message);
      toast.show();

      setTimeout(function () {
        toast.addClass("hiding");
        setTimeout(function () {
          toast.hide().removeClass("hiding");
        }, 400);
      }, 3000);
    }
  }

  // ========================================================================
  // AUTO-SCROLL TO CURRENT MATCH IN NAV STRIP
  // ========================================================================

  const currentNavItem = $(".yuv-nav-item.current");
  if (currentNavItem.length) {
    const navStrip = $(".yuv-nav-strip");
    const scrollLeft =
      currentNavItem.offset().left -
      navStrip.offset().left -
      navStrip.width() / 2 +
      currentNavItem.width() / 2;
    navStrip.scrollLeft(navStrip.scrollLeft() + scrollLeft);
  }

  // ========================================================================
  // RESULT BARS ANIMATION (if already voted)
  // ========================================================================

  if (hasVoted) {
    setTimeout(function () {
      $(".yuv-result-bar").each(function () {
        const bar = $(this);
        const targetWidth = bar.css("width");

        // Start from 0 and animate to target width
        bar.css("width", "0");
        setTimeout(function () {
          bar.css("width", targetWidth);
        }, 100);
      });
    }, 300);
  }
});
