<?php

namespace Drupal\Core\Validation;

/**
 * Defines an interface used in validation.
 *
 * This replaces the interface used by the Symfony validator in order
 * to indicate that the Drupal code is actually independent from the
 * Symfony translation component.
 *
 * @see https://github.com/symfony/symfony/pull/6189
 * @see https://github.com/symfony/symfony/issues/15714
 */
interface TranslatorInterface {

  /**
   * Translates the given message.
   *
   * @param string $id
   *   The message id (may also be an object that can be cast to string).
   * @param array $parameters
   *   An array of parameters for the message.
   * @param string|null $domain
   *   The domain for the message or null to use the default.
   * @param string|null $locale
   *   The locale or null to use the default.
   *
   * @return string
   *   The translated string.
   *
   * @throws InvalidArgumentException
   *   If the locale contains invalid characters.
   */
  public function trans($id, array $parameters = [], $domain = NULL, $locale = NULL);

}
