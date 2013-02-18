<?php

/**
 * @file
 * Provides page callbacks for custom blocks.
 */

use Drupal\custom_block\Plugin\Core\Entity\CustomBlockType;
use Drupal\custom_block\Plugin\Core\Entity\CustomBlock;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Page callback: Displays add custom block links for available types.
 *
 * @return array
 *   A render array for a list of the custom block types that can be added or
 *   if there is only one custom block type defined for the site, the function
 *   returns the custom block add page for that custom block type.
 *
 * @see custom_block_menu()
 */
function custom_block_add_page() {
  $options = array();
  $request = drupal_container()->get('request');
  if (($theme = $request->attributes->get('theme')) && in_array($theme, array_keys(list_themes()))) {
    // We have navigated to this page from the block library and will keep track
    // of the theme for redirecting the user to the configuration page for the
    // newly created block in the given theme.
    $options = array(
      'query' => array('theme' => $theme)
    );
  }
  $types = entity_load_multiple('custom_block_type');
  if ($types && count($types) == 1) {
    $type = reset($types);
    return custom_block_add($type);
  }

  return array('#theme' => 'custom_block_add_list', '#content' => $types);
}

/**
 * Returns HTML for a list of available custom block types for block creation.
 *
 * @param $variables
 *   An associative array containing:
 *   - content: An array of block types.
 *
 * @see custom_block_add_page()
 *
 * @ingroup themeable
 */
function theme_custom_block_add_list($variables) {
  $content = $variables['content'];
  $output = '';

  if ($content) {
    $output = '<dl class="node-type-list">';
    foreach ($content as $type) {
      $output .= '<dt>' . l($type->label(), 'block/add/' . $type->id()) . '</dt>';
      $output .= '<dd>' . filter_xss_admin($type->description) . '</dd>';
    }
    $output .= '</dl>';
  }
  return $output;
}


/**
 * Page callback: Presents the custom block creation form.
 *
 * @param Drupal\custom_block\Plugin\Core\Entity\CustomBlockType $block_type
 *   The custom block type to add.
 *
 * @return array
 *   A form array as expected by drupal_render().
 *
 * @see custom_block_menu()
 */
function custom_block_add(CustomBlockType $block_type) {
  drupal_set_title(t('Add %type custom block', array(
    '%type' => $block_type->label()
  )), PASS_THROUGH);
  $block = entity_create('custom_block', array(
    'type' => $block_type->id()
  ));
  $options = array();
  $request = drupal_container()->get('request');
  if (($theme = $request->attributes->get('theme')) && in_array($theme, array_keys(list_themes()))) {
    // We have navigated to this page from the block library and will keep track
    // of the theme for redirecting the user to the configuration page for the
    // newly created block in the given theme.
    $block->setTheme($theme);
  }
  return entity_get_form($block);
}

/**
 * Page callback: Presents the custom block edit form.
 *
 * @param Drupal\custom_block\Plugin\Core\Entity\CustomBlock $block
 *   The custom block to edit.
 *
 * @return array
 *   A form array as expected by drupal_render().
 *
 * @see custom_block_menu()
 */
function custom_block_edit(CustomBlock $block) {
  drupal_set_title(t('Edit custom block %label', array('%label' => $block->label())), PASS_THROUGH);
  return entity_get_form($block);
}

/**
 * Page callback: Form constructor for the custom block deletion form.
 *
 * @param Drupal\custom_block\Plugin\Core\Entity\CustomBlock $block
 *   The custom block to be deleted.
 *
 * @see custom_block_menu()
 * @see custom_block_delete_form_submit()
 *
 * @ingroup forms
 */
function custom_block_delete_form($form, &$form_state, CustomBlock $block) {
  $form_state['custom_block'] = $block;
  $form['id'] = array(
    '#type' => 'value',
    '#value' => $block->id(),
  );

  return confirm_form(
    $form,
    t('Are you sure you want to delete %label?', array('%label' => $block->label())),
    'admin/structure/block',
    t('This action cannot be undone.'),
    t('Delete')
  );
}

/**
 * Form submission handler for custom_block_delete_form().
 */
function custom_block_delete_form_submit($form, &$form_state) {
  // @todo Delete all configured instances of the block.
  $block = $form_state['custom_block'];
  $block->delete();

  drupal_set_message(t('Custom block %label has been deleted.', array('%label' => $block->label())));
  watchdog('custom_block', 'Custom block %label has been deleted.', array('%label' => $block->label()), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/structure/block';
}
