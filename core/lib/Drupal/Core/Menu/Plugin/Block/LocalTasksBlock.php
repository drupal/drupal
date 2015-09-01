<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\Plugin\Block\LocalTasksBlock.
 */

namespace Drupal\Core\Menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Tabs" block to display the local tasks.
 *
 * @Block(
 *   id = "local_tasks_block",
 *   admin_label = @Translation("Tabs"),
 * )
 */
class LocalTasksBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The local task manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Creates a LocalTasksBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $local_task_manager
   *   The local task manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LocalTaskManagerInterface $local_task_manager, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->localTaskManager = $local_task_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.menu.local_task'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'primary' => TRUE,
      'secondary' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration;

    $tabs = [
      '#theme' => 'menu_local_tasks',
    ];

    // Add only selected levels for the printed output.
    if ($config['primary']) {
      $links = $this->localTaskManager->getLocalTasks($this->routeMatch->getRouteName(), 0);
      // Do not display single tabs.
      $tabs += [
        '#primary' => count(Element::getVisibleChildren($links['tabs'])) > 1 ? $links['tabs'] : [],
      ];
    }
    if ($config['secondary']) {
      $links = $this->localTaskManager->getLocalTasks($this->routeMatch->getRouteName(), 1);
      // Do not display single tabs.
      $tabs += [
        '#secondary' => count(Element::getVisibleChildren($links['tabs'])) > 1 ? $links['tabs'] : [],
      ];
    }

    if (empty($tabs['#primary']) && empty($tabs['#secondary'])) {
      return [];
    }

    return $tabs;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // The "Page actions" block is never cacheable because of hooks creating
    // local tasks doesn't provide cacheability metadata.
    // @todo Remove after https://www.drupal.org/node/2511516 has landed.
    $form['cache']['#disabled'] = TRUE;
    $form['cache']['#description'] = $this->t('This block is never cacheable.');
    $form['cache']['max_age']['#value'] = 0;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // @todo Remove after https://www.drupal.org/node/2511516 has landed.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['route.name'];
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

}
