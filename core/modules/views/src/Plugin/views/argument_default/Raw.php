<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument_default\Raw.
 */

namespace Drupal\views\Plugin\views\argument_default;

use Drupal\Core\Form\FormStateInterface;
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
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AliasManagerInterface $alias_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.alias_manager')
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['index'] = array('default' => '');
    $options['use_alias'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['index'] = array(
      '#type' => 'select',
      '#title' => $this->t('Path component'),
      '#default_value' => $this->options['index'],
      // range(1, 10) returns an array with:
      // - keys that count from 0 to match PHP array keys from explode().
      // - values that count from 1 for display to humans.
      '#options' => range(1, 10),
      '#description' => $this->t('The numbering starts from 1, e.g. on the page admin/structure/types, the 3rd path component is "types".'),
    );
    $form['use_alias'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use path alias'),
      '#default_value' => $this->options['use_alias'],
      '#description' => $this->t('Use path alias instead of internal path.'),
    );
  }

  public function getArgument() {
    // @todo Remove dependency on the internal _system_path attribute:
    //   https://www.drupal.org/node/2293581.
    $path = $this->view->getRequest()->attributes->get('_system_path');
    if ($this->options['use_alias']) {
      $path = $this->aliasManager->getAliasByPath($path);
    }
    $args = explode('/', $path);
    if (isset($args[$this->options['index']])) {
      return $args[$this->options['index']];
    }
  }

}
