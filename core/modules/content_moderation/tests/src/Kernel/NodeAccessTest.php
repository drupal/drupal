<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests with node access enabled.
 *
 * @group content_moderation
 */
class NodeAccessTest extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
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
  protected function setUp() {
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
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    $this->moderationInformation = \Drupal::service('content_moderation.moderation_information');
  }

  /**
   * Tests for moderation information methods with node access.
   */
  public function testModerationInformation() {
    // Create an admin user.
    $user = $this->createUser([], NULL, TRUE);
    \Drupal::currentUser()->setAccount($user);

    // Create a node.
    $node = $this->createNode(['type' => 'page']);
    $this->assertEquals($node->getRevisionId(), $this->moderationInformation->getDefaultRevisionId('node', $node->id()));
    $this->assertEquals($node->getRevisionId(), $this->moderationInformation->getLatestRevisionId('node', $node->id()));

    // Create a non-admin user.
    $user = $this->createUser();
    \Drupal::currentUser()->setAccount($user);
    $this->assertEquals($node->getRevisionId(), $this->moderationInformation->getDefaultRevisionId('node', $node->id()));
    $this->assertEquals($node->getRevisionId(), $this->moderationInformation->getLatestRevisionId('node', $node->id()));
  }

}
