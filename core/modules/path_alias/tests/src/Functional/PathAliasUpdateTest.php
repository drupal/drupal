<?php

declare(strict_types=1);

namespace Drupal\Tests\path_alias\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update hooks the path_alias module.
 *
 * @group path_alias
 */
class PathAliasUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles[] = __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz';
  }

  /**
   * Tests path_alias_post_update_drop_path_alias_status_index.
   */
  public function testPathAliasStatusIndexRemoved(): void {
    $schema = \Drupal::database()->schema();
    $this->assertTrue($schema->indexExists('path_alias', 'path_alias__status'));
    $this->runUpdates();
    $this->assertFalse($schema->indexExists('path_alias', 'path_alias__status'));
  }

}
