<?php

declare(strict_types=1);

namespace Drupal\Tests\history\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\history\Hook\HistoryTokensHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for HistoryTokensHooks.
 */
#[CoversMethod(HistoryTokensHooks::class, 'tokenInfo')]
#[Group('history')]
class HistoryTokensHooksTest extends UnitTestCase {

  /**
   * Tests that tokenInfo() handles missing comment module gracefully.
   */
  public function testTokenInfoWithoutCommentModuleInstalled(): void {
    // Create a mock entity type that implements ContentEntityInterface.
    $entityType = $this->createMock(EntityTypeInterface::class);
    $entityType->expects($this->once())
      ->method('entityClassImplements')
      ->with(ContentEntityInterface::class)
      ->willReturn(TRUE);

    // Create a mock entity type manager that returns our entity type.
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn(['node' => $entityType]);

    // Create a container WITHOUT the comment module (service) installed.
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityTypeManager);
    \Drupal::setContainer($container);

    // Create the hooks class.
    $historyTokensHooks = new HistoryTokensHooks();

    $result = $historyTokensHooks->tokenInfo();

    $this->assertEquals(['tokens' => []], $result);
  }

}
