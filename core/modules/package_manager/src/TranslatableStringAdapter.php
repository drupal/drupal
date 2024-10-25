<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;

/**
 * An adapter for interoperable string translation.
 *
 * This class is designed to adapt Drupal's style of string translation so it
 * can be used with the Symfony-inspired architecture used by Composer Stager.
 *
 * If this object is cast to a string, it will be translated by Drupal's
 * translation system. It will ONLY be translated by Composer Stager if the
 * trans() method is explicitly called.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class TranslatableStringAdapter extends TranslatableMarkup implements TranslatableInterface, TranslationParametersInterface {

  /**
   * {@inheritdoc}
   */
  public function getAll(): array {
    return $this->getArguments();
  }

  /**
   * {@inheritdoc}
   */
  public function trans(?TranslatorInterface $translator = NULL, ?string $locale = NULL): string {
    // This method is NEVER used by Drupal to translate the underlying string;
    // it exists solely for Composer Stager's translation system to
    // transparently translate Drupal strings using its own architecture.
    return $translator->trans(
      $this->getUntranslatedString(),
      $this,
      // The 'context' option is the closest analogue to the Symfony-inspired
      // concept of translation domains.
      $this->getOption('context'),
      $locale ?? $this->getOption('langcode'),
    );
  }

}
