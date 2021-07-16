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
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(RequestStack $requestStack, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($requestStack);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOwnerName(): string {
    $name = parent::getOwnerName();
    if (!$this->moduleHandler->moduleExists($name)) {
      // The module does not exist or is not installed.
      throw new \RuntimeException("Module $name does not exist or is not installed");
    }
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDirectoryPath() {
    return $this->moduleHandler->getModule($this->getOwnerName())->getPath();
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
    return $this->t('Local files stored under module directory.');
  }

}
