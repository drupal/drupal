(function ($) {
  $(document).ready(function() {
    $.ajax({
      type: "POST",
      cache: false,
      url: Drupal.settings.statistics.url,
      data: Drupal.settings.statistics.data
    });
  });
})(jQuery);
