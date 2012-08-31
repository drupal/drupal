<?php

namespace Drupal\system;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller class for private file downloads.
 */
class FileDownload {

  /**
  * Page callback: Handles private file transfers.
  *
  * Call modules that implement hook_file_download() to find out if a file is
  * accessible and what headers it should be transferred with. If one or more
  * modules returned headers the download will start with the returned headers.
  * If a module returns -1 an AccessDeniedHttpException will be thrown.
  * If the file exists but no modules responded an AccessDeniedHttpException will
  * be thrown.If the file does not exist a NotFoundHttpException will be thrown.
  *
  * @see hook_file_download()
  */
  public function download() {
    // Merge remaining path arguments into relative file path.
    $args = func_get_args();
    $scheme = array_shift($args);
    $target = implode('/', $args);
    $uri = $scheme . '://' . $target;
    if (file_stream_wrapper_valid_scheme($scheme) && file_exists($uri)) {
      // Let other modules provide headers and controls access to the file.
      // module_invoke_all() uses array_merge_recursive() which merges header
      // values into a new array. To avoid that and allow modules to override
      // headers instead, use array_merge() to merge the returned arrays.
      $headers = array();
      foreach (module_implements('file_download') as $module) {
        $function = $module . '_file_download';
        $result = $function($uri);
        if ($result == -1) {
          throw new AccessDeniedHttpException();
        }
        if (isset($result) && is_array($result)) {
          $headers = array_merge($headers, $result);
        }
      }
      if (count($headers)) {
        return file_transfer($uri, $headers);
      }
      throw new AccessDeniedHttpException();
    }
    throw new NotFoundHttpException();
  }
}
