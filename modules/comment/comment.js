// $Id: comment.js,v 1.13 2009/10/09 15:39:12 dries Exp $
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
