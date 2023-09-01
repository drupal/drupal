<?php

namespace Drupal\Core\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for implementing system configuration forms.
 *
 * Subclasses of this form can choose to use config validation instead of form-
 * -specific validation logic. To do that, override copyFormValuesToConfig().
 */
abstract class ConfigFormBase extends FormBase {
  use ConfigFormBaseTrait;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected ?TypedConfigManagerInterface $typedConfigManager = NULL,
  ) {
    $this->setConfigFactory($config_factory);
    if ($this->typedConfigManager === NULL) {
      @trigger_error('Calling ConfigFormBase::__construct() without the $typedConfigManager argument is deprecated in drupal:10.2.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3373502', E_USER_DEPRECATED);
      $this->typedConfigManager = \Drupal::service('config.typed');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    assert($this->typedConfigManager instanceof TypedConfigManagerInterface);
    foreach ($this->getEditableConfigNames() as $config_name) {
      $config = $this->config($config_name);
      try {
        static::copyFormValuesToConfig($config, $form_state);
      }
      catch (\BadMethodCallException $e) {
        // Nothing to do: this config form does not yet use validation
        // constraints. Continue trying the other editable config, to allow
        // partial adoption.
        continue;
      }
      $typed_config = $this->typedConfigManager->createFromNameAndData($config_name, $config->getRawData());

      $violations = $typed_config->validate();
      // Rather than immediately applying all violation messages to the
      // corresponding form elements, first collect the messages. The structure
      // of the form may cause a single form element to contain multiple config
      // property paths thanks to `type: sequence`. Common example: a <textarea>
      // with one line per sequence item.
      // @see \Drupal\Core\Config\Schema\Sequence
      // @see \Drupal\Core\Config\Schema\SequenceDataDefinition
      $violations_per_form_element = [];
      foreach ($violations as $violation) {
        $property_path = $violation->getPropertyPath();
        $form_element_name = static::mapConfigKeyToFormElementName($config_name, $property_path);
        // Default to index 0.
        $index = 0;
        // Detect if this is a sequence property path, and if so, determine the
        // actual sequence index.
        $matches = [];
        if (preg_match("/.*\.(\d+)$/", $property_path, $matches) === 1) {
          $index = intval($matches[1]);
        }
        $violations_per_form_element[$form_element_name][$index] = $violation;
      }

      // Now that we know how many constraint violation messages exist per form
      // element, set them. This is crucial because FormState::setErrorByName()
      // only allows a single validation error message per form element.
      // @see \Drupal\Core\Form\FormState::setErrorByName()
      foreach ($violations_per_form_element as $form_element_name => $violations) {
        // When only a single message exists, just set it.
        if (count($violations) === 1) {
          $form_state->setErrorByName($form_element_name, reset($violations)->getMessage());
          continue;
        }

        // However, if multiple exist, that implies it's a single form element
        // containing a `type: sequence`.
        $form_state->setErrorByName($form_element_name, $this->formatMultipleViolationsMessage($form_element_name, $violations));
      }
    }
  }

  /**
   * Formats multiple violation messages associated with a single form element.
   *
   * Validation constraints only know the internal data structure (the
   * configuration schema structure), but this need not be a disadvantage:
   * rather than informing the user some values are wrong, it is possible
   * guide them directly to the Nth entry in the sequence.
   *
   * To further improve the user experience, it is possible to override
   * method in subclasses to use specific knowledge about the structure of the
   * form and the nature of the data being validated, to instead generate more
   * precise and/or shortened violation messages.
   *
   * @param string $form_element_name
   *   The form element for which to format multiple violation messages.
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   The list of constraint violations that apply to this form element.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected function formatMultipleViolationsMessage(string $form_element_name, array $violations): TranslatableMarkup {
    $transformed_message_parts = [];
    foreach ($violations as $index => $violation) {
      // Note that `@validation_error_message` (should) already contain a
      // trailing period, hence it is intentionally absent here.
      $transformed_message_parts[] = $this->t('Entry @human_index: @validation_error_message', [
        // Humans start counting from 1, not 0.
        '@human_index' => $index + 1,
        // Translators may not necessarily know what "violation constraint
        // messages" are, but they definitely know "validation errors".
        '@validation_error_message' => $violation->getMessage(),
      ]);
    }
    return $this->t(implode("\n", $transformed_message_parts));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getEditableConfigNames() as $config_name) {
      $config = $this->config($config_name);
      try {
        static::copyFormValuesToConfig($config, $form_state);
        $config->save();
      }
      catch (\BadMethodCallException $e) {
        // Nothing to do: this config form does not yet use validation
        // constraints. Continue trying the other editable config, to allow
        // partial adoption.
        continue;
      }
    }
    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
  }

  /**
   * Copies form values to Config keys.
   *
   * This should not change existing Config key-value pairs that are not being
   * edited by this form.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The configuration being edited.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\Core\Entity\EntityForm::copyFormValuesToEntity()
   */
  protected static function copyFormValuesToConfig(Config $config, FormStateInterface $form_state): void {
    // This allows ::submitForm() and ::validateForm() to know that this config
    // form is not yet using constraint-based validation.
    throw new \BadMethodCallException();
  }

  /**
   * Maps the given Config key to a form element name.
   *
   * @param string $config_name
   *   The name of the Config whose value triggered a validation error.
   * @param string $key
   *   The Config key that triggered a validation error (which corresponds to a
   *   property path on the validation constraint violation).
   *
   * @return string
   *   The corresponding form element name.
   */
  protected static function mapConfigKeyToFormElementName(string $config_name, string $key) : string {
    return self::defaultMapConfigKeyToFormElementName($config_name, $key);
  }

  /**
   * Default implementation for ::mapConfigKeyToFormElementName().
   *
   * Suitable when the configuration is mapped 1:1 to form elements: when the
   * keys in the Config match the form element names exactly.
   *
   * @param string $config_name
   *   The name of the Config whose value triggered a validation error.
   * @param string $key
   *   The Config key that triggered a validation error (which corresponds to a
   *   property path on the validation constraint violation).
   *
   * @return string
   *   The corresponding form element name.
   */
  final protected static function defaultMapConfigKeyToFormElementName(string $config_name, string $key) : string {
    return str_replace('.', '][', $key);
  }

}
