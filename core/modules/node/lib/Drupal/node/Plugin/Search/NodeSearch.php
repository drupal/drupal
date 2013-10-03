<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Search\NodeSearch.
 */

namespace Drupal\node\Plugin\Search;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\AccessibleInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\search\Annotation\SearchPlugin;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\search\Plugin\SearchIndexingInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching for node entities using the Search module index.
 *
 * @SearchPlugin(
 *   id = "node_search",
 *   title = @Translation("Content"),
 *   path = "node"
 * )
 */
class NodeSearch extends SearchPluginBase implements AccessibleInterface, SearchIndexingInterface, PluginFormInterface {

  /**
   * A database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * An entity manager object.
   *
   * @var \Drupal\Core\Entity\EntityManager
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
   * The Drupal state object used to set 'node.cron_last'.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $state;

  /**
   * The Drupal account to use for checking for access to advanced search.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

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
    'langcode' => array('column' => 'n.langcode'),
    'author' => array('column' => 'n.uid'),
    'term' => array('column' => 'ti.tid', 'join' => array('table' => 'taxonomy_index', 'alias' => 'ti', 'condition' => 'n.nid = ti.nid')),
  );

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('plugin.manager.entity'),
      $container->get('module_handler'),
      $container->get('config.factory')->get('search.settings'),
      $container->get('keyvalue')->get('state'),
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
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection object.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   An entity manager object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module manager object.
   * @param \Drupal\Core\Config\Config $search_settings
   *   A config object for 'search.settings'.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $state
   *   The Drupal state object used to set 'node.cron_last'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The $account object to use for checking for access to advanced search.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $database, EntityManager $entity_manager, ModuleHandlerInterface $module_handler, Config $search_settings, KeyValueStoreInterface $state, AccountInterface $account = NULL) {
    $this->database = $database;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->searchSettings = $search_settings;
    $this->state = $state;
    $this->account = $account;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL) {
    return !empty($account) && $account->hasPermission('access content');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $results = array();
    if (!$this->isSearchExecutable()) {
      return $results;
    }
    $keys = $this->keywords;

    // Build matching conditions.
    $query = $this->database
      ->select('search_index', 'i', array('target' => 'slave'))
      ->extend('Drupal\search\SearchQuery')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->join('node_field_data', 'n', 'n.nid = i.sid');
    $query->condition('n.status', 1)
      ->addTag('node_access')
      ->searchExpression($keys, $this->getPluginId());

    // Handle advanced search filters in the f query string.
    // $_GET['f'] is an array that looks like this in the URL:
    // ?f[]=type:page&f[]=term:27&f[]=term:13&f[]=langcode:en
    // So $parameters['f'] looks like:
    // array('type:page', 'term:27', 'term:13', 'langcode:en');
    // We need to parse this out into query conditions.
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
    // Only continue if the first pass query matches.
    if (!$query->executeFirstPass()) {
      return array();
    }

    // Add the ranking expressions.
    $this->addNodeRankings($query);

    // Load results.
    $find = $query
      // Add the language code of the indexed item to the result of the query,
      // since the node will be rendered using the respective language.
      ->fields('i', array('langcode'))
      ->limit(10)
      ->execute();

    $node_storage = $this->entityManager->getStorageController('node');
    $node_render = $this->entityManager->getRenderController('node');

    foreach ($find as $item) {
      // Render the node.
      $node = $node_storage->load($item->sid);
      $build = $node_render->view($node, 'search_result', $item->langcode);
      unset($build['#theme']);
      $node->rendered = drupal_render($build);

      // Fetch comment count for snippet.
      $node->rendered .= ' ' . $this->moduleHandler->invoke('comment', 'node_update_index', array($node, $item->langcode));

      $extra = $this->moduleHandler->invokeAll('node_search_result', array($node, $item->langcode));

      $language = language_load($item->langcode);
      $uri = $node->uri();
      $username = array(
        '#theme' => 'username',
        '#account' => $node->getAuthor(),
      );
      $results[] = array(
        'link' => url($uri['path'], array_merge($uri['options'], array('absolute' => TRUE, 'language' => $language))),
        'type' => check_plain($this->entityManager->getStorageController('node_type')->load($node->bundle())->label()),
        'title' => $node->label($item->langcode),
        'user' => drupal_render($username),
        'date' => $node->getChangedTime(),
        'node' => $node,
        'extra' => $extra,
        'score' => $item->calculated_score,
        'snippet' => search_excerpt($keys, $node->rendered, $item->langcode),
        'langcode' => $node->language()->id,
      );
    }
    return $results;
  }

  /**
   * Gathers the rankings from the the hook_ranking() implementations.
   *
   * @param $query
   *   A query object that has been extended with the Search DB Extender.
   */
  protected function addNodeRankings(SelectExtender $query) {
    if ($ranking = $this->moduleHandler->invokeAll('ranking')) {
      $tables = &$query->getTables();
      foreach ($ranking as $rank => $values) {
        // @todo - move rank out of drupal variables.
        if ($node_rank = variable_get('node_rank_' . $rank, 0)) {
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
    $limit = (int) $this->searchSettings->get('index.cron_limit');

    $result = $this->database->queryRange("SELECT DISTINCT n.nid FROM {node} n LEFT JOIN {search_dataset} d ON d.type = :type AND d.sid = n.nid WHERE d.sid IS NULL OR d.reindex <> 0 ORDER BY d.reindex ASC, n.nid ASC", 0, $limit, array(':type' => $this->getPluginId()), array('target' => 'slave'));
    $nids = $result->fetchCol();
    if (!$nids) {
      return;
    }

    // The indexing throttle should be aware of the number of language variants
    // of a node.
    $counter = 0;
    $node_storage = $this->entityManager->getStorageController('node');
    foreach ($node_storage->loadMultiple($nids) as $node) {
      // Determine when the maximum number of indexable items is reached.
      $counter += count($node->getTranslationLanguages());
      if ($counter > $limit) {
        break;
      }
      $this->indexNode($node);
    }
  }

  /**
   * Indexes a single node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node to index.
   */
  protected function indexNode(EntityInterface $node) {
    // Save the changed time of the most recent indexed node, for the search
    // results half-life calculation.
    $this->state->set('node.cron_last', $node->getChangedTime());

    $languages = $node->getTranslationLanguages();
    $node_render = $this->entityManager->getRenderController('node');

    foreach ($languages as $language) {
      // Render the node.
      $build = $node_render->view($node, 'search_index', $language->id);

      unset($build['#theme']);
      $node->rendered = drupal_render($build);

      $text = '<h1>' . check_plain($node->label($language->id)) . '</h1>' . $node->rendered;

      // Fetch extra data normally not visible.
      $extra = $this->moduleHandler->invokeAll('node_update_index', array($node, $language->id));
      foreach ($extra as $t) {
        $text .= $t;
      }

      // Update index.
      search_index($node->id(), $this->getPluginId(), $text, $language->id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetIndex() {
    $this->database->update('search_dataset')
      ->fields(array('reindex' => REQUEST_TIME))
      ->condition('type', $this->getPluginId())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function indexStatus() {
    $total = $this->database->query('SELECT COUNT(*) FROM {node}')->fetchField();
    $remaining = $this->database->query("SELECT COUNT(*) FROM {node} n LEFT JOIN {search_dataset} d ON d.type = :type AND d.sid = n.nid WHERE d.sid IS NULL OR d.reindex <> 0", array(':type' => $this->getPluginId()))->fetchField();
    return array('remaining' => $remaining, 'total' => $total);
  }

  /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, array &$form_state) {
    // Add keyword boxes.
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => t('Advanced search'),
      '#collapsed' => TRUE,
      '#attributes' => array('class' => array('search-advanced')),
      '#access' => $this->account && $this->account->hasPermission('use advanced search'),
    );
    $form['advanced']['keywords-fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Keywords'),
      '#collapsible' => FALSE,
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
    $node_types = $this->entityManager->getStorageController('node_type')->loadMultiple();
    $types = array_map('check_plain', node_type_get_names());
    $form['advanced']['types-fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Types'),
      '#collapsible' => FALSE,
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
    foreach (language_list(Language::STATE_ALL) as $langcode => $language) {
      // Make locked languages appear special in the list.
      $language_options[$langcode] = $language->locked ? t('- @name -', array('@name' => $language->name)) : $language->name;
    }
    if (count($language_options) > 1) {
      $form['advanced']['lang-fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => t('Languages'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
      );
      $form['advanced']['lang-fieldset']['language'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Languages'),
        '#prefix' => '<div class="criterion">',
        '#suffix' => '</div>',
        '#options' => $language_options,
      );
    }

    // Add a submit handler.
    $form['#submit'][] = array($this, 'searchFormSubmit');
  }

  /**
   * Handles submission of elements added in searchFormAlter().
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param array $form_state
   *   A keyed array containing the current state of the form.
   */
  public function searchFormSubmit(array &$form, array &$form_state) {
    // Initialize using any existing basic search keywords.
    $keys = $form_state['values']['processed_keys'];
    $filters = array();

    // Collect extra restrictions.
    if (isset($form_state['values']['type']) && is_array($form_state['values']['type'])) {
      // Retrieve selected types - Form API sets the value of unselected
      // checkboxes to 0.
      foreach ($form_state['values']['type'] as $type) {
        if ($type) {
          $filters[] = 'type:' . $type;
        }
      }
    }

    if (isset($form_state['values']['term']) && is_array($form_state['values']['term'])) {
      foreach ($form_state['values']['term'] as $term) {
        $filters[] = 'term:' . $term;
      }
    }
    if (isset($form_state['values']['language']) && is_array($form_state['values']['language'])) {
      foreach ($form_state['values']['language'] as $language) {
        if ($language) {
          $filters[] = 'language:' . $language;
        }
      }
    }
    if ($form_state['values']['or'] != '') {
      if (preg_match_all('/ ("[^"]+"|[^" ]+)/i', ' ' . $form_state['values']['or'], $matches)) {
        $keys .= ' ' . implode(' OR ', $matches[1]);
      }
    }
    if ($form_state['values']['negative'] != '') {
      if (preg_match_all('/ ("[^"]+"|[^" ]+)/i', ' ' . $form_state['values']['negative'], $matches)) {
        $keys .= ' -' . implode(' -', $matches[1]);
      }
    }
    if ($form_state['values']['phrase'] != '') {
      $keys .= ' "' . str_replace('"', ' ', $form_state['values']['phrase']) . '"';
    }
    if (!empty($keys)) {
      form_set_value($form['basic']['processed_keys'], trim($keys), $form_state);
    }
    $path = $form_state['action'] . '/' . $keys;
    $options = array();
    if ($filters) {
      $options['query'] = array('f' => $filters);
    }

    $form_state['redirect'] = array($path, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    // Output form for defining rank factor weights.
    $form['content_ranking'] = array(
      '#type' => 'details',
      '#title' => t('Content ranking'),
    );
    $form['content_ranking']['#theme'] = 'node_search_admin';
    $form['content_ranking']['info'] = array(
      '#value' => '<em>' . t('The following numbers control which properties the content search should favor when ordering the results. Higher numbers mean more influence, zero means the property is ignored. Changing these numbers does not require the search index to be rebuilt. Changes take effect immediately.') . '</em>'
    );

    // Note: reversed to reflect that higher number = higher ranking.
    $options = drupal_map_assoc(range(0, 10));
    foreach ($this->moduleHandler->invokeAll('ranking') as $var => $values) {
      $form['content_ranking']['factors']['node_rank_' . $var] = array(
        '#title' => $values['title'],
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => variable_get('node_rank_' . $var, 0),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    foreach ($this->moduleHandler->invokeAll('ranking') as $var => $values) {
      if (isset($form_state['values']['node_rank_' . $var])) {
        // @todo Fix when https://drupal.org/node/1831632 is in.
        variable_set('node_rank_' . $var, $form_state['values']['node_rank_' . $var]);
      }
    }
  }

}
