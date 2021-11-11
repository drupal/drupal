<?php

namespace Drupal\Tests\ckeditor5\Traits;

use Drupal\Core\Session\AccountInterface;

/**
 * Synchronizes the child site's CSRF token seed back to the test runner.
 *
 * For the test to be able to generate valid CSRF tokens, it needs access to the
 * CSRF token seed in the child site (i.e. tested site). This requires reading
 * the CSRF token seed from the session that gets created in the child site
 * after logging in, and then setting it in the test runner's container.
 * Otherwise, the test runner would generate its own CSRF token seed and would
 * hence generate CSRF tokens that are not valid for the session in the child
 * site.
 *
 * @see \Drupal\Core\Access\CsrfTokenGenerator::get()
 *
 * @internal
 */
trait SynchronizeCsrfTokenSeedTrait {

  /**
   * {@inheritdoc}
   */
  protected function drupalLogin(AccountInterface $account) {
    parent::drupalLogin($account);
    $session_data = $this->container->get('session_handler.write_safe')->read($this->getSession()->getCookie($this->getSessionName()));
    $csrf_token_seed = unserialize(explode('_sf2_meta|', $session_data)[1])['s'];
    $this->container->get('session_manager.metadata_bag')->setCsrfTokenSeed($csrf_token_seed);
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalLogout() {
    parent::drupalLogout();
    $this->container->get('session_manager.metadata_bag')->stampNew();
  }

}
