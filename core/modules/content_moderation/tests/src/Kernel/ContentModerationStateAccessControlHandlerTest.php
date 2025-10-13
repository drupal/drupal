<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\ContentModerationStateAccessControlHandler;
use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\content_moderation\ContentModerationStateAccessControlHandler.
 */
#[CoversClass(ContentModerationStateAccessControlHandler::class)]
#[Group('content_moderation')]
#[RunTestsInSeparateProcesses]
class ContentModerationStateAccessControlHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'workflows',
    'user',
  ];

  /**
   * The content_moderation_state access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessControlHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('user');
    $this->accessControlHandler = $this->container->get('entity_type.manager')->getAccessControlHandler('content_moderation_state');
  }

  /**
   * Tests handler.
   *
   * @legacy-covers ::checkAccess
   * @legacy-covers ::checkCreateAccess
   */
  public function testHandler(): void {
    $entity = ContentModerationState::create([]);
    $this->assertFalse($this->accessControlHandler->access($entity, 'view'));
    $this->assertFalse($this->accessControlHandler->access($entity, 'update'));
    $this->assertFalse($this->accessControlHandler->access($entity, 'delete'));
    $this->assertFalse($this->accessControlHandler->createAccess());
  }

}
