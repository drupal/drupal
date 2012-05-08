(function ($) {

  "use strict";

  $(document).ready(function() {
    var nid = Drupal.settings.statistics.nid;
    var basePath = Drupal.settings.basePath
    $.ajax({
      type: "POST",
      cache: false,
      url: basePath+"core/modules/statistics/statistics.php",
      data: "nid="+nid
    });
  });
})(jQuery);
