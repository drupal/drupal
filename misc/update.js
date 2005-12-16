if (isJsEnabled()) {
  addLoadEvent(function() {
    if ($('edit-has_js')) {
      $('edit-has_js').value = 1;
    }

    if ($('progress')) {
      updateCallback = function (progress, status) {
        if (progress == 100) {
          window.location = window.location.href.split('op=')[0] +'op=finished';
        }
      }

      this.progress = new progressBar('updateprogress', updateCallback, HTTPGet);
      this.progress.setProgress(-1, 'Starting updates...');
      $('progress').appendChild(this.progress.element);
      this.progress.startMonitoring('update.php?op=do_update', 0);
    }
  });
}
