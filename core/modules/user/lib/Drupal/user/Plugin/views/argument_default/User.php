<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\argument_default\User.
 */

namespace Drupal\user\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;

/**
 * Default argument plugin to extract a user from request.
 *
 * @ViewsArgumentDefault(
 *   id = "user",
 *   title = @Translation("User ID from route context")
 * )
 */
class User extends ArgumentDefaultPluginBase {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a default argument User object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['user'] = array('default' => '', 'bool' => TRUE, 'translatable' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['user'] = array(
      '#type' => 'checkbox',
      '#title' => t('Also look for a node and use the node author'),
      '#default_value' => $this->options['user'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {

    // If there is a user object in the current route.
    if ($this->request->attributes->has('user')) {
      $user = $this->request->attributes->get('user');
      if ($user instanceof UserInterface) {
        return $user->id();
      }
    }

    // If option to use node author; and node in current route.
    if (!empty($this->options['user']) && $this->request->attributes->has('node')) {
      $node = $this->request->attributes->get('node');
      if ($node instanceof NodeInterface) {
        return $node->getOwnerId();
      }
    }

    // If the current page is a view that takes uid as an argument.
    $view = views_get_page_view();

    if ($view && isset($view->argument['uid'])) {
      return $view->argument['uid']->argument;
    }
  }

}
