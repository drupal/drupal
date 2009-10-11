// $Id: contact.js,v 1.2 2009/10/11 18:34:10 dries Exp $
(function ($) {

Drupal.behaviors.contact = {
  attach: function(context) {
    $.each(['name', 'mail'], function () {
      var cookie = $.cookie('Drupal.visitor.' + this);
      if (cookie) {
        $('#contact-site-form input[name=' + this + ']', context).once('comment').val(cookie);
        $('#contact-personal-form input[name=' + this + ']', context).once('comment').val(cookie);
      }
    });
  }
};

})(jQuery);
