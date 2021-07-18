<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ExtensionList;

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
      $this->uri = $uri;
    }

    $extension_name = \Drupal::getContainer()->getParameter('install_profile');
    $this->validateExtensionInstalled($extension_name);

    [$scheme] = explode('://', $uri, 2);
    $dirname = dirname($this->getTarget($uri));
    $dirname = $dirname !== '.' ? rtrim("$dirname", '/') : '';
    return "$scheme://{$dirname}";
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
    $extension_name = \Drupal::getContainer()->getParameter('install_profile');
    $this->validateExtensionInstalled($extension_name);
    return $extension_name;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExtensionInstalled(string $extension_name): void {
    $installed = $this->doGetExtensionList()->getAllInstalledInfo();
    if (!array_key_exists($extension_name, $installed)) {
      throw new UnknownExtensionException("The profile $extension_name does not exist.");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetExtensionList(): ExtensionList {
    return \Drupal::service('extension.list.profile');
  }

}
