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
  public static $modules = [
    'language',
    'content_translation',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpMigratedFiles();
    $this->installSchema('file', ['file_usage']);
    $this->executeMigrations([
      'language',
      'd6_language_content_settings',
      'd6_node',
      'd6_node_translation',
    ]);
  }

  /**
   * Test node migration from Drupal 6 to 8.
   */
  public function testNode() {
    // Confirm there are only classic node migration map tables. This shows
    // that only the classic migration ran.
    $results = $this->nodeMigrateMapTableCount('6');
    $this->assertSame(13, $results['node']);
    $this->assertSame(0, $results['node_complete']);
    $node = Node::load(1);
    $this->assertIdentical('1', $node->id(), 'Node 1 loaded.');
    $this->assertIdentical('und', $node->langcode->value);
    $this->assertIdentical('body test rev 3', $node->body->value);
    $this->assertIdentical('teaser test rev 3', $node->body->summary);
    $this->assertIdentical('filtered_html', $node->body->format);
    $this->assertIdentical('story', $node->getType(), 'Node has the correct bundle.');
    $this->assertIdentical('Test title rev 3', $node->getTitle(), 'Node has the correct title.');
    $this->assertIdentical('1390095702', $node->getCreatedTime(), 'Node has the correct created time.');
    $this->assertIdentical(FALSE, $node->isSticky());
    $this->assertIdentical('1', $node->getOwnerId());
    $this->assertIdentical('1420861423', $node->getRevisionCreationTime());

    /** @var \Drupal\node\NodeInterface $node_revision */
    $node_revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision(2001);
    $this->assertIdentical('Test title rev 3', $node_revision->getTitle());
    $this->assertIdentical('2', $node_revision->getRevisionUser()->id(), 'Node revision has the correct user');
    $this->assertSame('1', $node_revision->id(), 'Node 1 loaded.');
    $this->assertSame('2001', $node_revision->getRevisionId(), 'Node 1 revision 2001 loaded.');
    // This is empty on the first revision.
    $this->assertIdentical('modified rev 3', $node_revision->revision_log->value);
    $this->assertIdentical('This is a shared text field', $node->field_test->value);
    $this->assertIdentical('filtered_html', $node->field_test->format);
    $this->assertIdentical('10', $node->field_test_two->value);
    $this->assertIdentical('20', $node->field_test_two[1]->value);

    $this->assertIdentical('42.42', $node->field_test_three->value, 'Single field second value is correct.');
    $this->assertIdentical('3412', $node->field_test_integer_selectlist[0]->value);
    $this->assertIdentical('1', $node->field_test_identical1->value, 'Integer value is correct');
    $this->assertIdentical('1', $node->field_test_identical2->value, 'Integer value is correct');
    $this->assertIdentical('This is a field with exclude unset.', $node->field_test_exclude_unset->value, 'Field with exclude unset is correct.');

    // Test that date fields are migrated.
    $this->assertSame('2013-01-02T04:05:00', $node->field_test_date->value, 'Date field is correct');
    $this->assertSame('1391357160', $node->field_test_datestamp->value, 'Datestamp field is correct');
    $this->assertSame('2015-03-04T06:07:00', $node->field_test_datetime->value, 'Datetime field is correct');

    // Test that link fields are migrated.
    $this->assertIdentical('https://www.drupal.org/project/drupal', $node->field_test_link->uri);
    $this->assertIdentical('Drupal project page', $node->field_test_link->title);
    $this->assertIdentical(['target' => '_blank'], $node->field_test_link->options['attributes']);

    // Test the file field meta.
    $this->assertIdentical('desc', $node->field_test_filefield->description);
    $this->assertIdentical('4', $node->field_test_filefield->target_id);

    // Test that an email field is migrated.
    $this->assertSame('PrincessRuwenne@example.com', $node->field_test_email->value);

    // Test that node reference field values were migrated.
    $node = Node::load(18);
    $this->assertCount(2, $node->field_company);
    $this->assertSame('Klingon Empire', $node->field_company[0]->entity->label());
    $this->assertSame('Romulan Empire', $node->field_company[1]->entity->label());
    $this->assertCount(1, $node->field_company_2);
    $this->assertSame('Klingon Empire', $node->field_company_2[0]->entity->label());
    $this->assertCount(1, $node->field_company_3);
    $this->assertSame('Romulan Empire', $node->field_company_3[0]->entity->label());

    // Test that user reference field values were migrated.
    $this->assertCount(1, $node->field_commander);
    $this->assertSame('joe.roe', $node->field_commander[0]->entity->getAccountName());

    $node = Node::load(2);
    $this->assertIdentical('Test title rev 3', $node->getTitle());
    $this->assertIdentical('test rev 3', $node->body->value);
    $this->assertIdentical('filtered_html', $node->body->format);

    // Test that a link field with an external link is migrated.
    $this->assertIdentical('http://groups.drupal.org/', $node->field_test_link->uri);
    $this->assertIdentical('Drupal Groups', $node->field_test_link->title);
    $this->assertIdentical([], $node->field_test_link->options['attributes']);

    $node = Node::load(3);
    // Test multivalue field.
    $value_1 = $node->field_multivalue->value;
    $value_2 = $node->field_multivalue[1]->value;

    // SQLite does not support scales for float data types so we need to convert
    // the value manually.
    if ($this->container->get('database')->driver() == 'sqlite') {
      $value_1 = sprintf('%01.2f', $value_1);
      $value_2 = sprintf('%01.2f', $value_2);
    }
    $this->assertSame('33.00', $value_1);
    $this->assertSame('44.00', $value_2);

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

    // Test that content_translation_source is set.
    $manager = $this->container->get('content_translation.manager');
    $this->assertIdentical('en', $manager->getTranslationMetadata($node->getTranslation('fr'))->getSource());

    // Test that content_translation_source for a source other than English.
    $node = Node::load(12);
    $this->assertIdentical('zu', $manager->getTranslationMetadata($node->getTranslation('en'))->getSource());

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
    $title = $this->rerunMigration([
      'sourceid1' => 2,
      'destid1' => NULL,
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    ]);
    $node = Node::load(2);
    $this->assertIdentical($title, $node->getTitle());

    // Test synchronized field.
    $value = 'jsmith@example.com';
    $node = Node::load(21);
    $this->assertSame($value, $node->field_sync->value);
    $this->assertArrayNotHasKey('field_sync', $node->getTranslatableFields());

    $node = $node->getTranslation('fr');
    $this->assertSame($value, $node->field_sync->value);
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
      ->fields([
        'title' => $title,
        'format' => 2,
      ])
      ->condition('vid', 3)
      ->execute();
    $migration = $this->getMigration('d6_node:story');
    $table_name = $migration->getIdMap()->mapTableName();
    $default_connection = \Drupal::database();
    $default_connection->truncate($table_name)->execute();
    if ($new_row) {
      $hash = $migration->getIdMap()->getSourceIdsHash(['nid' => $new_row['sourceid1']]);
      $new_row['source_ids_hash'] = $hash;
      $default_connection->insert($table_name)
        ->fields($new_row)
        ->execute();
    }
    $this->executeMigration($migration);
    return $title;
  }

}
