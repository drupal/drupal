<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\EditDetails.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\views\Views;
use Drupal\views_ui\ViewUI;

/**
 * Provides a form for editing the details of a View.
 */
class EditDetails extends ViewsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'edit-details';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'views_ui_edit_details_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $view = &$form_state['view'];

    $form['#title'] = $this->t('View name and description');
    $form['#section'] = 'details';

    $form['details'] = array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll')),
    );
    $form['details']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Human-readable name'),
      '#description' => $this->t('A descriptive human-readable name for this view. Spaces are allowed'),
      '#default_value' => $view->label(),
    );
    $form['details']['langcode'] = array(
      '#type' => 'language_select',
      '#title' => $this->t('View language'),
      '#description' => $this->t('Language of labels and other textual elements in this view.'),
      '#default_value' => $view->get('langcode'),
    );
    $form['details']['tag'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('View tag'),
      '#description' => $this->t('Optionally, enter a comma delimited list of tags for this view to use in filtering and sorting views on the administrative page.'),
      '#default_value' => $view->get('tag'),
      '#autocomplete_path' => 'admin/views/ajax/autocomplete/tag',
    );
    $form['details']['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('View description'),
      '#description' => $this->t('This description will appear on the Views administrative UI to tell you what the view is about.'),
      '#default_value' => $view->get('description'),
    );

    $view->getStandardButtons($form, $form_state, 'views_ui_edit_details_form');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $view = $form_state['view'];
    foreach ($form_state['values'] as $key => $value) {
      // Only save values onto the view if they're actual view properties
      // (as opposed to 'op' or 'form_build_id').
      if (isset($form['details'][$key])) {
        $view->set($key, $value);
      }
    }
    $bases = Views::viewsData()->fetchBaseTables();
    $form_state['#page_title'] = $view->label();

    if (isset($bases[$view->get('base_table')])) {
      $form_state['#page_title'] .= ' (' . $bases[$view->get('base_table')]['title'] . ')';
    }

    $view->cacheSet();
  }

}
