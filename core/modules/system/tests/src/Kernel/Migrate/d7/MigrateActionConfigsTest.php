<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Migrate\d7;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to null.
 *
 * @group migrate_drupal_7
 */
class MigrateActionConfigsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('action_settings');
  }

  /**
   * Tests migration of action variables to null.
   */
  public function testActionSettings(): void {
    $config = $this->config('action.settings');
    $this->assertTrue($config->isNew());
  }

}
