<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\Fixtures\ConfigEntityBaseWithPluginBag.
 */

namespace Drupal\Tests\Core\Config\Entity\Fixtures;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\EntityWithPluginBagInterface;

/**
 * Enables testing of dependency calculation.
 *
 * @see \Drupal\Tests\Core\Config\Entity\ConfigEntityBaseUnitTest::testCalculateDependenciesWithPluginBag()
 * @see \Drupal\Core\Config\Entity\ConfigEntityBase::calculateDependencies()
 */
abstract class ConfigEntityBaseWithPluginBag extends ConfigEntityBase implements EntityWithPluginBagInterface {
}
