<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\row\EntityRow.
 */

namespace Drupal\views\Plugin\views\row;

use Drupal\Core\Entity\EntityManager;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Annotation\ViewsRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generic entity row plugin to provide a common base for all entity types.
 *
 * @ViewsRow(
 *   id = "entity",
 *   derivative = "Drupal\views\Plugin\Derivative\ViewsEntityRow"
 * )
 */
class EntityRow extends RowPluginBase {

  /**
   * The table the entity is using for storage.
   *
   * @var string
   */
  public $base_table;

  /**
   * The actual field which is used for the entity id.
   *
   * @var string
   */
  public $base_field;

  /**
   * Stores the entity type of the result entities.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Contains the entity info of the entity type of this row plugin instance.
   *
   * @see entity_get_info
   */
  protected $entityInfo;

  /**
   * Contains an array of render arrays, one for each rendered entity.
   *
   * @var array
   */
  protected $build = array();

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityManager $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->entityType = $this->definition['entity_type'];
    $this->entityInfo = $this->entityManager->getDefinition($this->entityType);
    $this->base_table = $this->entityInfo['base_table'];
    $this->base_field = $this->entityInfo['entity_keys']['id'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity.manager'));
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode'] = array('default' => 'default');

    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = $this->buildViewModeOptions();
    $form['view_mode'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('View mode'),
      '#default_value' => $this->options['view_mode'],
    );
  }

  /**
   * Return the main options, which are shown in the summary title.
   */
  protected function buildViewModeOptions() {
    $options = array('default' => t('Default'));
    $view_modes = entity_get_view_modes($this->entityType);
    foreach ($view_modes as $mode => $settings) {
      $options[$mode] = $settings['label'];
    }

    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\PluginBase::summaryTitle().
   */
  public function summaryTitle() {
    $options = $this->buildViewModeOptions();
    if (isset($options[$this->options['view_mode']])) {
      return check_plain($options[$this->options['view_mode']]);
    }
    else {
      return t('No view mode selected');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($result) {
    parent::preRender($result);

    if ($result) {
      // Get all entities which will be used to render in rows.
      $entities = array();
      foreach ($result as $row) {
        $entity = $row->_entity;
        $entity->view = $this->view;
        $entities[$entity->id()] = $entity;
      }

      // Prepare the render arrays for all rows.
      $this->build = entity_view_multiple($entities, $this->options['view_mode']);
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::render().
   */
  public function render($row) {
    $entity_id = $row->{$this->field_alias};
    return $this->build[$entity_id];
  }
}
