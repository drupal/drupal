<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\entity_test\Entity\EntityTestRevPub;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests deleting a revision with revision delete form.
 *
 * @group Entity
 * @group #slow
 * @coversDefaultClass \Drupal\Core\Entity\Form\RevisionDeleteForm
 */
class RevisionDeleteFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'entity_test',
    'entity_test_revlog',
    'dblog',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests title by whether entity supports revision creation dates.
   *
   * @param string $entityTypeId
   *   The entity type to test.
   * @param string $expectedQuestion
   *   The expected question/page title.
   *
   * @covers ::getQuestion
   * @dataProvider providerPageTitle
   */
  public function testPageTitle(string $entityTypeId, string $expectedQuestion): void {
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage($entityTypeId);

    $entity = $storage->create([
      'type' => $entityTypeId,
      'name' => 'delete revision',
    ]);
    if ($entity instanceof RevisionLogInterface) {
      $date = new \DateTime('11 January 2009 4:00:00pm');
      $entity->setRevisionCreationTime($date->getTimestamp());
    }
    $entity->setNewRevision();
    $entity->save();
    $revisionId = $entity->getRevisionId();

    // Create a new latest revision.
    if ($entity instanceof RevisionLogInterface) {
      $entity->setRevisionCreationTime($date->modify('+1 hour')->getTimestamp());
    }
    $entity->setNewRevision();
    $entity->save();

    // Reload the entity.
    $revision = $storage->loadRevision($revisionId);
    $this->drupalGet($revision->toUrl('revision-delete-form'));
    $this->assertSession()->pageTextContains($expectedQuestion);
    $this->assertSession()->buttonExists('Delete');
    $this->assertSession()->linkExists('Cancel');
  }

  /**
   * Data provider for testPageTitle.
   */
  public static function providerPageTitle(): array {
    return [
      ['entity_test_rev', 'Are you sure you want to delete the revision?'],
      ['entity_test_revlog', 'Are you sure you want to delete the revision from Sun, 01/11/2009 - 16:00?'],
    ];
  }

  /**
   * Test cannot delete latest revision.
   *
   * @covers \Drupal\Core\Entity\EntityAccessControlHandler::checkAccess
   */
  public function testAccessDeleteLatestDefault(): void {
    /** @var \Drupal\entity_test\Entity\EntityTestRev $entity */
    $entity = EntityTestRev::create();
    $entity->setName('delete revision');
    $entity->save();

    $entity->setNewRevision();
    $entity->save();

    $this->drupalGet($entity->toUrl('revision-delete-form'));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Ensure that forward revision can be deleted.
   *
   * @covers \Drupal\Core\Entity\EntityAccessControlHandler::checkAccess
   */
  public function testAccessDeleteLatestForwardRevision(): void {
    /** @var \Drupal\entity_test\Entity\EntityTestRevPub $entity */
    $entity = EntityTestRevPub::create();
    $entity->setName('delete revision');
    $entity->save();

    $entity->isDefaultRevision(TRUE);
    $entity->setPublished();
    $entity->setNewRevision();
    $entity->save();

    $entity->isDefaultRevision(FALSE);
    $entity->setUnpublished();
    $entity->setNewRevision();
    $entity->save();

    $this->drupalGet($entity->toUrl('revision-delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($entity->access('delete revision', $this->rootUser, FALSE));
  }

  /**
   * Test cannot delete default revision.
   *
   * @covers \Drupal\Core\Entity\EntityAccessControlHandler::checkAccess
   */
  public function testAccessDeleteDefault(): void {
    /** @var \Drupal\entity_test\Entity\EntityTestRevPub $entity */
    $entity = EntityTestRevPub::create();
    $entity->setName('delete revision');
    $entity->save();

    $entity->isDefaultRevision(TRUE);
    $entity->setPublished();
    $entity->setNewRevision();
    $entity->save();
    $revisionId = $entity->getRevisionId();

    $entity->isDefaultRevision(FALSE);
    $entity->setUnpublished();
    $entity->setNewRevision();
    $entity->save();

    // Reload the entity.
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_revpub');
    /** @var \Drupal\entity_test\Entity\EntityTestRevPub $revision */
    $revision = $storage->loadRevision($revisionId);
    // Check default but not latest.
    $this->assertTrue($revision->isDefaultRevision());
    $this->assertFalse($revision->isLatestRevision());
    $this->drupalGet($revision->toUrl('revision-delete-form'));
    $this->assertSession()->statusCodeEquals(403);
    $this->assertFalse($revision->access('delete revision', $this->rootUser, FALSE));
  }

  /**
   * Test can delete non-latest revision.
   *
   * @covers \Drupal\Core\Entity\EntityAccessControlHandler::checkAccess
   */
  public function testAccessDeleteNonLatest(): void {
    /** @var \Drupal\entity_test\Entity\EntityTestRev $entity */
    $entity = EntityTestRev::create();
    $entity->setName('delete revision');
    $entity->save();
    $entity->isDefaultRevision();
    $revisionId = $entity->getRevisionId();

    $entity->setNewRevision();
    $entity->save();

    // Reload the entity.
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_rev');
    $revision = $storage->loadRevision($revisionId);
    $this->drupalGet($revision->toUrl('revision-delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($revision->access('delete revision', $this->rootUser, FALSE));
  }

  /**
   * Tests revision deletion, and expected response after deletion.
   *
   * @param array $permissions
   *   If not empty, a user will be created and logged in with these
   *   permissions.
   * @param string $entityTypeId
   *   The entity type to test.
   * @param string $entityLabel
   *   The entity label, which corresponds to access grants.
   * @param int $totalRevisions
   *   Total number of revisions to create.
   * @param string $expectedLog
   *   Expected log.
   * @param string $expectedMessage
   *   Expected messenger message.
   * @param string|int $expectedDestination
   *   Expected destination after deletion.
   *
   * @covers ::submitForm
   * @dataProvider providerSubmitForm
   */
  public function testSubmitForm(array $permissions, string $entityTypeId, string $entityLabel, int $totalRevisions, string $expectedLog, string $expectedMessage, $expectedDestination): void {
    if (count($permissions) > 0) {
      $this->drupalLogin($this->createUser($permissions));
    }
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage($entityTypeId);

    $entity = $storage->create([
      'type' => $entityTypeId,
      'name' => $entityLabel,
    ]);
    if ($entity instanceof RevisionLogInterface) {
      $date = new \DateTime('11 January 2009 4:00:00pm');
      $entity->setRevisionCreationTime($date->getTimestamp());
    }
    $entity->save();
    $revisionId = $entity->getRevisionId();

    $otherRevisionIds = [];
    for ($i = 0; $i < $totalRevisions - 1; $i++) {
      if ($entity instanceof RevisionLogInterface) {
        $entity->setRevisionCreationTime($date->modify('+1 hour')->getTimestamp());
      }
      $entity->setNewRevision();
      $entity->save();
      $otherRevisionIds[] = $entity->getRevisionId();
    }

    $revision = $storage->loadRevision($revisionId);
    $this->drupalGet($revision->toUrl('revision-delete-form'));
    $this->submitForm([], 'Delete');

    // The revision was deleted.
    $this->assertNull($storage->loadRevision($revisionId));
    // Make sure the other revisions were not deleted.
    foreach ($otherRevisionIds as $otherRevisionId) {
      $this->assertNotNull($storage->loadRevision($otherRevisionId));
    }

    // Destination.
    if ($expectedDestination === 404) {
      $this->assertSession()->statusCodeEquals(404);
    }
    else {
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals($expectedDestination);
    }

    // Logger log.
    $logs = $this->getLogs($entity->getEntityType()->getProvider());
    $this->assertEquals([0 => $expectedLog], $logs);
    // Messenger message.
    $this->assertSession()->pageTextContains($expectedMessage);
  }

  /**
   * Data provider for testSubmitForm.
   */
  public static function providerSubmitForm(): array {
    $data = [];

    $data['not supporting revision log, one revision remaining after delete, no view access'] = [
      [],
      'entity_test_rev',
      'view all revisions, delete revision',
      2,
      'entity_test_rev: deleted <em class="placeholder">view all revisions, delete revision</em> revision <em class="placeholder">1</em>.',
      'Revision of Entity Test Bundle view all revisions, delete revision has been deleted.',
      '/entity_test_rev/1/revisions',
    ];

    $data['not supporting revision log, one revision remaining after delete, view access'] = [
      ['view test entity'],
      'entity_test_rev',
      'view, view all revisions, delete revision',
      2,
      'entity_test_rev: deleted <em class="placeholder">view, view all revisions, delete revision</em> revision <em class="placeholder">1</em>.',
      'Revision of Entity Test Bundle view, view all revisions, delete revision has been deleted.',
      '/entity_test_rev/1/revisions',
    ];

    $data['supporting revision log, one revision remaining after delete, no view access'] = [
      [],
      'entity_test_revlog',
      'view all revisions, delete revision',
      2,
      'entity_test_revlog: deleted <em class="placeholder">view all revisions, delete revision</em> revision <em class="placeholder">1</em>.',
      'Revision from Sun, 01/11/2009 - 16:00 of Test entity - revisions log view all revisions, delete revision has been deleted.',
      '/entity_test_revlog/1/revisions',
    ];

    $data['supporting revision log, one revision remaining after delete, view access'] = [
      [],
      'entity_test_revlog',
      'view, view all revisions, delete revision',
      2,
      'entity_test_revlog: deleted <em class="placeholder">view, view all revisions, delete revision</em> revision <em class="placeholder">1</em>.',
      'Revision from Sun, 01/11/2009 - 16:00 of Test entity - revisions log view, view all revisions, delete revision has been deleted.',
      '/entity_test_revlog/1/revisions',
    ];

    return $data;
  }

  /**
   * Loads watchdog entries by channel.
   *
   * @param string $channel
   *   The logger channel.
   *
   * @return string[]
   *   Watchdog entries.
   */
  protected function getLogs(string $channel): array {
    $logs = \Drupal::database()->query("SELECT * FROM {watchdog} WHERE type = :type", [':type' => $channel])->fetchAll();
    return array_map(function (object $log) {
      return (string) new FormattableMarkup($log->message, unserialize($log->variables));
    }, $logs);
  }

}
