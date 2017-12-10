<?php

namespace Drupal\Tests\aggregator\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that node settings are properly updated during database updates.
 *
 * @group aggregator
 */
class AggregatorUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz',
    ];
  }

  /**
   * Tests that the 'Source feed' field is required.
   *
   * @see aggregator_update_8200()
   */
  public function testSourceFeedRequired() {
    // Check that the 'fid' field is not required prior to the update.
    $field_definition = \Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition('fid', 'aggregator_item');
    $this->assertFalse($field_definition->isRequired());

    // Run updates.
    $this->runUpdates();

    // Check that the 'fid' field is now required.
    $field_definition = \Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition('fid', 'aggregator_item');
    $this->assertTrue($field_definition->isRequired());
  }

  /**
   * Tests that the 'Update interval' field has a default value.
   */
  public function testUpdateIntervalDefaultValue() {
    // Check that the 'refresh' field does not have a default value prior to the
    // update.
    $field_definition = \Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition('refresh', 'aggregator_feed');
    $this->assertSame([], $field_definition->getDefaultValueLiteral());

    // Run updates.
    $this->runUpdates();

    // Check that the 'refresh' has a default value now.
    $field_definition = \Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition('refresh', 'aggregator_feed');
    $this->assertSame([['value' => 3600]], $field_definition->getDefaultValueLiteral());
  }

}
