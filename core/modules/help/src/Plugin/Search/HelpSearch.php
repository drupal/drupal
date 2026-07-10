<?php

namespace Drupal\help\Plugin\Search;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\help\HelpSectionManager;
use Drupal\search\Attribute\Search;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\search\SearchIndexInterface;
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
 * @internal
 *   Plugin classes are internal.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Instead, use
 *   \Drupal\search_help\Plugin\Search\HelpSearch.
 * @see https://www.drupal.org/node/3581109
 */
#[Search(
  id: 'help_search',
  title: new TranslatableMarkup('Help'),
  use_admin_theme: TRUE,
)]
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
   *   The plugin ID for the plugin instance.
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
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Instead, use \Drupal\search_help\Plugin\Search\HelpSearch. See https://www.drupal.org/node/3581109', E_USER_DEPRECATED);
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
  public function access($operation = 'view', ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
  }

  /**
   * {@inheritdoc}
   */
  public function indexClear() {
  }

  /**
   * {@inheritdoc}
   */
  public function markForReindex() {
  }

  /**
   * {@inheritdoc}
   */
  public function indexStatus() {
    return [];
  }

}
