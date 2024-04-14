<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\Connection;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @group system
 * @group Update
 * @covers system_post_update_add_langcode_to_all_translatable_config
 */
class SimpleConfigLangcodeTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that langcodes are added to simple config objects that need them.
   */
  public function testLangcodesAddedToSimpleConfig(): void {
    /** @var \Drupal\Core\Database\Connection $database */
    $database = $this->container->get(Connection::class);

    // Remove the langcode from `user.mail`, which has translatable values; it
    // should be restored by the update path. We need to change it in the
    // database directly, to avoid running afoul of config validation.
    $data = $this->config('user.mail')->clear('langcode')->getRawData();
    $database->update('config')
      ->fields([
        'data' => serialize($data),
      ])
      ->condition('name', 'user.mail')
      ->execute();

    // Add a langcode to `node.settings`, which has no translatable values; it
    // should be removed by the update path. We need to change it in the
    // database directly, to avoid running afoul of config validation.
    $data = $this->config('node.settings')->set('langcode', 'en')->getRawData();
    $database->update('config')
      ->fields([
        'data' => serialize($data),
      ])
      ->condition('name', 'node.settings')
      ->execute();

    $this->runUpdates();
    $this->assertSame('en', $this->config('user.mail')->get('langcode'));
    $this->assertArrayNotHasKey('langcode', $this->config('node.settings')->getRawData());
  }

}
