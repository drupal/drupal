<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument_default\Node.
 */

namespace Drupal\node\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default argument plugin to extract a node.
 *
 * This plugin actually has no options so it odes not need to do a great deal.
 *
 * @ViewsArgumentDefault(
 *   id = "node",
 *   title = @Translation("Content ID from URL")
 * )
 */
class Node extends ArgumentDefaultPluginBase {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new Node instance.
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
  public function getArgument() {
    if (($node = $this->request->attributes->get('node')) && $node instanceof NodeInterface) {
      return $node->id();
    }
  }
}
