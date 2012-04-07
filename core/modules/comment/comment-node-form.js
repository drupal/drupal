/**
 * @file
 * Attaches comment behaviors to the node form.
 */

(function ($) {

Drupal.behaviors.commentFieldsetSummaries = {
  attach: function (context) {
    var $context = $(context);
    $context.find('fieldset.comment-node-settings-form').drupalSetSummary(function (context) {
      return Drupal.checkPlain($(context).find('.form-item-comment input:checked').next('label').text());
    });

    // Provide the summary for the node type form.
    $context.find('fieldset.comment-node-type-settings-form').drupalSetSummary(function(context) {
      var $context = $(context);
      var vals = [];

      // Default comment setting.
      vals.push($context.find(".form-item-comment select option:selected").text());

      // Threading.
      var threading = $(context).find(".form-item-comment-default-mode input:checked").next('label').text();
      if (threading) {
        vals.push(threading);
      }

      // Comments per page.
      var number = $context.find(".form-item-comment-default-per-page select option:selected").val();
      vals.push(Drupal.t('@number comments per page', {'@number': number}));

      return Drupal.checkPlain(vals.join(', '));
    });
  }
};

})(jQuery);
