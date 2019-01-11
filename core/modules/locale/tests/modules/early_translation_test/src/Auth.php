<?php

namespace Drupal\early_translation_test;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test authentication provider.
 */
class Auth implements AuthenticationProviderInterface {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs an authentication provider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    // Authentication providers are called early during in the bootstrap.
    // Getting the user storage used to result in a circular reference since
    // translation involves a call to \Drupal\locale\LocaleLookup that tries to
    // get the user roles.
    // @see https://www.drupal.org/node/2241461
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    return NULL;
  }

}
