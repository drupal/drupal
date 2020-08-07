<?php

namespace Drupal\help_topics\Plugin\Search;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\help\HelpSectionManager;
use Drupal\help_topics\SearchableHelpInterface;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\search\SearchIndexInterface;
use Drupal\search\SearchQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching for help using the Search module index.
 *
 * Help items are indexed if their HelpSection plugin implements
 * \Drupal\help\HelpSearchInterface.
 *
 * @see \Drupal\help\HelpSearchInterface
 * @see \Drupal\help\HelpSectionPluginInterface
 *
 * @SearchPlugin(
 *   id = "help_search",
 *   title = @Translation("Help"),
 *   use_admin_theme = TRUE,
 * )
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class HelpSearch extends SearchPluginBase implements AccessibleInterface, SearchIndexingInterface {

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * A config object for 'search.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $searchSettings;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Drupal account to use for checking for access to search.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The state object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The help section plugin manager.
   *
   * @var \Drupal\help\HelpSectionManager
   */
  protected $helpSectionManager;

  /**
   * The search index.
   *
   * @var \Drupal\search\SearchIndexInterface
   */
  protected $searchIndex;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('config.factory')->get('search.settings'),
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('state'),
      $container->get('plugin.manager.help_section'),
      $container->get('search.index')
    );
  }

  /**
   * Constructs a \Drupal\help_search\Plugin\Search\HelpSearch object.
   *
   * @param array $configuration
   *   Configuration for the plugin.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The current database connection.
   * @param \Drupal\Core\Config\Config $search_settings
   *   A config object for 'search.settings'.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The $account object to use for checking for access to view help.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state object.
   * @param \Drupal\help\HelpSectionManager $help_section_manager
   *   The help section manager.
   * @param \Drupal\search\SearchIndexInterface $search_index
   *   The search index.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, Config $search_settings, LanguageManagerInterface $language_manager, MessengerInterface $messenger, AccountInterface $account, StateInterface $state, HelpSectionManager $help_section_manager, SearchIndexInterface $search_index) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->searchSettings = $search_settings;
    $this->languageManager = $language_manager;
    $this->messenger = $messenger;
    $this->account = $account;
    $this->state = $state;
    $this->helpSectionManager = $help_section_manager;
    $this->searchIndex = $search_index;
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIfHasPermission($account, 'access administration pages');
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getPluginId();
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

    return [];
  }

  /**
   * Finds the search results.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Results from search query execute() method, or NULL if the search
   *   failed.
   */
  protected function findResults() {
    // We need to check access for the current user to see the topics that
    // could be returned by search. Each entry in the help_search_items
    // database has an optional permission that comes from the HelpSection
    // plugin, in addition to the generic 'access administration pages'
    // permission. In order to enforce these permissions so only topics that
    // the current user has permission to view are selected by the query, make
    // a list of the permission strings and pre-check those permissions.
    $this->addCacheContexts(['user.permissions']);
    if (!$this->account->hasPermission('access administration pages')) {
      return NULL;
    }
    $permissions = $this->database
      ->select('help_search_items', 'hsi')
      ->distinct()
      ->fields('hsi', ['permission'])
      ->condition('permission', '', '<>')
      ->execute()
      ->fetchCol();
    $denied_permissions = array_filter($permissions, function ($permission) {
      return !$this->account->hasPermission($permission);
    });

    $query = $this->database
      ->select('search_index', 'i')
      // Restrict the search to the current interface language.
      ->condition('i.langcode', $this->languageManager->getCurrentLanguage()->getId())
      ->extend(SearchQuery::class)
      ->extend(PagerSelectExtender::class);
    $query->innerJoin('help_search_items', 'hsi', 'i.sid = hsi.sid AND i.type = :type', [':type' => $this->getType()]);
    if ($denied_permissions) {
      $query->condition('hsi.permission', $denied_permissions, 'NOT IN');
    }
    $query->searchExpression($this->getKeywords(), $this->getType());

    $find = $query
      ->fields('i', ['langcode'])
      ->fields('hsi', ['section_plugin_id', 'topic_id'])
      // Since SearchQuery makes these into GROUP BY queries, if we add
      // a field, for PostgreSQL we also need to make it an aggregate or a
      // GROUP BY. In this case, we want GROUP BY.
      ->groupBy('i.langcode')
      ->groupBy('hsi.section_plugin_id')
      ->groupBy('hsi.topic_id')
      ->limit(10)
      ->execute();

    // Check query status and set messages if needed.
    $status = $query->getStatus();

    if ($status & SearchQuery::EXPRESSIONS_IGNORED) {
      $this->messenger->addWarning($this->t('Your search used too many AND/OR expressions. Only the first @count terms were included in this search.', ['@count' => $this->searchSettings->get('and_or_limit')]));
    }

    if ($status & SearchQuery::LOWER_CASE_OR) {
      $this->messenger->addWarning($this->t('Search for either of the two terms with uppercase <strong>OR</strong>. For example, <strong>cats OR dogs</strong>.'));
    }

    if ($status & SearchQuery::NO_POSITIVE_KEYWORDS) {
      $this->messenger->addWarning($this->formatPlural($this->searchSettings->get('index.minimum_word_size'), 'You must include at least one keyword to match in the content, and punctuation is ignored.', 'You must include at least one keyword to match in the content. Keywords must be at least @count characters, and punctuation is ignored.'));
    }

    return $find;
  }

  /**
   * Prepares search results for display.
   *
   * @param \Drupal\Core\Database\StatementInterface $found
   *   Results found from a successful search query execute() method.
   *
   * @return array
   *   List of search result render arrays, with links, snippets, etc.
   */
  protected function prepareResults(StatementInterface $found) {
    $results = [];
    $plugins = [];
    $languages = [];
    $keys = $this->getKeywords();
    foreach ($found as $item) {
      $section_plugin_id = $item->section_plugin_id;
      if (!isset($plugins[$section_plugin_id])) {
        $plugins[$section_plugin_id] = $this->getSectionPlugin($section_plugin_id);
      }
      if ($plugins[$section_plugin_id]) {
        $langcode = $item->langcode;
        if (!isset($languages[$langcode])) {
          $languages[$langcode] = $this->languageManager->getLanguage($item->langcode);
        }
        $topic = $plugins[$section_plugin_id]->renderTopicForSearch($item->topic_id, $languages[$langcode]);
        if ($topic) {
          if (isset($topic['cacheable_metadata'])) {
            $this->addCacheableDependency($topic['cacheable_metadata']);
          }
          $results[] = [
            'title' => $topic['title'],
            'link' => $topic['url']->toString(),
            'snippet' => search_excerpt($keys, $topic['title'] . ' ' . $topic['text'], $item->langcode),
            'langcode' => $item->langcode,
          ];
        }
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    // Update the list of items to be indexed.
    $this->updateTopicList();

    // Find some items that need to be updated. Start with ones that have
    // never been indexed.
    $limit = (int) $this->searchSettings->get('index.cron_limit');

    $query = $this->database->select('help_search_items', 'hsi');
    $query->fields('hsi', ['sid', 'section_plugin_id', 'topic_id']);
    $query->leftJoin('search_dataset', 'sd', 'sd.sid = hsi.sid AND sd.type = :type', [':type' => $this->getType()]);
    $query->where('sd.sid IS NULL');
    $query->groupBy('hsi.sid')
      ->groupBy('hsi.section_plugin_id')
      ->groupBy('hsi.topic_id')
      ->range(0, $limit);
    $items = $query->execute()->fetchAll();

    // If there is still space in the indexing limit, index items that have
    // been indexed before, but are currently marked as needing a re-index.
    if (count($items) < $limit) {
      $query = $this->database->select('help_search_items', 'hsi');
      $query->fields('hsi', ['sid', 'section_plugin_id', 'topic_id']);
      $query->leftJoin('search_dataset', 'sd', 'sd.sid = hsi.sid AND sd.type = :type', [':type' => $this->getType()]);
      $query->condition('sd.reindex', 0, '<>');
      $query->groupBy('hsi.sid')
        ->groupBy('hsi.section_plugin_id')
        ->groupBy('hsi.topic_id')
        ->range(0, $limit - count($items));
      $items = $items + $query->execute()->fetchAll();
    }

    // Index the items we have chosen, in all available languages.
    $language_list = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
    $section_plugins = [];

    $words = [];
    try {
      foreach ($items as $item) {
        $section_plugin_id = $item->section_plugin_id;
        if (!isset($section_plugins[$section_plugin_id])) {
          $section_plugins[$section_plugin_id] = $this->getSectionPlugin($section_plugin_id);
        }

        if (!$section_plugins[$section_plugin_id]) {
          $this->removeItemsFromIndex($item->sid);
          continue;
        }

        $section_plugin = $section_plugins[$section_plugin_id];
        $this->searchIndex->clear($this->getType(), $item->sid);
        foreach ($language_list as $langcode => $language) {
          $topic = $section_plugin->renderTopicForSearch($item->topic_id, $language);
          if ($topic) {
            // Index the title plus body text.
            $text = '<h1>' . $topic['title'] . '</h1>' . "\n" . $topic['text'];
            $words += $this->searchIndex->index($this->getType(), $item->sid, $langcode, $text, FALSE);
          }
        }
      }
    }
    finally {
      $this->searchIndex->updateWordWeights($words);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexClear() {
    $this->searchIndex->clear($this->getType());
  }

  /**
   * Rebuilds the database table containing topics to be indexed.
   */
  public function updateTopicList() {
    // Start by fetching the existing list, so we can remove items not found
    // at the end.
    $old_list = $this->database->select('help_search_items', 'hsi')
      ->fields('hsi', ['sid', 'topic_id', 'section_plugin_id', 'permission'])
      ->execute();
    $old_list_ordered = [];
    $sids_to_remove = [];
    foreach ($old_list as $item) {
      $old_list_ordered[$item->section_plugin_id][$item->topic_id] = $item;
      $sids_to_remove[$item->sid] = $item->sid;
    }

    $section_plugins = $this->helpSectionManager->getDefinitions();
    foreach ($section_plugins as $section_plugin_id => $section_plugin_definition) {
      $plugin = $this->getSectionPlugin($section_plugin_id);
      if (!$plugin) {
        continue;
      }
      $permission = $section_plugin_definition['permission'] ?? '';
      foreach ($plugin->listSearchableTopics() as $topic_id) {
        if (isset($old_list_ordered[$section_plugin_id][$topic_id])) {
          $old_item = $old_list_ordered[$section_plugin_id][$topic_id];
          if ($old_item->permission == $permission) {
            // Record has not changed.
            unset($sids_to_remove[$old_item->sid]);
            continue;
          }

          // Permission has changed, update record.
          $this->database->update('help_search_items')
            ->condition('sid', $old_item->sid)
            ->fields(['permission' => $permission])
            ->execute();
          unset($sids_to_remove[$old_item->sid]);
          continue;
        }

        // New record, create it.
        $this->database->insert('help_search_items')
          ->fields([
            'section_plugin_id' => $section_plugin_id,
            'permission' => $permission,
            'topic_id' => $topic_id,
          ])
          ->execute();
      }
    }

    // Remove remaining items from the index.
    $this->removeItemsFromIndex($sids_to_remove);
  }

  /**
   * {@inheritdoc}
   */
  public function markForReindex() {
    $this->updateTopicList();
    $this->searchIndex->markForReindex($this->getType());
  }

  /**
   * {@inheritdoc}
   */
  public function indexStatus() {
    $this->updateTopicList();
    $total = $this->database->select('help_search_items', 'hsi')
      ->countQuery()
      ->execute()
      ->fetchField();

    $query = $this->database->select('help_search_items', 'hsi');
    $query->addExpression('COUNT(DISTINCT(hsi.sid))');
    $query->leftJoin('search_dataset', 'sd', 'hsi.sid = sd.sid AND sd.type = :type', [':type' => $this->getType()]);
    $condition = $this->database->condition('OR');
    $condition->condition('sd.reindex', 0, '<>')
      ->isNull('sd.sid');
    $query->condition($condition);
    $remaining = $query->execute()->fetchField();

    return [
      'remaining' => $remaining,
      'total' => $total,
    ];
  }

  /**
   * Removes an item or items from the search index.
   *
   * @param int|int[] $sids
   *   Search ID (sid) of item or items to remove.
   */
  protected function removeItemsFromIndex($sids) {
    $sids = (array) $sids;

    // Remove items from our table in batches of 100, to avoid problems
    // with having too many placeholders in database queries.
    foreach (array_chunk($sids, 100) as $this_list) {
      $this->database->delete('help_search_items')
        ->condition('sid', $this_list, 'IN')
        ->execute();
    }
    // Remove items from the search tables individually, as there is no bulk
    // function to delete items from the search index.
    foreach ($sids as $sid) {
      $this->searchIndex->clear($this->getType(), $sid);
    }
  }

  /**
   * Instantiates a help section plugin and verifies it is searchable.
   *
   * @param string $section_plugin_id
   *   Type of plugin to instantiate.
   *
   * @return \Drupal\help_topics\SearchableHelpInterface|false
   *   Plugin object, or FALSE if it is not searchable.
   */
  protected function getSectionPlugin($section_plugin_id) {
    /** @var \Drupal\help\HelpSectionPluginInterface $section_plugin */
    $section_plugin = $this->helpSectionManager->createInstance($section_plugin_id);
    // Intentionally return boolean to allow caching of results.
    return $section_plugin instanceof SearchableHelpInterface ? $section_plugin : FALSE;
  }

}
