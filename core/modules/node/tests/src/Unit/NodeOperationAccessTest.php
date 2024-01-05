<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeAccessControlHandler;
use Drupal\node\NodeGrantDatabaseStorageInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests node operations.
 *
 * @coversDefaultClass \Drupal\node\NodeAccessControlHandler
 * @group node
 */
class NodeOperationAccessTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Cache utility calls container directly.
    $cacheContextsManager = $this->getMockBuilder(CacheContextsManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $cacheContextsManager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cacheContextsManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests revision operations.
   *
   * @param string $operation
   *   A revision operation.
   * @param array $hasPermissionMap
   *   A map of permissions, to whether they should be granted.
   * @param bool|null $assertAccess
   *   Whether the access is allowed or denied.
   * @param bool|null $isDefaultRevision
   *   Whether the node should be default revision, or NULL if not to expect it
   *   to be called.
   *
   * @dataProvider providerTestRevisionOperations
   */
  public function testRevisionOperations($operation, array $hasPermissionMap, $assertAccess, $isDefaultRevision = NULL) {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')
      ->willReturnMap($hasPermissionMap);

    $entityType = $this->createMock(EntityTypeInterface::class);
    $grants = $this->createMock(NodeGrantDatabaseStorageInterface::class);
    $grants->expects($this->any())
      ->method('access')
      ->willReturn(AccessResult::neutral());

    $language = $this->createMock(LanguageInterface::class);
    $language->expects($this->any())
      ->method('getId')
      ->willReturn('de');

    $nid = 333;
    /** @var \Drupal\node\NodeInterface|\PHPUnit\Framework\MockObject\MockObject $node */
    $node = $this->createMock(NodeInterface::class);
    $node->expects($this->any())
      ->method('language')
      ->willReturn($language);
    $node->expects($this->any())
      ->method('id')
      ->willReturn($nid);
    $node->expects($this->any())
      ->method('getCacheContexts')
      ->willReturn([]);
    $node->expects($this->any())
      ->method('getCacheTags')
      ->willReturn([]);
    $node->expects($this->any())
      ->method('getCacheMaxAge')
      ->willReturn(-1);
    $node->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn('node');

    if (isset($isDefaultRevision)) {
      $node->expects($this->atLeastOnce())
        ->method('isDefaultRevision')
        ->willReturn($isDefaultRevision);
    }

    $nodeStorage = $this->createMock(NodeStorageInterface::class);
    $nodeStorage->expects($this->any())
      ->method('load')
      ->with($nid)
      ->willReturn($node);
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('node')
      ->willReturn($nodeStorage);

    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->expects($this->any())
      ->method('invokeAll')
      ->willReturn([]);
    $accessControl = new NodeAccessControlHandler($entityType, $grants, $entityTypeManager);
    $accessControl->setModuleHandler($moduleHandler);

    $access = $accessControl->access($node, $operation, $account, FALSE);
    $this->assertEquals($assertAccess, $access);
  }

  /**
   * Data provider for revisionOperationsProvider.
   *
   * @return array
   *   Data for testing.
   */
  public function providerTestRevisionOperations() {
    $data = [];

    // Tests 'bypass node access' never works on revision operations.
    $data['bypass, view all revisions'] = [
      'view all revisions',
      [
        ['access content', TRUE],
        ['bypass node access', TRUE],
      ],
      FALSE,
    ];
    $data['bypass, view revision'] = [
      'view revision',
      [
        ['access content', TRUE],
        ['bypass node access', TRUE],
      ],
      FALSE,
    ];
    $data['bypass, revert'] = [
      'revert revision',
      [
        ['access content', TRUE],
        ['bypass node access', TRUE],
      ],
      FALSE,
    ];
    $data['bypass, delete revision'] = [
      'delete revision',
      [
        ['access content', TRUE],
        ['bypass node access', TRUE],
      ],
      FALSE,
    ];

    $data['view all revisions'] = [
      'view all revisions',
      [
        ['access content', TRUE],
        ['view all revisions', TRUE],
      ],
      TRUE,
    ];
    $data['view all revisions with view access'] = [
      'view all revisions',
      [
        ['access content', TRUE],
        ['view all revisions', TRUE],
        // Bypass for 'view' operation.
        ['bypass node access', TRUE],
      ],
      TRUE,
    ];

    $data['view revision, without view access'] = [
      'view revision',
      [
        ['access content', TRUE],
        ['view all revisions', TRUE],
      ],
      FALSE,
    ];

    $data['view revision, with view access'] = [
      'view revision',
      [
        ['access content', TRUE],
        ['view all revisions', TRUE],
        // Bypass for 'view' operation.
        ['bypass node access', TRUE],
      ],
      TRUE,
    ];

    // Cannot revert if no update access.
    $data['revert, without update access, non default'] = [
      'revert revision',
      [
        ['access content', TRUE],
        ['revert all revisions', TRUE],
      ],
      FALSE,
      FALSE,
    ];

    // Can revert if has update access.
    $data['revert, with update access, non default'] = [
      'revert revision',
      [
        ['access content', TRUE],
        ['revert all revisions', TRUE],
        // Bypass for 'update' operation.
        ['bypass node access', TRUE],
      ],
      TRUE,
      FALSE,
    ];

    // Can never revert default revision.
    $data['revert, with update access, default revision'] = [
      'revert revision',
      [
        ['access content', TRUE],
        ['revert all revisions', TRUE],
        // Bypass for 'update' operation.
        ['bypass node access', TRUE],
      ],
      FALSE,
      TRUE,
    ];

    // Cannot delete non default revision if no delete access.
    $data['delete revision, without delete access, non default'] = [
      'delete revision',
      [
        ['access content', TRUE],
        ['delete all revisions', TRUE],
      ],
      FALSE,
      FALSE,
    ];

    // Can delete non default revision if delete access.
    $data['delete revision, with delete access, non default'] = [
      'delete revision',
      [
        ['access content', TRUE],
        ['delete all revisions', TRUE],
        // Bypass for 'delete' operation.
        ['bypass node access', TRUE],
      ],
      TRUE,
      FALSE,
    ];

    return $data;
  }

}
