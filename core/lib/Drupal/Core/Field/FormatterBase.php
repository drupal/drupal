<?php

namespace Drupal\Core\Field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for 'Field formatter' plugin implementations.
 *
 * @ingroup field_formatter
 */
abstract class FormatterBase extends PluginSettingsBase implements FormatterInterface, ContainerFactoryPluginInterface {

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * The formatter settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The label display setting.
   *
   * @var string
   */
  protected $label;

  /**
   * The view mode.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct([], $plugin_id, $plugin_definition);

    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->label = $label;
    $this->viewMode = $view_mode;
    $this->thirdPartySettings = $third_party_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings']);
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    // Default the language to the current content language.
    if (empty($langcode)) {
      $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }
    $elements = $this->viewElements($items, $langcode);

    // If there are actual renderable children, use #theme => field, otherwise,
    // let access cacheability metadata pass through for correct bubbling.
    if (Element::children($elements)) {
      $entity = $items->getEntity();
      $entity_type = $entity->getEntityTypeId();
      $field_name = $this->fieldDefinition->getName();
      $info = [
        '#theme' => 'field',
        '#title' => $this->fieldDefinition->getLabel(),
        '#label_display' => $this->label,
        '#view_mode' => $this->viewMode,
        '#language' => $items->getLangcode(),
        '#field_name' => $field_name,
        '#field_type' => $this->fieldDefinition->getType(),
        '#field_translatable' => $this->fieldDefinition->isTranslatable(),
        '#entity_type' => $entity_type,
        '#bundle' => $entity->bundle(),
        '#object' => $entity,
        '#items' => $items,
        '#formatter' => $this->getPluginId(),
        '#is_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#third_party_settings' => $this->getThirdPartySettings(),
      ];

      $elements = array_merge($info, $elements);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {}

  /**
   * Returns the array of field settings.
   *
   * @return array
   *   The array of settings.
   */
  protected function getFieldSettings() {
    return $this->fieldDefinition->getSettings();
  }

  /**
   * Returns the value of a field setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getFieldSetting($setting_name) {
    return $this->fieldDefinition->getSetting($setting_name);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // By default, formatters are available for all fields.
    return TRUE;
  }

}
