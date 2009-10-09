// $Id$
(function ($) {

Drupal.behaviors.contact = {
  attach: function(context) {
    $.each(['name', 'mail'], function () {
      var cookie = $.cookie('Drupal.user.' + this);
      if (cookie) {
        $('#contact-site-form input[name=' + this + ']', context).once('comment').val(cookie);
      }
    });
  }
};

})(jQuery);
