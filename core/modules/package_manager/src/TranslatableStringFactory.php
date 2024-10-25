<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Core\StringTranslation\TranslationInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;

/**
 * Creates translatable strings that can interoperate with Composer Stager.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class TranslatableStringFactory implements TranslatableFactoryInterface {

  public function __construct(
    private readonly TranslatableFactoryInterface $decorated,
    private readonly TranslationInterface $translation,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function createDomainOptions(): DomainOptionsInterface {
    return $this->decorated->createDomainOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function createTranslatableMessage(string $message, ?TranslationParametersInterface $parameters = NULL, ?string $domain = NULL): TranslatableInterface {
    return new TranslatableStringAdapter(
      $message,
      $parameters?->getAll() ?? [],
      // TranslatableMarkup's 'context' option is the closest analogue to the
      // $domain parameter.
      ['context' => $domain ?? ''],
      $this->translation,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createTranslationParameters(array $parameters = []): TranslationParametersInterface {
    return $this->decorated->createTranslationParameters($parameters);
  }

}
