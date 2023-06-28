<?php

/**
 * @file
 * Post update functions for the Help module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\search\Entity\SearchPage;
use Drupal\user\RoleInterface;

/**
 * Install or update config for help topics if the search module installed.
 */
function help_post_update_help_topics_search() {
  $module_handler = \Drupal::moduleHandler();
  if (!$module_handler->moduleExists('search')) {
    // No dependencies to update or install.
    return;
  }
  if ($module_handler->moduleExists('help_topics')) {
    if ($page = SearchPage::load('help_search')) {
      // Resave to update module dependency.
      $page->save();
    }
  }
  else {
    $factory = \Drupal::configFactory();
    // Install optional config for the search page.
    $config = $factory->getEditable('search.page.help_search');
    $config->setData([
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'module' => [
          'help',
        ],
      ],
      'id' => 'help_search',
      'label' => 'Help',
      'path' => 'help',
      'weight' => 0,
      'plugin' => 'help_search',
      'configuration' => [],
    ])->save(TRUE);
    if (\Drupal::service('theme_handler')->themeExists('claro') && $factory->get('block.block.claro_help_search')->isNew()) {
      // Optional block only if it's not created manually earlier.
      $config = $factory->getEditable('block.block.claro_help_search');
      $config->setData([
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [
          'module' => [
            'search',
            'system',
          ],
          'theme' => [
            'claro',
          ],
          'enforced' => [
            'config' => [
              'search.page.help_search',
            ],
          ],
        ],
        'id' => 'claro_help_search',
        'theme' => 'claro',
        'region' => 'help',
        'weight' => -4,
        'provider' => NULL,
        'plugin' => 'search_form_block',
        'settings' => [
          'id' => 'search_form_block',
          'label' => 'Search help',
          'label_display' => 'visible',
          'provider' => 'search',
          'page_id' => 'help_search',
        ],
        'visibility' => [
          'request_path' => [
            'id' => 'request_path',
            'negate' => FALSE,
            'context_mapping' => [],
            'pages' => '/admin/help',
          ],
        ],
      ])->save(TRUE);
    }
  }
}

/**
 * Uninstall the help_topics module if installed.
 */
function help_post_update_help_topics_uninstall() {
  if (\Drupal::moduleHandler()->moduleExists('help_topics')) {
    \Drupal::service('module_installer')->uninstall(['help_topics'], FALSE);
  }
}

/**
 * Grant all admin roles the 'access help pages' permission.
 */
function help_post_update_add_permissions_to_roles(?array &$sandbox = []): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'user_role', function (RoleInterface $role): bool {
    if ($role->isAdmin() || !$role->hasPermission('access administration pages')) {
      return FALSE;
    }
    $role->grantPermission('access help pages');
    return TRUE;
  });
}
