<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter to show only the latest translation affected revision of an entity.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("latest_translation_affected_revision")]
class LatestTranslationAffectedRevision extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Views Handler Plugin Manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinHandler;

  /**
   * Constructs a new LatestRevisionTranslationAffected.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager Service.
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_handler
   *   Views Handler Plugin Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ViewsHandlerManager $join_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->joinHandler = $join_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.views.join')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    $query_base_table = $this->relationship ?: $this->view->storage->get('base_table');

    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityType());
    $keys = $entity_type->getKeys();

    $subquery = $query->getConnection()->select($query_base_table, 'base_table');
    $subquery->addExpression("MAX(base_table.{$keys['revision']})", $keys['revision']);
    $subquery->fields('base_table', [$keys['id'], 'langcode']);
    $subquery->groupBy("base_table.{$keys['id']}");
    $subquery->groupBy('base_table.langcode');
    $subquery->condition('base_table.revision_translation_affected', '1');

    $definition = [
      'table formula' => $subquery,
      'type' => 'INNER',
      'field' => $keys['id'],
      'left_table' => $query_base_table,
      'left_field' => $keys['id'],
      'extra' => [
        ['left_field' => $keys['revision'], 'field' => $keys['revision'], 'operator' => '='],
        ['left_field' => 'langcode', 'field' => 'langcode', 'operator' => '='],
      ],
    ];

    $join = $this->joinHandler->createInstance('standard', $definition);
    $query->addTable($query_base_table, $this->relationship, $join);
    $query->addWhere($this->options['group'], "$query_base_table.revision_translation_affected", '1', '=');
  }

}
