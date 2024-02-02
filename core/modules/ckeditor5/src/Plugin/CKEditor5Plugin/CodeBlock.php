<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Code Block plugin configuration.
 *
 * @internal
 *   Plugin classes are internal.
 */
class CodeBlock extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['languages'] = [
      '#title' => $this->t('Programming languages'),
      '#type' => 'textarea',
      '#description' => $this->t('A list of programming languages that will be provided in the "Code Block" dropdown. Enter one value per line, in the format key|label. Example: php|PHP.'),
    ];
    if (!empty($this->configuration['languages'])) {
      $as_selectors = '';
      foreach ($this->configuration['languages'] as $language) {
        $as_selectors .= sprintf("%s|%s\n", $language['language'], $language['label']);
      }
      $form['languages']['#default_value'] = $as_selectors;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_value = $form_state->getValue('languages');
    [$styles, $not_parseable_lines] = self::parseLanguagesFromValue($form_value);
    if (!empty($not_parseable_lines)) {
      $line_numbers = array_keys($not_parseable_lines);
      $form_state->setError($form['languages'], $this->formatPlural(
        count($not_parseable_lines),
        'Line @line-number does not contain a valid value. Enter a valid language key followed by a pipe symbol and a label.',
        'Lines @line-numbers do not contain a valid value. Enter a valid language key followed by a pipe symbol and a label.',
        [
          '@line-number' => reset($line_numbers),
          '@line-numbers' => implode(', ', $line_numbers),
        ]
      ));
    }
    $form_state->setValue('languages', $styles);
  }

  /**
   * Parses the line-based (for form) Code Block configuration.
   *
   * @param string $form_value
   *   A string containing >=1 lines with on each line a language key and label.
   *
   * @return array
   *   The parsed equivalent: a list of arrays with each containing:
   *   - label: the label after the pipe symbol, with whitespace trimmed
   *   - language: the key for the language
   */
  protected static function parseLanguagesFromValue(string $form_value): array {
    $not_parseable_lines = [];

    $lines = explode("\n", $form_value);
    $languages = [];
    foreach ($lines as $line) {
      if (empty(trim($line))) {
        continue;
      }

      // Parse the line.
      [$language, $label] = array_map('trim', explode('|', $line));

      $languages[] = [
        'label' => $label,
        'language' => $language,
      ];
    }
    return [$languages, $not_parseable_lines];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['languages'] = $form_state->getValue('languages');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'languages' => [
        ['language' => 'plaintext', 'label' => 'Plain text'],
        ['language' => 'c', 'label' => 'C'],
        ['language' => 'cs', 'label' => 'C#'],
        ['language' => 'cpp', 'label' => 'C++'],
        ['language' => 'css', 'label' => 'CSS'],
        ['language' => 'diff', 'label' => 'Diff'],
        ['language' => 'html', 'label' => 'HTML'],
        ['language' => 'java', 'label' => 'Java'],
        ['language' => 'javascript', 'label' => 'JavaScript'],
        ['language' => 'php', 'label' => 'PHP'],
        ['language' => 'python', 'label' => 'Python'],
        ['language' => 'ruby', 'label' => 'Ruby'],
        ['language' => 'typescript', 'label' => 'TypeScript'],
        ['language' => 'xml', 'label' => 'XML'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    return [
      'codeBlock' => [
        'languages' => $this->configuration['languages'],
      ],
    ];
  }

}
