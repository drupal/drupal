// $Id$

(function ($) {

Drupal.behaviors.menuFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-menu', context).setSummary(function (context) {
      return Drupal.checkPlain($('#edit-menu-link-title', context).val()) || Drupal.t('Not in menu');
    });
  }
};

Drupal.behaviors.menuDisplayForm = {
  attach: function () {
    $('fieldset#edit-menu .form-item:first').before('<div class="form-item form-type-checkbox form-item-menu-create"><label for="edit-menu-create" class="option"><input type="checkbox" class="form-checkbox" id="edit-menu-create" name="menu[create]"/> ' + Drupal.t('Create a menu item.') + '</label></div>');
    $('fieldset#edit-menu .form-item:gt(0)').hide();
    $('#edit-menu-create').change(function () {
    	if($(this).is(':checked')){
    	  $('fieldset#edit-menu .form-item:gt(0)').show();
    	  $('#edit-menu-link-title').val(Drupal.checkPlain($('#edit-title').val())).change();
    	}else{
    	  $('fieldset#edit-menu .form-item:gt(0)').hide();
    	  $('#edit-menu-link-title').val('').change();
    	}
    });
    $('#edit-menu-link-title').keyup(function () {
    	$('#edit-menu-create').attr('checked', $(this).val());
    });
  }
};

})(jQuery);
