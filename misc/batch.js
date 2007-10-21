// $Id$

/**
 * Attaches the batch behavior to progress bars.
 */
Drupal.behaviors.batch = function (context) {
  // This behavior attaches by ID, so is only valid once on a page.
  if ($('#progress.batch-processed').size()) {
    return;
  }
  $('#progress', context).addClass('batch-processed').each(function () {
    var holder = this;
    var uri = Drupal.settings.batch.uri;
    var initMessage = Drupal.settings.batch.initMessage;
    var errorMessage = Drupal.settings.batch.errorMessage;

    // Success: redirect to the summary.
    var updateCallback = function (progress, status, pb) {
      if (progress == 100) {
        pb.stopMonitoring();
        window.location = uri+'&op=finished';
      }
    };

    var errorCallback = function (pb) {
      var div = document.createElement('p');
      div.className = 'error';
      $(div).html(errorMessage);
      $(holder).prepend(div);
      $('#wait').hide();
    };

    var progress = new Drupal.progressBar('updateprogress', updateCallback, "POST", errorCallback);
    progress.setProgress(-1, initMessage);
    $(holder).append(progress.element);
    progress.startMonitoring(uri+'&op=do', 10);
  });
};
