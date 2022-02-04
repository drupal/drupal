<?php

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\node\NodeInterface;
use Drupal\Tests\file\Kernel\Migrate\d6\FileMigrationTestTrait;
use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;

/**
 * Test class for a complete node migration for Drupal 6.
 *
 * @group migrate_drupal_6
 */
class MigrateNodeCompleteTest extends MigrateNodeTestBase {

  use FileMigrationTestTrait;
  use CreateTestContentEntitiesTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'menu_ui',
  ];

  /**
   * The entity storage for node.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpMigratedFiles();

    $this->createContent();

    $this->nodeStorage = $this->container->get('entity_type.manager')
      ->getStorage('node');
    $this->nodeStorage->delete($this->nodeStorage->loadMultiple());

    $this->installSchema('file', ['file_usage']);
    $this->executeMigrations([
      'language',
      'd6_language_content_settings',
      'd6_node_complete',
    ]);
  }

  /**
   * Tests the complete node migration.
   */
  public function testNodeCompleteMigration() {
    $db = \Drupal::database();
    $this->assertEquals($this->expectedNodeFieldRevisionTable(), $db->select('node_field_revision', 'nr')
      ->fields('nr')
      ->orderBy('vid')
      ->orderBy('langcode')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC));
    $this->assertEquals($this->expectedNodeFieldDataTable(), $db->select('node_field_data', 'nr')
      ->fields('nr')
      ->orderBy('nid')
      ->orderBy('vid')
      ->orderBy('langcode')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC));

    // Now load and test each revision, including the field 'field_text_plain'
    // which has text reflecting the revision.
    $data = $this->expectedRevisionEntityData()[0];
    foreach ($this->expectedNodeFieldRevisionTable() as $key => $revision) {
      $this->assertRevision($revision, $data[$key]);
    }

    // Test the order in multi-value fields.
    $revision = $this->nodeStorage->loadRevision(21);
    $this->assertSame([
      ['target_id' => '15'],
      ['target_id' => '16'],
    ], $revision->get('field_company')->getValue());
  }

  /**
   * Asserts various aspects of a node revision.
   *
   * @param array $revision
   *   An array of revision data matching a node_field_revision table row.
   * @param array $data
   *   An array of revision data.
   *
   * @internal
   */
  protected function assertRevision(array $revision, array $data): void {
    /** @var  \Drupal\node\NodeInterface $actual */
    $actual = $this->nodeStorage->loadRevision($revision['vid'])
      ->getTranslation($revision['langcode']);
    $this->assertInstanceOf(NodeInterface::class, $actual);
    $this->assertSame($revision['title'], $actual->getTitle(), sprintf("Title '%s' does not match actual '%s' for revision '%d' langcode '%s'", $revision['title'], $actual->getTitle(), $revision['vid'], $revision['langcode']));
    $this->assertSame($revision['revision_translation_affected'], $actual->get('revision_translation_affected')->value, sprintf("revision_translation_affected '%s' does not match actual '%s' for revision '%d' langcode '%s'", $revision['revision_translation_affected'], $actual->get('revision_translation_affected')->value, $revision['vid'], $revision['langcode']));

    $this->assertSame($data['created'], $actual->getRevisionCreationTime(), sprintf("Creation time '%s' does not match actual '%s' for revision '%d' langcode '%s'", $data['created'], $actual->getRevisionCreationTime(), $revision['vid'], $revision['langcode']));
    $this->assertSame($data['changed'], $actual->getChangedTime(), sprintf("Changed time '%s' does not match actual '%s' for revision '%d' langcode '%s'", $data['changed'], $actual->getChangedTime(), $revision['vid'], $revision['langcode']));
    $this->assertSame($data['log'], $actual->getRevisionLogMessage(), sprintf("Revision log '%s' does not match actual '%s' for revision '%d' langcode '%s'", var_export($data['log'], TRUE), $actual->getRevisionLogMessage(), $revision['vid'], $revision['langcode']));
  }

  /**
   * Provides the expected node_field_data table.
   *
   * @return array
   *   The expected table rows.
   */
  protected function expectedNodeFieldDataTable() {
    return [
      0 =>
        [
          'nid' => '1',
          'vid' => '2001',
          'type' => 'story',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Test title rev 3',
          'created' => '1390095702',
          'changed' => '1420861423',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      1 =>
        [
          'nid' => '2',
          'vid' => '3',
          'type' => 'story',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Test title rev 3',
          'created' => '1388271197',
          'changed' => '1420718386',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      2 =>
        [
          'nid' => '3',
          'vid' => '4',
          'type' => 'test_planet',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Test page title rev 4',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      3 =>
        [
          'nid' => '4',
          'vid' => '6',
          'type' => 'test_planet',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Node 4',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      4 =>
        [
          'nid' => '5',
          'vid' => '7',
          'type' => 'test_planet',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Node 5',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      5 =>
        [
          'nid' => '6',
          'vid' => '8',
          'type' => 'test_planet',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Node 6',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      6 =>
        [
          'nid' => '7',
          'vid' => '9',
          'type' => 'test_planet',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Node 7',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      7 =>
        [
          'nid' => '8',
          'vid' => '10',
          'type' => 'test_planet',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Node 8',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      8 =>
        [
          'nid' => '9',
          'vid' => '12',
          'type' => 'story',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Once upon a time',
          'created' => '1444671588',
          'changed' => '1444671588',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      9 =>
        [
          'nid' => '10',
          'vid' => '14',
          'type' => 'page',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'The Real McCoy',
          'created' => '1444238800',
          'changed' => '1444238808',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      10 =>
        [
          'nid' => '10',
          'vid' => '14',
          'type' => 'page',
          'langcode' => 'fr',
          'status' => '1',
          'uid' => '1',
          'title' => 'Le Vrai McCoy',
          'created' => '1444239050',
          'changed' => '1444239050',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      11 =>
        [
          'nid' => '12',
          'vid' => '23',
          'type' => 'page',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'The Zulu People',
          'created' => '1444239050',
          'changed' => '1444239050',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'zu',
          'content_translation_outdated' => '0',
        ],
      12 =>
        [
          'nid' => '12',
          'vid' => '23',
          'type' => 'page',
          'langcode' => 'fr',
          'status' => '1',
          'uid' => '1',
          'title' => 'Le peuple zoulou',
          'created' => '1520613038',
          'changed' => '1520613305',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'zu',
          'content_translation_outdated' => '0',
        ],
      13 =>
        [
          'nid' => '12',
          'vid' => '23',
          'type' => 'page',
          'langcode' => 'zu',
          'status' => '1',
          'uid' => '1',
          'title' => 'Abantu zulu',
          'created' => '1444238800',
          'changed' => '1444238808',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'zu',
          'content_translation_outdated' => '0',
        ],
      14 =>
        [
          'nid' => '14',
          'vid' => '17',
          'type' => 'company',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'United Federation of Planets',
          'created' => '1493066668',
          'changed' => '1493066668',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      15 =>
        [
          'nid' => '15',
          'vid' => '18',
          'type' => 'company',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Klingon Empire',
          'created' => '1493066677',
          'changed' => '1493066677',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      16 =>
        [
          'nid' => '16',
          'vid' => '19',
          'type' => 'company',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Romulan Empire',
          'created' => '1493066684',
          'changed' => '1493066684',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      17 =>
        [
          'nid' => '17',
          'vid' => '20',
          'type' => 'company',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Ferengi Commerce Authority',
          'created' => '1493066693',
          'changed' => '1493066693',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      18 =>
        [
          'nid' => '18',
          'vid' => '21',
          'type' => 'employee',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Ambassador Sarek',
          'created' => '1493066711',
          'changed' => '1494966544',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      19 =>
        [
          'nid' => '19',
          'vid' => '22',
          'type' => 'forum',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'New Forum Topic',
          'created' => '1501955771',
          'changed' => '1501955771',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      20 =>
        [
          'nid' => '21',
          'vid' => '2003',
          'type' => 'employee',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'John Smith - EN',
          'created' => '1534014650',
          'changed' => '1534014650',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      21 =>
        [
          'nid' => '21',
          'vid' => '2003',
          'type' => 'employee',
          'langcode' => 'fr',
          'status' => '1',
          'uid' => '1',
          'title' => 'John Smith - FR',
          'created' => '1534014687',
          'changed' => '1534014687',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
    ];
  }

  /**
   * Provides the expected node_field_revision table.
   *
   * @return array
   *   The table.
   */
  protected function expectedNodeFieldRevisionTable() {
    return [
      0 =>
        [
          'nid' => '1',
          'vid' => '1',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Test title',
          'created' => '1390095702',
          'changed' => '1390095702',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      1 =>
        [
          'nid' => '2',
          'vid' => '3',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Test title rev 3',
          'created' => '1388271197',
          'changed' => '1420718386',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      2 =>
        [
          'nid' => '3',
          'vid' => '4',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Test page title rev 4',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      3 =>
        [
          'nid' => '1',
          'vid' => '5',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Test title rev 2',
          'created' => '1390095702',
          'changed' => '1390095703',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      4 =>
        [
          'nid' => '4',
          'vid' => '6',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Node 4',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      5 =>
        [
          'nid' => '5',
          'vid' => '7',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Node 5',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      6 =>
        [
          'nid' => '6',
          'vid' => '8',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Node 6',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      7 =>
        [
          'nid' => '7',
          'vid' => '9',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Node 7',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      8 =>
        [
          'nid' => '8',
          'vid' => '10',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Node 8',
          'created' => '1388271527',
          'changed' => '1390095701',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      9 =>
        [
          'nid' => '9',
          'vid' => '11',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Node 9',
          'created' => '1444671588',
          'changed' => '1390095701',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      10 =>
        [
          'nid' => '9',
          'vid' => '12',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Once upon a time',
          'created' => '1444671588',
          'changed' => '1444671588',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      11 =>
        [
          'nid' => '10',
          'vid' => '13',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'The Real McCoy',
          'created' => '1444238800',
          'changed' => '1444238808',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      12 =>
        [
          'nid' => '10',
          'vid' => '14',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'The Real McCoy',
          'created' => '1444238800',
          'changed' => '1444238808',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      13 =>
        [
          'nid' => '10',
          'vid' => '14',
          'langcode' => 'fr',
          'status' => '1',
          'uid' => '1',
          'title' => 'Le Vrai McCoy',
          'created' => '1444239050',
          'changed' => '1444239050',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      14 =>
        [
          'nid' => '12',
          'vid' => '15',
          'langcode' => 'zu',
          'status' => '1',
          'uid' => '1',
          'title' => 'Abantu zulu',
          'created' => '1444238800',
          'changed' => '1444238808',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'zu',
          'content_translation_outdated' => '0',
        ],
      15 =>
        [
          'nid' => '12',
          'vid' => '16',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'The Zulu People',
          'created' => '1444239050',
          'changed' => '1444239050',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'zu',
          'content_translation_outdated' => '0',
        ],
      16 =>
        [
          'nid' => '12',
          'vid' => '16',
          'langcode' => 'zu',
          'status' => '1',
          'uid' => '1',
          'title' => 'Abantu zulu',
          'created' => '1444238800',
          'changed' => '1444238808',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'zu',
          'content_translation_outdated' => '0',
        ],
      17 =>
        [
          'nid' => '14',
          'vid' => '17',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'United Federation of Planets',
          'created' => '1493066668',
          'changed' => '1493066668',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      18 =>
        [
          'nid' => '15',
          'vid' => '18',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Klingon Empire',
          'created' => '1493066677',
          'changed' => '1493066677',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      19 =>
        [
          'nid' => '16',
          'vid' => '19',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Romulan Empire',
          'created' => '1493066684',
          'changed' => '1493066684',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      20 =>
        [
          'nid' => '17',
          'vid' => '20',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Ferengi Commerce Authority',
          'created' => '1493066693',
          'changed' => '1493066693',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      21 =>
        [
          'nid' => '18',
          'vid' => '21',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Ambassador Sarek',
          'created' => '1493066711',
          'changed' => '1494966544',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      22 =>
        [
          'nid' => '19',
          'vid' => '22',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'New Forum Topic',
          'created' => '1501955771',
          'changed' => '1501955771',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      23 =>
        [
          'nid' => '12',
          'vid' => '23',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'The Zulu People',
          'created' => '1444239050',
          'changed' => '1444239050',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'zu',
          'content_translation_outdated' => '0',
        ],
      24 =>
        [
          'nid' => '12',
          'vid' => '23',
          'langcode' => 'fr',
          'status' => '1',
          'uid' => '1',
          'title' => 'Le peuple zoulou',
          'created' => '1520613038',
          'changed' => '1520613305',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'zu',
          'content_translation_outdated' => '0',
        ],
      25 =>
        [
          'nid' => '12',
          'vid' => '23',
          'langcode' => 'zu',
          'status' => '1',
          'uid' => '1',
          'title' => 'Abantu zulu',
          'created' => '1444238800',
          'changed' => '1444238808',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'zu',
          'content_translation_outdated' => '0',
        ],
      26 =>
        [
          'nid' => '1',
          'vid' => '2001',
          'langcode' => 'und',
          'status' => '1',
          'uid' => '1',
          'title' => 'Test title rev 3',
          'created' => '1390095702',
          'changed' => '1420861423',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      27 =>
        [
          'nid' => '21',
          'vid' => '2002',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'John Smith - EN',
          'created' => '1534014650',
          'changed' => '1534014650',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      28 =>
        [
          'nid' => '21',
          'vid' => '2003',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'John Smith - EN',
          'created' => '1534014650',
          'changed' => '1534014650',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      29 =>
        [
          'nid' => '21',
          'vid' => '2003',
          'langcode' => 'fr',
          'status' => '1',
          'uid' => '1',
          'title' => 'John Smith - FR',
          'created' => '1534014687',
          'changed' => '1534014687',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
    ];
  }

  /**
   * Provides the expected node_field_revision table.
   *
   * @return array
   *   Selected properties and fields on the revision.
   */
  protected function expectedRevisionEntityData() {
    return [
      $revision_data = [
        // Node 1, revision 1, und.
        0 =>
          [
            'log' => NULL,
            'created' => '1390095702',
            'changed' => '1390095702',
          ],
        // Node 2, revision 3, und.
        1 =>
          [
            'log' => NULL,
            'created' => '1420718386',
            'changed' => '1420718386',
          ],
        // Node 3, revision 4, und.
        2 =>
          [
            'log' => NULL,
            'created' => '1390095701',
            'changed' => '1390095701',
          ],
        // Node 1, revision 5, und.
        3 =>
          [
            'log' => 'modified rev 2',
            'created' => '1390095703',
            'changed' => '1390095703',
          ],
        // Node 4, revision 6, und.
        4 =>
          [
            'log' => NULL,
            'created' => '1390095701',
            'changed' => '1390095701',
          ],
        // Node 5, revision 7, und.
        5 =>
          [
            'log' => NULL,
            'created' => '1390095701',
            'changed' => '1390095701',
          ],
        // Node 6, revision 8, und.
        6 =>
          [
            'log' => NULL,
            'created' => '1390095701',
            'changed' => '1390095701',
          ],
        // Node 7, revision 9, und.
        7 =>
          [
            'log' => NULL,
            'created' => '1390095701',
            'changed' => '1390095701',
          ],
        // Node 8, revision 10, und.
        8 =>
          [
            'log' => NULL,
            'created' => '1390095701',
            'changed' => '1390095701',
          ],
        // Node 9, revision 11, und.
        9 =>
          [
            'log' => NULL,
            'created' => '1390095701',
            'changed' => '1390095701',
          ],
        // Node 9, revision 12, und.
        10 =>
          [
            'log' => NULL,
            'created' => '1444671588',
            'changed' => '1444671588',
          ],
        // Node 10, revision 13, en.
        11 =>
          [
            'log' => NULL,
            'created' => '1444238808',
            'changed' => '1444238808',
          ],
        // Node 10, revision 14, en.
        12 =>
          [
            'log' => NULL,
            'created' => '1444239050',
            'changed' => '1444238808',
          ],
        // Node 10, revision 14, fr.
        13 =>
          [
            'log' => NULL,
            'created' => '1444239050',
            'changed' => '1444239050',
          ],
        // Node 12, revision 15, zu.
        14 =>
          [
            'log' => NULL,
            'created' => '1444238808',
            'changed' => '1444238808',
          ],
        // Node 12, revision 16, en.
        15 =>
          [
            'log' => NULL,
            'created' => '1444239050',
            'changed' => '1444239050',
          ],
        // Node 12, revision 16, zu.
        16 =>
          [
            'log' => NULL,
            'created' => '1444239050',
            'changed' => '1444238808',
          ],
        // Node 14, revision 17, und.
        17 =>
          [
            'log' => NULL,
            'created' => '1493066668',
            'changed' => '1493066668',
          ],
        // Node 15, revision 18, und.
        18 =>
          [
            'log' => NULL,
            'created' => '1493066677',
            'changed' => '1493066677',
          ],
        // Node 16, revision 19, und.
        19 =>
          [
            'log' => NULL,
            'created' => '1493066684',
            'changed' => '1493066684',
          ],
        // Node 17, revision 20, und.
        20 =>
          [
            'log' => NULL,
            'created' => '1493066693',
            'changed' => '1493066693',
          ],
        // Node 18, revision 21, und.
        21 =>
          [
            'log' => NULL,
            'created' => '1494966544',
            'changed' => '1494966544',
          ],
        // Node 19, revision 22, und.
        22 =>
          [
            'log' => NULL,
            'created' => '1501955771',
            'changed' => '1501955771',
          ],
        // Node 12, revision 23, en.
        23 =>
          [
            'log' => NULL,
            'created' => '1520613305',
            'changed' => '1444239050',
          ],
        // Node 12, revision 23, fr.
        24 =>
          [
            'log' => NULL,
            'created' => '1520613305',
            'changed' => '1520613305',
          ],
        // Node 12, revision 23, zu.
        25 =>
          [
            'log' => NULL,
            'created' => '1520613305',
            'changed' => '1444238808',
          ],
        // Node 1, revision 2001, und.
        26 =>
          [
            'log' => 'modified rev 3',
            'created' => '1420861423',
            'changed' => '1420861423',
          ],
        // Node 21, revision 2002, en.
        27 =>
          [
            'log' => NULL,
            'created' => '1534014650',
            'changed' => '1534014650',
          ],
        // Node 21, revision 2003, en.
        28 =>
          [
            'log' => NULL,
            'created' => '1534014687',
            'changed' => '1534014650',
          ],
        // Node 21, revision 2003, fr.
        29 =>
          [
            'log' => NULL,
            'created' => '1534014687',
            'changed' => '1534014687',
          ],
      ],
    ];
  }

}
