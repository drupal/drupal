<?php

namespace Drupal\Core\StreamWrapper;

/**
 * Defines the read-only theme:// stream wrapper for theme files.
 *
 * Usage:
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
  public function getDirectoryPath() {
    return \Drupal::service('theme_handler')->getTheme($this->getExtensionName())->getPath();
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExtensionInstalled(string $extension_name): void {
    \Drupal::service('theme_handler')->getTheme($extension_name);
  }

}
