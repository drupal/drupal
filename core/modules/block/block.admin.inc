<?php

/**
 * @file
 * Admin page callbacks for the block module.
 */

use Drupal\block\Plugin\Core\Entity\Block;

/**
 * Page callback: Attaches CSS for the block region demo.
 *
 * @see block_menu()
 */
function block_admin_demo($theme = NULL) {
  drupal_add_css(drupal_get_path('module', 'block') . '/block.admin.css');
  return '';
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
  return drupal_container()->get('plugin.manager.entity')
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
  if ($theme_key == variable_get('theme_default', 'stark')) {
    $theme_title = t('!theme (default theme)', array('!theme' => $theme_title));
  }
  elseif ($admin_theme && $theme_key == $admin_theme) {
    $theme_title = t('!theme (administration theme)', array('!theme' => $theme_title));
  }

  // Get the block subject for the page title.
  drupal_set_title(t("Configure %label block in %theme", array('%label' => $entity->label(), '%theme' => $theme_title)), PASS_THROUGH);

  return entity_get_form($entity);
}

/**
 * Form constructor for the block instance deletion form.
 *
 * @param \Drupal\block\Plugin\Core\Entity\Block $entity
 *   The block instance.
 *
 * @see block_menu()
 * @see block_admin_block_delete_submit()
 */
function block_admin_block_delete(array $form, array &$form_state, Block $entity) {
  $form['id'] = array('#type' => 'value', '#value' => $entity->id());

  return confirm_form($form, t('Are you sure you want to delete the block %name?', array('%name' => $entity->label())), 'admin/structure/block', '', t('Delete'), t('Cancel'));
}

/**
 * Form submission handler for block_admin_block_delete().
 *
 * @see block_admin_block_delete()
 */
function block_admin_block_delete_submit($form, &$form_state) {
  $entity = entity_load('block', $form_state['values']['id']);
  drupal_set_message(t('The block %name has been removed.', array('%name' => $entity->label())));
  $form_state['redirect'] = 'admin/structure/block/list/block_plugin_ui:' . $entity->get('theme');
  $entity->delete();
}

/**
 * Processes variables for block-admin-display-form.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $form
 *
 * @see block-admin-display.tpl.php
 * @see theme_block_admin_display()
 */
function template_preprocess_block_admin_display_form(&$variables) {
  $variables['block_regions'] = $variables['form']['block_regions']['#value'];
  if (isset($variables['block_regions'][BLOCK_REGION_NONE])) {
    $variables['block_regions'][BLOCK_REGION_NONE] = t('Disabled');
  }

  foreach ($variables['block_regions'] as $key => $value) {
    // Initialize an empty array for the region.
    $variables['block_listing'][$key] = array();
  }

  // Initialize disabled blocks array.
  $variables['block_listing'][BLOCK_REGION_NONE] = array();

  // Add each block in the form to the appropriate place in the block listing.
  foreach (element_children($variables['form']['blocks']) as $i) {
    $block = &$variables['form']['blocks'][$i];

    // Fetch the region for the current block.
    $region = (isset($block['region']['#default_value']) ? $block['region']['#default_value'] : BLOCK_REGION_NONE);

    // Set special classes needed for table drag and drop.
    $block['region']['#attributes']['class'] = array('block-region-select', 'block-region-' . $region);
    $block['weight']['#attributes']['class'] = array('block-weight', 'block-weight-' . $region);

    $variables['block_listing'][$region][$i] = new stdClass();
    $variables['block_listing'][$region][$i]->row_class = !empty($block['#attributes']['class']) ? implode(' ', $block['#attributes']['class']) : '';
    $variables['block_listing'][$region][$i]->block_modified = !empty($block['#attributes']['class']) && in_array('block-modified', $block['#attributes']['class']);
    $variables['block_listing'][$region][$i]->block_title = drupal_render($block['info']);
    $variables['block_listing'][$region][$i]->region_select = drupal_render($block['region']) . drupal_render($block['theme']);
    $variables['block_listing'][$region][$i]->weight_select = drupal_render($block['weight']);
    $variables['block_listing'][$region][$i]->operations = drupal_render($block['operations']);
    $variables['block_listing'][$region][$i]->printed = FALSE;
  }

  $variables['form_submit'] = drupal_render_children($variables['form']);
}
