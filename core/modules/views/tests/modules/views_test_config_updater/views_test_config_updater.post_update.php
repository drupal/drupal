<?php

/**
 * @file
 * Post update functions for Views Test Config Updater.
 */

declare(strict_types=1);

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewsConfigUpdater;

/**
 * Test post update to set deprecations disabled.
 */
function views_test_config_updater_post_update_set_deprecations_disabled(?array &$sandbox = NULL): void {
  /** @var \Drupal\views\ViewsConfigUpdater $viewsConfigUpdater */
  $viewsConfigUpdater = \Drupal::service(ViewsConfigUpdater::class);
  $viewsConfigUpdater->setDeprecationsEnabled(FALSE);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', static fn (ViewEntityInterface $view): bool => TRUE);
}
