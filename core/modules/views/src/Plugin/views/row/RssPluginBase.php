<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\views\row\RssPluginBase.
 */

namespace Drupal\views\Plugin\views\row;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Views RSS row plugins.
 */
abstract class RssPluginBase extends RowPluginBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a RssPluginBase  object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * The ID of the entity type for which this is an RSS row plugin.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode'] = array('default' => 'default');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['view_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Display type'),
      '#options' => $this->buildOptionsForm_summary_options(),
      '#default_value' => $this->options['view_mode'],
    );
  }

  /**
   * Return the main options, which are shown in the summary title.
   */
  public function buildOptionsForm_summary_options() {
    $view_modes = $this->entityManager->getViewModes($this->entityTypeId);
    $options = array();
    foreach ($view_modes as $mode => $settings) {
      $options[$mode] = $settings['label'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $view_mode = $this->entityManager
      ->getStorage('entity_view_mode')
      ->load($this->entityTypeId . '.' . $this->options['view_mode']);
    if ($view_mode) {
      $dependencies[$view_mode->getConfigDependencyKey()][] = $view_mode->getConfigDependencyName();
    }

    return $dependencies;
  }

}
