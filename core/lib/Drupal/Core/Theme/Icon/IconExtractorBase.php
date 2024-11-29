<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Icon;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Theme\Icon\Exception\IconPackConfigErrorException;

/**
 * Base class for icon extractor plugins.
 *
 * @internal
 *   This API is experimental.
 */
abstract class IconExtractorBase extends PluginBase implements IconExtractorInterface, PluginWithFormsInterface {

  use PluginWithFormsTrait;

  // Remove internal values and allow extractor to add any needed values.
  private const DEFINITION_REMOVE = [
    'enabled',
    'template',
    'description',
    'links',
    'config',
  ];

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description(): string {
    return (string) $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    if (!isset($this->configuration['settings'])) {
      return $form;
    }

    return IconExtractorSettingsForm::generateSettingsForm($this->configuration['settings'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function loadIcon(array $icon_data): ?IconDefinitionInterface {
    if (!isset($icon_data['icon_id']) || empty($icon_data['icon_id'])) {
      return NULL;
    }

    return $this->createIcon(
      $icon_data['icon_id'],
      $icon_data['source'] ?? '',
      $icon_data['group'] ?? NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createIcon(string $icon_id, ?string $source = NULL, ?string $group = NULL, ?array $data = NULL): IconDefinitionInterface {
    if (!isset($this->configuration['template'])) {
      throw new IconPackConfigErrorException(sprintf('Missing `template` in your definition, extractor %s requires this value.', $this->getPluginId()));
    }

    // Clean unused pack definition values as they will be passed to the context
    // of the Twig.
    $data_definition = array_diff_key($this->configuration, array_flip(self::DEFINITION_REMOVE));

    return IconDefinition::create(
      $this->configuration['id'],
      $icon_id,
      $this->configuration['template'],
      $source,
      $group,
      $data ? array_merge($data, $data_definition) : $data_definition,
    );
  }

}
