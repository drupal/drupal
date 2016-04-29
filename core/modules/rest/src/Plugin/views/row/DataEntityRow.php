<?php

namespace Drupal\rest\Plugin\views\row;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin which displays entities as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "data_entity",
 *   title = @Translation("Entity"),
 *   help = @Translation("Use entities as row data."),
 *   display_types = {"data"}
 * )
 */
class DataEntityRow extends RowPluginBase {

  use EntityTranslationRenderTrait;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = FALSE;

  /**
   * Contains the entity type of this row plugin instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  public $entityManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity.manager'), $container->get('language_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    return $this->getEntityTranslation($row->_entity, $row);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->view->getBaseEntityType()->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityManager() {
    return $this->entityManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getView() {
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();
    $this->getEntityTranslationRenderer()->query($this->view->getQuery());
  }

}
