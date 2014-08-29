<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\Fixtures\ConfigEntityBaseWithThirdPartySettings.
 */

namespace Drupal\Tests\Core\Config\Entity\Fixtures;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsTrait;

/**
 * Enables testing of dependency calculation.
 *
 * @see \Drupal\Tests\Core\Config\Entity\ConfigEntityBaseUnitTest::testCalculateDependenciesWithThirdPartySettings()
 * @see \Drupal\Core\Config\Entity\ConfigEntityBase::calculateDependencies()
 */
abstract class ConfigEntityBaseWithThirdPartySettings extends ConfigEntityBase implements ThirdPartySettingsInterface {
  use ThirdPartySettingsTrait;

}
