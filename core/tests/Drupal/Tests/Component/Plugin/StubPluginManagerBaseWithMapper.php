<?php

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
   */
  public function __construct(MapperInterface $mapper) {
    $this->mapper = $mapper;
  }

}
