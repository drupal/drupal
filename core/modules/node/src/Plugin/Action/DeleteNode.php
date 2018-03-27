<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\DeleteAction;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Redirects to a node deletion form.
 *
 * @deprecated in Drupal 8.6.x, to be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\Action\Plugin\Action\DeleteAction instead.
 *
 * @see \Drupal\Core\Action\Plugin\Action\DeleteAction
 * @see https://www.drupal.org/node/2934349
 *
 * @Action(
 *   id = "node_delete_action",
 *   label = @Translation("Delete content")
 * )
 */
<<<<<<< HEAD
class DeleteNode extends DeleteAction {
=======
class DeleteNode extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The tempstore object.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new DeleteNode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user) {
    $this->currentUser = $current_user;
    $this->tempStore = $temp_store_factory->get('node_multiple_delete_confirm');

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.private_tempstore'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $info = [];
    /** @var \Drupal\node\NodeInterface $node */
    foreach ($entities as $node) {
      $langcode = $node->language()->getId();
      $info[$node->id()][$langcode] = $langcode;
    }
    $this->tempStore->set($this->currentUser->id(), $info);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $temp_store_factory, $current_user);
    @trigger_error(__NAMESPACE__ . '\DeleteNode is deprecated in Drupal 8.6.x, will be removed before Drupal 9.0.0. Use \Drupal\Core\Action\Plugin\Action\DeleteAction instead. See https://www.drupal.org/node/2934349.', E_USER_DEPRECATED);
  }

}
