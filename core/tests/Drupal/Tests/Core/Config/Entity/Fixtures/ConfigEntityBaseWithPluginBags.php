<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\Fixtures\ConfigEntityBaseWithPluginBags.
 */

namespace Drupal\Tests\Core\Config\Entity\Fixtures;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginBagsInterface;

/**
 * Enables testing of dependency calculation.
 *
 * @see \Drupal\Tests\Core\Config\Entity\ConfigEntityBaseUnitTest::testCalculateDependenciesWithPluginBags()
 * @see \Drupal\Core\Config\Entity\ConfigEntityBase::calculateDependencies()
 */
abstract class ConfigEntityBaseWithPluginBags extends ConfigEntityBase implements EntityWithPluginBagsInterface {
}
