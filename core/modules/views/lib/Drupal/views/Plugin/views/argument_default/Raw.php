<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument_default\Raw.
 */

namespace Drupal\views\Plugin\views\argument_default;

use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
class Raw extends ArgumentDefaultPluginBase {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a Raw object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Request $request, AliasManagerInterface $alias_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->request = $request;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request'),
      $container->get('path.alias_manager.cached')
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['index'] = array('default' => '');
    $options['use_alias'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Using range(1, 10) will create an array keyed 0-9, which allows arg() to
    // properly function since it is also zero-based.
    $form['index'] = array(
      '#type' => 'select',
      '#title' => t('Path component'),
      '#default_value' => $this->options['index'],
      '#options' => range(1, 10),
      '#description' => t('The numbering starts from 1, e.g. on the page admin/structure/types, the 3rd path component is "types".'),
    );
    $form['use_alias'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use path alias'),
      '#default_value' => $this->options['use_alias'],
      '#description' => t('Use path alias instead of internal path.'),
    );
  }

  public function getArgument() {
    $path = $this->request->attributes->get('_system_path');
    if ($this->options['use_alias']) {
      $path = $this->aliasManager->getPathAlias($path);
    }
    $args = explode('/', $path);
    if (isset($args[$this->options['index']])) {
      return $args[$this->options['index']];
    }
  }

}
