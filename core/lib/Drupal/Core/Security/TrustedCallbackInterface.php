<?php

namespace Drupal\Core\Security;

/**
 * Interface to declare trusted callbacks.
 *
 * @see \Drupal\Core\Security\DoTrustedCallbackTrait
 */
interface TrustedCallbackInterface {

  /**
   * Untrusted callbacks throw exceptions.
   *
   * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. There is
   *   no replacement. This is the default behavior.
   *
   * @see https://www.drupal.org/node/3627046
   */
  const THROW_EXCEPTION = 'exception';

  /**
   * Untrusted callbacks trigger silenced E_USER_DEPRECATION errors.
   *
   * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. There is
   *   no replacement. This behavior is no longer supported.
   *
   * @see https://www.drupal.org/node/3627046
   */
  const TRIGGER_SILENCED_DEPRECATION = 'silenced_deprecation';

  /**
   * Lists the trusted callbacks provided by the implementing class.
   *
   * Trusted callbacks are public methods on the implementing class and can be
   * invoked via
   * \Drupal\Core\Security\DoTrustedCallbackTrait::doTrustedCallback().
   *
   * @return string[]
   *   List of method names implemented by the class that can be used as trusted
   *   callbacks.
   *
   * @see \Drupal\Core\Security\DoTrustedCallbackTrait::doTrustedCallback()
   */
  public static function trustedCallbacks();

}
