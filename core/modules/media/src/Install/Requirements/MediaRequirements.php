<?php

declare(strict_types=1);

namespace Drupal\media\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\File\FileSystemInterface;

/**
 * Install time requirements for the media module.
 */
class MediaRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements = [];
    $destination = 'public://media-icons/generic';
    \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $is_writable = is_writable($destination);
    $is_directory = is_dir($destination);
    if (!$is_writable || !$is_directory) {
      if (!$is_directory) {
        $error = t('The directory %directory does not exist.', ['%directory' => $destination]);
      }
      else {
        $error = t('The directory %directory is not writable.', ['%directory' => $destination]);
      }
      $description = t('An automated attempt to create this directory failed, possibly due to a permissions problem. To proceed with the installation, either create the directory and modify its permissions manually or ensure that the installer has the permissions to create it automatically. For more information, see INSTALL.txt or the <a href=":handbook_url">online handbook</a>.', [':handbook_url' => 'https://www.drupal.org/server-permissions']);
      $description = $error . ' ' . $description;
      $requirements['media']['description'] = $description;
      $requirements['media']['severity'] = RequirementSeverity::Error;
    }
    return $requirements;
  }

}
