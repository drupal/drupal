<?php

namespace Drupal\config_translation\FormElement;

use Drupal\Core\Config\Config;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\language\Config\LanguageConfigOverride;

/**
 * Provides a common base class for form elements.
 */
abstract class FormElementBase implements ElementInterface {

  use StringTranslationTrait;

  /**
   * The schema element this form is for.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
   */
  protected $element;

  /**
   * The data definition of the element this form element is for.
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected $definition;

  /**
   * Constructs a FormElementBase.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $element
   *   The schema element this form element is for.
   */
  public function __construct(TypedDataInterface $element) {
    $this->element = $element;
    $this->definition = $element->getDataDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(TypedDataInterface $schema) {
    return new static($schema);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationBuild(LanguageInterface $source_language, LanguageInterface $translation_language, $source_config, $translation_config, array $parents, $base_key = NULL) {
    $build['#theme'] = 'config_translation_manage_form_element';

    // For accessibility we make source and translation appear next to each
    // other in the source for each element, which is why we utilize the
    // 'source' and 'translation' sub-keys for the form. The form values,
    // however, should mirror the configuration structure, so that we can
    // traverse the configuration schema and still access the right
    // configuration values in ConfigTranslationFormBase::setConfig().
    // Therefore we make the 'source' and 'translation' keys the top-level
    // keys in $form_state['values'].
    $build['source'] = $this->getSourceElement($source_language, $source_config);
    $build['translation'] = $this->getTranslationElement($translation_language, $source_config, $translation_config);

    $build['source']['#parents'] = array_merge(['source'], $parents);
    $build['translation']['#parents'] = array_merge(['translation'], $parents);
    return $build;
  }

  /**
   * Returns the source element for a given configuration definition.
   *
   * This can be either a render array that actually outputs the source values
   * directly or a read-only form element with the source values depending on
   * what is considered to provide a more intuitive user interface for the
   * translator.
   *
   * @param \Drupal\Core\Language\LanguageInterface $source_language
   *   Thee source language of the configuration object.
   * @param mixed $source_config
   *   The configuration value of the element in the source language.
   *
   * @return array
   *   A render array for the source value.
   */
  protected function getSourceElement(LanguageInterface $source_language, $source_config) {
    if ($source_config) {
      $value = '<span lang="' . $source_language->getId() . '">' . nl2br($source_config) . '</span>';
    }
    else {
      $value = $this->t('(Empty)');
    }

    return [
      '#type' => 'item',
      '#title' => $this->t('@label <span class="visually-hidden">(@source_language)</span>', [
        // Labels originate from configuration schema and are translatable.
        '@label' => $this->t($this->definition->getLabel()),
        '@source_language' => $source_language->getName(),
      ]),
      '#markup' => $value,
    ];
  }

  /**
   * Returns the translation form element for a given configuration definition.
   *
   * For complex data structures (such as mappings) that are translatable
   * wholesale but contain non-translatable properties, the form element is
   * responsible for checking access to the source value of those properties. In
   * case of formatted text, for example, access to the source text format must
   * be checked. If the translator does not have access to the text format, the
   * textarea must be disabled and the translator may not be able to translate
   * this particular configuration element. If the translator does have access
   * to the text format, the element must be locked down to that particular text
   * format; in other words, the format may not be changed by the translator
   * (because the text format property is not itself translatable).
   *
   * In addition, the form element is responsible for checking whether the
   * value of such non-translatable properties in the translated configuration
   * is equal to the corresponding source values. If not, that means that the
   * source value has changed after the translation was added. In this case -
   * again - the translation of this element must be disabled if the translator
   * does not have access to the source value of the non-translatable property.
   * For example, if a formatted text element, whose source format was plain
   * text when it was first translated, gets changed to the Full HTML format,
   * simply changing the format of the translation would lead to an XSS
   * vulnerability as the translated text, that was intended to be escaped,
   * would now be displayed unescaped. Thus, if the translator does not have
   * access to the Full HTML format, the translation for this particular element
   * may not be updated at all (the textarea must be disabled). Only if access
   * to the Full HTML format is granted, an explicit translation taking into
   * account the updated source value(s) may be submitted.
   *
   * In the specific case of formatted text this logic is implemented by
   * utilizing a form element of type 'text_format' and its #format and
   * #allowed_formats properties. The access logic explained above is then
   * handled by the 'text_format' element itself, specifically by
   * \Drupal\filter\Element\TextFormat::processFormat(). In case such a rich
   * element is not available for translation of complex data, similar access
   * logic must be implemented manually.
   *
   * @param \Drupal\Core\Language\LanguageInterface $translation_language
   *   The language to display the translation form for.
   * @param mixed $source_config
   *   The configuration value of the element in the source language.
   * @param mixed $translation_config
   *   The configuration value of the element in the language to translate to.
   *
   * @return array
   *   Form API array to represent the form element.
   *
   * @see \Drupal\config_translation\FormElement\TextFormat
   * @see \Drupal\filter\Element\TextFormat::processFormat()
   */
  protected function getTranslationElement(LanguageInterface $translation_language, $source_config, $translation_config) {
    // Add basic properties that apply to all form elements.
    return [
      '#title' => $this->t('@label <span class="visually-hidden">(@source_language)</span>', [
        // Labels originate from configuration schema and are translatable.
        '@label' => $this->t($this->definition->getLabel()),
        '@source_language' => $translation_language->getName(),
      ]),
      '#default_value' => $translation_config,
      '#attributes' => ['lang' => $translation_language->getId()],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig(Config $base_config, LanguageConfigOverride $config_translation, $config_values, $base_key = NULL) {
    // Save the configuration values, if they are different from the source
    // values in the base configuration. Otherwise remove the override.
    if ($base_config->get($base_key) !== $config_values) {
      $config_translation->set($base_key, $config_values);
    }
    else {
      $config_translation->clear($base_key);
    }
  }

}
