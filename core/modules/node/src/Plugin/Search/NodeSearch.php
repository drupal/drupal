<?php

namespace Drupal\node\Plugin\Search;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search\Attribute\Search;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Drupal\search\SearchIndexInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching for node entities using the Search module index.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Instead, use
 *   \Drupal\search_node\Plugin\Search\SearchNode.
 * @see https://www.drupal.org/node/3590298
 */
#[Search(
  id: 'node_search',
  title: new TranslatableMarkup('Content'),
)]
class NodeSearch extends ConfigurableSearchPluginBase implements AccessibleInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('config.factory')->get('search.settings'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('database.replica'),
      $container->get('search.index'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * Constructs a \Drupal\node\Plugin\Search\NodeSearch object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected Connection $database,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected Config $searchSettings,
    protected LanguageManagerInterface $languageManager,
    protected RendererInterface $renderer,
    MessengerInterface $messenger,
    protected AccountInterface $account,
    protected Connection $databaseReplica,
    protected SearchIndexInterface $searchIndex,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
  ) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Instead, use \Drupal\search_node\Plugin\Search\SearchNode. See https://www.drupal.org/node/3590298', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setMessenger($messenger);
    $this->addCacheTags(['node_list']);
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
  public function isSearchExecutable() {
    return FALSE;
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

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
