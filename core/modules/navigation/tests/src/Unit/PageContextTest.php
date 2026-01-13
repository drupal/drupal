<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Unit;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\navigation\EntityRouteHelper;
use Drupal\navigation\Plugin\TopBarItem\PageContext;
use Drupal\Tests\UnitTestCase;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\WorkflowTypeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the PageContext Top Bar item build output.
 */
#[CoversClass(PageContext::class)]
#[Group('navigation')]
class PageContextTest extends UnitTestCase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Tests the build method when no entity is present on the route.
   */
  public function testBuildWhenNoEntityOnRoute(): void {
    $route_helper = $this->prophesize(EntityRouteHelper::class);
    $route_helper->getContentEntityFromRoute()->willReturn(NULL);
    $entity_repository = $this->prophesize(EntityRepositoryInterface::class)->reveal();
    $moderation_information = $this->prophesize(ModerationInformationInterface::class)->reveal();

    $plugin = new PageContext([], 'page_context', [], $route_helper->reveal(), $entity_repository, $moderation_information);
    $build = $plugin->build();

    $this->assertSame([
      '#cache' => [
        'contexts' => ['route'],
      ],
    ], $build);
  }

  /**
   * Tests the build of an entity label within the page context plugin.
   *
   * @param mixed $label_value
   *   The return value of the entity label method. Can be a string,
   *   render array, stringable object, or an invalid value.
   * @param string|array|null $expected
   *   The expected parsed label value for the page context. If the label is
   *   invalid, the value should be NULL.
   */
  #[DataProvider('entityLabelProvider')]
  public function testBuildEntityLabel(mixed $label_value, string|array|null $expected): void {
    // Route returns an entity with different label return types.
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->label()->willReturn($label_value);

    $route_helper = $this->prophesize(EntityRouteHelper::class);
    $route_helper->getContentEntityFromRoute()->willReturn($entity->reveal());

    $entity_repository = $this->prophesize(EntityRepositoryInterface::class)->reveal();
    $moderation_information = $this->prophesize(ModerationInformationInterface::class)->reveal();

    $plugin = new PageContext([], 'page_context', [], $route_helper->reveal(), $entity_repository, $moderation_information);
    $build = $plugin->build();

    $expected_base = [
      '#cache' => [
        'contexts' => ['route'],
      ],
    ];

    if ($expected === NULL) {
      // Invalid label → no components added.
      $this->assertSame($expected_base, $build);
      return;
    }

    // Title component should be present as the first item.
    $this->assertArrayHasKey(0, $build);
    $this->assertSame('component', $build[0]['#type']);
    $this->assertSame('navigation:title', $build[0]['#component']);
    $this->assertSame('database', $build[0]['#props']['icon']);
    $this->assertSame($expected, $build[0]['#slots']['content']);
  }

  /**
   * Data provider for entity label scenarios.
   *
   * @return array
   *   [label_return_value, expected]
   */
  public static function entityLabelProvider(): array {
    $stringable = new class () implements \Stringable {

      /**
       * Dummy string method.
       *
       * @return string
       *   The dummy entity label.
       */
      public function __toString(): string {
        return 'Stringable Label';
      }

    };
    return [
      'string' => ['My Label', 'My Label'],
      'stringable' => [$stringable, 'Stringable Label'],
      'render_array' => [['#markup' => 'Rendered Label'], NULL],
      'invalid' => [new \stdClass(), NULL],
    ];
  }

  /**
   * Tests the status badge for published and unpublished entities.
   */
  public function testBuildStatusBadge(): void {
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->willImplement(EntityPublishedInterface::class);
    $entity->isPublished()->willReturn(TRUE);
    $entity->label()->willReturn('Published Title');

    $route_helper = $this->prophesize(EntityRouteHelper::class);
    $route_helper->getContentEntityFromRoute()->willReturn($entity->reveal());
    $entity_repository = $this->prophesize(EntityRepositoryInterface::class)->reveal();

    $published_plugin = new PageContext([], 'page_context', [], $route_helper->reveal(), $entity_repository, NULL);
    $build = $published_plugin->build();

    $this->assertSame('Published Title', $build[0]['#slots']['content']);
    $this->assertSame('navigation:badge', $build[1]['#component']);
    $this->assertSame('Published', $build[1]['#slots']['label']);
    $this->assertSame('success', $build[1]['#props']['status']);

    // Now assert the Unpublished path.
    $entity->isPublished()->willReturn(FALSE);
    $entity->label()->willReturn('Unpublished Title');
    $route_helper->getContentEntityFromRoute()->willReturn($entity->reveal());

    $plugin = new PageContext([], 'page_context', [], $route_helper->reveal(), $entity_repository, NULL);
    $build = $plugin->build();
    $this->assertSame('Unpublished Title', $build[0]['#slots']['content']);
    $this->assertSame('Unpublished', $build[1]['#slots']['label']);
    $this->assertSame('info', $build[1]['#props']['status']);
  }

  /**
   * Tests content moderation build output with no pending revisions.
   */
  public function testBuildContentModerationNoPending(): void {
    // Mock moderated content entity with state 'draft'.
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->get('moderation_state')->willReturn((object) ['value' => 'draft']);
    $entity->isDefaultRevision()->willReturn(TRUE);
    $entity->getEntityTypeId()->willReturn('node');
    $entity->id()->willReturn('1');
    $entity->label()->willReturn('Example Title');

    // Workflow chain: workflow -> type plugin -> state('draft')->label() => 'Draft'.
    $state = $this->prophesize(StateInterface::class);
    $state->label()->willReturn('Draft');

    $type = $this->prophesize(WorkflowTypeInterface::class);
    $type->getState('draft')->willReturn($state->reveal());

    $workflow = $this->prophesize(WorkflowInterface::class);
    $workflow->getTypePlugin()->willReturn($type->reveal());

    $moderation = $this->prophesize(ModerationInformationInterface::class);
    $moderation->isModeratedEntity($entity->reveal())->willReturn(TRUE);
    $moderation->getWorkflowForEntity($entity->reveal())->willReturn($workflow->reveal());
    $moderation->hasPendingRevision($entity->reveal())->willReturn(FALSE);

    $route_helper = $this->prophesize(EntityRouteHelper::class);
    $route_helper->getContentEntityFromRoute()->willReturn($entity->reveal());
    $entity_repository = $this->prophesize(EntityRepositoryInterface::class)->reveal();

    $plugin = new PageContext([], 'page_context', [], $route_helper->reveal(), $entity_repository, $moderation->reveal());
    $build = $plugin->build();

    // Title component.
    $this->assertSame('Example Title', $build[0]['#slots']['content']);
    // Badge component with label Draft, default status info (not published interface).
    $this->assertSame('navigation:badge', $build[1]['#component']);
    $this->assertSame('Draft', $build[1]['#slots']['label']);
    $this->assertSame('info', $build[1]['#props']['status']);
  }

  /**
   * Tests the content moderation build when is active with a pending entity.
   */
  public function testBuildContentModerationWithPendingActive(): void {
    // Entity current state is 'draft', active is 'published'.
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->get('moderation_state')->willReturn((object) ['value' => 'draft']);
    $entity->isDefaultRevision()->willReturn(TRUE);
    $entity->getEntityTypeId()->willReturn('node');
    $entity->id()->willReturn('1');
    $entity->label()->willReturn('Example Title');

    $active = $this->prophesize(ContentEntityInterface::class);
    $active->get('moderation_state')->willReturn((object) ['value' => 'published']);

    // State objects and labels.
    $draft_state = $this->prophesize(StateInterface::class);
    $draft_state->label()->willReturn('Draft');
    $published_state = $this->prophesize(StateInterface::class);
    $published_state->label()->willReturn('Published');

    $type = $this->prophesize(WorkflowTypeInterface::class);
    $type->getState('draft')->willReturn($draft_state->reveal());
    $type->getState('published')->willReturn($published_state->reveal());

    $workflow = $this->prophesize(WorkflowInterface::class);
    $workflow->getTypePlugin()->willReturn($type->reveal());

    $moderation = $this->prophesize(ModerationInformationInterface::class);
    $moderation->isModeratedEntity($entity->reveal())->willReturn(TRUE);
    $moderation->getWorkflowForEntity($entity->reveal())->willReturn($workflow->reveal());
    $moderation->getWorkflowForEntity($active->reveal())->willReturn($workflow->reveal());
    $moderation->hasPendingRevision($entity->reveal())->willReturn(TRUE);

    $entity_repository = $this->prophesize(EntityRepositoryInterface::class);
    $entity_repository->getActive('node', '1')->willReturn($active->reveal());

    $route_helper = $this->prophesize(EntityRouteHelper::class);
    $route_helper->getContentEntityFromRoute()->willReturn($entity->reveal());

    $plugin = new PageContext([], 'page_context', [], $route_helper->reveal(), $entity_repository->reveal(), $moderation->reveal());
    $build = $plugin->build();

    $this->assertSame('Example Title', $build[0]['#slots']['content']);
    $this->assertSame('navigation:badge', $build[1]['#component']);
    $this->assertSame('Draft (Published available)', $build[1]['#slots']['label']);
    // Status still defaults to info as entity might not be EntityPublishedInterface here.
    $this->assertSame('info', $build[1]['#props']['status']);
  }

  /**
   * Tests the behavior of a plugin with no valid badge present.
   */
  public function testNoValidBadge(): void {
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->label()->willReturn('Simple Title');

    $route_helper = $this->prophesize(EntityRouteHelper::class);
    $route_helper->getContentEntityFromRoute()->willReturn($entity->reveal());
    $entity_repository = $this->prophesize(EntityRepositoryInterface::class)->reveal();

    $plugin = new PageContext([], 'page_context', [], $route_helper->reveal(), $entity_repository, NULL);
    $build = $plugin->build();

    $this->assertSame('Simple Title', $build[0]['#slots']['content']);
    // Only one component (title) should be present.
    $this->assertArrayNotHasKey(1, $build);
  }

}
