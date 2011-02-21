//$Id: simplenews.js,v 1.6 2011/01/18 09:51:59 mirodietiker Exp $
(function ($) {

/**
 * Set text of Save button dependent on the selected send option.
 */
Drupal.behaviors.simplenewsCommandSend = {
  attach: function (context) {
    var commandSend = $(".simplenews-command-send", context);
    var sendButton = function () {
      switch ($(":radio:checked", commandSend).val()) {
      case '0':
        $('#edit-submit', context).attr({value: Drupal.t('Send test')});
        $('.form-item-simplenews-test-address', context).fadeIn();
        break;
      case '1':
        $('#edit-submit', context).attr({value: Drupal.t('Save and send')});
        $('.form-item-simplenews-test-address', context).slideUp();
        break;
      }
    }

    // Update send button at page load and when a send option is selected.
    sendButton();
    commandSend.click( function() { sendButton(); });
  }
};

})(jQuery);