<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\views\argument\Uid.
 */

namespace Drupal\user\Plugin\views\argument;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\views\Plugin\views\argument\Numeric;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a user id.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("user_uid")
 */
class Uid extends Numeric {

  /**
   * The user storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storageController;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The user storage controller.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageControllerInterface $storage_controller) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storageController = $storage_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity.manager')->getStorageController('user'));
  }

  /**
   * Override the behavior of title(). Get the name of the user.
   *
   * @return array
   *    A list of usernames.
   */
  public function titleQuery() {
    return array_map(function($account) {
      return String::checkPlain($account->label());
    }, $this->storageController->loadMultiple($this->value));
  }

}
