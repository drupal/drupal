<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after a recipe has been applied.
 *
 * Subscribers to this event should avoid modifying config or content, because
 * it is very likely that the recipe was applied as part of a chain of recipes,
 * so config and content are probably about to change again. This event is best
 * used for tasks like notifications, logging or updating a value in state.
 */
final class RecipeAppliedEvent extends Event {

  /**
   * Constructs a RecipeAppliedEvent object.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   The recipe that was applied.
   */
  public function __construct(public readonly Recipe $recipe) {
  }

}
