// $Id$
(function ($) {

/**
 * Implementation of Drupal.behaviors for admin.
 */
Drupal.behaviors.admin = {
  attach: function(context) {

    // Set the intial state of the toolbar.
    $('#toolbar', context).once('toolbar', Drupal.admin.toolbar.init);

    // Add the toggling element for shortcuts visibility.
    $('#toolbar div.toolbar-menu').prepend('<span class="toggle toggle-active"><a href="#">' + Drupal.t('Show shortcuts') + '</a></span>');

    // Toggling of admin shortcuts visibility.
    $('#toolbar span.toggle', context).once('toolbar-toggle').click(function() {
      Drupal.admin.toolbar.toggle();
      return false;
    });
  }
};

/**
 * Initialize cautiously to avoid collisions with other modules.
 */
Drupal.admin = Drupal.admin || {};
Drupal.admin.toolbar = Drupal.admin.toolbar || {};

/**
 * Retrieve last saved cookie settings and set up the initial toolbar state.
 */
Drupal.admin.toolbar.init = function() {
  // Retrieve the collapsed status from a stored cookie.
  var collapsed = $.cookie('Drupal.admin.toolbar.collapsed');

  // Expand or collapse the toolbar based on the cookie value.
  if (collapsed == 1) {
    Drupal.admin.toolbar.collapse();
  }
  else {
    Drupal.admin.toolbar.expand();
  }
}

/**
 * Collapse the admin toolbar.
 */
Drupal.admin.toolbar.collapse = function() {
  $('#toolbar div.toolbar-shortcuts').addClass('collapsed');
  $('#toolbar span.toggle').removeClass('toggle-active');
  $('body').removeClass('toolbar-shortcuts');
  $.cookie(
    'Drupal.admin.toolbar.collapsed', 
    1, 
    {path: Drupal.settings.basePath}
  );
}

/**
 * Expand the admin toolbar.
 */
Drupal.admin.toolbar.expand = function() {
  $('#toolbar div.toolbar-shortcuts').removeClass('collapsed');
  $('#toolbar span.toggle').addClass('toggle-active');
  $('body').addClass('toolbar-shortcuts');
  $.cookie(
    'Drupal.admin.toolbar.collapsed', 
    0, 
    {path: Drupal.settings.basePath}
  );
}

/**
 * Toggle the admin toolbar.
 */
Drupal.admin.toolbar.toggle = function() {
  if ($('#toolbar div.toolbar-shortcuts').is('.collapsed')) {
    Drupal.admin.toolbar.expand();
  }
  else {
    Drupal.admin.toolbar.collapse();
  }
}

})(jQuery);
