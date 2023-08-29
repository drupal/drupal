<?php

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\node\NodeInterface;

/**
 * Node content revisions migration.
 *
 * @group migrate_drupal_6
 */
class MigrateNodeRevisionTest extends MigrateNodeTestBase {

  /**
   * The entity storage for node.
   *
   * @var \Drupal\Core\Entity\RevisionableStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'content_translation', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations(['d6_node', 'd6_node_revision']);
    $this->nodeStorage = $this->container->get('entity_type.manager')
      ->getStorage('node');
  }

  /**
   * Asserts various aspects of a node revision.
   *
   * @param int $id
   *   The revision ID.
   * @param string $langcode
   *   The revision language.
   * @param string $title
   *   The expected title.
   * @param string|null $log
   *   The revision log message.
   * @param int $timestamp
   *   The revision's time stamp.
   *
   * @internal
   */
  protected function assertRevision(int $id, string $langcode, string $title, ?string $log, int $timestamp): void {
    /** @var  \Drupal\node\NodeInterface $revision */
    $revision = $this->nodeStorage->loadRevision($id)
      ->getTranslation($langcode);
    $this->assertInstanceOf(NodeInterface::class, $revision);
    $this->assertSame($title, $revision->getTitle());
    $this->assertSame($log, $revision->revision_log->value);
    $this->assertSame($timestamp, (int) $revision->getRevisionCreationTime());
  }

  /**
   * Tests node revisions migration from Drupal 6 to 8.
   */
  public function testNodeRevision() {
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadRevision(2001);
    /** @var \Drupal\node\NodeInterface $node */
    $this->assertSame('1', $node->id());
    $this->assertSame('2001', $node->getRevisionId());
    $this->assertSame('und', $node->langcode->value);
    $this->assertSame('Test title rev 3', $node->getTitle());
    $this->assertSame('body test rev 3', $node->body->value);
    $this->assertSame('teaser test rev 3', $node->body->summary);
    $this->assertSame('2', $node->getRevisionUser()->id());
    $this->assertSame('modified rev 3', $node->revision_log->value);
    $this->assertSame('1420861423', $node->getRevisionCreationTime());

    $this->assertRevision(1, 'und', 'Test title', NULL, 1390095702);
    $this->assertRevision(3, 'und', 'Test title rev 3', NULL, 1420718386);
    $this->assertRevision(4, 'und', 'Test page title rev 4', NULL, 1390095701);
    $this->assertRevision(5, 'und', 'Test title rev 2', 'modified rev 2', 1390095703);
    $this->assertRevision(6, 'und', 'Node 4', NULL, 1390095701);
    $this->assertRevision(7, 'und', 'Node 5', NULL, 1390095701);
    $this->assertRevision(8, 'und', 'Node 6', NULL, 1390095701);
    $this->assertRevision(9, 'und', 'Node 7', NULL, 1390095701);
    $this->assertRevision(10, 'und', 'Node 8', NULL, 1390095701);
    $this->assertRevision(11, 'und', 'Node 9', NULL, 1390095701);
    $this->assertRevision(12, 'und', 'Once upon a time', NULL, 1444671588);
    $this->assertRevision(13, 'en', 'The Real McCoy', NULL, 1444238808);
    $this->assertRevision(15, 'zu', 'Abantu zulu', NULL, 1444238808);
    $this->assertRevision(17, 'und', 'United Federation of Planets', NULL, 1493066668);
    $this->assertRevision(18, 'und', 'Klingon Empire', NULL, 1493066677);
    $this->assertRevision(19, 'und', 'Romulan Empire', NULL, 1493066684);
    $this->assertRevision(20, 'und', 'Ferengi Commerce Authority', NULL, 1493066693);
    $this->assertRevision(21, 'und', 'Ambassador Sarek', NULL, 1494966544);
    $this->assertRevision(22, 'und', 'New Forum Topic', NULL, 1501955771);
    $this->assertRevision(2001, 'und', 'Test title rev 3', 'modified rev 3', 1420861423);
    $this->assertRevision(2002, 'en', 'John Smith - EN', NULL, 1534014650);

    // Test that the revision translations are not migrated and there should not
    // be a revision with id of 2003.
    $ids = [2, 14, 16, 23, 2003];
    foreach ($ids as $id) {
      $this->assertNull($this->nodeStorage->loadRevision($id));
    }

  }

}
