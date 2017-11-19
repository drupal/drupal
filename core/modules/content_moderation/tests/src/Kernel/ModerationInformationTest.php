<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * @coversDefaultClass \Drupal\content_moderation\ModerationInformation
 * @group content_moderation
 */
class ModerationInformationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['content_moderation', 'entity_test', 'user', 'workflows'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig(['content_moderation']);
  }

  /**
   * @covers ::getDefaultRevisionId
   * @covers ::getLatestRevisionId
   */
  public function testDefaultAndLatestRevisionId() {
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_rev', 'entity_test_rev');
    $workflow->save();

    $entity_test_rev = EntityTestRev::create([
      'name' => 'Default Revision',
      'moderation_state' => 'published',
    ]);
    $entity_test_rev->save();

    $entity_test_rev->name = 'Pending revision';
    $entity_test_rev->moderation_state = 'draft';
    $entity_test_rev->save();

    /** @var \Drupal\content_moderation\ModerationInformationInterface $moderation_info */
    $moderation_info = \Drupal::service('content_moderation.moderation_information');

    // Check that moderation information service returns the correct default
    // revision ID.
    $default_revision_id = $moderation_info->getDefaultRevisionId('entity_test_rev', $entity_test_rev->id());
    $this->assertSame(1, $default_revision_id);

    // Check that moderation information service returns the correct latest
    // revision ID.
    $latest_revision_id = $moderation_info->getLatestRevisionId('entity_test_rev', $entity_test_rev->id());
    $this->assertSame(2, $latest_revision_id);
  }

}
