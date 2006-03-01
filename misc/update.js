if (isJsEnabled()) {
  addLoadEvent(function() {
    if ($('edit-has_js')) {
      $('edit-has_js').value = 1;
    }

    if ($('progress')) {
      updateCallback = function (progress, status, pb) {
        if (progress == 100) {
          pb.stopMonitoring();
          window.location = window.location.href.split('op=')[0] +'op=finished';
        }
      }

      errorCallback = function (pb) {
        window.location = window.location.href.split('op=')[0] +'op=error';
      }

      var progress = new progressBar('updateprogress', updateCallback, HTTPPost, errorCallback);
      progress.setProgress(-1, 'Starting updates');
      $('progress').appendChild(progress.element);
      progress.startMonitoring('update.php?op=do_update', 0);
    }
  });
}
