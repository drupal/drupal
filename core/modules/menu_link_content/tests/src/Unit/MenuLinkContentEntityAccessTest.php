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

/**
 * Tests menu link content entity access.
 *
 * @coversDefaultClass \Drupal\menu_link_content\MenuLinkContentAccessControlHandler
 * @group menu_link_content
 */
class MenuLinkContentEntityAccessTest extends UnitTestCase {

  /**
   * Tests an operation not implemented by the access control handler.
   *
   * @covers ::checkAccess
   */
  public function testUnrecognizedOperation(): void {
    $entityType = $this->createMock(EntityTypeInterface::class);
    $accessManager = $this->createMock(AccessManagerInterface::class);
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->expects($this->any())
      ->method('invokeAll')
      ->willReturn([]);

    $language = $this->createMock(LanguageInterface::class);
    $language->expects($this->any())
      ->method('getId')
      ->willReturn('de');

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->any())
      ->method('language')
      ->willReturn($language);

    $account = $this->createMock(AccountInterface::class);

    $accessControl = new MenuLinkContentAccessControlHandler($entityType, $accessManager);
    $accessControl->setModuleHandler($moduleHandler);
    $access = $accessControl->access($entity, 'not-an-op', $account, TRUE);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
  }

}
