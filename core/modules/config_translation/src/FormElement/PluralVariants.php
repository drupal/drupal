<?php

namespace Drupal\config_translation\FormElement;

use Drupal\Component\Gettext\PoItem;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Config;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Config\LanguageConfigOverride;

/**
 * Defines form elements for plurals in configuration translation.
 */
class PluralVariants extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  protected function getSourceElement(LanguageInterface $source_language, $source_config) {
    $plurals = $this->getNumberOfPlurals($source_language->getId());
    $values = explode(PoItem::DELIMITER, $source_config);
    $element = [
      '#type' => 'fieldset',
      '#title' => new FormattableMarkup('@label <span class="visually-hidden">(@source_language)</span>', [
        // Labels originate from configuration schema and are translatable.
        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        '@label' => $this->t($this->definition->getLabel()),
        '@source_language' => $source_language->getName(),
      ]),
      '#tree' => TRUE,
    ];
    for ($i = 0; $i < $plurals; $i++) {
      $element[$i] = [
        '#type' => 'item',
        // @todo Should use better labels https://www.drupal.org/node/2499639
        '#title' => $i == 0 ? $this->t('Singular form') : $this->formatPlural($i, 'First plural form', '@count. plural form'),
        '#markup' => new FormattableMarkup('<span lang="@langcode">@value</span>', [
          '@langcode' => $source_language->getId(),
          '@value' => $values[$i] ?? $this->t('(Empty)'),
        ]),
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationElement(LanguageInterface $translation_language, $source_config, $translation_config) {
    $plurals = $this->getNumberOfPlurals($translation_language->getId());
    $values = explode(PoItem::DELIMITER, $translation_config);
    $element = [
      '#type' => 'fieldset',
      '#title' => new FormattableMarkup('@label <span class="visually-hidden">(@translation_language)</span>', [
        // Labels originate from configuration schema and are translatable.
        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        '@label' => $this->t($this->definition->getLabel()),
        '@translation_language' => $translation_language->getName(),
      ]),
      '#tree' => TRUE,
    ];
    for ($i = 0; $i < $plurals; $i++) {
      $element[$i] = [
        '#type' => 'textfield',
        // @todo Should use better labels https://www.drupal.org/node/2499639
        '#title' => $i == 0 ? $this->t('Singular form') : $this->formatPlural($i, 'First plural form', '@count. plural form'),
        '#default_value' => $values[$i] ?? '',
        '#attributes' => ['lang' => $translation_language->getId()],
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig(Config $base_config, LanguageConfigOverride $config_translation, $config_values, $base_key = NULL) {
    $config_values = implode(PoItem::DELIMITER, $config_values);
    parent::setConfig($base_config, $config_translation, $config_values, $base_key);
  }

}
