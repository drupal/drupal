// $Id: comment.js,v 1.2 2007/05/20 12:34:47 dries Exp $
if (Drupal.jsEnabled) {
  $(document).ready(function() {
    var parts = new Array("name", "homepage", "mail");
    var cookie = '';
    for (i=0;i<3;i++) {
      cookie = Drupal.comment.getCookie('comment_info_' + parts[i]);
      if (cookie != '') {
        $("#comment-form input[@name=" + parts[i] + "]").val(cookie);
      }
    }
  });
};

Drupal.comment = {};

Drupal.comment.getCookie = function(name) {
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
}
