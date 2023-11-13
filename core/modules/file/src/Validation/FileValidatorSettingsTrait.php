<?php

namespace Drupal\file\Validation;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;

/**
 * Provides a trait to create validators from settings.
 */
trait FileValidatorSettingsTrait {

  /**
   * Gets the upload validators for the specified settings.
   *
   * @param array $settings
   *   An associative array of settings. The following keys are supported:
   *     - max_filesize: The maximum file size in bytes. Defaults to the PHP max
   *     upload size.
   *     - file_extensions: A space-separated list of allowed file extensions.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  public function getFileUploadValidators(array $settings): array {
    $validators = [
      // Add in our check of the file name length.
      'FileNameLength' => [],
    ];

    // Cap the upload size according to the PHP limit.
    $maxFilesize = Bytes::toNumber(Environment::getUploadMaxSize());
    if (!empty($settings['max_filesize'])) {
      $maxFilesize = min($maxFilesize, Bytes::toNumber($settings['max_filesize']));
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['FileSizeLimit'] = ['fileLimit' => $maxFilesize];

    // Add the extension check if necessary.
    if (!empty($settings['file_extensions'])) {
      $validators['FileExtension'] = [
        'extensions' => $settings['file_extensions'],
      ];
    }

    return $validators;
  }

}
