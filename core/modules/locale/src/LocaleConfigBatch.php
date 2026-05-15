<?php

namespace Drupal\locale;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Provides the locale config update batch services.
 */
class LocaleConfigBatch {

  use StringTranslationTrait;

  public function __construct(
    protected readonly LocaleConfigManager $localeConfigManager,
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly MessengerInterface $messenger,
    /**
     * @var \Closure(): \Psr\Log\LoggerInterface
     */
    #[AutowireServiceClosure('logger.channel.locale')]
    protected readonly \Closure $logger,
  ) {}

  /**
   * Builds a locale batch to refresh configuration.
   *
   * @param array $options
   *   An array with options that can have the following elements:
   *   - 'finish_feedback': (optional) Whether or not to give feedback to the
   *     user when the batch is finished. Defaults to TRUE.
   * @param array $langcodes
   *   (optional) Array of language codes. Defaults to all translatable
   *   languages.
   * @param array $components
   *   (optional) Array of component lists indexed by type. If not present or it
   *   is an empty array, it will update all components.
   * @param bool $update_default_config_langcodes
   *   Determines whether default configuration langcodes should be updated.
   *   This should only happen during site and extension install.
   *
   * @return array
   *   The batch definition.
   */
  public function buildBatch(array $options, array $langcodes = [], array $components = [], bool $update_default_config_langcodes = FALSE): ?array {
    $langcodes = $langcodes ?: array_keys($this->languageManager->getLanguages());
    if ($langcodes && $names = $this->localeConfigManager->getComponentNames($components)) {
      // If the component list is empty we need to ensure that all configuration
      // in the default collection is using the site's default langcode.
      $options += ['finish_feedback' => TRUE];
      $batch_builder = (new BatchBuilder())
        ->setTitle($this->t('Updating configuration translations'))
        ->setInitMessage($this->t('Starting configuration update'))
        ->setErrorMessage($this->t('Error updating configuration translations'));

      if ($update_default_config_langcodes && $this->languageManager->getDefaultLanguage()->getId() !== 'en') {
        $batch_builder->addOperation(self::class . ':batchUpdateDefaultConfigLangcodes');
      }

      // Chunking the array of names into batches of 20 for better performance.
      $name_chunks = array_chunk($names, 20);

      foreach ($name_chunks as $chunk) {
        // During installation the caching of configuration objects is disabled
        // so it is very expensive to initialize the \Drupal::config() object
        // on each request. We batch a small number of configuration object
        // upgrades together to improve the overall performance of the process.
        $batch_builder->addOperation(self::class . ':batchUpdateConfigTranslations', [$chunk, $langcodes]);
      }

      if (!empty($options['finish_feedback'])) {
        $batch_builder->setFinishCallback(self::class . ':batchFinished');
      }
      return $batch_builder->toArray();
    }
    return NULL;
  }

  /**
   * Implements callback_batch_operation().
   *
   * Updates default configuration when new modules or themes are installed.
   *
   * @param array|\ArrayAccess $context
   *   The batch context.
   */
  public function batchUpdateDefaultConfigLangcodes(array|\ArrayAccess &$context): void {
    $this->localeConfigManager->updateDefaultConfigLangcodes();
    $context['finished'] = 1;
    $context['message'] = $this->t('Updated default configuration to %langcode', ['%langcode' => $this->languageManager->getDefaultLanguage()->getId()]);
  }

  /**
   * Implements callback_batch_operation().
   *
   * Performs configuration translation refresh.
   *
   * @param array $names
   *   An array of names of configuration objects to update.
   * @param array $langcodes
   *   (optional) Array of language codes to update. Defaults to all languages.
   * @param array|\ArrayAccess $context
   *   Contains a list of files imported.
   *
   * @see \Drupal\locale\LocaleConfigBatch::buildBatch()
   */
  public function batchUpdateConfigTranslations(array $names, array $langcodes, array|\ArrayAccess &$context): void {
    if (!isset($context['results']['stats']['config'])) {
      $context['results']['stats']['config'] = 0;
    }
    $context['results']['stats']['config'] += $this->localeConfigManager->updateConfigTranslations($names, $langcodes);
    foreach ($names as $name) {
      $context['results']['names'][] = $name;
    }
    $context['results']['langcodes'] = $langcodes;
    $context['finished'] = 1;
  }

  /**
   * Implements callback_batch_finished().
   *
   * Finishes callback of system page locale import batch.
   *
   * @param bool $success
   *   Information about the success of the batch import.
   * @param array $results
   *   Information about the results of the batch import.
   *
   * @see \Drupal\locale\LocaleConfigBatch::buildBatch()
   */
  public function batchFinished(bool $success, array $results): void {
    if ($success) {
      $configuration = $results['stats']['config'] ?? 0;
      if ($configuration) {
        $this->messenger->addStatus($this->t('The configuration was successfully updated. There are %number configuration objects updated.', ['%number' => $configuration]));
        ($this->logger)()->notice('The configuration was successfully updated. %number configuration objects updated.', ['%number' => $configuration]);
      }
      else {
        $this->messenger->addStatus($this->t('No configuration objects have been updated.'));
        ($this->logger)()->notice('No configuration objects have been updated.');
      }
    }
  }

}
