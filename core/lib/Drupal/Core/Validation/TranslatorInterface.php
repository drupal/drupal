<?php

namespace Drupal\Core\Validation;

use Symfony\Component\Translation\TranslatorInterface as SymfonyTranslatorInterface;

/**
 * Defines an interface used in validation.
 *
 * This extends the interface used by the Symfony validator in order to indicate
 * that the Drupal code is actually independent from the Symfony translation
 * component.
 *
 * @see https://github.com/symfony/symfony/pull/6189
 * @see https://github.com/symfony/symfony/issues/15714
 */
interface TranslatorInterface extends SymfonyTranslatorInterface {

}
