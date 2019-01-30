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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The tempstore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * Request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new EntityDeleteMultipleAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PrivateTempStoreFactory $temp_store_factory, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tempStore = $temp_store_factory->get('entity_delete_multiple_confirm');
    $this->requestStack = $request_stack;
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
    if (!$this->requestStack->getCurrentRequest()->hasSession()) {
      return AccessResult::neutral();
    }
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
