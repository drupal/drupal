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
