<?php

namespace Drupal\Tests\content_moderation\Unit;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\content_moderation\Access\LatestRevisionCheck;
use Drupal\content_moderation\ModerationInformation;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\content_moderation\Access\LatestRevisionCheck
 * @group content_moderation
 */
class LatestRevisionCheckTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Initialize Drupal container since the cache context manager is needed.
    $contexts_manager = $this->prophesize(CacheContextsManager::class);
    $contexts_manager->assertValidTokens(Argument::any())->willReturn(TRUE);
    $builder = new ContainerBuilder();
    $builder->set('cache_contexts_manager', $contexts_manager->reveal());
    \Drupal::setContainer($builder);
  }

  /**
   * Test the access check of the LatestRevisionCheck service.
   *
   * @param string $entity_class
   *   The class of the entity to mock.
   * @param string $entity_type
   *   The machine name of the entity to mock.
   * @param bool $has_pending_revision
   *   Whether this entity should have a pending revision in the system.
   * @param array $account_permissions
   *   An array of permissions the account has.
   * @param bool $is_owner
   *   Indicates if the user should be the owner of the entity.
   * @param string $result_class
   *   The AccessResult class that should result. One of AccessResultAllowed,
   *   AccessResultForbidden, AccessResultNeutral.
   *
   * @dataProvider accessSituationProvider
   */
  public function testLatestAccessPermissions($entity_class, $entity_type, $has_pending_revision, array $account_permissions, $is_owner, $result_class) {

    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->prophesize(AccountInterface::class);
    $possible_permissions = [
      'view latest version',
      'view any unpublished content',
      'view own unpublished content',
    ];
    foreach ($possible_permissions as $permission) {
      $account->hasPermission($permission)->willReturn(in_array($permission, $account_permissions));
    }
    $account->id()->willReturn(42);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->prophesize($entity_class);
    $entity->getCacheContexts()->willReturn([]);
    $entity->getCacheTags()->willReturn([]);
    $entity->getCacheMaxAge()->willReturn(0);
    if (is_subclass_of($entity_class, EntityOwnerInterface::class)) {
      $entity->getOwnerId()->willReturn($is_owner ? 42 : 3);
    }

    /** @var \Drupal\content_moderation\ModerationInformation $mod_info */
    $mod_info = $this->prophesize(ModerationInformation::class);
    $mod_info->hasPendingRevision($entity->reveal())->willReturn($has_pending_revision);

    $route = $this->prophesize(Route::class);

    $route->getOption('_content_moderation_entity_type')->willReturn($entity_type);

    $route_match = $this->prophesize(RouteMatch::class);
    $route_match->getParameter($entity_type)->willReturn($entity->reveal());

    $lrc = new LatestRevisionCheck($mod_info->reveal());

    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = $lrc->access($route->reveal(), $route_match->reveal(), $account->reveal());

    $this->assertInstanceOf($result_class, $result);

  }

  /**
   * Data provider for testLastAccessPermissions().
   */
  public function accessSituationProvider() {
    return [
      // Node with global permissions and latest version.
      [Node::class, 'node', TRUE, ['view latest version', 'view any unpublished content'], FALSE, AccessResultAllowed::class],
      // Node with global permissions and no latest version.
      [Node::class, 'node', FALSE, ['view latest version', 'view any unpublished content'], FALSE, AccessResultForbidden::class],
      // Node with own content permissions and latest version.
      [Node::class, 'node', TRUE, ['view latest version', 'view own unpublished content'], TRUE, AccessResultAllowed::class],
      // Node with own content permissions and no latest version.
      [Node::class, 'node', FALSE, ['view latest version', 'view own unpublished content'], FALSE, AccessResultForbidden::class],
      // Node with own content permissions and latest version, but no perms to
      // view latest version.
      [Node::class, 'node', TRUE, ['view own unpublished content'], TRUE, AccessResultNeutral::class],
      // Node with own content permissions and no latest version, but no perms
      // to view latest version.
      [Node::class, 'node', TRUE, ['view own unpublished content'], FALSE, AccessResultNeutral::class],
      // Block with pending revision, and permissions to view any.
      [BlockContent::class, 'block_content', TRUE, ['view latest version', 'view any unpublished content'], FALSE, AccessResultAllowed::class],
      // Block with no pending revision.
      [BlockContent::class, 'block_content', FALSE, ['view latest version', 'view any unpublished content'], FALSE, AccessResultForbidden::class],
      // Block with pending revision, but no permission to view any.
      [BlockContent::class, 'block_content', TRUE, ['view latest version', 'view own unpublished content'], FALSE, AccessResultNeutral::class],
      // Block with no pending revision.
      [BlockContent::class, 'block_content', FALSE, ['view latest version', 'view own unpublished content'], FALSE, AccessResultForbidden::class],
    ];
  }

}
