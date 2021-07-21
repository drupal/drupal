<?php

namespace Drupal\Tests\content_moderation\Unit\Plugin\Action;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\Plugin\Action\ModerationStateChange;
use Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\workflows\StateInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\WorkflowTypeInterface;

/**
 * @coversDefaultClass \Drupal\content_moderation\Plugin\Action\ModerationStateChange
 * @group content_moderation
 */
class ModerationStateChangeTest extends UnitTestCase {

  /**
   * The mocked node.
   *
   * @var \Drupal\node\NodeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $node;

  /**
   * The moderation info service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moderationInfo;

  /**
   * The moderation info service.
   *
   * @var \Drupal\workflows\WorkflowInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $workflow;

  /**
   * The cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheContextsManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The user storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $nodeStorage;

  /**
   * The language object.
   *
   * @var \Drupal\Core\Language\LanguageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $language;

  /**
   * The user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Moderation state transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $validator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cacheContextsManager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->cacheContextsManager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $this->cacheContextsManager);
    \Drupal::setContainer($container);

    $this->setupMocks();
  }

  /**
   * Tests the execute method.
   */
  public function testExecuteModerationStateChange() {
    $this->moderationInfo = $this->createMock(ModerationInformationInterface::class);

    $this->node->expects($this->once())
      ->method('save');

    $moderation_state = $this
      ->getMockBuilder(ModerationStateFieldItemList::class)
      ->disableOriginalConstructor()
      ->getMock();
    $moderation_state->expects($this->once())
      ->method('__set')
      ->with('value', 'foobar');
    $this->node->moderation_state = $moderation_state;

    $config = ['state' => 'foobar'];
    $plugin = $this->getModerationStateChangeMock($config, $this->moderationInfo, $this->node);
    $plugin->execute($this->node);
  }

  /**
   * Data provider for the the access method test.
   */
  public function accessModerationStateChangeDataProvider() {
    $this->setupMocks();
    $this->moderationInfo = $this->createMock(ModerationInformationInterface::class);

    $this->workflow = $this->createMock(WorkflowInterface::class);
    $this->workflow->expects($this->any())
      ->method('getCacheContexts')
      ->willReturn([]);
    $this->workflow->expects($this->any())
      ->method('getCacheTags')
      ->willReturn([]);
    $this->workflow->expects($this->any())
      ->method('getCacheMaxAge')
      ->willReturn(0);

    $moderation_info = clone $this->moderationInfo;

    // No object given.
    $data['no-object-given'] = [$moderation_info, NULL, FALSE];

    // Invalid object given.
    $moderation_info = clone $this->moderationInfo;

    $data['invalid-object-given'] = [$moderation_info, new \stdClass(), FALSE];

    // Object has no workflow.
    $moderation_info = clone $this->moderationInfo;
    $node = clone $this->node;

    $moderation_info->expects($this->once())
      ->method('getWorkflowForEntity')
      ->with($node)
      ->will($this->returnValue(NULL));

    $data['no-workflow'] = [$moderation_info, $node, FALSE];

    // Different workflow.
    $workflow = clone $this->workflow;

    $workflow->expects($this->once())
      ->method('id')
      ->willReturn('bar');

    $moderation_info = clone $this->moderationInfo;
    $node = clone $this->node;

    $moderation_info->expects($this->once())
      ->method('getWorkflowForEntity')
      ->with($node)
      ->will($this->returnValue($workflow));

    $data['different-workflow'] = [$moderation_info, $node, FALSE];

    // Same workflow but no node update access.
    $workflow = clone $this->workflow;

    $workflow->expects($this->once())
      ->method('id')
      ->willReturn('foo');

    $moderation_info = clone $this->moderationInfo;
    $node = clone $this->node;

    $moderation_info->expects($this->once())
      ->method('getWorkflowForEntity')
      ->with($node)
      ->will($this->returnValue($workflow));

    $node->moderation_state = (object) ['value' => 'foobar'];

    $forbidden_access = new AccessResultForbidden();
    $node->expects($this->once())
      ->method('access')
      ->with('update', NULL, TRUE)
      ->willReturn($forbidden_access);

    $workflow_type = $this->createMock(WorkflowTypeInterface::class);
    $state = $this->createMock(StateInterface::class);

    $workflow_type->expects($this->once())
      ->method('getState')
      ->with('foobar')
      ->willReturn($state);

    $state->expects($this->once())
      ->method('canTransitionTo')
      ->with('bar')
      ->willReturn(FALSE);

    $workflow->expects($this->once())
      ->method('getTypePlugin')
      ->willReturn($workflow_type);

    $data['no-update-access'] = [$moderation_info, $node, FALSE];

    // Same workflow with node update access and no valid transition.
    $workflow = clone $this->workflow;

    $workflow->expects($this->once())
      ->method('id')
      ->willReturn('foo');

    $moderation_info = clone $this->moderationInfo;
    $node = clone $this->node;

    $moderation_info->expects($this->once())
      ->method('getWorkflowForEntity')
      ->with($node)
      ->will($this->returnValue($workflow));

    $node->moderation_state = (object) ['value' => 'foobar'];

    $allowed_access = new AccessResultAllowed();
    $node->expects($this->once())
      ->method('access')
      ->with('update', NULL, TRUE)
      ->willReturn($allowed_access);

    $workflow_type = $this->createMock(WorkflowTypeInterface::class);
    $state = $this->createMock(StateInterface::class);

    $workflow_type->expects($this->once())
      ->method('getState')
      ->with('foobar')
      ->willReturn($state);

    $state->expects($this->once())
      ->method('canTransitionTo')
      ->with('bar')
      ->willReturn(FALSE);

    $workflow->expects($this->once())
      ->method('getTypePlugin')
      ->willReturn($workflow_type);

    $data['invalid-transition'] = [$moderation_info, $node, FALSE];

    // Same workflow with update access, with valid transition and no transition
    // access.
    $workflow = clone $this->workflow;
    $workflow->expects($this->exactly(2))
      ->method('id')
      ->willReturn('foo');

    $moderation_info = clone $this->moderationInfo;
    $node = clone $this->node;

    $moderation_info->expects($this->once())
      ->method('getWorkflowForEntity')
      ->with($node)
      ->will($this->returnValue($workflow));

    $node->moderation_state = (object) ['value' => 'foobar'];

    $account = $this->createMock(AccountInterface::class);

    $node->expects($this->once())
      ->method('access')
      ->with('update', $account, TRUE)
      ->willReturn($allowed_access);

    $workflow_type = $this->createMock(WorkflowTypeInterface::class);
    $state = $this->createMock(StateInterface::class);
    $toState = $this->createMock(StateInterface::class);

    $workflow_type->expects($this->at(0))
      ->method('getState')
      ->with('foobar')
      ->willReturn($state);

    $workflow_type->expects($this->at(1))
      ->method('getState')
      ->with('bar')
      ->willReturn($toState);

    $state->expects($this->once())
      ->method('canTransitionTo')
      ->with('bar')
      ->willReturn(TRUE);

    $validator = clone $this->validator;
    $validator->expects($this->once())
      ->method('isTransitionValid')
      ->with($workflow, $state, $toState, $account, $node)
      ->willReturn(FALSE);

    $workflow->expects($this->exactly(2))
      ->method('getTypePlugin')
      ->willReturn($workflow_type);

    $data['no-transition-access'] = [
      $moderation_info,
      $node,
      FALSE,
      $account,
      $validator,
    ];

    // Same workflow with no update access, with valid transition and transition
    // access.
    $workflow = clone $this->workflow;
    $workflow->expects($this->exactly(2))
      ->method('id')
      ->willReturn('foo');

    $moderation_info = clone $this->moderationInfo;
    $node = clone $this->node;

    $moderation_info->expects($this->once())
      ->method('getWorkflowForEntity')
      ->with($node)
      ->will($this->returnValue($workflow));

    $node->moderation_state = (object) ['value' => 'foobar'];

    $account = $this->createMock(AccountInterface::class);

    $node->expects($this->once())
      ->method('access')
      ->with('update', $account, TRUE)
      ->willReturn($forbidden_access);

    $workflow_type = $this->createMock(WorkflowTypeInterface::class);
    $state = $this->createMock(StateInterface::class);
    $toState = $this->createMock(StateInterface::class);

    $workflow_type->expects($this->at(0))
      ->method('getState')
      ->with('foobar')
      ->willReturn($state);

    $workflow_type->expects($this->at(1))
      ->method('getState')
      ->with('bar')
      ->willReturn($toState);

    $state->expects($this->once())
      ->method('canTransitionTo')
      ->with('bar')
      ->willReturn(TRUE);

    $validator = clone $this->validator;
    $validator->expects($this->once())
      ->method('isTransitionValid')
      ->with($workflow, $state, $toState, $account, $node)
      ->willReturn(TRUE);

    $workflow->expects($this->exactly(2))
      ->method('getTypePlugin')
      ->willReturn($workflow_type);

    $data['no-update-access-transition-access'] = [
      $moderation_info,
      $node,
      FALSE,
      $account,
      $validator,
    ];

    // Same workflow with update access, with valid transition and transition
    // access.
    $workflow = clone $this->workflow;
    $workflow->expects($this->exactly(2))
      ->method('id')
      ->willReturn('foo');

    $moderation_info = clone $this->moderationInfo;
    $node = clone $this->node;

    $moderation_info->expects($this->once())
      ->method('getWorkflowForEntity')
      ->with($node)
      ->will($this->returnValue($workflow));

    $node->moderation_state = (object) ['value' => 'foobar'];

    $account = $this->createMock(AccountInterface::class);

    $node->expects($this->once())
      ->method('access')
      ->with('update', $account, TRUE)
      ->willReturn($allowed_access);

    $workflow_type = $this->createMock(WorkflowTypeInterface::class);
    $state = $this->createMock(StateInterface::class);
    $toState = $this->createMock(StateInterface::class);

    $workflow_type->expects($this->at(0))
      ->method('getState')
      ->with('foobar')
      ->willReturn($state);

    $workflow_type->expects($this->at(1))
      ->method('getState')
      ->with('bar')
      ->willReturn($toState);

    $state->expects($this->once())
      ->method('canTransitionTo')
      ->with('bar')
      ->willReturn(TRUE);

    $validator = clone $this->validator;
    $validator->expects($this->once())
      ->method('isTransitionValid')
      ->with($workflow, $state, $toState, $account, $node)
      ->willReturn(TRUE);

    $workflow->expects($this->exactly(2))
      ->method('getTypePlugin')
      ->willReturn($workflow_type);

    $data['transition-access'] = [$moderation_info, $node, TRUE, $account, $validator];

    return $data;
  }

  /**
   * Tests the access method.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface|\PHPUnit_Framework_MockObject_MockObject $moderation_info
   *   The moderation info service.
   * @param \Drupal\node\NodeInterface|\PHPUnit_Framework_MockObject_MockObject|\StdClass|null $node
   *   The mocked node.
   * @param bool $result
   *   The access result.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface|null $validator
   *   (optional) A state transition validator mock with true and false
   *   rules set up. Defaults to NULL.
   *
   * @dataProvider accessModerationStateChangeDataProvider
   */
  public function testAccessModerationStateChange(ModerationInformationInterface $moderation_info, $node, $result, AccountInterface $account = NULL, StateTransitionValidationInterface $validator = NULL) {
    $config = ['workflow' => 'foo', 'state' => 'bar'];
    $plugin = $this->getModerationStateChangeMock($config, $moderation_info, $node, $validator);
    $this->assertEquals($result, $plugin->access($node, $account));
  }

  /**
   * Mock required objects.
   */
  protected function setupMocks() {
    $this->node = $this->getMockBuilder(NodeInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entity_type_id = $this->returnValue('node');
    $entity_id = $this->returnValue(1);
    $this->node->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn($entity_type_id);

    $this->node->expects($this->any())
      ->method('id')
      ->willReturn($entity_id);

    $this->language = $this->createMock(LanguageInterface::class);
    $this->language->expects($this->any())
      ->method('getId')
      ->willReturn($this->returnValue('und'));

    $this->node->expects($this->any())
      ->method('language')
      ->willReturn($this->language);

    $validations = $this->createMock(EntityConstraintViolationListInterface::class);
    $validations->expects($this->any())
      ->method('count')
      ->willReturn(0);

    $this->node->expects($this->any())
      ->method('validate')
      ->willReturn($validations);

    $this->nodeStorage = $this->createMock(NodeStorageInterface::class);
    $this->nodeStorage->expects($this->any())
      ->method('getLatestTranslationAffectedRevisionId')
      ->with($entity_id, $this->node->language()->getId())
      ->willReturn($this->node->id());

    $this->nodeStorage->expects($this->any())
      ->method('loadRevision')
      ->with($entity_id)
      ->willReturn($this->node);

    $this->nodeStorage->expects($this->any())
      ->method('createRevision')
      ->with($this->node)
      ->willReturn($this->node);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('node')
      ->willReturn($this->nodeStorage);

    $this->currentUser = $this->createMock(AccountInterface::class);
    $this->currentUser->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $this->validator = $this->createMock(StateTransitionValidationInterface::class);
  }

  /**
   * Mocks Moderation state change action.
   */
  protected function getModerationStateChangeMock($config, $moderation_info, $node, StateTransitionValidationInterface $validator = NULL) {
    $usedValidator = $this->validator;
    if ($validator != NULL) {
      $usedValidator = $validator;
    }
    $plugin = $this->getMockBuilder(ModerationStateChange::class)
      ->setConstructorArgs([$config, 'moderation_state_change', ['type' => 'node'], $moderation_info, $this->entityTypeManager, $this->currentUser, $usedValidator])
      ->setMethods(['loadLatestRevision'])
      ->getMock();

    $plugin->expects($this->any())
      ->method('loadLatestRevision')
      ->willReturn($node);

    return $plugin;
  }

}
