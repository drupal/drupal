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
  return drupal_container()->get('plugin.manager.entity')
    ->getListController('picture_mapping')
    ->render();
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
