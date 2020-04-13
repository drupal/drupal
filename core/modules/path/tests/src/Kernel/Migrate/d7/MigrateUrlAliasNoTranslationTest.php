<?php

namespace Drupal\Tests\path\Kernel\Migrate\d7;

/**
 * Tests URL alias migration.
 *
 * @group path
 */
class MigrateUrlAliasNoTranslationTest extends MigrateUrlAliasTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d7_url_alias');
  }

}
