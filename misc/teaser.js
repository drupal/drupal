// $Id: teaser.js,v 1.3 2007/04/09 13:58:02 dries Exp $

/**
 * Auto-attach for teaser behaviour.
 *
 * Note: depends on resizable textareas.
 */
Drupal.teaserAttach = function() {
  $('textarea.teaser:not(.joined)').each(function() {
    var teaser = $(this).addClass('joined');

    // Move teaser textarea before body, and remove its form-item wrapper.
    var body = $('#'+ Drupal.settings.teaser[this.id]);
    var checkbox = $('#'+ Drupal.settings.teaserCheckbox[this.id]).parent();
    var parent = teaser[0].parentNode;
    $(body).before(teaser);
    $(parent).remove();

    function trim(text) {
      return text.replace(/^\s+/g, '').replace(/\s+$/g, '');
    }

    // Join the teaser back to the body.
    function join_teaser() {
      if (teaser.val()) {
        body.val(trim(teaser.val()) +'\r\n\r\n'+ trim(body.val()));
      }
      // Hide and disable teaser
      $(teaser).attr('disabled', 'disabled');
      $(teaser).parent().slideUp('fast');
      // Change label
      $(this).val(Drupal.settings.teaserButton[1]);
      // Show separate teaser checkbox
      $(checkbox).hide();
    }

    // Split the teaser from the body.
    function split_teaser() {
      body[0].focus();
      var selection = Drupal.getSelection(body[0]);
      var split = selection.start;
      var text = body.val();

      // Note: using val() fails sometimes. jQuery bug?
      teaser[0].value = trim(text.slice(0, split));
      body[0].value = trim(text.slice(split));
      // Reveal and enable teaser
      $(teaser).attr('disabled', '');
      $(teaser).parent().slideDown('fast');
      // Change label
      $(this).val(Drupal.settings.teaserButton[0]);
      // Show separate teaser checkbox
      $(checkbox).show();
    }

    // Add split/join button.
    var button = $('<div class="teaser-button-wrapper"><input type="button" class="teaser-button" /></div>');
    var include = $('#'+ this.id.substring(0, this.id.length - 2) +'include');
    $(include).parent().parent().before(button);

    // Extract the teaser from the body, if set. Otherwise, stay in joined mode.
    var text = body.val().split('<!--break-->', 2);
    if (text.length == 2) {
      teaser[0].value = trim(text[0]);
      body[0].value = trim(text[1]);
      $(teaser).attr('disabled', '');
      $('input', button).val(Drupal.settings.teaserButton[0]).toggle(join_teaser, split_teaser);
    }
    else {
      $(teaser).hide();
      $('input', button).val(Drupal.settings.teaserButton[1]).toggle(split_teaser, join_teaser);
      $(checkbox).hide();
    }

  });
}

if (Drupal.jsEnabled) {
  $(document).ready(Drupal.teaserAttach);
}
