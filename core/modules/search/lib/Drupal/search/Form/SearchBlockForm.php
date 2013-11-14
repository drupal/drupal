<?php

/**
 * @file
 * Contains \Drupal\search\Form\SearchBlockForm.
 */

namespace Drupal\search\Form;

use Drupal\Core\Form\FormBase;

/**
 * Builds the search form for the search block.
 */
class SearchBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['search_block_form'] = array(
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#title_display' => 'invisible',
      '#size' => 15,
      '#default_value' => '',
      '#attributes' => array('title' => $this->t('Enter the terms you wish to search for.')),
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Search'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // The search form relies on control of the redirect destination for its
    // functionality, so we override any static destination set in the request.
    // See http://drupal.org/node/292565.
    $request = $this->getRequest();
    if ($request->query->has('destination')) {
      $request->query->remove('destination');
    }

    // Check to see if the form was submitted empty.
    // If it is empty, display an error message.
    // (This method is used instead of setting #required to TRUE for this field
    // because that results in a confusing error message.  It would say a plain
    // "field is required" because the search keywords field has no title.
    // The error message would also complain about a missing #title field.)
    if ($form_state['values']['search_block_form'] == '') {
      form_set_error('keys', $this->t('Please enter some keywords.'));
    }

    $form_id = $form['form_id']['#value'];
    $info = search_get_default_plugin_info();
    if ($info) {
      $form_state['redirect_route'] = array(
        'route_name' => 'search.view_' . $info['id'],
        'route_parameters' => array(
          'keys' => trim($form_state['values'][$form_id]),
        ),
      );
    }
    else {
      form_set_error(NULL, $this->t('Search is currently disabled.'), 'error');
    }
  }
}
