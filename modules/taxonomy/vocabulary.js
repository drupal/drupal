// $Id: vocabulary.js,v 1.2 2009/08/24 03:11:34 webchick Exp $
(function ($) {

Drupal.behaviors.contentTypes = {
  attach: function () {
    if ($('#edit-machine-name').val() == $('#edit-name').val().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/_+/g, '_') || $('#edit-machine-name').val() == '') {
      $('.form-item-machine-name').hide();
      $('#edit-name').keyup(function () {
        var machine = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/_+/g, '_');
        if (machine != '_' && machine != '') {
          $('#edit-machine-name').val(machine);
          $('#vocabulary-name-suffix').empty().append(' Machine name: ' + machine + ' [').append($('<a href="#">' + Drupal.t('Edit') + '</a>').click(function () {
            $('.form-item-machine-name').show();
            $('#vocabulary-name-suffix').hide();
            $('#edit-name').unbind('keyup');
            return false;
          })).append(']');
        }
        else {
          $('#edit-machine-name').val(machine);
          $('#vocabulary-name-suffix').text('');
        }
      });
      $('#edit-name').keyup();
    }
  }
};

})(jQuery);
