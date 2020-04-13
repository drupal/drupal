<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests with node access enabled.
 *
 * @group content_moderation
 */
class NodeAccessTest extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;
  use ContentModerationTestTrait;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'filter',
    'node',
    'node_access_test',
    'system',
    'user',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('workflow');
    $this->installConfig(['content_moderation', 'filter']);
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);

    // Add a moderated node type.
    $node_type = NodeType::create([
      'type' => 'page',
      'label' => 'Page',
    ]);
    $node_type->save();
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    $this->moderationInformation = \Drupal::service('content_moderation.moderation_information');
  }

  /**
   * @covers \Drupal\content_moderation\ModerationInformation::getDefaultRevisionId
   */
  public function testGetDefaultRevisionId() {
    // Create an admin user.
    $user = $this->createUser([], NULL, TRUE);
    \Drupal::currentUser()->setAccount($user);

    // Create a node.
    $node = $this->createNode(['type' => 'page']);
    $this->assertEquals($node->getRevisionId(), $this->moderationInformation->getDefaultRevisionId('node', $node->id()));

    // Create a non-admin user.
    $user = $this->createUser();
    \Drupal::currentUser()->setAccount($user);
    $this->assertEquals($node->getRevisionId(), $this->moderationInformation->getDefaultRevisionId('node', $node->id()));
  }

}
