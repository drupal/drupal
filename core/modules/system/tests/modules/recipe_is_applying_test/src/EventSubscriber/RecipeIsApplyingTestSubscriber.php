<?php

declare(strict_types=1);

namespace Drupal\recipe_is_applying_test\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Recipe\RecipeAppliedEvent;
use Drupal\Core\Recipe\RecipeRunner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Records the isApplying flag when recipe-provided configuration is saved.
 */
class RecipeIsApplyingTestSubscriber implements EventSubscriberInterface {

  public function __construct(private KeyValueFactoryInterface $keyValueFactory) {
  }

  /**
   * Records the isApplying flag when the recipe's configuration is saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    if ($event->getConfig()->getName() === 'recipe_is_applying_test.settings') {
      $this->keyValueFactory->get('recipe_is_applying_test')->set('config_save', RecipeRunner::isApplying());
    }
  }

  /**
   * Records the isApplying flag when the recipe's applied event is triggered.
   *
   * @param \Drupal\Core\Recipe\RecipeAppliedEvent $event
   *   The recipe applied event.
   */
  public function onRecipeApplied(RecipeAppliedEvent $event): void {
    if ($event->recipe->name === 'Recipe isApplying test') {
      $this->keyValueFactory->get('recipe_is_applying_test')->set('recipe_applied_event', RecipeRunner::isApplying());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ConfigEvents::SAVE => 'onConfigSave',
      RecipeAppliedEvent::class => 'onRecipeApplied',
    ];
  }

}
