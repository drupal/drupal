<?php

namespace Drupal\views\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to use the raw value from the URL.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "raw",
 *   title = @Translation("Raw value from URL")
 * )
 */
class Raw extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs a Raw object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AliasManagerInterface $alias_manager, CurrentPathStack $current_path) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->aliasManager = $alias_manager;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.alias_manager'),
      $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['index'] = ['default' => ''];
    $options['use_alias'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['index'] = [
      '#type' => 'select',
      '#title' => $this->t('Path component'),
      '#default_value' => $this->options['index'],
      // range(1, 10) returns an array with:
      // - keys that count from 0 to match PHP array keys from explode().
      // - values that count from 1 for display to humans.
      '#options' => range(1, 10),
      '#description' => $this->t('The numbering starts from 1, e.g. on the page admin/structure/types, the 3rd path component is "types".'),
    ];
    $form['use_alias'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use path alias'),
      '#default_value' => $this->options['use_alias'],
      '#description' => $this->t('Use path alias instead of internal path.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    // Don't trim the leading slash since getAliasByPath() requires it.
    $path = rtrim($this->currentPath->getPath($this->view->getRequest()), '/');
    if ($this->options['use_alias']) {
      $path = $this->aliasManager->getAliasByPath($path);
    }
    $args = explode('/', $path);
    // Drop the empty first element created by the leading slash since the path
    // component index doesn't take it into account.
    array_shift($args);
    if (isset($args[$this->options['index']])) {
      return $args[$this->options['index']];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

}
