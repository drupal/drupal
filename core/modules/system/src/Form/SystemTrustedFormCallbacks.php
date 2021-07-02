<?php

namespace Drupal\system\Form;

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements TrustedFormCallbacks for system module.
 *
 * @package Drupal\system\Form
 */
class SystemTrustedFormCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #after_build callback for the function listed below.
   *
   * - locale_form_system_file_system_settings_alter()
   */
  public static function checkDirectory(array &$element, FormStateInterface $formState) {
    $directory = $element['#value'];
    if (strlen($directory) == 0) {
      return $element;
    }

    $logger = \Drupal::logger('file system');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    if (!is_dir($directory) && !$file_system->mkdir($directory, NULL, TRUE)) {
      // If the directory does not exist and cannot be created.
      $formState->setErrorByName($element['#parents'][0], t('The directory %directory does not exist and could not be created.', ['%directory' => $directory]));
      $logger->error('The directory %directory does not exist and could not be created.', ['%directory' => $directory]);
    }

    if (is_dir($directory) && !is_writable($directory) && !$file_system->chmod($directory)) {
      // If the directory is not writable and cannot be made so.
      $formState->setErrorByName($element['#parents'][0], t('The directory %directory exists but is not writable and could not be made writable.', ['%directory' => $directory]));
      $logger->error('The directory %directory exists but is not writable and could not be made writable.', ['%directory' => $directory]);
    }
    elseif (is_dir($directory)) {
      if ($element['#name'] == 'file_public_path') {
        // Create public .htaccess file.
        FileSecurity::writeHtaccess($directory, FALSE);
      }
      else {
        // Create private .htaccess file.
        FileSecurity::writeHtaccess($directory);
      }
    }

    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks() {
    return ['checkDirectory'];
  }

}
