<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d7;

/**
 * Extends Drupal\Tests\migrate_drupal\Kernel\d7\MigrationProcessTest to test
 * with deprecated modules.
 *
 * @see \Drupal\Tests\DeprecatedModulesTestTrait::removeDeprecatedModules()
 *
 * @group migrate_drupal
 * @group legacy
 */
class LegacyMigrationProcessTest extends MigrationProcessTest {

  /**
   * {@inheritdoc}
   */
  protected $excludeDeprecated = FALSE;

}
