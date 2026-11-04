<?php

namespace Drupal\views\Plugin\views\row;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for Views RSS row plugins.
 */
abstract class RssPluginBase extends RowPluginBase {

  /**
   * A fake view mode to only display titles.
   */
  const string TITLE_VIEW_MODE = '_views.rss.title';

  /**
   * Constructs a RssPluginBase  object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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

    // Select the rss view mode by default, otherwise select the first available
    // view mode.
    $view_modes = $this->entityDisplayRepository->getViewModes($this->entityTypeId);
    if (isset($view_modes['rss'])) {
      $options['view_mode'] = ['default' => 'rss'];
    }
    else {
      $options['view_mode'] = ['default' => key($view_modes)];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display type'),
      '#options' => $this->buildOptionsForm_summary_options(),
      '#default_value' => $this->options['view_mode'],
    ];
  }

  /**
   * Return the main options, which are shown in the summary title.
   */
  public function buildOptionsForm_summary_options() {
    $view_modes = $this->entityDisplayRepository->getViewModes($this->entityTypeId);
    $options = [];
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

    $view_mode = $this->entityTypeManager
      ->getStorage('entity_view_mode')
      ->load($this->entityTypeId . '.' . $this->options['view_mode']);
    if ($view_mode) {
      $dependencies[$view_mode->getConfigDependencyKey()][] = $view_mode->getConfigDependencyName();
    }

    return $dependencies;
  }

}
