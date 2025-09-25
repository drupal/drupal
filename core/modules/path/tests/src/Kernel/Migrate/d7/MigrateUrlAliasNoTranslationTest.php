<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Kernel\Migrate\d7;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests URL alias migration.
 */
#[Group('path')]
class MigrateUrlAliasNoTranslationTest extends MigrateUrlAliasTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d7_url_alias');
  }

}
