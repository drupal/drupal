<?php

namespace Drupal\search\Controller;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\search\Form\SearchPageForm;
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
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new search controller.
   *
   * @param \Drupal\search\SearchPageRepositoryInterface $search_page_repository
   *   The search page repository.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(SearchPageRepositoryInterface $search_page_repository, RendererInterface $renderer) {
    $this->searchPageRepository = $search_page_repository;
    $this->logger = $this->getLogger('search');
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search.search_page_repository'),
      $container->get('renderer')
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
    $build = [];
    $plugin = $entity->getPlugin();

    // Build the form first, because it may redirect during the submit,
    // and we don't want to build the results based on last time's request.
    $build['#cache']['contexts'][] = 'url.query_args:keys';
    if ($request->query->has('keys')) {
      $keys = trim($request->query->get('keys'));
      $plugin->setSearch($keys, $request->query->all(), $request->attributes->all());
    }

    $build['#title'] = $plugin->suggestedTitle();
    $build['search_form'] = $this->formBuilder()->getForm(SearchPageForm::class, $entity);

    // Build search results, if keywords or other search parameters are in the
    // GET parameters. Note that we need to try the search if 'keys' is in
    // there at all, vs. being empty, due to advanced search.
    $results = [];
    if ($request->query->has('keys')) {
      if ($plugin->isSearchExecutable()) {
        // Log the search.
        if ($this->config('search.settings')->get('logging')) {
          $this->logger->notice('Searched %type for %keys.', ['%keys' => $keys, '%type' => $entity->label()]);
        }

        // Collect the search results.
        $results = $plugin->buildResults();
      }
      else {
        // The search not being executable means that no keywords or other
        // conditions were entered.
        $this->messenger()->addError($this->t('Please enter some keywords.'));
      }
    }

    if (count($results)) {
      $build['search_results_title'] = [
        '#markup' => '<h2>' . $this->t('Search results') . '</h2>',
      ];
    }

    $build['search_results'] = [
      '#theme' => ['item_list__search_results__' . $plugin->getPluginId(), 'item_list__search_results'],
      '#items' => $results,
      '#empty' => [
        '#markup' => '<h3>' . $this->t('Your search yielded no results.') . '</h3>',
      ],
      '#list_type' => 'ol',
      '#context' => [
        'plugin' => $plugin->getPluginId(),
      ],
    ];

    $this->renderer->addCacheableDependency($build, $entity);
    if ($plugin instanceof CacheableDependencyInterface) {
      $this->renderer->addCacheableDependency($build, $plugin);
    }

    // If this plugin uses a search index, then also add the cache tag tracking
    // that search index, so that cached search result pages are invalidated
    // when necessary.
    if ($plugin->getType()) {
      $build['search_results']['#cache']['tags'][] = 'search_index';
      $build['search_results']['#cache']['tags'][] = 'search_index:' . $plugin->getType();
    }

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Creates a render array for the search help page.
   *
   * @param \Drupal\search\SearchPageInterface $entity
   *   The search page entity.
   *
   * @return array
   *   The search help page.
   */
  public function searchHelp(SearchPageInterface $entity) {
    $build = [];

    $build['search_help'] = $entity->getPlugin()->getHelp();

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
    return $this->t('Edit %label search page', ['%label' => $search_page->label()]);
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
      $this->messenger()->addStatus($this->t('The %label search page has been enabled.', ['%label' => $search_page->label()]));
    }
    elseif ($op == 'disable') {
      $this->messenger()->addStatus($this->t('The %label search page has been disabled.', ['%label' => $search_page->label()]));
    }

    $url = $search_page->urlInfo('collection');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
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

    $this->messenger()->addStatus($this->t('The default search page is now %label. Be sure to check the ordering of your search pages.', ['%label' => $search_page->label()]));
    return $this->redirect('entity.search_page.collection');
  }

}
