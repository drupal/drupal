<?php

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\file\Kernel\Migrate\d6\FileMigrationTestTrait;

/**
 * Node content migration.
 *
 * @group migrate_drupal_6
 */
class MigrateNodeTest extends MigrateNodeTestBase {

  use FileMigrationTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpMigratedFiles();
    $this->installSchema('file', ['file_usage']);
    $this->executeMigrations(['language', 'd6_node', 'd6_node_translation']);
  }

  /**
   * Test node migration from Drupal 6 to 8.
   */
  public function testNode() {
    $node = Node::load(1);
    $this->assertIdentical('1', $node->id(), 'Node 1 loaded.');
    $this->assertIdentical('und', $node->langcode->value);
    $this->assertIdentical('test', $node->body->value);
    $this->assertIdentical('test', $node->body->summary);
    $this->assertIdentical('filtered_html', $node->body->format);
    $this->assertIdentical('story', $node->getType(), 'Node has the correct bundle.');
    $this->assertIdentical('Test title', $node->getTitle(), 'Node has the correct title.');
    $this->assertIdentical('1388271197', $node->getCreatedTime(), 'Node has the correct created time.');
    $this->assertIdentical(FALSE, $node->isSticky());
    $this->assertIdentical('1', $node->getOwnerId());
    $this->assertIdentical('1420861423', $node->getRevisionCreationTime());

    /** @var \Drupal\node\NodeInterface $node_revision */
    $node_revision = \Drupal::entityManager()->getStorage('node')->loadRevision(1);
    $this->assertIdentical('Test title', $node_revision->getTitle());
    $this->assertIdentical('1', $node_revision->getRevisionUser()->id(), 'Node revision has the correct user');
    // This is empty on the first revision.
    $this->assertIdentical(NULL, $node_revision->revision_log->value);
    $this->assertIdentical('This is a shared text field', $node->field_test->value);
    $this->assertIdentical('filtered_html', $node->field_test->format);
    $this->assertIdentical('10', $node->field_test_two->value);
    $this->assertIdentical('20', $node->field_test_two[1]->value);

    $this->assertIdentical('42.42', $node->field_test_three->value, 'Single field second value is correct.');
    $this->assertIdentical('3412', $node->field_test_integer_selectlist[0]->value);
    $this->assertIdentical('1', $node->field_test_identical1->value, 'Integer value is correct');
    $this->assertIdentical('1', $node->field_test_identical2->value, 'Integer value is correct');
    $this->assertIdentical('This is a field with exclude unset.', $node->field_test_exclude_unset->value, 'Field with exclude unset is correct.');

    // Test that link fields are migrated.
    $this->assertIdentical('https://www.drupal.org/project/drupal', $node->field_test_link->uri);
    $this->assertIdentical('Drupal project page', $node->field_test_link->title);
    $this->assertIdentical(['target' => '_blank'], $node->field_test_link->options['attributes']);

    // Test the file field meta.
    $this->assertIdentical('desc', $node->field_test_filefield->description);
    $this->assertIdentical('5', $node->field_test_filefield->target_id);

    $node = Node::load(2);
    $this->assertIdentical('Test title rev 3', $node->getTitle());
    $this->assertIdentical('test rev 3', $node->body->value);
    $this->assertIdentical('filtered_html', $node->body->format);

    // Test that a link field with an external link is migrated.
    $this->assertIdentical('http://groups.drupal.org/', $node->field_test_link->uri);
    $this->assertIdentical('Drupal Groups', $node->field_test_link->title);
    $this->assertIdentical([], $node->field_test_link->options['attributes']);

    // Test that a link field with an internal link is migrated.
    $node = Node::load(9);
    $this->assertSame('internal:/node/10', $node->field_test_link->uri);
    $this->assertSame('Buy it now', $node->field_test_link->title);
    $this->assertSame(['attributes' => ['target' => '_blank']], $node->field_test_link->options);

    // Test that translations are working.
    $node = Node::load(10);
    $this->assertIdentical('en', $node->langcode->value);
    $this->assertIdentical('The Real McCoy', $node->title->value);
    $this->assertTrue($node->hasTranslation('fr'), "Node 10 has french translation");

    // Node 11 is a translation of node 10, and should not be imported separately.
    $this->assertNull(Node::load(11), "Node 11 doesn't exist in D8, it was a translation");

    // Rerun migration with two source database changes.
    // 1. Add an invalid link attributes and a different URL and
    // title. If only the attributes are changed the error does not occur.
    Database::getConnection('default', 'migrate')
      ->update('content_type_story')
      ->fields([
        'field_test_link_url' => 'https://www.drupal.org/node/2127611',
        'field_test_link_title' => 'Migrate API in Drupal 8',
        'field_test_link_attributes' => '',
      ])
      ->condition('nid', '2')
      ->condition('vid', '3')
      ->execute();

    // 2. Add a leading slash to an internal link.
    Database::getConnection('default', 'migrate')
      ->update('content_type_story')
      ->fields([
        'field_test_link_url' => '/node/10',
      ])
      ->condition('nid', '9')
      ->condition('vid', '12')
      ->execute();

    $this->rerunMigration();
    $node = Node::load(2);
    $this->assertIdentical('https://www.drupal.org/node/2127611', $node->field_test_link->uri);
    $this->assertIdentical('Migrate API in Drupal 8', $node->field_test_link->title);
    $this->assertIdentical([], $node->field_test_link->options['attributes']);

    $node = Node::load(9);
    $this->assertSame('internal:/node/10', $node->field_test_link->uri);
    $this->assertSame('Buy it now', $node->field_test_link->title);
    $this->assertSame(['attributes' => ['target' => '_blank']], $node->field_test_link->options);

    // Test that we can re-import using the EntityContentBase destination.
    $title = $this->rerunMigration();
    $node = Node::load(2);
    $this->assertIdentical($title, $node->getTitle());
    // Test multi-column fields are correctly upgraded.
    $this->assertIdentical('test rev 3', $node->body->value);
    $this->assertIdentical('full_html', $node->body->format);

    // Now insert a row indicating a failure and set to update later.
    $title = $this->rerunMigration(array(
      'sourceid1' => 2,
      'destid1' => NULL,
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    ));
    $node = Node::load(2);
    $this->assertIdentical($title, $node->getTitle());
  }

  /**
   * Execute the migration a second time.
   *
   * @param array $new_row
   *   An optional row to be inserted into the id map.
   *
   * @return string
   *   The new title in the source for vid 3.
   */
  protected function rerunMigration($new_row = []) {
    $title = $this->randomString();
    $source_connection = Database::getConnection('default', 'migrate');
    $source_connection->update('node_revisions')
      ->fields(array(
        'title' => $title,
        'format' => 2,
      ))
      ->condition('vid', 3)
      ->execute();
    $migration = $this->getMigration('d6_node:story');
    $table_name = $migration->getIdMap()->mapTableName();
    $default_connection = \Drupal::database();
    $default_connection->truncate($table_name)->execute();
    if ($new_row) {
      $hash = $migration->getIdMap()->getSourceIDsHash(['nid' => $new_row['sourceid1']]);
      $new_row['source_ids_hash'] = $hash;
      $default_connection->insert($table_name)
        ->fields($new_row)
        ->execute();
    }
    $this->executeMigration($migration);
    return $title;
  }

}
