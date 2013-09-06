<?php

/**
 * @file
 * Contains Drupal\aggregator\Form\CategorizeCategoryForm
 */

namespace Drupal\aggregator\Form;

/**
 * A form for categorizing feed items in a feed category.
 */
class CategorizeCategoryForm extends AggregatorCategorizeFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'aggregator_page_category_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $cid = NULL) {
    $items = $this->aggregatorItemStorage->loadByCategory($cid);
    return parent::buildForm($form, $form_state, $items);
  }

}
