<?php

declare(strict_types=1);

// cspell:ignore coveo
namespace Drupal\Core\Theme\Icon;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;

/**
 * Handle icon extractor settings form conversion from YAML to Drupal Form API.
 *
 * This class transform the YAML settings from the definition to the Drupal Form
 * API. Based on JSON schema only some types are handled to cover most of the
 * use cases, conversion from YAML to Drupal Form API:
 * - boolean => #type = checkbox
 * - number => #type = number
 * - integer => #type = number
 * - string => #type = textfield
 *
 * For all types, basic values from YAML to Drupal Form API:
 * - title => #title
 * - description => #description
 * - default => #default_value
 *
 * The string type convert:
 * - pattern => #pattern
 * - maxLength => #maxLength and #size
 * - minLength => override #pattern
 * A specific string key `format` is converted to support color field:
 * - format: color => #type = color
 *
 * If an `enum` is set, then the select is used:
 * - enum => #type = select and #options
 * The key `meta:enum` is used to support description for each enum.
 *
 * @internal
 *   This API is experimental.
 */
class IconExtractorSettingsForm {

  protected const COLOR_TYPE = 'color';

  /**
   * Create the Drupal Form API element from the settings.
   *
   * The generated form support default from the 'saved_value' key in the form
   * state. This value is set in IconPackManager::getExtractorPluginForms().
   *
   * @param array $settings
   *   The settings from the icon pack definition.
   * @param \Drupal\Core\Form\FormStateInterface|null $form_state
   *   The from state used to get values if no form context.
   *
   * @return array
   *   The form API generated.
   */
  public static function generateSettingsForm(array $settings, ?FormStateInterface $form_state = NULL): array {
    $saved_values = $form_state ? $form_state->getCompleteFormState()->getValue('saved_values') ?? [] : [];
    $form = [];

    foreach ($settings as $setting_id => $setting) {

      if (isset($setting['enum']) && is_array($setting['enum']) && !empty($setting['enum'])) {
        $form[$setting_id] = self::buildEnumForm($setting_id, $setting, $saved_values);
        continue;
      }

      // Settings format is a subset of JSON Schema, with only the scalars.
      $form[$setting_id] = match ($setting['type']) {
        'boolean' => self::buildBooleanForm($setting_id, $setting, $saved_values),
        'number' => self::buildNumberForm($setting_id, $setting, $saved_values),
        'integer' => self::buildNumberForm($setting_id, $setting, $saved_values),
        'string' => self::buildStringForm($setting_id, $setting, $saved_values),
        // Default to string if unsupported type.
        default => self::buildStringForm($setting_id, $setting, $saved_values),
      };
    }

    return array_filter($form);
  }

  /**
   * Init setting from common JSON Schema properties.
   *
   * @param string $setting_id
   *   The setting id from the icon pack definition.
   * @param array $setting
   *   The settings from the icon pack definition.
   * @param array $saved_values
   *   The default saved values if any.
   *
   * @return array
   *   The form API generated with minimal keys.
   */
  protected static function initSettingForm(string $setting_id, array $setting, array $saved_values): array {
    $form = [
      '#title' => $setting['title'] ?? $setting_id,
    ];

    if (isset($setting['description'])) {
      $form['#description'] = $setting['description'];
    }

    if (isset($setting['default'])) {
      $form['#default_value'] = $setting['default'];
    }

    if (isset($saved_values[$setting_id])) {
      $form['#default_value'] = $saved_values[$setting_id];
    }

    return $form;
  }

  /**
   * Build Drupal form for an enumerations to a select.
   *
   * @param string $setting_id
   *   The setting id from the icon pack definition.
   * @param array $setting
   *   The settings from the icon pack definition.
   * @param array $saved_values
   *   The default saved values if any.
   *
   * @return array
   *   The form API generated for enum as select.
   */
  protected static function buildEnumForm(string $setting_id, array $setting, array $saved_values): array {
    $form = self::initSettingForm($setting_id, $setting, $saved_values);
    $form['#type'] = 'select';
    $form['#options'] = self::getOptions($setting);
    return $form;
  }

  /**
   * Get option list for enumerations.
   *
   * @param array $setting
   *   The settings from the icon pack definition.
   *
   * @return array
   *   The enum options for select.
   */
  protected static function getOptions(array $setting): array {
    $options = array_combine($setting['enum'], $setting['enum']);
    foreach ($options as $key => $label) {
      if (is_string($label)) {
        $options[$key] = Unicode::ucwords($label);
      }
    }

    // Key meta:enum is used to provide a description for the enum list.
    // There is currently no official JSON Schema specification for enum
    // description, meta:enum is adopted by Adobe in
    // https://github.com/adobe/jsonschema2md and Coveo Open-Source in
    // https://github.com/coveooss/json-schema-for-humans.
    if (!isset($setting['meta:enum'])) {
      return $options;
    }
    $meta = $setting['meta:enum'];

    // Remove meta:enum items not found in options.
    return array_intersect_key($meta, $options);
  }

  /**
   * Build Drupal form for a boolean setting to a checkbox.
   *
   * @param string $setting_id
   *   The setting id from the icon pack definition.
   * @param array $setting
   *   The settings from the icon pack definition.
   * @param array $saved_values
   *   The default saved values if any.
   *
   * @return array
   *   The form API generated for enum as checkbox.
   */
  protected static function buildBooleanForm(string $setting_id, array $setting, array $saved_values): array {
    $form = self::initSettingForm($setting_id, $setting, $saved_values);
    $form['#type'] = 'checkbox';
    return $form;
  }

  /**
   * Build Drupal form for a string setting to a textfield.
   *
   * @param string $setting_id
   *   The setting id from the icon pack definition.
   * @param array $setting
   *   The settings from the icon pack definition.
   * @param array $saved_values
   *   The default saved values if any.
   *
   * @return array
   *   The form API generated for enum as textfield.
   */
  protected static function buildStringForm(string $setting_id, array $setting, array $saved_values): array {
    $form = self::initSettingForm($setting_id, $setting, $saved_values);

    if (isset($setting['format']) && $setting['format'] === self::COLOR_TYPE) {
      $form['#type'] = self::COLOR_TYPE;
      return $form;
    }

    $form['#type'] = 'textfield';

    if (isset($setting['pattern']) && !empty($setting['pattern'])) {
      $form['#pattern'] = $setting['pattern'];
    }

    if (isset($setting['maxLength'])) {
      $form['#maxlength'] = $setting['maxLength'];
    }

    // We don't support minLength and pattern together because it is not
    // possible to safely merge regular expressions.
    if (!isset($setting['pattern']) && isset($setting['minLength'])) {
      $form['#pattern'] = '^.{' . $setting['minLength'] . ',}$';
    }

    return $form;
  }

  /**
   * Build Drupal form for a number or integer setting.
   *
   * @param string $setting_id
   *   The setting id from the icon pack definition.
   * @param array $setting
   *   The settings from the icon pack definition.
   * @param array $saved_values
   *   The default saved values if any.
   *
   * @return array
   *   The form API generated for enum as number.
   */
  protected static function buildNumberForm(string $setting_id, array $setting, array $saved_values): array {
    $form = self::initSettingForm($setting_id, $setting, $saved_values);

    $form['#type'] = 'number';

    if ($setting['type'] === 'integer') {
      $form['#step'] = 1;
    }

    if (isset($setting['multipleOf'])) {
      $form['#step'] = $setting['multipleOf'];
    }

    if (isset($setting['minimum'])) {
      $form['#min'] = $setting['minimum'];
    }

    if (isset($setting['maximum'])) {
      $form['#max'] = $setting['maximum'];
    }

    return $form;
  }

}
