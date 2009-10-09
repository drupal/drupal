// $Id: contact.js,v 1.1 2009/10/09 15:39:12 dries Exp $
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
