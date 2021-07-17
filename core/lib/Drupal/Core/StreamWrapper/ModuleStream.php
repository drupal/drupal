<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function getExtensionName(): string {
    $extension_name = parent::getExtensionName();
    $this->getModuleHandler()->getModule($extension_name);
    return $extension_name;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDirectoryPath() {
    return $this->getModuleHandler()->getModule($this->getExtensionName())->getPath();
  }

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
    return $this->t('Local files stored under a module\'s directory.');
  }

  /**
   * Returns the module handler service.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   */
  protected function getModuleHandler(): ModuleHandlerInterface {
    if (!isset($this->moduleHandler)) {
      $this->moduleHandler = \Drupal::moduleHandler();
    }
    return $this->moduleHandler;
  }

}
