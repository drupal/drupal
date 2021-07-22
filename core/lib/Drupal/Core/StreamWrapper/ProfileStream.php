<?php

namespace Drupal\Core\StreamWrapper;

/**
 * Defines the read-only profile:// stream wrapper for installed profile files.
 *
 * Usage:
 * @code
 * profile://
 * @endcode
 * Points to the installed profile root directory.
 */
class ProfileStream extends ExtensionStreamBase {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Installed profile files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("Local files stored under the installed profile's directory.");
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }
    else {
      $this->setUri($uri);
    }
    [$scheme] = explode('://', $uri, 2);
    $dirname = dirname($this->getTarget($uri));
    $dirname = $dirname !== '.' ? rtrim("$dirname", '/') : '';
    return "$scheme://{$dirname}";
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return \Drupal::service('extension.list.profile')->getPath($this->getExtensionName());
  }

  /**
   * {@inheritdoc}
   */
  protected function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }
    [, $target] = explode('://', $uri, 2);
    // Remove erroneous leading or trailing, forward-slashes and backslashes.
    return trim($target, '\/');
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtensionName(): string {
    return \Drupal::getContainer()->getParameter('install_profile');
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExtensionInstalled(string $extension_name): void {}

}
