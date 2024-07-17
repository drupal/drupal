<?php

namespace Drupal\file\Controller;

use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines a controller to respond to file widget AJAX requests.
 */
class FileWidgetAjaxController {
  use StringTranslationTrait;

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
      'message' => $this->t('Starting upload...'),
      'percentage' => -1,
    ];

    if (extension_loaded('uploadprogress')) {
      $status = uploadprogress_get_info($key);
      if (isset($status['bytes_uploaded']) && !empty($status['bytes_total'])) {
        $progress['message'] = $this->t('Uploading... (@current of @total)', [
          '@current' => ByteSizeMarkup::create($status['bytes_uploaded']),
          '@total' => ByteSizeMarkup::create($status['bytes_total']),
        ]);
        $progress['percentage'] = round(100 * $status['bytes_uploaded'] / $status['bytes_total']);
      }
    }

    return new JsonResponse($progress);
  }

}
