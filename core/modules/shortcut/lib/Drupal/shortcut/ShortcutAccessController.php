<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutAccessController.
 */

namespace Drupal\shortcut;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access controller for the test entity type.
 */
class ShortcutAccessController extends EntityAccessController implements EntityControllerInterface {

  /**
   * The shortcut_set storage.
   *
   * @var \Drupal\shortcut\ShortcutSetStorage
   */
  protected $shortcutSetStorage;

  /**
   * Constructs a ShortcutAccessController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\shortcut\ShortcutSetStorage $shortcut_set_storage
   *   The shortcut_set storage.
   */
  public function __construct(EntityTypeInterface $entity_type, ShortcutSetStorage $shortcut_set_storage) {
    parent::__construct($entity_type);
    $this->shortcutSetStorage = $shortcut_set_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage('shortcut_set')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($shortcut_set = $this->shortcutSetStorage->load($entity->bundle())) {
      return shortcut_set_edit_access($shortcut_set, $account);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($shortcut_set = $this->shortcutSetStorage->load($entity_bundle)) {
      return shortcut_set_edit_access($shortcut_set, $account);
    }
  }

}
