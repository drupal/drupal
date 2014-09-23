<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\row\EntityRow.
 */

namespace Drupal\views\Plugin\views\row;

use Drupal\Component\Utility\String;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generic entity row plugin to provide a common base for all entity types.
 *
 * @ViewsRow(
 *   id = "entity",
 *   deriver = "Drupal\views\Plugin\Derivative\ViewsEntityRow"
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
   * Stores the entity type ID of the result entities.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Contains the entity type of this row plugin instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The renderer to be used to render the entity row.
   *
   * @var \Drupal\views\Entity\Rendering\RendererBase
   */
  protected $renderer;

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
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->entityTypeId = $this->definition['entity_type'];
    $this->entityType = $this->entityManager->getDefinition($this->entityTypeId);
    $this->base_table = $this->entityType->getBaseTable();
    $this->base_field = $this->entityType->getKey('id');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity.manager'), $container->get('language_manager'));
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode'] = array('default' => 'default');
    // @todo Make the current language renderer the default as soon as we have a
    //   translation language filter. See https://drupal.org/node/2161845.
    $options['rendering_language'] = array('default' => 'translation_language_renderer');

    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['view_mode'] = array(
      '#type' => 'select',
      '#options' => \Drupal::entityManager()->getViewModeOptions($this->entityTypeId),
      '#title' => $this->t('View mode'),
      '#default_value' => $this->options['view_mode'],
    );

    $options = $this->buildRenderingLanguageOptions();
    $form['rendering_language'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Rendering language'),
      '#default_value' => $this->options['rendering_language'],
      '#access' => $this->languageManager->isMultilingual(),
    );
  }

  /**
   * Returns the available rendering strategies for language-aware entities.
   *
   * @return array
   *   An array of available entity row renderers keyed by renderer identifiers.
   */
  protected function buildRenderingLanguageOptions() {
    // @todo Consider making these plugins. See https://drupal.org/node/2173811.
    return array(
      'current_language_renderer' => $this->t('Current language'),
      'default_language_renderer' => $this->t('Default language'),
      'translation_language_renderer' => $this->t('Translation language'),
    );
  }

  /**
   * Overrides Drupal\views\Plugin\views\PluginBase::summaryTitle().
   */
  public function summaryTitle() {
    $options = \Drupal::entityManager()->getViewModeOptions($this->entityTypeId);
    if (isset($options[$this->options['view_mode']])) {
      return String::checkPlain($options[$this->options['view_mode']]);
    }
    else {
      return $this->t('No view mode selected');
    }
  }

  /**
   * Returns the current renderer.
   *
   * @return \Drupal\views\Entity\Render\RendererBase
   *   The configured renderer.
   */
  protected function getRenderer() {
    if (!isset($this->renderer)) {
      $class = '\Drupal\views\Entity\Render\\' . Container::camelize($this->options['rendering_language']);
      $this->renderer = new $class($this->view, $this->entityType);
    }
    return $this->renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();
    $this->getRenderer()->query($this->view->getQuery());
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($result) {
    parent::preRender($result);
    if ($result) {
      $this->getRenderer()->preRender($result);
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::render().
   */
  public function render($row) {
    return $this->getRenderer()->render($row);
  }

}
