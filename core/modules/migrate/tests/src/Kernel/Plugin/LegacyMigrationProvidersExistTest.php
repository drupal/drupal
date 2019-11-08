<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

/**
 * Extends MigrationProvidersExistTest to test with deprecated modules.
 *
 * @see \Drupal\Tests\DeprecatedModulesTestTrait::removeDeprecatedModules()
 *
 * @group migrate_drupal_ui
 * @group legacy
 */
class LegacyMigrationProvidersExistTest extends MigrationProvidersExistTest {

  /**
   * {@inheritdoc}
   */
  protected $excludeDeprecated = FALSE;

}
