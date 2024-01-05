<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Component\Plugin\PluginManagerBase;

/**
 * Stubs \Drupal\Component\Plugin\PluginManagerBase to take a MapperInterface.
 */
final class StubPluginManagerBaseWithMapper extends PluginManagerBase {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Component\Plugin\Mapper\MapperInterface $mapper
   *   The plugin mapper interface.
   */
  public function __construct(MapperInterface $mapper) {
    $this->mapper = $mapper;
  }

}
