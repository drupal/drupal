<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity\Controller;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests EntityController.
 */
#[CoversClass(EntityController::class)]
#[Group('Entity')]
class EntityControllerTest extends UnitTestCase {

  /**
   * Tests edit and delete title callbacks.
   *
   * @param string $callback
   *   The controller title callback name.
   * @param string|null $entity_label
   *   The label of the entity being displayed by the controller.
   * @param string $expected_title
   *   The expected title return value.
   */
  #[TestWith(['deleteTitle', 'example', 'Delete <em class="placeholder">example</em>'])]
  #[TestWith(['deleteTitle', NULL, 'Delete'])]
  #[TestWith(['editTitle', 'example', 'Edit <em class="placeholder">example</em>'])]
  #[TestWith(['editTitle', NULL, 'Edit'])]
  public function testDeleteEditTitleCallbacks(
    string $callback,
    ?string $entity_label,
    string $expected_title,
  ): void {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $route_match = $this->createStub(RouteMatchInterface::class);
    $entity = $this->createStub(EntityInterface::class);
    $entity->method('label')
      ->willReturn($entity_label);
    $controller = $this->createPartialMock(EntityController::class, ['doGetEntity']);
    $controller->expects($this->once())
      ->method('doGetEntity')
      ->willReturn($entity);
    $this->assertEquals($expected_title, (string) $controller->$callback($route_match, $entity));
  }

}
