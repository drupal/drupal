<?php

/**
 * @file
 * Contains \Drupal\search\Form\SearchBlockForm.
 */

namespace Drupal\search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\search\SearchPageRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the search form for the search block.
 */
class SearchBlockForm extends FormBase {

  /**
   * The search page repository.
   *
   * @var \Drupal\search\SearchPageRepositoryInterface
   */
  protected $searchPageRepository;

  /**
   * Constructs a new SearchBlockForm.
   *
   * @param \Drupal\search\SearchPageRepositoryInterface $search_page_repository
   *   The search page repository.
   */
  public function __construct(SearchPageRepositoryInterface $search_page_repository) {
    $this->searchPageRepository = $search_page_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search.search_page_repository')
    );
  }

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
      $this->setFormError('keys', $form_state, $this->t('Please enter some keywords.'));
    }

    $form_id = $form['form_id']['#value'];
    if ($entity_id = $this->searchPageRepository->getDefaultSearchPage()) {
      $form_state['redirect_route'] = array(
        'route_name' => 'search.view_' . $entity_id,
        'route_parameters' => array(
          'keys' => trim($form_state['values'][$form_id]),
        ),
      );
    }
    else {
      $this->setFormError('', $form_state, $this->t('Search is currently disabled.'));
    }
  }

}
