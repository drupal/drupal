<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeAppliedEvent;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @group Recipe
 */
class RecipeEventsTest extends KernelTestBase implements EventSubscriberInterface {

  /**
   * The human-readable names of the recipes that have been applied.
   *
   * @var string[]
   */
  private array $recipesApplied = [];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RecipeAppliedEvent::class => 'onRecipeApply',
    ];
  }

  /**
   * Handles a recipe apply event for testing.
   */
  public function onRecipeApply(RecipeAppliedEvent $event): void {
    $this->recipesApplied[] = $event->recipe->name;
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    // Every time the container is rebuilt, ensure this object is subscribing to
    // events.
    $container->getDefinition('event_dispatcher')
      ->addMethodCall('addSubscriber', [$this]);
  }

  /**
   * Tests the recipe applied event.
   */
  public function testRecipeAppliedEvent(): void {
    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/recipe_include');
    RecipeRunner::processRecipe($recipe);

    $this->assertSame(['Install node with config', 'Recipe include'], $this->recipesApplied);
  }

}
