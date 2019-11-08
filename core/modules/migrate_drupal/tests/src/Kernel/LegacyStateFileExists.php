<?php

namespace Drupal\Tests\migrate_drupal\Kernel;

/**
 * Extends StateFileExists to test with deprecated modules.
 *
 * @see \Drupal\Tests\DeprecatedModulesTestTrait::removeDeprecatedModules()
 *
 * @group migrate_drupal
 * @group legacy
 */
class LegacyStateFileExists extends StateFileExists {

  /**
   * {@inheritdoc}
   */
  protected $excludeDeprecated = FALSE;

}
