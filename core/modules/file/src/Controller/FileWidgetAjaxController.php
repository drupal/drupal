<?php

namespace Drupal\file\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines a controller to respond to file widget AJAX requests.
 */
class FileWidgetAjaxController {

  /**
   * Returns the progress status for a file upload process.
   *
   * @param string $key
   *   The unique key for this upload process.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse object.
   */
  public function progress($key) {
    $progress = [
      'message' => t('Starting upload...'),
      'percentage' => -1,
    ];

    $implementation = file_progress_implementation();
    if ($implementation == 'uploadprogress') {
      $status = uploadprogress_get_info($key);
      if (isset($status['bytes_uploaded']) && !empty($status['bytes_total'])) {
        $progress['message'] = t('Uploading... (@current of @total)', ['@current' => format_size($status['bytes_uploaded']), '@total' => format_size($status['bytes_total'])]);
        $progress['percentage'] = round(100 * $status['bytes_uploaded'] / $status['bytes_total']);
      }
    }
    elseif ($implementation == 'apc') {
      $status = apcu_fetch('upload_' . $key);
      if (isset($status['current']) && !empty($status['total'])) {
        $progress['message'] = t('Uploading... (@current of @total)', ['@current' => format_size($status['current']), '@total' => format_size($status['total'])]);
        $progress['percentage'] = round(100 * $status['current'] / $status['total']);
      }
    }

    return new JsonResponse($progress);
  }

}
