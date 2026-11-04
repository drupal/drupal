<?php

namespace Drupal\Core\Menu\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a "Tabs" block to display the local tasks.
 */
#[Block(
  id: "local_tasks_block",
  admin_label: new TranslatableMarkup("Tabs")
)]
class LocalTasksBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Creates a LocalTasksBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $localTaskManager
   *   The local task manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LocalTaskManagerInterface $localTaskManager,
    protected RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => '0',
      'primary' => TRUE,
      'secondary' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration;
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($this->localTaskManager);
    // If the current route belongs to an entity, include cache tags of that
    // entity as well.
    $route_parameters = $this->routeMatch->getParameters()->all();
    foreach ($route_parameters as $parameter) {
      if ($parameter instanceof CacheableDependencyInterface) {
        $cacheability->addCacheableDependency($parameter);
      }
    }

    $tabs = [
      '#theme' => 'menu_local_tasks',
    ];

    // Add only selected levels for the printed output.
    if ($config['primary']) {
      $links = $this->localTaskManager->getLocalTasks($this->routeMatch->getRouteName(), 0);
      $cacheability = $cacheability->merge($links['cacheability']);
      // Do not display single tabs.
      $tabs += [
        '#primary' => count(Element::getVisibleChildren($links['tabs'])) > 1 ? $links['tabs'] : [],
      ];
    }
    if ($config['secondary']) {
      $links = $this->localTaskManager->getLocalTasks($this->routeMatch->getRouteName(), 1);
      $cacheability = $cacheability->merge($links['cacheability']);
      // Do not display single tabs.
      $tabs += [
        '#secondary' => count(Element::getVisibleChildren($links['tabs'])) > 1 ? $links['tabs'] : [],
      ];
    }

    $build = [];
    $cacheability->applyTo($build);
    if (empty($tabs['#primary']) && empty($tabs['#secondary'])) {
      return $build;
    }

    return $build + $tabs;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;
    $defaults = $this->defaultConfiguration();

    $form['levels'] = [
      '#type' => 'details',
      '#title' => $this->t('Shown tabs'),
      '#description' => $this->t('Select tabs being shown in the block'),
      // Open if not set to defaults.
      '#open' => $defaults['primary'] !== $config['primary'] || $defaults['secondary'] !== $config['secondary'],
    ];
    $form['levels']['primary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show primary tabs'),
      '#default_value' => $config['primary'],
    ];
    $form['levels']['secondary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show secondary tabs'),
      '#default_value' => $config['secondary'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $levels = $form_state->getValue('levels');
    $this->configuration['primary'] = $levels['primary'];
    $this->configuration['secondary'] = $levels['secondary'];
  }

  /**
   * {@inheritdoc}
   */
  public function createPlaceholder(): bool {
    return TRUE;
  }

}
