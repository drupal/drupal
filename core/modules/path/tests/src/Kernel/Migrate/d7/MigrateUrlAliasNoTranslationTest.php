<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Kernel\Migrate\d7;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests URL alias migration.
 */
#[Group('path')]
#[RunTestsInSeparateProcesses]
class MigrateUrlAliasNoTranslationTest extends MigrateUrlAliasTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d7_url_alias');
  }

}
