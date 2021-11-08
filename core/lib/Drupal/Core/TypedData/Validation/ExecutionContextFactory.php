<?php

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\Validation\TranslatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextFactoryInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Defines an execution factory for the Typed Data validator.
 *
 * We do not use the factory provided by Symfony as it is marked internal.
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use the class
 *   \Symfony\Component\Validator\Context\ExecutionContextFactory instead.
 *
 * @see https://www.drupal.org/node/3238432
 */
class ExecutionContextFactory implements ExecutionContextFactoryInterface {

  /**
   * @var \Drupal\Core\Validation\TranslatorInterface
   */
  protected $translator;

  /**
   * @var string|null
   */
  protected $translationDomain;

  /**
   * Constructs a new ExecutionContextFactory instance.
   *
   * @param \Drupal\Core\Validation\TranslatorInterface $translator
   *   The translator instance.
   * @param string $translationDomain
   *   (optional) The translation domain.
   */
  public function __construct(TranslatorInterface $translator, $translationDomain = NULL) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use the class \Symfony\Component\Validator\Context\ExecutionContextFactory instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    $this->translator = $translator;
    $this->translationDomain = $translationDomain;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContextFactory::createContext()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function createContext(ValidatorInterface $validator, $root): ExecutionContextInterface {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContextFactory::createContext() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return new ExecutionContext(
      $validator,
      $root,
      $this->translator,
      $this->translationDomain
    );
  }

}
