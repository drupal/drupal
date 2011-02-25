
if (isJsEnabled()) {
  addLoadEvent(function() {
    if ($('edit-has_js')) {
      $('edit-has_js').value = 1;
    }

    if ($('progress')) {
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
        div.innerHTML = 'An unrecoverable error has occured. You can find the error message below. It is advised to copy it to the clipboard for reference. Please continue to the <a href="update.php?op=error">update summary</a>';
        $('progress').insertBefore(div, $('progress').firstChild);
        $('wait').style.display = 'none';
      }

      var progress = new progressBar('updateprogress', updateCallback, HTTPPost, errorCallback);
      progress.setProgress(-1, 'Starting updates');
      $('progress').appendChild(progress.element);
      progress.startMonitoring('update.php?op=do_update', 0);
    }
  });
}
