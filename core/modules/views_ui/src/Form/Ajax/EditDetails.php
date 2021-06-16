<?php

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Provides a form for editing the details of a View.
 *
 * @internal
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
  public function getFormId() {
    return 'views_ui_edit_details_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $view = $form_state->get('view');

    $form['#title'] = $this->t('Name and description');
    $form['#section'] = 'details';

    $form['details'] = [
      '#theme_wrappers' => ['container'],
      '#attributes' => ['class' => ['scroll'], 'data-drupal-views-scroll' => TRUE],
    ];
    $form['details']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administrative name'),
      '#default_value' => $view->label(),
    ];
    $form['details']['langcode'] = [
      '#type' => 'language_select',
      '#title' => $this->t('View language'),
      '#description' => $this->t('Language of labels and other textual elements in this view.'),
      '#default_value' => $view->get('langcode'),
    ];
    $form['details']['description'] = [
       '#type' => 'textfield',
       '#title' => $this->t('Administrative description'),
       '#default_value' => $view->get('description'),
     ];
    $form['details']['tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administrative tags'),
      '#description' => $this->t('Enter a comma-separated list of words to describe your view.'),
      '#default_value' => $view->get('tag'),
      '#autocomplete_route_name' => 'views_ui.autocomplete',
    ];

    $view->getStandardButtons($form, $form_state, 'views_ui_edit_details_form');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    foreach ($form_state->getValues() as $key => $value) {
      // Only save values onto the view if they're actual view properties
      // (as opposed to 'op' or 'form_build_id').
      if (isset($form['details'][$key])) {
        $view->set($key, $value);
      }
    }
    $bases = Views::viewsData()->fetchBaseTables();
    $page_title = $view->label();
    if (isset($bases[$view->get('base_table')])) {
      $page_title .= ' (' . $bases[$view->get('base_table')]['title'] . ')';
    }
    $form_state->set('page_title', $page_title);

    $view->cacheSet();
  }

}
