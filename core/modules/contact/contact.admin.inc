<?php

/**
 * @file
 * Admin page callbacks for the Contact module.
 */

use Drupal\contact\Plugin\Core\Entity\Category;

/**
 * Page callback: Lists contact categories.
 *
 * @return array
 *   A build array in the format expected by drupal_render().
 *
 * @see contact_menu()
 */
function contact_category_list() {
  drupal_set_title(t('Contact form categories'));
  return drupal_container()->get('plugin.manager.entity')
    ->getListController('contact_category')
    ->render();
}

/**
 * Page callback: Presents the category creation form.
 *
 * @return array
 *   A form array as expected by drupal_render().
 *
 * @see contact_menu()
 */
function contact_category_add() {
  drupal_set_title(t('Add contact form category'));
  $category = entity_create('contact_category', array());
  return entity_get_form($category);
}

/**
 * Page callback: Presents the category edit form.
 *
 * @param Drupal\contact\Plugin\Core\Entity\Category $category
 *   The contact category to edit.
 *
 * @return array
 *   A form array as expected by drupal_render().
 *
 * @see contact_menu()
 */
function contact_category_edit(Category $category) {
  drupal_set_title(t('Edit %label contact form category', array('%label' => $category->label())), PASS_THROUGH);
  return entity_get_form($category);
}

/**
 * Page callback: Form constructor for the contact category deletion form.
 *
 * @param Drupal\contact\Plugin\Core\Entity\Category $category
 *   The contact category to be deleted.
 *
 * @see contact_menu()
 * @see contact_category_delete_form_submit()
 *
 * @ingroup forms
 */
function contact_category_delete_form($form, &$form_state, Category $category) {
  $form_state['contact_category'] = $category;
  $form['id'] = array(
    '#type' => 'value',
    '#value' => $category->id(),
  );

  return confirm_form(
    $form,
    t('Are you sure you want to delete %label?', array('%label' => $category->label())),
    'admin/structure/contact',
    t('This action cannot be undone.'),
    t('Delete')
  );
}

/**
 * Form submission handler for contact_category_delete_form().
 */
function contact_category_delete_form_submit($form, &$form_state) {
  $category = $form_state['contact_category'];
  $category->delete();

  drupal_set_message(t('Category %label has been deleted.', array('%label' => $category->label())));
  watchdog('contact', 'Category %label has been deleted.', array('%label' => $category->label()), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/structure/contact';
}
