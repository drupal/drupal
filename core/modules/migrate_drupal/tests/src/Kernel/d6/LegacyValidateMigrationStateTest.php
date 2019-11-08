<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d6;

/**
 * Extends Drupal\Tests\migrate_drupal\Kernel\d6\ValidateMigrationStateTest
 * to test with deprecated modules.
 *
 * @see \Drupal\Tests\DeprecatedModulesTestTrait::removeDeprecatedModules()
 *
 * @group migrate_drupal
 * @group legacy
 */
class LegacyValidateMigrationStateTest extends ValidateMigrationStateTest {

  /**
   * {@inheritdoc}
   */
  protected $excludeDeprecated = FALSE;

}
