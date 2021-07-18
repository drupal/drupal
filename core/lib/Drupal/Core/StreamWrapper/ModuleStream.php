<?php

namespace Drupal\Core\StreamWrapper;

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
  public function getDirectoryPath() {
    return \Drupal::moduleHandler()->getModule($this->getExtensionName())->getPath();
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExtensionInstalled(string $extension_name): void {
    \Drupal::moduleHandler()->getModule($extension_name);
  }

}
