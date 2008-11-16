Drupal.behaviors.contentTypes = {
  attach: function() {
    if ($('#edit-type').val() == $('#edit-name').val().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/_+/g, '_') || $('#edit-type').val() == '') {
      $('#edit-type-wrapper').hide();
      $('#edit-name').keyup(function() {
        var machine = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/_+/g, '_');
        if (machine != '_' && machine != '') {
          $('#edit-type').val(machine);
          $('#node-type-name-suffix').empty().append(' Machine name: ' + machine + ' [').append($('<a href="#">'+ Drupal.t('Edit') +'</a>').click(function() {
            $('#edit-type-wrapper').show();
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
