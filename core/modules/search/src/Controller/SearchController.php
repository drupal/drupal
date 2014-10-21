<?php

/**
 * @file
 * Contains Drupal\search\Controller\SearchController
 */

namespace Drupal\search\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\search\SearchPageInterface;
use Drupal\search\SearchPageRepositoryInterface;
use Psr\Log\LoggerInterface;
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
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new search controller.
   *
   * @param \Drupal\search\SearchPageRepositoryInterface $search_page_repository
   *   The search page repository.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(SearchPageRepositoryInterface $search_page_repository, LoggerInterface $logger) {
    $this->searchPageRepository = $search_page_repository;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search.search_page_repository'),
      $container->get('logger.factory')->get('search')
    );
  }

  /**
   * Creates a render array for the search page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\search\SearchPageInterface $entity
   *   The search page entity.
   *
   * @return array
   *   The search form and search results build array.
   */
  public function view(Request $request, SearchPageInterface $entity) {
    $build = array();
    $plugin = $entity->getPlugin();

    // Build the form first, because it may redirect during the submit,
    // and we don't want to build the results based on last time's request.
    if ($request->query->has('keys')) {
      $keys = trim($request->get('keys'));
      $plugin->setSearch($keys, $request->query->all(), $request->attributes->all());
    }

    $build['#title'] = $plugin->suggestedTitle();
    $build['search_form'] = $this->entityFormBuilder()->getForm($entity, 'search');

    // Build search results, if keywords or other search parameters are in the
    // GET parameters. Note that we need to try the search if 'keys' is in
    // there at all, vs. being empty, due to advanced search.
    $results = array();
    if ($request->query->has('keys')) {
      if ($plugin->isSearchExecutable()) {
        // Log the search.
        if ($this->config('search.settings')->get('logging')) {
          $this->logger->notice('Searched %type for %keys.', array('%keys' => $keys, '%type' => $entity->label()));
        }

        // Collect the search results.
        $results = $plugin->buildResults();
      }
      else {
        // The search not being executable means that no keywords or other
        // conditions were entered.
        drupal_set_message($this->t('Please enter some keywords.'), 'error');
      }
    }

    if (count($results)) {
      $build['search_results_title'] = array(
        '#markup' => '<h2>' . $this->t('Search results') . '</h2>',
      );
    }

    $no_results = t('<ul>
    <li>Check if your spelling is correct.</li>
    <li>Remove quotes around phrases to search for each word individually. <em>bike shed</em> will often show more results than <em>&quot;bike shed&quot;</em>.</li>
    <li>Consider loosening your query with <em>OR</em>. <em>bike OR shed</em> will often show more results than <em>bike shed</em>.</li>
    </ul>');
    $build['search_results'] = array(
      '#theme' => array('item_list__search_results__' . $plugin->getPluginId(), 'item_list__search_results'),
      '#items' => $results,
      '#empty' => array(
        // @todo Revisit where this help text is added.
        '#markup' => '<h3>' . $this->t('Your search yielded no results.') . '</h3>' . $no_results,
      ),
      '#list_type' => 'ol',
      '#attributes' => array(
        'class' => array(
          'search-results',
          $plugin->getPluginId() . '-results',
        ),
      ),
      '#cache' => array(
        'tags' => $entity->getCacheTags(),
      ),
    );

    $build['pager'] = array(
      '#theme' => 'pager',
    );

    $build['#attached']['library'][] = 'search/drupal.search.results';

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
