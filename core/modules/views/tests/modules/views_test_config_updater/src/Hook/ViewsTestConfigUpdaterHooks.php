<?php

declare(strict_types=1);

namespace Drupal\views_test_config_updater\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewsConfigUpdater;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hooks for the views_test_config_updater module.
 */
class ViewsTestConfigUpdaterHooks {

  public function __construct(
    protected readonly ViewsConfigUpdater $viewsConfigUpdater,
    #[Autowire(service: 'keyvalue')]
    protected readonly KeyValueFactoryInterface $keyValueFactory,
  ) {

  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('view_presave')]
  public function viewPresave(ViewEntityInterface $view): void {
    $this->keyValueFactory->get('views_test_config_updater')->set('deprecations_enabled', $this->viewsConfigUpdater->areDeprecationsEnabled());
  }

}
