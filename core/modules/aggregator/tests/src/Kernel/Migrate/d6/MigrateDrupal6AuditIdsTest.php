<?php

namespace Drupal\Tests\aggregator\Kernel\Migrate\d6;

use Drupal\migrate\Audit\AuditResult;
use Drupal\migrate\Audit\IdAuditor;

/**
 * Tests that aggregator Id conflicts are discovered.
 *
 * @group aggregator
 * @group legacy
 */
class MigrateDrupal6AuditIdsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install required schemas.
    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');
    $this->installEntitySchema('file');
  }

  /**
   * Tests all migrations with ID conflicts.
   */
  public function testIdConflicts() {
    // Get all Drupal 6 migrations.
    $migrations = $this->container
      ->get('plugin.manager.migration')
      ->createInstancesByTag('Drupal 6');

    // Create content.
    $entity_type_manager = \Drupal::entityTypeManager();

    // Create an aggregator feed.
    if ($entity_type_manager->hasDefinition('aggregator_feed')) {
      $feed = $entity_type_manager->getStorage('aggregator_feed')->create([
        'title' => 'feed',
        'url' => 'http://www.example.com',
      ]);
      $feed->save();

      // Create an aggregator feed item.
      $item = $entity_type_manager->getStorage('aggregator_item')->create([
        'title' => 'feed item',
        'fid' => $feed->id(),
        'link' => 'http://www.example.com',
      ]);
      $item->save();
    }

    // Audit the IDs of all migrations. There should be conflicts since content
    // has been created.
    $conflicts = array_map(
      function (AuditResult $result) {
        return $result->passed() ? NULL : $result->getMigration()->getBaseId();
      },
      (new IdAuditor())->auditMultiple($migrations)
    );

    $expected = [
      'd6_aggregator_feed',
      'd6_aggregator_item',
    ];
    $this->assertEmpty(array_diff(array_filter($conflicts), $expected));
  }

}
