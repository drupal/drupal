<?php

/**
 * @file
 * Admin page callbacks for the custom block module.
 */

use Drupal\custom_block\Plugin\Core\Entity\CustomBlockType;

/**
 * Page callback: Presents the custom block type creation form.
 *
 * @return array
 *   A form array as expected by drupal_render().
 *
 * @see custom_block_menu()
 */
function custom_block_type_add() {
  $block_type = entity_create('custom_block_type', array());
  return Drupal::entityManager()->getForm($block_type);
}

/**
 * Page callback: Presents the custom block type edit form.
 *
 * @param \Drupal\custom_block\Plugin\Core\Entity\CustomBlockType $block_type
 *   The custom block type to edit.
 *
 * @return array
 *   A form array as expected by drupal_render().
 *
 * @see custom_block_menu()
 */
function custom_block_type_edit(CustomBlockType $block_type) {
  return Drupal::entityManager()->getForm($block_type);
}

/**
 * Page callback: Form constructor for the custom block type deletion form.
 *
 * @param \Drupal\custom_block\Plugin\Core\Entity\CustomBlockType $block_type
 *   The custom block type to be deleted.
 *
 * @see custom_block_menu()
 * @see custom_block_type_delete_form_submit()
 *
 * @ingroup forms
 */
function custom_block_type_delete_form($form, &$form_state, CustomBlockType $block_type) {
  $form_state['custom_block_type'] = $block_type;
  $form['id'] = array(
    '#type' => 'value',
    '#value' => $block_type->id(),
  );

  $message = t('Are you sure you want to delete %label?', array('%label' => $block_type->label()));

  $blocks = Drupal::entityQuery('custom_block')->condition('type', $block_type->id())->execute();
  if (!empty($blocks)) {
    drupal_set_title($message, PASS_THROUGH);
    $caption = '<p>' . format_plural(count($blocks), '%label is used by 1 custom block on your site. You can not remove this block type until you have removed all of the %label blocks.', '%label is used by @count custom blocks on your site. You may not remove %label until you have removed all of the %label custom blocks.', array('%label' => $block_type->label())) . '</p>';
    $form['description'] = array('#markup' => $caption);
    return $form;
  }

  return confirm_form(
    $form,
    $message,
    'admin/structure/custom-blocks',
    t('This action cannot be undone.'),
    t('Delete')
  );
}

/**
 * Form submission handler for custom_block_type_delete_form().
 */
function custom_block_type_delete_form_submit($form, &$form_state) {
  $block_type = $form_state['custom_block_type'];
  $block_type->delete();

  drupal_set_message(t('Custom block type %label has been deleted.', array('%label' => $block_type->label())));
  watchdog('custom_block', 'Custom block type %label has been deleted.', array('%label' => $block_type->label()), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/structure/custom-blocks';
}
