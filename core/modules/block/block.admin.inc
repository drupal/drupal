<?php

/**
 * @file
 * Admin page callbacks for the block module.
 */

use Drupal\block\Plugin\Core\Entity\Block;
use Drupal\Core\Template\Attribute;

/**
 * Page callback: Attaches CSS for the block region demo.
 *
 * @see block_menu()
 */
function block_admin_demo($theme = NULL) {
  return array(
    '#attached' => array(
      'css' => array(drupal_get_path('module', 'block') . '/css/block.admin.css'),
    ),
  );
}

/**
 * Page callback: Shows the block administration page.
 *
 * @param string $theme
 *   The theme to display the administration page for.
 *
 * @return array
 *   A render array for a page containing a list of blocks.
 *
 * @see block_menu()
 */
function block_admin_display($theme) {
  return Drupal::entityManager()
    ->getListController('block')
    ->render($theme);
}

/**
 * Page callback: Build the block instance add form.
 *
 * @param string $plugin_id
 *   The plugin ID for the block instance.
 * @param string $theme
 *   The name of the theme for the block instance.
 *
 * @return array
 *   The block instance edit form.
 */
function block_admin_add($plugin_id, $theme) {
  $entity = entity_create('block', array(
    'plugin' => $plugin_id,
    'theme' => $theme,
  ));
  return entity_get_form($entity);
}

/**
 * Page callback: Build the block instance edit form.
 *
 * @param \Drupal\block\Plugin\Core\Entity\Block $entity
 *   The block instance.
 *
 * @return array
 *   The block instance edit form.
 */
function block_admin_edit(Block $entity) {
  // Get the theme for the page title.
  $admin_theme = config('system.theme')->get('admin');
  $themes = list_themes();
  $theme_key = $entity->get('theme');
  $theme = $themes[$theme_key];
  // Use meaningful titles for the main site and administrative themes.
  $theme_title = $theme->info['name'];
  if ($theme_key == config('system.theme')->get('default')) {
    $theme_title = t('!theme (default theme)', array('!theme' => $theme_title));
  }
  elseif ($admin_theme && $theme_key == $admin_theme) {
    $theme_title = t('!theme (administration theme)', array('!theme' => $theme_title));
  }

  // Get the block label for the page title.
  drupal_set_title(t("Configure %label block in %theme", array('%label' => $entity->label(), '%theme' => $theme_title)), PASS_THROUGH);

  return entity_get_form($entity);
}
