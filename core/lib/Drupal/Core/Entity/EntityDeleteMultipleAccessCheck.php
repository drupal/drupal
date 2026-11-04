<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks if the current user has delete access to the items of the tempstore.
 */
class EntityDeleteMultipleAccessCheck implements AccessInterface {

  /**
   * The tempstore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new EntityDeleteMultipleAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    PrivateTempStoreFactory $temp_store_factory,
    protected RequestStack $requestStack,
  ) {
    $this->tempStore = $temp_store_factory->get('entity_delete_multiple_confirm');
  }

  /**
   * Checks if the user has delete access for at least one item of the store.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param string $entity_type_id
   *   Entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed or forbidden, neutral if tempstore is empty.
   */
  public function access(AccountInterface $account, $entity_type_id) {
    $selection = $this->tempStore->get($account->id() . ':' . $entity_type_id);
    if (empty($selection) || !is_array($selection)) {
      return AccessResult::neutral();
    }

    $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple(array_keys($selection));
    foreach ($entities as $entity) {
      // As long as the user has access to delete one entity allow access to the
      // delete form. Access will be checked again in
      // Drupal\Core\Entity\Form\DeleteMultipleForm::submit() in case it has
      // changed in the meantime.
      if ($entity->access('delete', $account)) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
