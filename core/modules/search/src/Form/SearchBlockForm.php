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
    // Set up the form to submit using GET to the correct search page.
    $entity_id = $this->searchPageRepository->getDefaultSearchPage();
    if (!$entity_id) {
      $form['message'] = array(
        '#markup' => $this->t('Search is currently disabled'),
      );
      return $form;
    }

    $route = 'search.view_' . $entity_id;
    $form['#action'] = $this->url($route);
    $form['#token'] = FALSE;
    $form['#method'] = 'get';

    $form['keys'] = array(
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#title_display' => 'invisible',
      '#size' => 15,
      '#default_value' => '',
      '#attributes' => array('title' => $this->t('Enter the terms you wish to search for.')),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      // Prevent op from showing up in the query string.
      '#name' => '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // This form submits to the search page, so processing happens there.
  }
}
