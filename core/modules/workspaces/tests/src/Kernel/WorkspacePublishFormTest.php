<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces\Form\WorkspacePublishForm;
use Drupal\workspaces\WorkspaceOperationFactory;
use Drupal\workspaces\WorkspacePublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\workspaces\Form\WorkspacePublishForm
 * @group workspaces
 */
class WorkspacePublishFormTest extends KernelTestBase {

  /**
   * @covers ::submitForm
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
