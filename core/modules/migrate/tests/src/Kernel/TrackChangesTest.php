<?php

namespace Drupal\Tests\migrate\Kernel;

/**
 * Tests migration track changes property.
 *
 * @group migrate
 */
class TrackChangesTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'taxonomy',
    'migrate',
    'migrate_track_changes_test',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create source test table.
    $this->sourceDatabase->schema()->createTable('track_changes_term', [
      'fields' => [
        'tid' => [
          'description' => 'Serial',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'name' => [
          'description' => 'Name',
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ],
        'description' => [
          'description' => 'Name',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
          'default' => '',
        ],
      ],
      'primary key' => [
        'tid',
      ],
      'description' => 'Contains taxonomy terms to import',
    ]);

    // Add 4 items to source table.
    $this->sourceDatabase->insert('track_changes_term')
      ->fields([
        'name',
        'description',
      ])
      ->values([
        'name' => 'Item 1',
        'description' => 'Text item 1',
      ])
      ->values([
        'name' => 'Item 2',
        'description' => 'Text item 2',
      ])
      ->values([
        'name' => 'Item 3',
        'description' => 'Text item 3',
      ])
      ->values([
        'name' => 'Item 4',
        'description' => 'Text item 4',
      ])
      ->execute();

    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');

    $this->executeMigration('track_changes_test');
  }

  /**
   * Tests track changes property of SqlBase.
   */
  public function testTrackChanges() {
    // Assert all of the terms have been imported.
    $this->assertTermExists('name', 'Item 1');
    $this->assertTermExists('name', 'Item 2');
    $this->assertTermExists('description', 'Text item 3');
    $this->assertTermExists('description', 'Text item 4');

    // Update Item 1 triggering its track_changes by name.
    $this->sourceDatabase->update('track_changes_term')
      ->fields([
        'name' => 'Item 1 updated',
      ])
      ->condition('name', 'Item 1')
      ->execute();

    // Update Item 2 keeping it's track_changes name the same.
    $this->sourceDatabase->update('track_changes_term')
      ->fields([
        'name' => 'Item 2',
      ])
      ->condition('name', 'Item 2')
      ->execute();

    // Update Item 3 triggering its track_changes by field.
    $this->sourceDatabase->update('track_changes_term')
      ->fields([
        'description' => 'Text item 3 updated',
      ])
      ->condition('name', 'Item 3')
      ->execute();

    // Update Item 2 keeping it's track_changes field the same.
    $this->sourceDatabase->update('track_changes_term')
      ->fields([
        'description' => 'Text item 4',
      ])
      ->condition('name', 'Item 4')
      ->execute();

    // Execute migration again.
    $this->executeMigration('track_changes_test');

    // Item with name changes should be updated.
    $this->assertTermExists('name', 'Item 1 updated');
    $this->assertTermDoesNotExist('name', 'Item 1');

    // Item without name changes should not be updated.
    $this->assertTermExists('name', 'Item 2');

    // Item with field changes should be updated.
    $this->assertTermExists('description', 'Text item 3 updated');
    $this->assertTermDoesNotExist('description', 'Text item 3');

    // Item without field changes should not be updated.
    $this->assertTermExists('description', 'Text item 4');
  }

  /**
   * Assert that term with given name exists.
   *
   * @param string $property
   *   Property to evaluate.
   * @param string $value
   *   Value to evaluate.
   */
  protected function assertTermExists($property, $value) {
    self::assertTrue($this->termExists($property, $value));
  }

  /**
   * Assert that term with given title does not exist.
   *
   * @param string $property
   *   Property to evaluate.
   * @param string $value
   *   Value to evaluate.
   */
  protected function assertTermDoesNotExist($property, $value) {
    self::assertFalse($this->termExists($property, $value));
  }

  /**
   * Checks if term with given name exists.
   *
   * @param string $property
   *   Property to evaluate.
   * @param string $value
   *   Value to evaluate.
   *
   * @return bool
   */
  protected function termExists($property, $value) {
    $property = $property === 'description' ? 'description__value' : $property;
    $query = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE);
    $result = $query
      ->condition($property, $value)
      ->range(0, 1)
      ->execute();

    return !empty($result);
  }

}
