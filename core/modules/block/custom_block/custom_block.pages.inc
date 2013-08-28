<?php

/**
 * @file
 * Provides page callbacks for custom blocks.
 */

use Drupal\custom_block\Entity\CustomBlockType;
use Drupal\custom_block\Entity\CustomBlock;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Prepares variables for a custom block type creation list templates.
 *
 * Default template: custom-block-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of block types.
 *
 * @see custom_block_add_page()
 */
function template_preprocess_custom_block_add_list(&$variables) {
  $variables['types'] = array();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id] = array();
    $query = array();
    if (($destination = drupal_get_destination()) && $destination['destination'] !== current_path()) {
      // A destination parameter is set other than the current path so we
      // respect that by adding it to the generated links. If the current path
      // is returned, we ignore it as we don't want to end up back at block/add.
      $query = $destination;
    }
    $variables['types'][$type->id]['link'] = l($type->label(), 'block/add/' . $type->id(), array('query' => $query));
    $variables['types'][$type->id]['description'] = filter_xss_admin($type->description);
  }
}

/**
 * Page callback: Form constructor for the custom block deletion form.
 *
 * @param Drupal\custom_block\Entity\CustomBlock $block
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

  $instances = $block->getInstances();

  $form['message'] = array(
    '#markup' => format_plural(count($instances), 'This will also remove 1 placed block instance.', 'This will also remove @count placed block instances.'),
    '#access' => !empty($instances),
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
  $block = $form_state['custom_block'];
  $block->delete();

  drupal_set_message(t('Custom block %label has been deleted.', array('%label' => $block->label())));
  watchdog('custom_block', 'Custom block %label has been deleted.', array('%label' => $block->label()), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/structure/custom-blocks';
}
