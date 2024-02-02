<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Style plugin configuration.
 *
 * @internal
 *   Plugin classes are internal.
 */
class Style extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, CKEditor5PluginElementsSubsetInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['styles'] = [
      '#title' => $this->t('Styles'),
      '#type' => 'textarea',
      '#description' => $this->t('A list of classes that will be provided in the "Style" dropdown. Enter one or more classes on each line in the format: element.classA.classB|Label. Example: h1.title|Title. Advanced example: h1.fancy.title|Fancy title.<br />These styles should be available in your theme\'s CSS file.'),
    ];
    if (!empty($this->configuration['styles'])) {
      $as_selectors = '';
      foreach ($this->configuration['styles'] as $style) {
        [$tag, $classes] = self::getTagAndClasses(HTMLRestrictions::fromString($style['element']));
        $as_selectors .= sprintf("%s.%s|%s\n", $tag, implode('.', $classes), $style['label']);
      }
      $form['styles']['#default_value'] = $as_selectors;
    }

    return $form;
  }

  /**
   * Gets the tag and classes for a parsed style element.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $style_element
   *   A parsed style element.
   *
   * @return array
   *   An array containing two values:
   *   - a HTML tag name
   *   - a list of classes
   *
   * @internal
   */
  public static function getTagAndClasses(HTMLRestrictions $style_element): array {
    $tag = array_keys($style_element->getAllowedElements())[0];
    $classes = array_keys($style_element->getAllowedElements()[$tag]['class']);
    return [$tag, $classes];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Match the config schema structure at ckeditor5.plugin.ckeditor5_style.
    $form_value = $form_state->getValue('styles');
    [$styles, $invalid_lines] = self::parseStylesFormValue($form_value);
    if (!empty($invalid_lines)) {
      $line_numbers = array_keys($invalid_lines);
      $form_state->setError($form['styles'], $this->formatPlural(
        count($invalid_lines),
        'Line @line-number does not contain a valid value. Enter a valid CSS selector containing one or more classes, followed by a pipe symbol and a label.',
        'Lines @line-numbers do not contain a valid value. Enter a valid CSS selector containing one or more classes, followed by a pipe symbol and a label.',
        [
          '@line-number' => reset($line_numbers),
          '@line-numbers' => implode(', ', $line_numbers),
        ]
      ));
    }
    $form_state->setValue('styles', $styles);
  }

  /**
   * Parses the line-based (for form) style configuration.
   *
   * @param string $form_value
   *   A string containing >=1 lines with on each line a CSS selector targeting
   *   1 tag with >=1 classes, a pipe symbol and a label. An example of a single
   *   line: `p.foo.bar|Foo bar paragraph`.
   *
   * @return array
   *   The parsed equivalent: a list of arrays with each containing:
   *   - label: the label after the pipe symbol, with whitespace trimmed
   *   - element: the CKEditor 5 element equivalent of the tag + classes
   *
   * @internal
   *   This method is public only to allow the CKEditor 4 to 5 upgrade path to
   *   reuse this logic. Mark this private in https://www.drupal.org/i/3239012.
   *
   * @see \Drupal\ckeditor5\Plugin\CKEditor4To5Upgrade\Core::mapCKEditor4SettingsToCKEditor5Configuration()
   */
  public static function parseStylesFormValue(string $form_value): array {
    $invalid_lines = [];

    $lines = explode("\n", $form_value);
    $styles = [];
    foreach ($lines as $index => $line) {
      if (empty(trim($line))) {
        continue;
      }

      // Parse the line.
      [$selector, $label] = array_map('trim', explode('|', $line));

      // Validate the selector.
      $selector_matches = [];
      // @see https://www.w3.org/TR/CSS2/syndata.html#:~:text=In%20CSS%2C%20identifiers%20(including%20element,hyphen%20followed%20by%20a%20digit
      if (!preg_match('/^([a-z][0-9a-zA-Z\-]*)((\.[a-zA-Z0-9\x{00A0}-\x{FFFF}\-_]+)+)$/u', $selector, $selector_matches)) {
        $invalid_lines[$index + 1] = $line;
        continue;
      }

      // Parse selector into tag + classes and normalize.
      $tag = $selector_matches[1];
      $classes = array_filter(explode('.', $selector_matches[2]));
      $normalized = HTMLRestrictions::fromString(sprintf('<%s class="%s">', $tag, implode(' ', $classes)));

      $styles[] = [
        'label' => $label,
        'element' => $normalized->toCKEditor5ElementsArray()[0],
      ];
    }
    return [$styles, $invalid_lines];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['styles'] = $form_state->getValue('styles');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'styles' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSubset(): array {
    return array_column($this->configuration['styles'], 'element');
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $definitions = [];
    foreach ($this->configuration['styles'] as $style) {
      [$tag, $classes] = self::getTagAndClasses(HTMLRestrictions::fromString($style['element']));
      // Transform configured styles to the configuration structure expected by
      // the CKEditor 5 Style plugin.
      $definitions[] = [
        'name' => $style['label'],
        'element' => $tag,
        'classes' => $classes,
      ];
    }
    return [
      'style' => [
        'definitions' => $definitions,
      ],
    ];
  }

}
