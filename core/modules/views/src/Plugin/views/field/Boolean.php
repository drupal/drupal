<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Component\Utility\Xss as UtilityXss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * A handler to provide proper displays for booleans.
 *
 * Allows for display of true/false, yes/no, on/off, enabled/disabled.
 *
 * Definition terms:
 *   - output formats: An array where the first entry is displayed on boolean
 *     true and the second is displayed on boolean false. An example for sticky
 *     is:
 *      @code
 *      'output formats' => [
 *        'sticky' => [t('Sticky'), ''],
 *      ],
 *      @endcode
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("boolean")]
class Boolean extends FieldPluginBase {

  /**
   * The allowed formats.
   */
  public array $formats;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['type'] = ['default' => 'yes-no'];
    $options['type_custom_true'] = ['default' => ''];
    $options['type_custom_false'] = ['default' => ''];
    $options['not'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    $default_formats = [
      'yes-no' => [$this->t('Yes'), $this->t('No')],
      'true-false' => [$this->t('True'), $this->t('False')],
      'on-off' => [$this->t('On'), $this->t('Off')],
      'enabled-disabled' => [$this->t('Enabled'), $this->t('Disabled')],
      'boolean' => [1, 0],
      'unicode-yes-no' => ['✔', '✖'],
    ];
    $output_formats = $this->definition['output formats'] ?? [];
    $custom_format = ['custom' => [$this->t('Custom')]];
    $this->formats = array_merge($default_formats, $output_formats, $custom_format);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    foreach ($this->formats as $key => $item) {
      $options[$key] = implode('/', $item);
    }

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Output format'),
      '#options' => $options,
      '#default_value' => $this->options['type'],
    ];
    $form['type_custom_true'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom output for TRUE'),
      '#default_value' => $this->options['type_custom_true'],
      '#states' => [
        'visible' => [
          'select[name="options[type]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $form['type_custom_false'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom output for FALSE'),
      '#default_value' => $this->options['type_custom_false'],
      '#states' => [
        'visible' => [
          'select[name="options[type]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $form['not'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reverse'),
      '#description' => $this->t('If checked, true will be displayed as false.'),
      '#default_value' => $this->options['not'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if (!empty($this->options['not'])) {
      $value = !$value;
    }

    if ($this->options['type'] == 'custom') {
      $custom_value = $value ? $this->options['type_custom_true'] : $this->options['type_custom_false'];
      return ViewsRenderPipelineMarkup::create(UtilityXss::filterAdmin($custom_value));
    }
    elseif (isset($this->formats[$this->options['type']])) {
      return $value ? $this->formats[$this->options['type']][0] : $this->formats[$this->options['type']][1];
    }
    else {
      return $value ? $this->formats['yes-no'][0] : $this->formats['yes-no'][1];
    }
  }

}
