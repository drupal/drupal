<?php

namespace Drupal\FunctionalTests\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\entity_test_revlog\Entity\EntityTestWithRevisionLog;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests reverting a revision with revision revert form.
 *
 * @group Entity
 * @coversDefaultClass \Drupal\Core\Entity\Form\RevisionRevertForm
 */
class RevisionRevertFormTest extends BrowserTestBase {

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
    $storage = \Drupal::entityTypeManager()->getStorage($entityTypeId);

    $entity = $storage->create([
      'type' => $entityTypeId,
      'name' => 'revert',
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
    $this->drupalGet($revision->toUrl('revision-revert-form'));
    $this->assertSession()->pageTextContains($expectedQuestion);
    $this->assertSession()->buttonExists('Revert');
    $this->assertSession()->linkExists('Cancel');
  }

  /**
   * Data provider for testPageTitle.
   */
  public function providerPageTitle(): array {
    return [
      ['entity_test_rev', 'Are you sure you want to revert the revision?'],
      ['entity_test_revlog', 'Are you sure you want to revert to the revision from Sun, 01/11/2009 - 16:00?'],
    ];
  }

  /**
   * Test cannot revert latest revision.
   *
   * @covers \Drupal\Core\Entity\EntityAccessControlHandler::checkAccess
   */
  public function testAccessRevertLatest(): void {
    /** @var \Drupal\entity_test\Entity\EntityTestRev $entity */
    $entity = EntityTestRev::create();
    $entity->setName('revert');
    $entity->save();

    $entity->setNewRevision();
    $entity->save();

    $this->drupalGet($entity->toUrl('revision-revert-form'));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test can revert non-latest revision.
   *
   * @covers \Drupal\Core\Entity\EntityAccessControlHandler::checkAccess
   */
  public function testAccessRevertNonLatest(): void {
    /** @var \Drupal\entity_test\Entity\EntityTestRev $entity */
    $entity = EntityTestRev::create();
    $entity->setName('revert');
    $entity->save();
    $revisionId = $entity->getRevisionId();

    $entity->setNewRevision();
    $entity->save();

    // Reload the entity.
    $revision = \Drupal::entityTypeManager()->getStorage('entity_test_rev')
      ->loadRevision($revisionId);
    $this->drupalGet($revision->toUrl('revision-revert-form'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests revision revert, and expected response after revert.
   *
   * @param array $permissions
   *   If not empty, a user will be created and logged in with these
   *   permissions.
   * @param string $entityTypeId
   *   The entity type to test.
   * @param string $entityLabel
   *   The entity label, which corresponds to access grants.
   * @param string $expectedLog
   *   Expected log.
   * @param string $expectedMessage
   *   Expected messenger message.
   * @param string $expectedDestination
   *   Expected destination after deletion.
   *
   * @covers ::submitForm
   * @dataProvider providerSubmitForm
   */
  public function testSubmitForm(array $permissions, string $entityTypeId, string $entityLabel, string $expectedLog, string $expectedMessage, string $expectedDestination): void {
    if (count($permissions) > 0) {
      $this->drupalLogin($this->createUser($permissions));
    }
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

    if ($entity instanceof RevisionLogInterface) {
      $entity->setRevisionCreationTime($date->modify('+1 hour')->getTimestamp());
    }
    $entity->setNewRevision();
    $entity->save();

    $revision = $storage->loadRevision($revisionId);
    $this->drupalGet($revision->toUrl('revision-revert-form'));

    $count = $this->countRevisions($entityTypeId);
    $this->submitForm([], 'Revert');

    // A new revision was created.
    $this->assertEquals($count + 1, $this->countRevisions($entityTypeId));

    // Destination.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals($expectedDestination);

    // Logger log.
    $logs = $this->getLogs($entity->getEntityType()->getProvider());
    $this->assertEquals([0 => $expectedLog], $logs);
    // Messenger message.
    $this->assertSession()->pageTextContains($expectedMessage);
  }

  /**
   * Data provider for testSubmitForm.
   */
  public function providerSubmitForm(): array {
    $data = [];

    $data['not supporting revision log, no version history access'] = [
      ['view test entity'],
      'entity_test_rev',
      'view, revert',
      'entity_test_rev: reverted <em class="placeholder">view, revert</em> revision <em class="placeholder">1</em>.',
      'Entity Test Bundle view, revert has been reverted.',
      '/entity_test_rev/manage/1',
    ];

    $data['not supporting revision log, version history access'] = [
      ['view test entity'],
      'entity_test_rev',
      'view, view all revisions, revert',
      'entity_test_rev: reverted <em class="placeholder">view, view all revisions, revert</em> revision <em class="placeholder">1</em>.',
      'Entity Test Bundle view, view all revisions, revert has been reverted.',
      '/entity_test_rev/1/revisions',
    ];

    $data['supporting revision log, no version history access'] = [
      [],
      'entity_test_revlog',
      'view, revert',
      'entity_test_revlog: reverted <em class="placeholder">view, revert</em> revision <em class="placeholder">1</em>.',
      'Test entity - revisions log view, revert has been reverted to the revision from Sun, 01/11/2009 - 16:00.',
      '/entity_test_revlog/manage/1',
    ];

    $data['supporting revision log, version history access'] = [
      [],
      'entity_test_revlog',
      'view, view all revisions, revert',
      'entity_test_revlog: reverted <em class="placeholder">view, view all revisions, revert</em> revision <em class="placeholder">1</em>.',
      'Test entity - revisions log view, view all revisions, revert has been reverted to the revision from Sun, 01/11/2009 - 16:00.',
      '/entity_test_revlog/1/revisions',
    ];

    return $data;
  }

  /**
   * Tests the revert process.
   *
   * @covers ::prepareRevision
   */
  public function testPrepareRevision(): void {
    $user = $this->createUser();
    $this->drupalLogin($user);

    /** @var \Drupal\entity_test_revlog\Entity\EntityTestWithRevisionLog $entity */
    $entity = EntityTestWithRevisionLog::create([
      'type' => 'entity_test_revlog',
      'name' => 'revert',
    ]);

    $date = new \DateTime('11 January 2009 4:00:00pm');
    $entity->setRevisionCreationTime($date->getTimestamp());
    $entity->isDefaultRevision(TRUE);
    $entity->setNewRevision();
    $entity->save();

    $revisionCreationTime = $date->modify('+1 hour')->getTimestamp();
    $entity->setRevisionCreationTime($revisionCreationTime);
    $entity->setRevisionUserId(0);
    $entity->isDefaultRevision(FALSE);
    $entity->setNewRevision();
    $entity->save();
    $targetRevertRevisionId = $entity->getRevisionId();

    // Create a another revision so the previous revision can be reverted to.
    $entity->setRevisionCreationTime($date->modify('+1 hour')->getTimestamp());
    $entity->isDefaultRevision(FALSE);
    $entity->setNewRevision();
    $entity->save();

    $count = $this->countRevisions($entity->getEntityTypeId());

    // Load the revision to be copied.
    $storage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());
    /** @var \Drupal\entity_test_revlog\Entity\EntityTestWithRevisionLog $targetRevision */
    $targetRevision = $storage->loadRevision($targetRevertRevisionId);

    $this->drupalGet($targetRevision->toUrl('revision-revert-form'));
    $this->submitForm([], 'Revert');

    // Load the new latest revision.
    /** @var \Drupal\entity_test_revlog\Entity\EntityTestWithRevisionLog $latestRevision */
    $latestRevision = $storage->loadUnchanged($entity->id());
    $this->assertEquals($count + 1, $this->countRevisions($entity->getEntityTypeId()));
    $this->assertEquals('Copy of the revision from <em class="placeholder">Sun, 01/11/2009 - 17:00</em>.', $latestRevision->getRevisionLogMessage());
    $this->assertGreaterThan($revisionCreationTime, $latestRevision->getRevisionCreationTime());
    $this->assertEquals($user->id(), $latestRevision->getRevisionUserId());
    $this->assertTrue($latestRevision->isDefaultRevision());
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

  /**
   * Count number of revisions for an entity type.
   *
   * @param string $entityTypeId
   *   The entity type.
   *
   * @return int
   *   Number of revisions for an entity type.
   */
  protected function countRevisions(string $entityTypeId): int {
    return (int) \Drupal::entityTypeManager()->getStorage($entityTypeId)
      ->getQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->count()
      ->execute();
  }

}
