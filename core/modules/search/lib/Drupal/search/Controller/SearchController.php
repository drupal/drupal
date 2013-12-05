<?php

/**
 * @file
 * Contains Drupal\search\Controller\SearchController
 */

namespace Drupal\search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\search\SearchPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Route controller for search.
 */
class SearchController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The search plugin manager.
   *
   * @var \Drupal\search\SearchPluginManager
   */
  protected $searchManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new search controller.
   *
   * @param \Drupal\search\SearchPluginManager $search_plugin_manager
   *   The search plugin manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(SearchPluginManager $search_plugin_manager, FormBuilderInterface $form_builder) {
    $this->searchManager = $search_plugin_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.search'),
      $container->get('form_builder')
    );
  }

  /**
   * Creates a render array for the search page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $plugin_id
   *   The ID of a search plugin.
   * @param string $keys
   *   Search keywords.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The search form and search results or redirect response.
   */
  public function view(Request $request, $plugin_id = NULL, $keys = NULL) {
    $info = FALSE;
    $keys = trim($keys);
    // Also try to pull search keywords from the request to support old GET
    // format of searches for existing links.
    if (!$keys && $request->query->has('keys')) {
      $keys = trim($request->query->get('keys'));
    }
    $build['#title'] = $this->t('Search');

    if (!empty($plugin_id)) {
      $active_plugin_info = $this->searchManager->getActiveDefinitions();
      if (isset($active_plugin_info[$plugin_id])) {
        $info = $active_plugin_info[$plugin_id];
      }
    }

    if (empty($plugin_id) || empty($info)) {
      // No path or invalid path: find the default plugin. Note that if there
      // are no enabled search plugins, this function should never be called,
      // since hook_menu() would not have defined any search paths.
      $info = search_get_default_plugin_info();
      // Redirect from bare /search or an invalid path to the default search
      // path.
      $path = 'search/' . $info['path'];
      if ($keys) {
        $path .= '/' . $keys;
      }

      return $this->redirect('search.view_' . $info['id']);
    }
    $plugin = $this->searchManager->createInstance($plugin_id);
    $plugin->setSearch($keys, $request->query->all(), $request->attributes->all());
    // Default results output is an empty string.
    $results = array('#markup' => '');

    // Process the search form. Note that if there is
    // \Drupal::request()->request data, search_form_submit() will cause a
    // redirect to search/[path]/[keys], which will get us back to this page
    // callback. In other words, the search form submits with POST but redirects
    // to GET. This way we can keep the search query URL clean as a whistle.
    if ($request->request->has('form_id') || $request->request->get('form_id') != 'search_form') {
      // Only search if there are keywords or non-empty conditions.
      if ($plugin->isSearchExecutable()) {
        // Log the search keys.
        watchdog('search', 'Searched %type for %keys.', array('%keys' => $keys, '%type' => $info['title']), WATCHDOG_NOTICE, l(t('results'), 'search/' . $info['path'] . '/' . $keys));

        // Collect the search results.
        $results = $plugin->buildResults();
      }
    }
    // The form may be altered based on whether the search was run.
    $build['search_form'] = $this->formBuilder->getForm('\Drupal\search\Form\SearchForm', $plugin);
    $build['search_results'] = $results;
    return $build;
  }

}
