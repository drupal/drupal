// $Id: update.js,v 1.10 2006/12/27 14:11:45 unconed Exp $

if (Drupal.jsEnabled) {
  $(document).ready(function() {
    $('#edit-has-js').each(function() { this.value = 1; });
    $('#progress').each(function () {
      var holder = this;

      // Success: redirect to the summary.
      var updateCallback = function (progress, status, pb) {
        if (progress == 100) {
          pb.stopMonitoring();
          window.location = window.location.href.split('op=')[0] +'op=finished';
        }
      }

      // Failure: point out error message and provide link to the summary.
      var errorCallback = function (pb) {
        var div = document.createElement('p');
        div.className = 'error';
        $(div).html('An unrecoverable error has occured. You can find the error message below. It is advised to copy it to the clipboard for reference. Please continue to the <a href="update.php?op=error">update summary</a>');
        $(holder).prepend(div);
        $('#wait').hide();
      }

      var progress = new Drupal.progressBar('updateprogress', updateCallback, "POST", errorCallback);
      progress.setProgress(-1, 'Starting updates');
      $(holder).append(progress.element);
      progress.startMonitoring('update.php?op=do_update', 0);
    });
  });
}
