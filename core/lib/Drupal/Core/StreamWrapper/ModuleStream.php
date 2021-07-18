<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ExtensionList;

/**
 * Defines the read-only module:// stream wrapper for module files.
 *
 * Usage:
 * @code
 * module://{name}
 * @endcode
 * Points to the module {name} root directory. Only enabled modules can be
 * referred.
 */
class ModuleStream extends ExtensionStreamBase {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Module files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("Local files stored under a module's directory.");
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExtensionInstalled(string $extension_name): void {
    $installed = $this->doGetExtensionList()->getAllInstalledInfo();
    if (!array_key_exists($extension_name, $installed)) {
      throw new UnknownExtensionException("The module $extension_name does not exist.");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetExtensionList(): ExtensionList {
    return \Drupal::service('extension.list.module');
  }

}
