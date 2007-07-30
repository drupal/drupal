// $Id: book.js,v 1.1 2007/07/30 18:20:21 dries Exp $

Drupal.behaviors.bookSelect = function(context) {
   // This behavior attaches by ID, so is only valid once on a page.
  if ($('#edit-book-bid.book-select-processed').size()) {
    return;
  }
  // Hide the button in the node form, since it's not needed when JS is enabled.
  $('#edit-book-pick-book').css('display', 'none');

  // Binds a function to the keyup and change actions of the book select to
  // retrieve parent options. Mark as processed so this binding is only done once.
  $('#edit-book-bid')
    .keyup(Drupal.bookFillSelect)
    .change(Drupal.bookFillSelect)
    .addClass('book-select-processed');
}

// This function passes the form information and the book ID to a Drupal callback
// and retrieves a parent select with changed options to replace the one in the form.
Drupal.bookFillSelect = function() {
  // Create a progress bar and substitute it for the parent select.
  pb = new Drupal.progressBar('book_progress');
  pb.setProgress(-1, Drupal.t('Updating parents...'));
  $('#edit-book-plid-wrapper').html(pb.element);

  $.get(Drupal.settings.book.formCallback +'/'+ $('#'+ Drupal.settings.book.formId +' input[@name=form_build_id]').val() +'/'+ $('#edit-book-bid').val(), {}, function(data) {
    parsedData = Drupal.parseJson(data);
    // Insert the new select, and remove the progress bar.
    $('#edit-book-plid-wrapper').after(parsedData['book']).remove();
  });
};
