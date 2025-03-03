<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Traits;

use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;

/**
 * Contains helper methods for testing Composer Stager interactions.
 *
 * @internal
 *
 * @property \Symfony\Component\DependencyInjection\ContainerInterface $container
 */
trait ComposerStagerTestTrait {

  /**
   * Creates a Composer Stager translatable message.
   *
   * @param string $message
   *   A message containing optional placeholders corresponding to parameters (next). Example:
   *   ```php
   *   $message = 'Hello, %first_name %last_name.';
   *   ```.
   * @param \PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface|null $parameters
   *   Translation parameters.
   * @param string|null $domain
   *   An arbitrary domain for grouping translations or null to use the default. See
   *   {@see \PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface}.
   *
   * @return \PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface
   *   A message that can be translated by Composer Stager.
   */
  protected function createComposeStagerMessage(
    string $message,
    ?TranslationParametersInterface $parameters = NULL,
    ?string $domain = NULL,
  ): TranslatableInterface {
    /** @var \PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface $translatable_factory */
    $translatable_factory = $this->container->get(TranslatableFactoryInterface::class);

    return $translatable_factory->createTranslatableMessage($message, $parameters, $domain);
  }

}
