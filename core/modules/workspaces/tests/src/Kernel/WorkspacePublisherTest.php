<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Component\Datetime\Time;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces\Form\WorkspacePublishForm;
use Drupal\workspaces\WorkspaceOperationFactory;
use Drupal\workspaces\WorkspacePublisher;
use Drupal\workspaces\WorkspacePublisherInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;

/**
 * Tests workspace publishing.
 */
#[CoversClass(WorkspacePublisher::class)]
#[Group('workspaces')]
#[RunTestsInSeparateProcesses]
class WorkspacePublisherTest extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'workspaces',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('workspace');

    $this->installSchema('node', ['node_access']);
    $this->installSchema('workspaces', ['workspace_association', 'workspace_association_revision']);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $container->getDefinition('datetime.time')
      ->setClass(TestTime::class);
  }

  /**
   * Tests that publishing a workspace updates the changed time of its entities.
   */
  public function testPublishingChangedTime(): void {
    // Create an entity in Live.
    $entity = $this->createNode(['status' => TRUE]);

    $initial_request_time = \Drupal::time()->getRequestTime();
    $this->assertEquals($initial_request_time, $entity->getChangedTime());

    // Create a new workspace, activate it, and make some changes to the entity.
    $workspace = Workspace::create(['id' => 'test_changed', 'label' => 'Test changed']);
    $workspace->save();
    $this->switchToWorkspace('test_changed');

    // Simulate passing time.
    TestTime::$offset = 1;

    $entity = $this->entityTypeManager->getStorage('node')->loadUnchanged($entity->id());
    $entity->title = $this->randomString();
    $entity->save();

    $this->assertEquals($initial_request_time + 1, $entity->getChangedTime());

    // Publish the workspace and check that the changed time has been updated.
    TestTime::$offset = 2;
    $workspace->publish();

    $entity = $this->entityTypeManager->getStorage('node')->loadUnchanged($entity->id());
    $this->assertEquals($initial_request_time + 2, $entity->getChangedTime());
  }

  /**
   * Tests submit form with exception.
   *
   * @legacy-covers \Drupal\workspaces\Form\WorkspacePublishForm::submitForm
   */
  public function testSubmitFormWithException(): void {
    /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = \Drupal::service('messenger');

    $workspaceOperationFactory = $this->createMock(WorkspaceOperationFactory::class);
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    /** @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory */
    $loggerFactory = \Drupal::service('logger.factory');
    $loggerFactory->addLogger($logger);

    $workspace = $this->createMock(Workspace::class);
    $workspacePublisher = $this->createMock(WorkspacePublisherInterface::class);

    $workspace
      ->expects($this->any())
      ->method('label');

    $workspace
      ->expects($this->once())
      ->method('publish')
      ->willThrowException(new \Exception('Unexpected error'));

    $workspaceOperationFactory
      ->expects($this->once())
      ->method('getPublisher')
      ->willReturn($workspacePublisher);

    $workspacePublisher
      ->expects($this->once())
      ->method('getTargetLabel');

    $publishForm = new WorkspacePublishForm(
      $workspaceOperationFactory,
      $entityTypeManager
    );

    $form = [];
    $formState = new FormState();

    $publishForm->buildForm($form, $formState, $workspace);

    $logger
      ->expects($this->once())
      ->method('log')
      ->with(RfcLogLevel::ERROR, 'Unexpected error');

    $publishForm->submitForm($form, $formState);

    $messages = $messenger->messagesByType(MessengerInterface::TYPE_ERROR);
    $this->assertCount(1, $messages);
    $this->assertEquals('Publication failed. All errors have been logged.', $messages[0]);
  }

}

/**
 * A test-only implementation of the time service.
 */
class TestTime extends Time {

  /**
   * An offset to add to the request time.
   */
  public static int $offset = 0;

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    return parent::getRequestTime() + static::$offset;
  }

}
