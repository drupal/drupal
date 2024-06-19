<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Unit;

use Drupal\content_moderation\Routing\ContentModerationRouteSubscriber;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\content_moderation\Routing\ContentModerationRouteSubscriber
 *
 * @group content_moderation
 */
class ContentModerationRouteSubscriberTest extends UnitTestCase {

  /**
   * The test content moderation route subscriber.
   *
   * @var \Drupal\content_moderation\Routing\ContentModerationRouteSubscriber
   */
  protected $routeSubscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $this->routeSubscriber = new ContentModerationRouteSubscriber($entity_type_manager);
    $this->setupEntityTypes();
  }

  /**
   * Creates the entity type manager mock returning entity type objects.
   */
  protected function setupEntityTypes() {
    $definition = $this->createMock(EntityTypeInterface::class);
    $definition->expects($this->any())
      ->method('getClass')
      ->willReturn(TestEntity::class);
    $definition->expects($this->any())
      ->method('isRevisionable')
      ->willReturn(FALSE);
    $revisionable_definition = $this->createMock(EntityTypeInterface::class);
    $revisionable_definition->expects($this->any())
      ->method('getClass')
      ->willReturn(TestEntity::class);
    $revisionable_definition->expects($this->any())
      ->method('isRevisionable')
      ->willReturn(TRUE);
    $entity_types = [
      'entity_test' => $definition,
      'entity_test_rev' => $revisionable_definition,
    ];

    $reflector = new \ReflectionProperty($this->routeSubscriber, 'moderatedEntityTypes');
    $reflector->setValue($this->routeSubscriber, $entity_types);
  }

  /**
   * Data provider for ::testSetLatestRevisionFlag.
   */
  public static function setLatestRevisionFlagTestCases() {
    return [
      'Entity parameter not on an entity form' => [
        [],
        [
          'entity_test' => [
            'type' => 'entity:entity_test_rev',
          ],
        ],
      ],
      'Entity parameter on an entity form' => [
        [
          '_entity_form' => 'entity_test_rev.edit',
        ],
        [
          'entity_test_rev' => [
            'type' => 'entity:entity_test_rev',
          ],
        ],
        [
          'entity_test_rev' => [
            'type' => 'entity:entity_test_rev',
            'load_latest_revision' => TRUE,
          ],
        ],
      ],
      'Entity form with no operation' => [
        [
          '_entity_form' => 'entity_test_rev',
        ],
        [
          'entity_test_rev' => [
            'type' => 'entity:entity_test_rev',
          ],
        ],
        [
          'entity_test_rev' => [
            'type' => 'entity:entity_test_rev',
            'load_latest_revision' => TRUE,
          ],
        ],
      ],
      'Non-moderated entity form' => [
        [
          '_entity_form' => 'entity_test_mulrev',
        ],
        [
          'entity_test_mulrev' => [
            'type' => 'entity:entity_test_mulrev',
          ],
        ],
      ],
      'Multiple entity parameters on an entity form' => [
        [
          '_entity_form' => 'entity_test_rev.edit',
        ],
        [
          'entity_test_rev' => [
            'type' => 'entity:entity_test_rev',
          ],
          'node' => [
            'type' => 'entity:node',
          ],
        ],
        [
          'entity_test_rev' => [
            'type' => 'entity:entity_test_rev',
            'load_latest_revision' => TRUE,
          ],
          'node' => [
            'type' => 'entity:node',
          ],
        ],
      ],
      'Overridden load_latest_revision flag does not change' => [
        [
          '_entity_form' => 'entity_test_rev.edit',
        ],
        [
          'entity_test_rev' => [
            'type' => 'entity:entity_test_rev',
            'load_latest_revision' => FALSE,
          ],
        ],
      ],
      'Non-revisionable entity type will not change' => [
        [
          '_entity_form' => 'entity_test.edit',
        ],
        [
          'entity_test' => [
            'type' => 'entity:entity_test',
          ],
        ],
        FALSE,
        FALSE,
      ],
      'Overridden load_latest_revision flag does not change with multiple parameters' => [
        [
          '_entity_form' => 'entity_test_rev.edit',
        ],
        [
          'entity_test_rev' => [
            'type' => 'entity:entity_test_rev',
          ],
          'node' => [
            'type' => 'entity:node',
            'load_latest_revision' => FALSE,
          ],
        ],
        [
          'entity_test_rev' => [
            'type' => 'entity:entity_test_rev',
            'load_latest_revision' => TRUE,
          ],
          'node' => [
            'type' => 'entity:node',
            'load_latest_revision' => FALSE,
          ],
        ],
      ],
      'Parameter without type is unchanged' => [
        [
          '_entity_form' => 'entity_test_rev.edit',
        ],
        [
          'entity_test_rev' => [
            'type' => 'entity:entity_test_rev',
          ],
          'unrelated_param' => [
            'foo' => 'bar',
          ],
        ],
        [
          'entity_test_rev' => [
            'type' => 'entity:entity_test_rev',
            'load_latest_revision' => TRUE,
          ],
          'unrelated_param' => [
            'foo' => 'bar',
          ],
        ],
      ],
    ];
  }

  /**
   * Tests that the "load_latest_revision" flag is handled correctly.
   *
   * @param array $defaults
   *   The route defaults.
   * @param array $parameters
   *   The route parameters.
   * @param array|bool $expected_parameters
   *   (optional) The expected route parameters. Defaults to FALSE.
   *
   * @covers ::setLatestRevisionFlag
   *
   * @dataProvider setLatestRevisionFlagTestCases
   */
  public function testSetLatestRevisionFlag($defaults, $parameters, $expected_parameters = FALSE): void {
    $route = new Route('/foo/{entity_test}', $defaults, [], [
      'parameters' => $parameters,
    ]);

    $route_collection = new RouteCollection();
    $route_collection->add('test', $route);
    $event = new RouteBuildEvent($route_collection);
    $this->routeSubscriber->onAlterRoutes($event);

    // If expected parameters have not been provided, assert they are unchanged.
    $this->assertEquals($expected_parameters ?: $parameters, $route->getOption('parameters'));
  }

}

/**
 * A concrete entity.
 */
class TestEntity extends EntityBase {
}
