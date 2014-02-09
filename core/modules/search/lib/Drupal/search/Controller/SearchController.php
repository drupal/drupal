<?php

/**
 * @file
 * Contains Drupal\search\Controller\SearchController
 */

namespace Drupal\search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\search\SearchPageInterface;
use Drupal\search\SearchPageRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Route controller for search.
 */
class SearchController extends ControllerBase {

  /**
   * The search page repository.
   *
   * @var \Drupal\search\SearchPageRepositoryInterface
   */
  protected $searchPageRepository;

  /**
   * Constructs a new search controller.
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
   * Creates a render array for the search page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\search\SearchPageInterface $entity
   *   The search page entity.
   * @param string $keys
   *   (optional) Search keywords, defaults to an empty string.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The search form and search results or redirect response.
   */
  public function view(Request $request, SearchPageInterface $entity, $keys = '') {
    // Also try to pull search keywords from the request to support old GET
    // format of searches for existing links.
    if (!$keys && $request->query->has('keys')) {
      $keys = $request->query->get('keys');
    }
    $keys = trim($keys);
    $build['#title'] = $this->t('Search');

    $plugin = $entity->getPlugin();
    $plugin->setSearch($keys, $request->query->all(), $request->attributes->all());
    $results = array();

    // Process the search form. Note that if there is
    // \Drupal::request()->request data, search_form_submit() will cause a
    // redirect to search/[path]/[keys], which will get us back to this page
    // callback. In other words, the search form submits with POST but redirects
    // to GET. This way we can keep the search query URL clean as a whistle.
    if ($request->request->has('form_id') || $request->request->get('form_id') != 'search_form') {
      // Only search if there are keywords or non-empty conditions.
      if ($plugin->isSearchExecutable()) {
        // Log the search keys.
        watchdog('search', 'Searched %type for %keys.', array('%keys' => $keys, '%type' => $entity->label()), WATCHDOG_NOTICE, $this->l(t('results'), 'search.view_' . $entity->id(), array('keys' => $keys)));

        // Collect the search results.
        $results = $plugin->buildResults();
      }
    }
    // The form may be altered based on whether the search was run.
    $build['search_form'] = $this->entityFormBuilder()->getForm($entity, 'search');
    if (count($results)) {
      $build['search_results_title'] = array(
        '#markup' => '<h2>' . $this->t('Search results') . '</h2>',
      );
    }

    $build['search_results'] = array(
      '#theme' => array('item_list__search_results__' . $plugin->getPluginId(), 'item_list__search_results'),
      '#items' => $results,
      '#empty' => array(
        // @todo Revisit where this help text is added.
        '#markup' => '<h3>' . $this->t('Your search yielded no results.') . '</h3>' . search_help('search#noresults', drupal_help_arg()),
      ),
      '#list_type' => 'ol',
      '#attributes' => array(
        'class' => array(
          'search-results',
          $plugin->getPluginId() . '-results',
        ),
      ),
    );

    $build['pager'] = array(
      '#theme' => 'pager',
    );

    return $build;
  }

  /**
   * Redirects to a search page.
   *
   * This is used to redirect from /search to the default search page.
   *
   * @param \Drupal\search\SearchPageInterface $entity
   *   The search page entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the search page.
   */
  public function redirectSearchPage(SearchPageInterface $entity) {
    return $this->redirect('search.view_' . $entity->id());
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\search\SearchPageInterface $search_page
   *   The search page entity.
   *
   * @return string
   *   The title for the search page edit form.
   */
  public function editTitle(SearchPageInterface $search_page) {
    return $this->t('Edit %label search page', array('%label' => $search_page->label()));
  }

  /**
   * Performs an operation on the search page entity.
   *
   * @param \Drupal\search\SearchPageInterface $search_page
   *   The search page entity.
   * @param string $op
   *   The operation to perform, usually 'enable' or 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the search settings page.
   */
  public function performOperation(SearchPageInterface $search_page, $op) {
    $search_page->$op()->save();

    if ($op == 'enable') {
      drupal_set_message($this->t('The %label search page has been enabled.', array('%label' => $search_page->label())));
    }
    elseif ($op == 'disable') {
      drupal_set_message($this->t('The %label search page has been disabled.', array('%label' => $search_page->label())));
    }

    return $this->redirect('search.settings');
  }

  /**
   * Sets the search page as the default.
   *
   * @param \Drupal\search\SearchPageInterface $search_page
   *   The search page entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the search settings page.
   */
  public function setAsDefault(SearchPageInterface $search_page) {
    // Set the default page to this search page.
    $this->searchPageRepository->setDefaultSearchPage($search_page);

    drupal_set_message($this->t('The default search page is now %label. Be sure to check the ordering of your search pages.', array('%label' => $search_page->label())));
    return $this->redirect('search.settings');
  }

}
