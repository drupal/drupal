<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Search\NodeSearch.
 */

namespace Drupal\node\Plugin\Search;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\String;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\node\NodeInterface;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\Search\SearchQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching for node entities using the Search module index.
 *
 * @SearchPlugin(
 *   id = "node_search",
 *   title = @Translation("Content")
 * )
 */
class NodeSearch extends ConfigurableSearchPluginBase implements AccessibleInterface, SearchIndexingInterface {

  /**
   * A database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * An entity manager object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * A module manager object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A config object for 'search.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $searchSettings;

  /**
   * The Drupal account to use for checking for access to advanced search.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * An array of additional rankings from hook_ranking().
   *
   * @var array
   */
  protected $rankings;

  /**
   * The list of options and info for advanced search filters.
   *
   * Each entry in the array has the option as the key and and for its value, an
   * array that determines how the value is matched in the database query. The
   * possible keys in that array are:
   * - column: (required) Name of the database column to match against.
   * - join: (optional) Information on a table to join. By default the data is
   *   matched against the {node_field_data} table.
   * - operator: (optional) OR or AND, defaults to OR.
   *
   * @var array
   */
  protected $advanced = array(
    'type' => array('column' => 'n.type'),
    'language' => array('column' => 'i.langcode'),
    'author' => array('column' => 'n.uid'),
    'term' => array('column' => 'ti.tid', 'join' => array('table' => 'taxonomy_index', 'alias' => 'ti', 'condition' => 'n.nid = ti.nid')),
  );

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('config.factory')->get('search.settings'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a \Drupal\node\Plugin\Search\NodeSearch object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   An entity manager object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module manager object.
   * @param \Drupal\Core\Config\Config $search_settings
   *   A config object for 'search.settings'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The $account object to use for checking for access to advanced search.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, Config $search_settings, AccountInterface $account = NULL) {
    $this->database = $database;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->searchSettings = $search_settings;
    $this->account = $account;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIfHasPermission($account, 'access content');
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function isSearchExecutable() {
    // Node search is executable if we have keywords or an advanced parameter.
    // At least, we should parse out the parameters and see if there are any
    // keyword matches in that case, rather than just printing out the
    // "Please enter keywords" message.
    return !empty($this->keywords) || (isset($this->searchParameters['f']) && count($this->searchParameters['f']));
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if ($this->isSearchExecutable()) {
      $results = $this->findResults();

      if ($results) {
        return $this->prepareResults($results);
      }
    }

    return array();
  }

  /**
   * Queries to find search results, and sets status messages.
   *
   * This method can assume that $this->isSearchExecutable() has already been
   * checked and returned TRUE.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Results from search query execute() method, or NULL if the search
   *   failed.
   */
  protected function findResults() {
    $keys = $this->keywords;

    // Build matching conditions.
    $query = $this->database
      ->select('search_index', 'i', array('target' => 'replica'))
      ->extend('Drupal\search\SearchQuery')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->join('node_field_data', 'n', 'n.nid = i.sid');
    $query->condition('n.status', 1)
      ->addTag('node_access')
      ->searchExpression($keys, $this->getPluginId());

    // Handle advanced search filters in the f query string.
    // \Drupal::request()->query->get('f') is an array that looks like this in
    // the URL: ?f[]=type:page&f[]=term:27&f[]=term:13&f[]=langcode:en
    // So $parameters['f'] looks like:
    // array('type:page', 'term:27', 'term:13', 'langcode:en');
    // We need to parse this out into query conditions, some of which go into
    // the keywords string, and some of which are separate conditions.
    $parameters = $this->getParameters();
    if (!empty($parameters['f']) && is_array($parameters['f'])) {
      $filters = array();
      // Match any query value that is an expected option and a value
      // separated by ':' like 'term:27'.
      $pattern = '/^(' . implode('|', array_keys($this->advanced)) . '):([^ ]*)/i';
      foreach ($parameters['f'] as $item) {
        if (preg_match($pattern, $item, $m)) {
          // Use the matched value as the array key to eliminate duplicates.
          $filters[$m[1]][$m[2]] = $m[2];
        }
      }

      // Now turn these into query conditions. This assumes that everything in
      // $filters is a known type of advanced search.
      foreach ($filters as $option => $matched) {
        $info = $this->advanced[$option];
        // Insert additional conditions. By default, all use the OR operator.
        $operator = empty($info['operator']) ? 'OR' : $info['operator'];
        $where = new Condition($operator);
        foreach ($matched as $value) {
          $where->condition($info['column'], $value);
        }
        $query->condition($where);
        if (!empty($info['join'])) {
          $query->join($info['join']['table'], $info['join']['alias'], $info['join']['condition']);
        }
      }
    }

    // Add the ranking expressions.
    $this->addNodeRankings($query);

    // Run the query.
    $find = $query
      // Add the language code of the indexed item to the result of the query,
      // since the node will be rendered using the respective language.
      ->fields('i', array('langcode'))
      // And since SearchQuery makes these into GROUP BY queries, if we add
      // a field, for PostgreSQL we also need to make it an aggregate or a
      // GROUP BY. In this case, we want GROUP BY.
      ->groupBy('i.langcode')
      ->limit(10)
      ->execute();

    // Check query status and set messages if needed.
    $status = $query->getStatus();

    if ($status & SearchQuery::EXPRESSIONS_IGNORED) {
      drupal_set_message($this->t('Your search used too many AND/OR expressions. Only the first @count terms were included in this search.', array('@count' => $this->searchSettings->get('and_or_limit'))), 'warning');
    }

    if ($status & SearchQuery::LOWER_CASE_OR) {
      drupal_set_message($this->t('Search for either of the two terms with uppercase <strong>OR</strong>. For example, <strong>cats OR dogs</strong>.'), 'warning');
    }

    if ($status & SearchQuery::NO_POSITIVE_KEYWORDS) {
      drupal_set_message($this->formatPlural($this->searchSettings->get('index.minimum_word_size'), 'You must include at least one positive keyword with 1 character or more.', 'You must include at least one positive keyword with @count characters or more.'), 'warning');
    }

    return $find;
  }

  /**
   * Prepares search results for rendering.
   *
   * @param \Drupal\Core\Database\StatementInterface $found
   *   Results found from a successful search query execute() method.
   *
   * @return array
   *   Array of search result item render arrays (empty array if no results).
   */
  protected function prepareResults(StatementInterface $found) {
    $results = array();

    $node_storage = $this->entityManager->getStorage('node');
    $node_render = $this->entityManager->getViewBuilder('node');
    $keys = $this->keywords;

    foreach ($found as $item) {
      // Render the node.
      /** @var \Drupal\node\NodeInterface $node */
      $node = $node_storage->load($item->sid)->getTranslation($item->langcode);
      $build = $node_render->view($node, 'search_result', $item->langcode);
      unset($build['#theme']);

      // Fetch comment count for snippet.
      $node->rendered = SafeMarkup::set(
        drupal_render($build) . ' ' .
        SafeMarkup::escape($this->moduleHandler->invoke('comment', 'node_update_index', array($node, $item->langcode)))
      );

      $extra = $this->moduleHandler->invokeAll('node_search_result', array($node, $item->langcode));

      $language = language_load($item->langcode);
      $username = array(
        '#theme' => 'username',
        '#account' => $node->getOwner(),
      );
      $results[] = array(
        'link' => $node->url('canonical', array('absolute' => TRUE, 'language' => $language)),
        'type' => String::checkPlain($this->entityManager->getStorage('node_type')->load($node->bundle())->label()),
        'title' => $node->label(),
        'user' => drupal_render($username),
        'date' => $node->getChangedTime(),
        'node' => $node,
        'extra' => $extra,
        'score' => $item->calculated_score,
        'snippet' => search_excerpt($keys, $node->rendered, $item->langcode),
        'langcode' => $node->language()->getId(),
      );
    }
    return $results;
  }

  /**
   * Adds the configured rankings to the search query.
   *
   * @param $query
   *   A query object that has been extended with the Search DB Extender.
   */
  protected function addNodeRankings(SelectExtender $query) {
    if ($ranking = $this->getRankings()) {
      $tables = &$query->getTables();
      foreach ($ranking as $rank => $values) {
        if (isset($this->configuration['rankings'][$rank]) && !empty($this->configuration['rankings'][$rank])) {
          $node_rank = $this->configuration['rankings'][$rank];
          // If the table defined in the ranking isn't already joined, then add it.
          if (isset($values['join']) && !isset($tables[$values['join']['alias']])) {
            $query->addJoin($values['join']['type'], $values['join']['table'], $values['join']['alias'], $values['join']['on']);
          }
          $arguments = isset($values['arguments']) ? $values['arguments'] : array();
          $query->addScore($values['score'], $arguments, $node_rank);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    // Interpret the cron limit setting as the maximum number of nodes to index
    // per cron run.
    $limit = (int) $this->searchSettings->get('index.cron_limit');

    $result = $this->database->queryRange("SELECT n.nid, MAX(sd.reindex) FROM {node} n LEFT JOIN {search_dataset} sd ON sd.sid = n.nid AND sd.type = :type WHERE sd.sid IS NULL OR sd.reindex <> 0 GROUP BY n.nid ORDER BY MAX(sd.reindex) is null DESC, MAX(sd.reindex) ASC, n.nid ASC", 0, $limit, array(':type' => $this->getPluginId()), array('target' => 'replica'));
    $nids = $result->fetchCol();
    if (!$nids) {
      return;
    }

    $node_storage = $this->entityManager->getStorage('node');
    foreach ($node_storage->loadMultiple($nids) as $node) {
      $this->indexNode($node);
    }
  }

  /**
   * Indexes a single node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to index.
   */
  protected function indexNode(NodeInterface $node) {
    $languages = $node->getTranslationLanguages();
    $node_render = $this->entityManager->getViewBuilder('node');

    foreach ($languages as $language) {
      $node = $node->getTranslation($language->getId());
      // Render the node.
      $build = $node_render->view($node, 'search_index', $language->getId());

      unset($build['#theme']);
      $node->rendered = drupal_render($build);

      $text = '<h1>' . String::checkPlain($node->label($language->getId())) . '</h1>' . $node->rendered;

      // Fetch extra data normally not visible.
      $extra = $this->moduleHandler->invokeAll('node_update_index', array($node, $language->getId()));
      foreach ($extra as $t) {
        $text .= $t;
      }

      // Update index, using search index "type" equal to the plugin ID.
      search_index($this->getPluginId(), $node->id(), $language->getId(), $text);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexClear() {
    // All NodeSearch pages share a common search index "type" equal to
    // the plugin ID.
    search_index_clear($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function markForReindex() {
    // All NodeSearch pages share a common search index "type" equal to
    // the plugin ID.
    search_mark_for_reindex($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function indexStatus() {
    $total = $this->database->query('SELECT COUNT(*) FROM {node}')->fetchField();
    $remaining = $this->database->query("SELECT COUNT(DISTINCT n.nid) FROM {node} n LEFT JOIN {search_dataset} sd ON sd.sid = n.nid AND sd.type = :type WHERE sd.sid IS NULL OR sd.reindex <> 0", array(':type' => $this->getPluginId()))->fetchField();

    return array('remaining' => $remaining, 'total' => $total);
  }

  /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, FormStateInterface $form_state) {
    // Add advanced search keyword-related boxes.
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => t('Advanced search'),
      '#attributes' => array('class' => array('search-advanced')),
      '#access' => $this->account && $this->account->hasPermission('use advanced search'),
    );
    $form['advanced']['keywords-fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Keywords'),
    );
    $form['advanced']['keywords'] = array(
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
    );
    $form['advanced']['keywords-fieldset']['keywords']['or'] = array(
      '#type' => 'textfield',
      '#title' => t('Containing any of the words'),
      '#size' => 30,
      '#maxlength' => 255,
    );
    $form['advanced']['keywords-fieldset']['keywords']['phrase'] = array(
      '#type' => 'textfield',
      '#title' => t('Containing the phrase'),
      '#size' => 30,
      '#maxlength' => 255,
    );
    $form['advanced']['keywords-fieldset']['keywords']['negative'] = array(
      '#type' => 'textfield',
      '#title' => t('Containing none of the words'),
      '#size' => 30,
      '#maxlength' => 255,
    );

    // Add node types.
    $types = array_map(array('\Drupal\Component\Utility\String', 'checkPlain'), node_type_get_names());
    $form['advanced']['types-fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Types'),
    );
    $form['advanced']['types-fieldset']['type'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Only of the type(s)'),
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
      '#options' => $types,
    );
    $form['advanced']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Advanced search'),
      '#prefix' => '<div class="action">',
      '#suffix' => '</div>',
      '#weight' => 100,
    );

    // Add languages.
    $language_options = array();
    $language_list = \Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    foreach ($language_list as $langcode => $language) {
      // Make locked languages appear special in the list.
      $language_options[$langcode] = $language->isLocked() ? t('- @name -', array('@name' => $language->getName())) : $language->getName();
    }
    if (count($language_options) > 1) {
      $form['advanced']['lang-fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => t('Languages'),
      );
      $form['advanced']['lang-fieldset']['language'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Languages'),
        '#prefix' => '<div class="criterion">',
        '#suffix' => '</div>',
        '#options' => $language_options,
      );
    }
  }

  /*
   * {@inheritdoc}
   */
  public function buildSearchUrlQuery(FormStateInterface $form_state) {
    // Read keyword and advanced search information from the form values,
    // and put these into the GET parameters.
    $keys = trim($form_state->getValue('keys'));

    // Collect extra filters.
    $filters = array();
    if ($form_state->hasValue('type') && is_array($form_state->getValue('type'))) {
      // Retrieve selected types - Form API sets the value of unselected
      // checkboxes to 0.
      foreach ($form_state->getValue('type') as $type) {
        if ($type) {
          $filters[] = 'type:' . $type;
        }
      }
    }

    if ($form_state->hasValue('term') && is_array($form_state->getValue('term'))) {
      foreach ($form_state->getValue('term') as $term) {
        $filters[] = 'term:' . $term;
      }
    }
    if ($form_state->hasValue('language') && is_array($form_state->getValue('language'))) {
      foreach ($form_state->getValue('language') as $language) {
        if ($language) {
          $filters[] = 'language:' . $language;
        }
      }
    }
    if ($form_state->getValue('or') != '') {
      if (preg_match_all('/ ("[^"]+"|[^" ]+)/i', ' ' . $form_state->getValue('or'), $matches)) {
        $keys .= ' ' . implode(' OR ', $matches[1]);
      }
    }
    if ($form_state->getValue('negative') != '') {
      if (preg_match_all('/ ("[^"]+"|[^" ]+)/i', ' ' . $form_state->getValue('negative'), $matches)) {
        $keys .= ' -' . implode(' -', $matches[1]);
      }
    }
    if ($form_state->getValue('phrase') != '') {
      $keys .= ' "' . str_replace('"', ' ', $form_state->getValue('phrase')) . '"';
    }
    $keys = trim($keys);

    // Put the keywords and advanced parameters into GET parameters. Make sure
    // to put keywords into the query even if it is empty, because the page
    // controller uses that to decide it's time to check for search results.
    $query = array('keys' => $keys);
    if ($filters) {
      $query['f'] = $filters;
    }

    return $query;
  }

  /**
   * Gathers ranking definitions from hook_ranking().
   *
   * @return array
   *   An array of ranking definitions.
   */
  protected function getRankings() {
    if (!$this->rankings) {
      $this->rankings = $this->moduleHandler->invokeAll('ranking');
    }
    return $this->rankings;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = array(
      'rankings' => array(),
    );
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Output form for defining rank factor weights.
    $form['content_ranking'] = array(
      '#type' => 'details',
      '#title' => t('Content ranking'),
      '#open' => TRUE,
    );
    $form['content_ranking']['info'] = array(
      '#markup' => '<p><em>' . $this->t('Influence is a numeric multiplier used in ordering search results. A higher number means the corresponding factor has more influence on search results; zero means the factor is ignored. Changing these numbers does not require the search index to be rebuilt. Changes take effect immediately.') . '</em></p>'
    );
    // Prepare table.
    $header = [$this->t('Factor'), $this->t('Influence')];
    $form['content_ranking']['rankings'] = array(
      '#type' => 'table',
      '#header' => $header,
    );

    // Note: reversed to reflect that higher number = higher ranking.
    $range = range(0, 10);
    $options = array_combine($range, $range);
    foreach ($this->getRankings() as $var => $values) {
      $form['content_ranking']['rankings'][$var]['name'] = array(
        '#markup' => $values['title'],
      );
      $form['content_ranking']['rankings'][$var]['value'] = array(
        '#type' => 'select',
        '#options' => $options,
        '#attributes' => ['aria-label' => $this->t("Influence of '@title'", ['@title' => $values['title']])],
        '#default_value' => isset($this->configuration['rankings'][$var]) ? $this->configuration['rankings'][$var] : 0,
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getRankings() as $var => $values) {
      if (!$form_state->isValueEmpty(['rankings', $var, 'value'])) {
        $this->configuration['rankings'][$var] = $form_state->getValue(['rankings', $var, 'value']);
      }
      else {
        unset($this->configuration['rankings'][$var]);
      }
    }
  }

}
