<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Action\CancelUser.
 */

namespace Drupal\user\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\TempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cancels a user account.
 *
 * @Action(
 *   id = "user_cancel_user_action",
 *   label = @Translation("Cancel the selected user accounts"),
 *   type = "user",
 *   confirm_form_path = "admin/people/cancel"
 * )
 */
class CancelUser extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\TempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a DeleteNode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\TempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TempStoreFactory $temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('user.tempstore'));
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->tempStoreFactory->get('user_user_operations_cancel')->set(\Drupal::currentUser()->id(), $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple(array($object));
  }

}
