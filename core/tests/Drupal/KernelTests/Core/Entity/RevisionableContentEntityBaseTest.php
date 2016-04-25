<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestWithRevisionLog;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\Core\Entity\RevisionableContentEntityBase
 * @group Entity
 */
class RevisionableContentEntityBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_revlog');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
  }

  public function testRevisionableContentEntity() {
    $user = User::create(['name' => 'test name']);
    $user->save();
    /** @var \Drupal\entity_test\Entity\EntityTestWithRevisionLog $entity */
    $entity = EntityTestWithRevisionLog::create([
      'type' => 'entity_test_revlog',
    ]);
    $entity->save();

    $entity->setNewRevision(TRUE);
    $random_timestamp = rand(1e8, 2e8);
    $entity->setRevisionCreationTime($random_timestamp);
    $entity->setRevisionUserId($user->id());
    $entity->setRevisionLogMessage('This is my log message');
    $entity->save();

    $revision_id = $entity->getRevisionId();

    $entity = \Drupal::entityTypeManager()->getStorage('entity_test_revlog')->loadRevision($revision_id);
    $this->assertEquals($random_timestamp, $entity->getRevisionCreationTime());
    $this->assertEquals($user->id(), $entity->getRevisionUserId());
    $this->assertEquals($user->id(), $entity->getRevisionUser()->id());
    $this->assertEquals('This is my log message', $entity->getRevisionLogMessage());
  }

}
