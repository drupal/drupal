<?php

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\Validation\ExecutionContext as NewExecutionContext;
use Drupal\Core\Validation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Defines an execution context class.
 *
 * We do not use the context provided by Symfony as it is marked internal, so
 * this class is pretty much the same, but has some code style changes as well
 * as exceptions for methods we don't support.
 */
class ExecutionContext extends NewExecutionContext {

  /**
   * Creates a new ExecutionContext.
   *
   * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
   *   The validator.
   * @param mixed $root
   *   The root.
   * @param \Drupal\Core\Validation\TranslatorInterface $translator
   *   The translator.
   * @param string $translationDomain
   *   (optional) The translation domain.
   *
   * @internal Called by \Drupal\Core\Validation\ExecutionContextFactory.
   *    Should not be used in user code.
   */
  public function __construct(ValidatorInterface $validator, $root, TranslatorInterface $translator, $translationDomain = NULL) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Instead, use \Drupal\Core\Validation\ExecutionContext. See https://www.drupal.org/node/3396238', E_USER_DEPRECATED);
    parent::__construct($validator, $root, $translator, $translationDomain);
  }

}
