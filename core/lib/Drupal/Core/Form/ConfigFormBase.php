<?php

namespace Drupal\Core\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Render\Element;
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
   * The $form_state key which stores a map of config keys to form elements.
   *
   * This map is generated and stored by ::storeConfigKeyToFormElementMap(),
   * which is one of the form's #after_build callbacks.
   *
   * @see ::storeConfigKeyToFormElementMap()
   *
   * @var string
   */
  protected const CONFIG_KEY_TO_FORM_ELEMENT_MAP = 'config_targets';

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
    protected $typedConfigManager = NULL,
  ) {
    $this->setConfigFactory($config_factory);

    if (!$typedConfigManager instanceof TypedConfigManagerInterface) {
      $type = get_debug_type($typedConfigManager);
      @trigger_error("Passing $type to the \$typedConfigManager parameter of ConfigFormBase::__construct() is deprecated in drupal:10.2.0 and must be an instance of \Drupal\Core\Config\TypedConfigManagerInterface in drupal:11.0.0. See https://www.drupal.org/node/3404140", E_USER_DEPRECATED);
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
   * Returns the typed config manager service.
   *
   * @return \Drupal\Core\Config\TypedConfigManagerInterface
   *   The typed config manager service.
   */
  protected function typedConfigManager(): TypedConfigManagerInterface {
    if ($this->typedConfigManager instanceof TypedConfigManagerInterface) {
      return $this->typedConfigManager;
    }
    return \Drupal::service('config.typed');
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

    // Load default values from config into any element with a #config_target
    // property.
    $form['#process'][] = '::loadDefaultValuesFromConfig';
    $form['#after_build'][] = '::storeConfigKeyToFormElementMap';

    return $form;
  }

  /**
   * Process callback to recursively load default values from #config_target.
   *
   * @param array $element
   *   The form element.
   *
   * @return array
   *   The form element, with its default value populated.
   */
  public function loadDefaultValuesFromConfig(array $element): array {
    if (array_key_exists('#config_target', $element) && !array_key_exists('#default_value', $element)) {
      $target = $element['#config_target'];
      if (is_string($target)) {
        $target = ConfigTarget::fromString($target);
      }

      $config = $this->configFactory()->getEditable($target->configName);
      $element['#default_value'] = $target->getValue($config);
    }

    foreach (Element::children($element) as $key) {
      $element[$key] = $this->loadDefaultValuesFromConfig($element[$key]);
    }
    return $element;
  }

  /**
   * #after_build callback which stores a map of element names to config keys.
   *
   * This will store an array in the form state whose keys are strings in the
   * form of `CONFIG_NAME:PROPERTY_PATH`, and whose values are instances of
   * \Drupal\Core\Form\ConfigTarget.
   *
   * This callback is run in the form's #after_build stage, rather than
   * #process, to guarantee that all of the form's elements have their final
   * #name and #parents properties set.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The processed element.
   *
   * @see \Drupal\Core\Form\ConfigFormBase::buildForm()
   */
  public function storeConfigKeyToFormElementMap(array $element, FormStateInterface $form_state): array {
    // Empty the map to ensure the information is always correct after
    // rebuilding the form.
    $form_state->set(static::CONFIG_KEY_TO_FORM_ELEMENT_MAP, []);

    return $this->doStoreConfigMap($element, $form_state);
  }

  /**
   * Helper method for #after_build callback ::storeConfigKeyToFormElementMap().
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The processed element.
   *
   * @see \Drupal\Core\Form\ConfigFormBase::storeConfigKeyToFormElementMap()
   */
  protected function doStoreConfigMap(array $element, FormStateInterface $form_state): array {
    if (array_key_exists('#config_target', $element)) {
      $map = $form_state->get(static::CONFIG_KEY_TO_FORM_ELEMENT_MAP) ?? [];

      /** @var \Drupal\Core\Form\ConfigTarget|string $target */
      $target = $element['#config_target'];
      if (is_string($target)) {
        $target = ConfigTarget::fromString($target);
      }
      elseif ($target->toConfig instanceof \Closure || $target->fromConfig instanceof \Closure) {
        // If the form is using closures as toConfig or fromConfig callables
        // then form cannot be cached.
        $form_state->disableCache();
      }

      foreach ($target->propertyPaths as $property_path) {
        if (isset($map[$target->configName][$property_path])) {
          throw new \LogicException(sprintf('Two #config_targets both target "%s" in the "%s" config: `%s` and `%s`.',
            $property_path,
            $target->configName,
            '$form[\'' . implode("']['", $map[$target->configName][$property_path]) . '\']',
            '$form[\'' . implode("']['", $element['#array_parents']) . '\']',
          ));
        }
        $map[$target->configName][$property_path] = $element['#array_parents'];
      }
      $form_state->set(static::CONFIG_KEY_TO_FORM_ELEMENT_MAP, $map);
    }
    foreach (Element::children($element) as $key) {
      $element[$key] = $this->doStoreConfigMap($element[$key], $form_state);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $map = $form_state->get(static::CONFIG_KEY_TO_FORM_ELEMENT_MAP) ?? [];
    foreach (array_keys($map) as $config_name) {
      $config = $this->configFactory()->getEditable($config_name);
      static::copyFormValuesToConfig($config, $form_state, $form);
      $typed_config = $this->typedConfigManager()->createFromNameAndData($config_name, $config->getRawData());

      $violations = $typed_config->validate();
      // Rather than immediately applying all violation messages to the
      // corresponding form elements, first collect the messages. The structure
      // of the form may cause a single form element to contain multiple config
      // property paths thanks to `type: sequence`. Common example: a <textarea>
      // with one line per sequence item.
      // @see \Drupal\Core\Config\Schema\Sequence
      // @see \Drupal\Core\Config\Schema\SequenceDataDefinition
      $violations_per_form_element = [];
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      foreach ($violations as $violation) {
        $property_path = $violation->getPropertyPath();
        // Default to index 0.
        $index = 0;

        // Detect if this is a sequence item property path, and if so, attempt
        // to fall back to the containing sequence's property path.
        if (!isset($map[$config_name][$property_path]) && preg_match("/.*\.(\d+)$/", $property_path, $matches) === 1) {
          $index = intval($matches[1]);
          // The property path as known in the config key-to-form element map
          // will not have the sequence index in it.
          $property_path = rtrim($property_path, '0123456789.');
        }

        if (isset($map[$config_name][$property_path])) {
          $config_target = ConfigTarget::fromForm($map[$config_name][$property_path], $form);
          $form_element_name = implode('][', $config_target->elementParents);
        }
        else {
          // We cannot determine where to place the violation. The only option
          // is the entire form.
          $form_element_name = '';
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
    $map = $form_state->get(static::CONFIG_KEY_TO_FORM_ELEMENT_MAP) ?? [];
    foreach (array_keys($map) as $config_name) {
      $config = $this->configFactory()->getEditable($config_name);
      static::copyFormValuesToConfig($config, $form_state, $form);
      $config->save();
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
   * @param array $form
   *   The form array.
   *
   * @see \Drupal\Core\Entity\EntityForm::copyFormValuesToEntity()
   */
  private static function copyFormValuesToConfig(Config $config, FormStateInterface $form_state, array $form): void {
    $map = $form_state->get(static::CONFIG_KEY_TO_FORM_ELEMENT_MAP);

    foreach ($map[$config->getName()] as $array_parents) {
      $target = ConfigTarget::fromForm($array_parents, $form);
      if ($target->configName !== $config->getName()) {
        continue;
      }
      $value = $form_state->getValue($target->elementParents);
      $target->setValue($config, $value, $form_state);
    }
  }

}
