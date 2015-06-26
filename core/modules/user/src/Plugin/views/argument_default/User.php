<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\views\argument_default\User.
 */

namespace Drupal\user\Plugin\views\argument_default;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Plugin\CacheablePluginInterface;
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
class User extends ArgumentDefaultPluginBase implements CacheablePluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new User instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

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
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['user'] = array('default' => '');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['user'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Also look for a node and use the node author'),
      '#default_value' => $this->options['user'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {

    // If there is a user object in the current route.
    if ($user = $this->routeMatch->getParameter('user')) {
      if ($user instanceof UserInterface) {
        return $user->id();
      }
    }

    // If option to use node author; and node in current route.
    if (!empty($this->options['user']) && $node = $this->routeMatch->getParameter('node')) {
      if ($node instanceof NodeInterface) {
        return $node->getOwnerId();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

}
