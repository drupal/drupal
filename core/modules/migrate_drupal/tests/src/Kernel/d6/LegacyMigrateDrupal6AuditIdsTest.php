<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d6;

/**
 * Extends MigrateDrupal6AuditIdsTest to test with deprecated modules.
 *
 * @see \Drupal\Tests\DeprecatedModulesTestTrait::removeDeprecatedModules()
 *
 * @group migrate_drupal
 * @group legacy
 */
class LegacyMigrateDrupal6AuditIdsTest extends MigrateDrupal6AuditIdsTest {

  /**
   * {@inheritdoc}
   */
  protected $excludeDeprecated = FALSE;

}
