<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ExtensionList;

/**
 * Defines the read-only theme:// stream wrapper for theme files.
 *
 * Usage:
 *
 * @code
 * theme://{name}
 * @endcode
 * Points to the theme {name} root directory. Only installed themes can be
 * referred.
 */
class ThemeStream extends ExtensionStreamBase {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Theme files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("Local files stored under a theme's directory.");
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExtensionInstalled(string $extension_name): void {
    $installed = $this->doGetExtensionList()->getAllInstalledInfo();
    if (!array_key_exists($extension_name, $installed)) {
      throw new UnknownExtensionException("The theme $extension_name does not exist.");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetExtensionList(): ExtensionList {
    return \Drupal::service('extension.list.theme');
  }

}
