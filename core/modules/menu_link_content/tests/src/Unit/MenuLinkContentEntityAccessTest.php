<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Unit;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\menu_link_content\MenuLinkContentAccessControlHandler;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests menu link content entity access.
 */
#[CoversClass(MenuLinkContentAccessControlHandler::class)]
#[Group('menu_link_content')]
class MenuLinkContentEntityAccessTest extends UnitTestCase {

  /**
   * Tests an operation not implemented by the access control handler.
   *
   * @legacy-covers ::checkAccess
   */
  public function testUnrecognizedOperation(): void {
    $entityType = $this->createStub(EntityTypeInterface::class);
    $accessManager = $this->createStub(AccessManagerInterface::class);
    $moduleHandler = $this->createStub(ModuleHandlerInterface::class);
    $moduleHandler
      ->method('invokeAll')
      ->willReturn([]);

    $language = $this->createStub(LanguageInterface::class);
    $language
      ->method('getId')
      ->willReturn('de');

    $entity = $this->createStub(ContentEntityInterface::class);
    $entity
      ->method('language')
      ->willReturn($language);

    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn(42);
    $accessControl = new MenuLinkContentAccessControlHandler($entityType, $accessManager);
    $accessControl->setModuleHandler($moduleHandler);
    $access = $accessControl->access($entity, 'not-an-op', $account, TRUE);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
  }

}
