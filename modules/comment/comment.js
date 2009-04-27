// $Id: comment.js,v 1.11 2009/04/27 20:19:36 webchick Exp $
(function ($) {

Drupal.behaviors.comment = {
  attach: function (context, settings) {
    $.each(['name', 'homepage', 'mail'], function () {
      var cookie = Drupal.comment.getCookie('comment_info_' + this);
      if (cookie) {
        $('#comment-form input[name=' + this + ']:not(.comment-processed)', context)
          .val(cookie)
          .addClass('comment-processed');
      }
    });
  }
};

Drupal.comment = {};

Drupal.comment.getCookie = function (name) {
  var search = name + '=';
  var returnValue = '';

  if (document.cookie.length > 0) {
    offset = document.cookie.indexOf(search);
    if (offset != -1) {
      offset += search.length;
      var end = document.cookie.indexOf(';', offset);
      if (end == -1) {
        end = document.cookie.length;
      }
      returnValue = decodeURIComponent(document.cookie.substring(offset, end).replace(/\+/g, '%20'));
    }
  }

  return returnValue;
};

})(jQuery);
