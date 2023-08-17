<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Session\UserSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Tests content moderation access.
 *
 * @group content_moderation
 */
class ContentModerationAccessTest extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;
  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'filter',
    'node',
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
    $this->installConfig(['content_moderation', 'filter']);
    $this->installSchema('node', ['node_access']);

    // Add a moderated node type.
    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $node_type->save();
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();
  }

  /**
   * Tests access cacheability.
   */
  public function testAccessCacheability() {
    $node = $this->createNode(['type' => 'page']);

    /** @var \Drupal\user\RoleInterface $authenticated */
    $authenticated = Role::create([
      'id' => 'authenticated',
      'label' => 'Authenticated',
    ]);
    $authenticated->grantPermission('access content');
    $authenticated->grantPermission('edit any page content');
    $authenticated->save();

    $account = new UserSession([
      'uid' => 2,
      'roles' => ['authenticated'],
    ]);

    $result = $node->access('update', $account, TRUE);
    $this->assertFalse($result->isAllowed());
    $this->assertEqualsCanonicalizing(['user.permissions'], $result->getCacheContexts());
    $this->assertEqualsCanonicalizing(['config:workflows.workflow.editorial', 'node:' . $node->id()], $result->getCacheTags());
    $this->assertEquals(CacheBackendInterface::CACHE_PERMANENT, $result->getCacheMaxAge());

    $authenticated->grantPermission('use editorial transition create_new_draft');
    $authenticated->save();

    \Drupal::entityTypeManager()->getAccessControlHandler('node')->resetCache();
    $result = $node->access('update', $account, TRUE);
    $this->assertTrue($result->isAllowed());
    $this->assertEqualsCanonicalizing(['user.permissions'], $result->getCacheContexts());
    $this->assertEqualsCanonicalizing(['config:workflows.workflow.editorial', 'node:' . $node->id()], $result->getCacheTags());
    $this->assertEquals(CacheBackendInterface::CACHE_PERMANENT, $result->getCacheMaxAge());
  }

}
