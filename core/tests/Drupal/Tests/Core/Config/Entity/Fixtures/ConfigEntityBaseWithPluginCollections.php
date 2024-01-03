<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config\Entity\Fixtures;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Enables testing of dependency calculation.
 *
 * @see \Drupal\Tests\Core\Config\Entity\ConfigEntityBaseUnitTest::testCalculateDependenciesWithPluginCollections()
 * @see \Drupal\Core\Config\Entity\ConfigEntityBase::calculateDependencies()
 */
abstract class ConfigEntityBaseWithPluginCollections extends ConfigEntityBase implements EntityWithPluginCollectionInterface {
}
