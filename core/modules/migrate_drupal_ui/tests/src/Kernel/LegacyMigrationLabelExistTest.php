<?php

namespace Drupal\Tests\migrate_drupal_ui\Kernel;

/**
 * Extends MigrationLabelExistTest to test with deprecated modules.
 *
 * @see \Drupal\Tests\DeprecatedModulesTestTrait::removeDeprecatedModules()
 *
 * @group migrate_drupal_ui
 * @group legacy
 */
class LegacyMigrationLabelExistTest extends MigrationLabelExistTest {

  /**
   * {@inheritdoc}
   */
  protected $excludeDeprecated = FALSE;

}
