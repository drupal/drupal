<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutAccessControlHandler.
 */

namespace Drupal\shortcut;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the shortcut entity type.
 *
 * @see \Drupal\shortcut\Entity\Shortcut
 */
class ShortcutAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The shortcut_set storage.
   *
   * @var \Drupal\shortcut\ShortcutSetStorageInterface
   */
  protected $shortcutSetStorage;

  /**
   * Constructs a ShortcutAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\shortcut\ShortcutSetStorageInterface $shortcut_set_storage
   *   The shortcut_set storage.
   */
  public function __construct(EntityTypeInterface $entity_type, ShortcutSetStorageInterface $shortcut_set_storage) {
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
    // @todo Fix this bizarre code: how can a shortcut exist without a shortcut
    // set? The above if-test is unnecessary. See https://www.drupal.org/node/2339903.
    return AccessResult::neutral()->cacheUntilEntityChanges($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($shortcut_set = $this->shortcutSetStorage->load($entity_bundle)) {
      return shortcut_set_edit_access($shortcut_set, $account);
    }
    // @todo Fix this bizarre code: how can a shortcut exist without a shortcut
    // set? The above if-test is unnecessary. See https://www.drupal.org/node/2339903.
    return AccessResult::neutral();
  }

}
