// $Id$
(function ($) {

Drupal.behaviors.comment = {
  attach: function (context, settings) {
    $.each(['name', 'homepage', 'mail'], function () {
      var cookie = $.cookie('Drupal.visitor.' + this);
      if (cookie) {
        $('#comment-form input[name=' + this + ']', context).once('comment').val(cookie);
      }
    });
  }
};

})(jQuery);
