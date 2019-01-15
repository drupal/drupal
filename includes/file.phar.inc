<?php

use Drupal\Core\Security\PharExtensionInterceptor;
use TYPO3\PharStreamWrapper\Manager as PharStreamWrapperManager;
use TYPO3\PharStreamWrapper\Behavior as PharStreamWrapperBehavior;
use TYPO3\PharStreamWrapper\PharStreamWrapper;

/**
 * Registers a phar stream wrapper that is more secure than PHP's built-in one.
 *
 * @see file_get_stream_wrappers()
 */
function file_register_phar_wrapper() {
  $directory = DRUPAL_ROOT . '/misc/typo3/phar-stream-wrapper/src';
  include_once $directory . '/Assertable.php';
  include_once $directory . '/Behavior.php';
  include_once $directory . '/Exception.php';
  include_once $directory . '/Helper.php';
  include_once $directory . '/Manager.php';
  include_once $directory . '/PharStreamWrapper.php';
  include_once DRUPAL_ROOT . '/misc/typo3/drupal-security/PharExtensionInterceptor.php';

  // Set up a stream wrapper to handle insecurities due to PHP's built-in
  // phar stream wrapper.
  try {
    $behavior = new PharStreamWrapperBehavior();
    PharStreamWrapperManager::initialize(
      $behavior->withAssertion(new PharExtensionInterceptor())
    );
  }
  catch (\LogicException $e) {
    // Continue if the PharStreamWrapperManager is already initialized.
    // For example, this occurs following a drupal_static_reset(), such
    // as during tests.
  };

  // To prevent file_stream_wrapper_valid_scheme() treating "phar" as a valid
  // scheme, this is registered with PHP only, not with  hook_stream_wrappers()
  // or the internal storage of file_get_stream_wrappers().
  stream_wrapper_register('phar', '\\TYPO3\\PharStreamWrapper\\PharStreamWrapper');
}
