<?php

namespace Drupal\node\Plugin\Search;

use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search\SearchIndexInterface;
use Drupal\search_node\Plugin\Search\SearchNode as CoreSearchNode;

/**
 * Handles searching for node entities using the Search module index.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Instead, use
 *   \Drupal\search_node\Plugin\Search\SearchNode.
 *
 * @see https://www.drupal.org/node/3590298
 */
class NodeSearch extends CoreSearchNode {

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
    parent::__construct($configuration, $plugin_id, $plugin_definition, $this->database, $this->entityTypeManager, $this->moduleHandler, $this->searchSettings, $this->languageManager, $this->renderer, $messenger, $this->account, $this->databaseReplica, $this->searchIndex, $this->entityTypeBundleInfo);
    $this->setMessenger($messenger);
    $this->addCacheTags(['node_list']);
  }

}
