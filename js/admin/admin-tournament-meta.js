/**
 * Tournament Meta Admin JavaScript
 * Handles contestant counter, category filter, and tournament size selector
 */

jQuery(document).ready(function ($) {
  const $contestantList = $("#yuv-contestant-list");
  const $counter = $("#contestant-counter");
  const $maxCount = $("#contestant-max");
  const $addBtn = $(".yuv-add-contestant-btn");
  const $sizeSelect = $("#yuv_tournament_size");
  const $categoryFilter = $("#yuv-category-filter");
  const $searchInput = $("#yuv-contestant-search");

  /**
   * Update contestant counter
   */
  function updateCounter() {
    const currentCount = $contestantList.find(".yuv-contestant-item").length;
    const maxCount = parseInt($maxCount.text()) || 16;

    $counter.text(currentCount);

    // Disable add button if max reached
    if (currentCount >= maxCount) {
      $addBtn.prop("disabled", true).css("opacity", "0.5");
    } else {
      $addBtn.prop("disabled", false).css("opacity", "1");
    }
  }

  /**
   * Update tournament size and related UI
   */
  $sizeSelect.on("change", function () {
    const newSize = parseInt($(this).val());
    $maxCount.text(newSize);

    // Update info text
    const $infoText = $(".yuv-bracket-info");
    const oldText = $infoText.text();
    const newText = oldText.replace(/\d+\s+takmičara/, newSize + " takmičara");
    $infoText.text(newText);

    updateCounter();
  });

  /**
   * Handle add contestant button click
   */
  $(document).on("click", ".yuv-add-contestant-btn", function (e) {
    e.preventDefault();

    const currentCount = $contestantList.find(".yuv-contestant-item").length;
    const maxCount = parseInt($maxCount.text()) || 16;

    if (currentCount >= maxCount) {
      alert("Dostignut je maksimalan broj takmičara (" + maxCount + ")");
      return;
    }

    updateCounter();
  });

  /**
   * Handle remove contestant button click
   */
  $(document).on("click", ".yuv-remove-contestant", function (e) {
    e.preventDefault();
    $(this).closest(".yuv-contestant-item").remove();
    updateCounter();
  });

  /**
   * Category filter integration with search
   */
  $categoryFilter.on("change", function () {
    // Clear search input when category changes
    $searchInput.val("");

    // Trigger search if there's a search term
    if ($searchInput.val().trim().length > 0) {
      $searchInput.trigger("input");
    }
  });

  /**
   * Enhanced search with category filter
   * Note: This integrates with existing search AJAX
   */
  const originalSearchHandler =
    $searchInput.data("events")?.input?.[0]?.handler;

  if (originalSearchHandler) {
    $searchInput.off("input").on("input", function (e) {
      const searchTerm = $(this).val().trim();
      const selectedCategory = $categoryFilter.val();

      if (searchTerm.length < 2) {
        $("#yuv-search-results").empty().hide();
        return;
      }

      // Make AJAX call with category filter
      $.ajax({
        url: ajaxurl,
        method: "POST",
        data: {
          action: "yuv_search_voting_lists",
          search: searchTerm,
          category: selectedCategory,
          nonce: $("#yuv_tournament_meta_nonce").val(),
        },
        success: function (response) {
          if (response.success && response.data.length > 0) {
            displaySearchResults(response.data);
          } else {
            $("#yuv-search-results")
              .html('<div style="padding: 10px;">Nema rezultata</div>')
              .show();
          }
        },
        error: function () {
          $("#yuv-search-results")
            .html('<div style="padding: 10px;">Greška pri pretrazi</div>')
            .show();
        },
      });
    });
  }

  /**
   * Display search results
   */
  function displaySearchResults(results) {
    const $resultsDiv = $("#yuv-search-results");
    $resultsDiv.empty();

    results.forEach(function (item) {
      const $result = $("<div>")
        .addClass("yuv-search-result-item")
        .css({
          padding: "10px",
          "border-bottom": "1px solid #ddd",
          cursor: "pointer",
        })
        .html(
          "<strong>" +
            item.title +
            "</strong>" +
            (item.category
              ? '<br><small style="color: #666;">' + item.category + "</small>"
              : "")
        )
        .data("item", item)
        .on("click", function () {
          addContestantFromSearch($(this).data("item"));
        });

      $resultsDiv.append($result);
    });

    $resultsDiv.show();
  }

  /**
   * Add contestant from search results
   */
  function addContestantFromSearch(item) {
    const currentCount = $contestantList.find(".yuv-contestant-item").length;
    const maxCount = parseInt($maxCount.text()) || 16;

    if (currentCount >= maxCount) {
      alert("Dostignut je maksimalan broj takmičara (" + maxCount + ")");
      return;
    }

    // Create new contestant item
    const newIndex = currentCount;
    const $newItem = $("<li>")
      .addClass("yuv-contestant-item")
      .html(
        '<div class="yuv-contestant-preview">' +
          (item.image
            ? '<img src="' + item.image + '" alt="' + item.title + '">'
            : '<div class="yuv-no-image">Nema slike</div>') +
          "</div>" +
          '<div class="yuv-contestant-details">' +
          "<strong>" +
          item.title +
          "</strong>" +
          (item.category ? "<br><small>" + item.category + "</small>" : "") +
          "</div>" +
          '<input type="hidden" name="yuv_contestants[' +
          newIndex +
          '][list_id]" value="' +
          item.id +
          '">' +
          '<input type="hidden" name="yuv_contestants[' +
          newIndex +
          '][title]" value="' +
          item.title +
          '">' +
          '<input type="hidden" name="yuv_contestants[' +
          newIndex +
          '][image]" value="' +
          (item.image || "") +
          '">' +
          '<input type="hidden" name="yuv_contestants[' +
          newIndex +
          '][category]" value="' +
          (item.category || "") +
          '">' +
          '<button type="button" class="button yuv-remove-contestant">Ukloni</button>'
      );

    $contestantList.append($newItem);

    // Clear search
    $searchInput.val("");
    $("#yuv-search-results").empty().hide();

    updateCounter();
  }

  /**
   * Initialize counter on page load
   */
  updateCounter();

  /**
   * MutationObserver to detect dynamically added contestants
   */
  const observer = new MutationObserver(function (mutations) {
    updateCounter();
  });

  if ($contestantList.length > 0) {
    observer.observe($contestantList[0], {
      childList: true,
      subtree: false,
    });
  }
});
