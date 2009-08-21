// $Id$
(function ($) {

Drupal.behaviors.contentTypes = {
  attach: function (context) {
    // Provide the vertical tab summaries.
    $('fieldset#edit-submission', context).setSummary(function(context) {
      var vals = [];
      vals.push(Drupal.checkPlain($('#edit-title-label', context).val()) || Drupal.t('Requires a title'));
      vals.push(Drupal.checkPlain($('#edit-body-label', context).val()) || Drupal.t('No body'));
      return vals.join(', ');
    });
    $('fieldset#edit-workflow', context).setSummary(function(context) {
      var vals = [];
      $("input[name^='node_options']:checked", context).parent().each(function() {
        vals.push(Drupal.checkPlain($(this).text()));
      });
      if (!$('#edit-node-options-status', context).is(':checked')) {
        vals.unshift(Drupal.t('Not published'));
      }
      return vals.join(', ');
    });
    $('fieldset#edit-display', context).setSummary(function(context) {
      var vals = [];
      $('input:checked', context).parent().each(function() {
        vals.push(Drupal.checkPlain($(this).text()));
      });
      if (!$('#edit-node-submitted', context).is(':checked')) {
        vals.unshift(Drupal.t("Don't display post information"));
      }
      return vals.join(', ');
    });

    // Process the machine name.
    if ($('#edit-type').val() == $('#edit-name').val().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/_+/g, '_') || $('#edit-type').val() == '') {
      $('.form-item.type-wrapper').hide();
      $('#edit-name').keyup(function () {
        var machine = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/_+/g, '_');
        if (machine != '_' && machine != '') {
          $('#edit-type').val(machine);
          $('#node-type-name-suffix').empty().append(' Machine name: ' + machine + ' [').append($('<a href="#">' + Drupal.t('Edit') + '</a>').click(function () {
            $('.form-item-textfield.type-wrapper').show();
            $('#node-type-name-suffix').hide();
            $('#edit-name').unbind('keyup');
            return false;
          })).append(']');
        }
        else {
          $('#edit-type').val(machine);
          $('#node-type-name-suffix').text('');
        }
      });
      $('#edit-name').keyup();
    }
  }
};

})(jQuery);
