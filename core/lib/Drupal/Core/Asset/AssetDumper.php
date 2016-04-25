<?php

namespace Drupal\Core\Asset;

use Drupal\Component\Utility\Crypt;

/**
 * Dumps a CSS or JavaScript asset.
 */
class AssetDumper implements AssetDumperInterface {

  /**
   * {@inheritdoc}
   *
   * The file name for the CSS or JS cache file is generated from the hash of
   * the aggregated contents of the files in $data. This forces proxies and
   * browsers to download new CSS when the CSS changes.
   */
  public function dump($data, $file_extension) {
    // Prefix filename to prevent blocking by firewalls which reject files
    // starting with "ad*".
    $filename = $file_extension. '_' . Crypt::hashBase64($data) . '.' . $file_extension;
    // Create the css/ or js/ path within the files folder.
    $path = 'public://' . $file_extension;
    $uri = $path . '/' . $filename;
    // Create the CSS or JS file.
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    if (!file_exists($uri) && !file_unmanaged_save_data($data, $uri, FILE_EXISTS_REPLACE)) {
      return FALSE;
    }
    // If CSS/JS gzip compression is enabled and the zlib extension is available
    // then create a gzipped version of this file. This file is served
    // conditionally to browsers that accept gzip using .htaccess rules.
    // It's possible that the rewrite rules in .htaccess aren't working on this
    // server, but there's no harm (other than the time spent generating the
    // file) in generating the file anyway. Sites on servers where rewrite rules
    // aren't working can set css.gzip to FALSE in order to skip
    // generating a file that won't be used.
    if (extension_loaded('zlib') && \Drupal::config('system.performance')->get($file_extension . '.gzip')) {
      if (!file_exists($uri . '.gz') && !file_unmanaged_save_data(gzencode($data, 9, FORCE_GZIP), $uri . '.gz', FILE_EXISTS_REPLACE)) {
        return FALSE;
      }
    }
    return $uri;
  }

}
