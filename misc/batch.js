// $Id$
(function ($) {

/**
 * Attaches the batch behavior to progress bars.
 */
Drupal.behaviors.batch = {
  attach: function (context, settings) {
    $('#progress', context).once('batch', function () {
      var holder = $(this);

      // Success: redirect to the summary.
      var updateCallback = function (progress, status, pb) {
        if (progress == 100) {
          pb.stopMonitoring();
          window.location = settings.batch.uri + '&op=finished';
        }
      };

      var errorCallback = function (pb) {
        holder.prepend($('<p class="error"></p>').html(settings.batch.errorMessage));
        $('#wait').hide();
      };

      var progress = new Drupal.progressBar('updateprogress', updateCallback, 'POST', errorCallback);
      progress.setProgress(-1, settings.batch.initMessage);
      holder.append(progress.element);
      progress.startMonitoring(settings.batch.uri + '&op=do', 10);
    });
  }
};

})(jQuery);
