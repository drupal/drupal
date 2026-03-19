<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * Stubbed source plugin for testing base class implementations.
 */
class StubSourcePlugin extends SourcePluginBase {

  /**
   * Helper for setting internal module handler implementation.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler): void {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator(): \Iterator {
    return [];
  }

}
