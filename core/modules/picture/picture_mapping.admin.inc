<?php

/**
 * @file
 * Administration functions to maintain picture mappings.
 */

use \Drupal\picture\PictureMapping;

/**
 * Page callback: Presents the picture list page.
 *
 * @return
 *   A render array for a page containing a list of content.
 *
 * @see picture_menu()
 */
function picture_mapping_page() {
  $controller = entity_list_controller('picture_mapping');
  return $controller->render();
}

/**
 * Page callback: Presents the picture mapping editing form.
 *
 * @param Drupal\picture\PictureMapping $picture_mapping
 *
 * @return
 *   A render array for a page containing a list of content.
 *
 * @see picture_menu()
 */
function picture_mapping_page_edit($picture_mapping) {
  drupal_set_title(t('<em>Edit picture mapping</em> @label', array('@label' => $picture_mapping->label())), PASS_THROUGH);
  return entity_get_form($picture_mapping);
}

/**
 * Page callback: Presents the picture mapping duplicate form.
 *
 * @param Drupal\picture\PictureMapping $picture_mapping
 *
 * @return
 *   A render array for a page containing a list of content.
 *
 * @see picture_menu()
 */
function picture_mapping_page_duplicate($picture_mapping) {
  drupal_set_title(t('<em>Duplicate picture mapping</em> @label', array('@label' => $picture_mapping->label())), PASS_THROUGH);
  return entity_get_form($picture_mapping->createDuplicate());
}

/**
 * Page callback: Provides the new picture mapping addition form.
 *
 * @return
 *   A render array for a page containing a list of content.
 *
 * @see picture_menu()
 */
function picture_mapping_page_add() {
  $picture_mapping = entity_create('picture_mapping', array());
  $form = entity_get_form($picture_mapping);
  return $form;
}

/**
 * Page callback: Form constructor for picture action confirmation form.
 *
 * @param Drupal\picture\PictureMapping $picture_mapping
 * @param string $action
 *
 * @see picture_menu()
 */
function picture_mapping_action_confirm($form, &$form_state, $picture_mapping, $action) {
  // Always provide entity id in the same form key as in the entity edit form.
  if (in_array($action, array('delete'))) {
    $form['id'] = array('#type' => 'value', '#value' => $picture_mapping->id());
    $form['action'] = array('#type' => 'value', '#value' => $action);
    $form_state['picture_mapping'] = $picture_mapping;
    $form = confirm_form($form,
      t('Are you sure you want to @action the picture_mapping %title?', array('@action' => $action, '%title' => $picture_mapping->label())),
      'admin/config/media/picturemapping',
      $action == 'delete' ? t('This action cannot be undone.') : '',
      t(drupal_ucfirst($action)),
      t('Cancel')
    );
  }
  return $form;
}

/**
 * Form submission handler for picture_action_confirm().
 */
function picture_mapping_action_confirm_submit($form, &$form_state) {
  $picture_mapping = $form_state['picture_mapping'];
  $action = $form_state['values']['action'];
  $picture_mapping->{$action}();
  $verb = '';
  switch ($action) {
    case 'delete':
      $verb = 'deleted';
      break;
  }
  drupal_set_message(t('Picture mapping %label has been @action.', array('%label' => $picture_mapping->label(), '@action' => $verb)));
  watchdog('picture', 'Picture mapping %label has been @action.', array('%label' => $picture_mapping->label(), '@action' => $verb), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/config/media/picturemapping';
}
