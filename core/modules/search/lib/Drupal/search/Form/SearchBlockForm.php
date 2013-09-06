<?php

/**
 * @file
 * Contains \Drupal\search\Form\SearchBlockForm.
 */

namespace Drupal\search\Form;

use Drupal\Core\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds the search form for the search block.
 */
class SearchBlockForm implements FormInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'search_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, Request $request = NULL) {
    // Save the request variable for use in the submit method.
    $this->request = $request;

    $form['search_block_form'] = array(
      '#type' => 'search',
      '#title' => t('Search'),
      '#title_display' => 'invisible',
      '#size' => 15,
      '#default_value' => '',
      '#attributes' => array('title' => t('Enter the terms you wish to search for.')),
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Search'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // No validation necessary at this time.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // The search form relies on control of the redirect destination for its
    // functionality, so we override any static destination set in the request.
    // See http://drupal.org/node/292565.
    if ($this->request->query->has('destination')) {
      $this->request->query->remove('destination');
    }

    // Check to see if the form was submitted empty.
    // If it is empty, display an error message.
    // (This method is used instead of setting #required to TRUE for this field
    // because that results in a confusing error message.  It would say a plain
    // "field is required" because the search keywords field has no title.
    // The error message would also complain about a missing #title field.)
    if ($form_state['values']['search_block_form'] == '') {
      form_set_error('keys', t('Please enter some keywords.'));
    }

    $form_id = $form['form_id']['#value'];
    $info = search_get_default_plugin_info();
    if ($info) {
      $form_state['redirect'] = 'search/' . $info['path'] . '/' . trim($form_state['values'][$form_id]);
    }
    else {
      form_set_error(NULL, t('Search is currently disabled.'), 'error');
    }
  }
}
