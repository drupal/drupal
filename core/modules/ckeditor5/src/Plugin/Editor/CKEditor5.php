<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Editor;

use Drupal\ckeditor5\HTMLRestrictionsUtilities;
use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Heading;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ckeditor5\SmartDefaultSettings;
use Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraint;
use Drupal\editor\EditorInterface;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Plugin\EditorBase;
use Drupal\filter\FilterFormatInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Defines a CKEditor 5-based text editor for Drupal.
 *
 * @Editor(
 *   id = "ckeditor5",
 *   label = @Translation("CKEditor 5"),
 *   supports_content_filtering = TRUE,
 *   supports_inline_editing = TRUE,
 *   is_xss_safe = FALSE,
 *   supported_element_types = {
 *     "textarea"
 *   }
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class CKEditor5 extends EditorBase implements ContainerFactoryPluginInterface {

  use SchemaCheckTrait;

  /**
   * The CKEditor plugin manager.
   *
   * @var \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface
   */
  protected $ckeditor5PluginManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Smart default settings utility.
   *
   * @var \Drupal\ckeditor5\SmartDefaultSettings
   */
  protected $smartDefaultSettings;

  /**
   * The set of configured CKEditor 5 plugins.
   *
   * @var \Drupal\ckeditor5\Plugin\CKEditor5PluginInterface[]
   */
  private $plugins = [];

  /**
   * The submitted editor.
   *
   * @var \Drupal\editor\EditorInterface
   */
  private $submittedEditor;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a CKEditor5 editor plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface $ckeditor5_plugin_manager
   *   The CKEditor5 plugin manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\ckeditor5\SmartDefaultSettings $smart_default_settings
   *   The smart default settings utility.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CKEditor5PluginManagerInterface $ckeditor5_plugin_manager, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, SmartDefaultSettings $smart_default_settings, CacheBackendInterface $cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ckeditor5PluginManager = $ckeditor5_plugin_manager;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
    $this->smartDefaultSettings = $smart_default_settings;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.ckeditor5.plugin'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('ckeditor5.smart_default_settings'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return [
      'toolbar' => [
        'items' => ['heading', 'bold', 'italic'],
      ],
      'plugins' => [
        'ckeditor5_heading' => Heading::DEFAULT_CONFIGURATION,
      ],
    ];
  }

  /**
   * Validates a Text Editor + Text Format pair.
   *
   * Drupal is designed to only verify schema conformity (and validation) of
   * individual config entities. The Text Editor module layers a tightly coupled
   * Editor entity on top of the Filter module's FilterFormat config entity.
   * This inextricable coupling is clearly visible in EditorInterface:
   * \Drupal\editor\EditorInterface::getFilterFormat(). They are always paired.
   * Because not every text editor is guaranteed to be compatible with every
   * text format, the pair must be validated.
   *
   * @param \Drupal\editor\EditorInterface $text_editor
   *   The paired text editor to validate.
   * @param \Drupal\filter\FilterFormatInterface $text_format
   *   The paired text format to validate.
   * @param bool $all_compatibility_problems
   *   Get all compatibility problems (default) or only fundamental ones.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   The validation constraint violations.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the text editor is not configured to use CKEditor 5.
   *
   * @see \Drupal\editor\EditorInterface::getFilterFormat()
   * @see ckeditor5.pair.schema.yml
   */
  public static function validatePair(EditorInterface $text_editor, FilterFormatInterface $text_format, bool $all_compatibility_problems = TRUE): ConstraintViolationListInterface {
    if ($text_editor->getEditor() !== 'ckeditor5') {
      throw new \InvalidArgumentException('This text editor is not configured to use CKEditor 5.');
    }

    $typed_config_manager = \Drupal::getContainer()->get('config.typed');
    $typed_config = $typed_config_manager->createFromNameAndData(
      'ckeditor5_valid_pair__format_and_editor',
      [
        // A mix of:
        // - editor.editor.*.settings — note that "settings" is top-level in
        //   editor.editor.*, and so it is here, so all validation constraints
        //   will continue to work fine.
        'settings' => $text_editor->toArray()['settings'],
        // - filter.format.*.filters — note that "filters" is top-level in
        //   filter.format.*, and so it is here, so all validation constraints
        //   will continue to work fine.
        'filters' => $text_format->toArray()['filters'],
        // - editor.editor.*.image_upload — note that "image_upload" is
        //   top-level in editor.editor.*, and so it is here, so all validation
        //   constraints will continue to work fine.
        'image_upload' => $text_editor->toArray()['image_upload'],
      ]
    );
    $violations = $typed_config->validate();

    // Only consider validation constraint violations covering the pair, so not
    // irrelevant details such as a PrimitiveTypeConstraint in filter settings,
    // which do not affect CKEditor 5 anyway.
    foreach ($violations as $i => $violation) {
      assert($violation instanceof ConstraintViolation);
      if (explode('.', $violation->getPropertyPath())[0] === 'filters' && is_a($violation->getConstraint(), PrimitiveTypeConstraint::class)) {
        $violations->remove($i);
      }
    }

    if (!$all_compatibility_problems) {
      foreach ($violations as $i => $violation) {
        // Remove all violations that are not fundamental — these are at the
        // root (property path '').
        if ($violation->getPropertyPath() !== '') {
          $violations->remove($i);
        }
      }
    }

    return $violations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $editor = $form_state->get('editor');
    assert($editor instanceof Editor);
    $language = $this->languageManager->getCurrentLanguage();

    // When enabling CKEditor 5, generate sensible settings from the
    // pre-existing text editor/format rather than the hardcoded defaults
    // whenever possible.
    // @todo Remove after https://www.drupal.org/project/drupal/issues/3226673.
    $format = $form_state->getFormObject()->getEntity();
    assert($format instanceof FilterFormatInterface);
    if ($editor->isNew() && !$form_state->get('ckeditor5_is_active') && $form_state->get('ckeditor5_is_selected')) {
      assert($editor->getSettings() === $this->getDefaultSettings());
      if (!$format->isNew()) {
        [$editor, $messages] = $this->smartDefaultSettings->computeSmartDefaultSettings($editor, $format);
        foreach ($messages as $message) {
          $this->messenger()->addMessage($message);
        }
      }
      $eventual_editor_and_format = $this->getEventualEditorWithPrimedFilterFormat($form_state, $editor);
      // Provide the validated eventual pair in form state to
      // ::getGeneratedAllowedHtmlValue(), to update filter_html's
      // "allowed_html".
      $form_state->set('ckeditor5_validated_pair', $eventual_editor_and_format);
      // Ensure that CKEditor 5 plugins that need to interact with the Editor
      // config entity are able to access the computed Editor, which was cloned
      // from $form_state->get('editor').
      // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\ImageUpload::buildConfigurationForm
      $form_state->set('editor', $editor);
    }

    // AJAX validation errors should appear visually close to the text editor
    // since this is a very long form: otherwise they would not be noticed.
    $form['real_time_validation_errors_location'] = [
      '#type' => 'container',
      '#id' => 'ckeditor5-realtime-validation-messages-container',
    ];

    $form['toolbar'] = [
      '#type' => 'container',
      '#title' => $this->t('CKEditor 5 toolbar configuration'),
      '#theme' => 'ckeditor5_settings_toolbar',
      '#attached' => [
        'library' => $this->ckeditor5PluginManager->getAdminLibraries(),
        'drupalSettings' => [
          'ckeditor5' => [
            'language' => [
              'dir' => $language->getDirection(),
              'langcode' => $language->getId(),
            ],
          ],
        ],
      ],
    ];

    $form['available_items_description'] = [
      '#type' => 'container',
      '#markup' => $this->t('Press the down arrow key to add to the toolbar.'),
      '#id' => 'available-button-description',
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    $form['active_items_description'] = [
      '#type' => 'container',
      '#markup' => $this->t('Move this button in the toolbar by pressing the left or right arrow keys. Press the up arrow key to remove from the toolbar.'),
      '#id' => 'active-button-description',
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    // The items are encoded in markup to provide a no-JS fallback.
    // Although CKEditor 5 is useless without JS it would still be possible
    // to see all the available toolbar items provided by plugins in the format
    // that needs to be entered in the textarea. The UI app parses this list.
    $form['toolbar']['available'] = [
      '#type' => 'container',
      '#title' => 'Available items',
      '#id' => 'ckeditor5-toolbar-buttons-available',
      'available_items' => [
        '#markup' => Json::encode($this->ckeditor5PluginManager->getToolbarItems()),
      ],
    ];

    $editor_settings = $editor->getSettings();
    // This form field requires a JSON-style array of valid toolbar items.
    // e.g. ["bold","italic","|","uploadImage"].
    // CKEditor 5 config for toolbar items takes an array of strings which
    // correspond to the keys under toolbar_items in a plugin yml or annotation.
    // @see https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html
    $form['toolbar']['items'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Toolbar items'),
      '#rows' => 1,
      '#default_value' => Json::encode($editor_settings['toolbar']['items']),
      '#id' => 'ckeditor5-toolbar-buttons-selected',
      '#attributes' => [
        'tabindex' => '-1',
        'aria-hidden' => 'true',
      ],
    ];

    $form['plugin_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('CKEditor5 plugin settings'),
      '#id' => 'ckeditor5-plugin-settings',
    ];

    $this->injectPluginSettingsForm($form, $form_state, $editor);

    // Allow reliable detection of switching to CKEditor 5 from another text
    // editor (or none at all).
    $form['is_already_using_ckeditor5'] = [
      '#type' => 'hidden',
      '#default_value' => TRUE,
    ];

    return $form;
  }

  /**
   * Determines whether the plugin settings form should be visible.
   *
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $definition
   *   The configurable CKEditor 5 plugin to assess the visibility for.
   * @param \Drupal\editor\EditorInterface $editor
   *   A configured text editor object.
   *
   * @return bool
   *   Whether this configurable plugin's settings form should be visible.
   */
  private function shouldHaveVisiblePluginSettingsForm(CKEditor5PluginDefinition $definition, EditorInterface $editor): bool {
    assert($definition->isConfigurable());
    $enabled_plugins = $this->ckeditor5PluginManager->getEnabledDefinitions($editor);

    $plugin_id = $definition->id();

    // Enabled plugins should be configurable.
    if (isset($enabled_plugins[$plugin_id])) {
      return TRUE;
    }

    // There are two circumstances where a plugin not listed in $enabled_plugins
    // due to isEnabled() returning false, that should still have its config
    // form provided:
    // 1 - A conditionally enabled plugin that does not depend on a toolbar item
    // to be active.
    // 2 - A conditionally enabled plugin that does depend on a toolbar item,
    // and that toolbar item is active.
    if ($definition->hasConditions()) {
      $conditions = $definition->getConditions();
      if (!array_key_exists('toolbarItem', $conditions)) {
        return TRUE;
      }
      elseif (in_array($conditions['toolbarItem'], $editor->getSettings()['toolbar']['items'], TRUE)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Injects the CKEditor plugins settings forms as a vertical tabs subform.
   *
   * @param array &$form
   *   A reference to an associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\editor\EditorInterface $editor
   *   A configured text editor object.
   */
  private function injectPluginSettingsForm(array &$form, FormStateInterface $form_state, EditorInterface $editor): void {
    $definitions = $this->ckeditor5PluginManager->getDefinitions();
    $eventual_editor_and_format = $this->getEventualEditorWithPrimedFilterFormat($form_state, $editor);

    foreach ($definitions as $plugin_id => $definition) {
      if ($definition->isConfigurable() && $this->shouldHaveVisiblePluginSettingsForm($definition, $eventual_editor_and_format)) {
        $plugin = $this->ckeditor5PluginManager->getPlugin($plugin_id, $editor);
        $plugin_settings_form = [];
        $form['plugins'][$plugin_id] = [
          '#type' => 'details',
          '#title' => $definition->label(),
          '#open' => TRUE,
          '#group' => 'editor][settings][plugin_settings',
          '#attributes' => [
            'data-ckeditor5-plugin-id' => $plugin_id,
          ],
        ];
        $form['plugins'][$plugin_id] += $plugin->buildConfigurationForm($plugin_settings_form, $form_state);
      }
    }
  }

  /**
   * Form #after_build callback: provides text editor state changes.
   *
   * Updates the internal $this->entity object with submitted values when the
   * form is being rebuilt (e.g. submitted via AJAX), so that subsequent
   * processing (e.g. AJAX callbacks) can rely on it.
   *
   * @see \Drupal\Core\Entity\EntityForm::afterBuild()
   */
  public static function assessActiveTextEditorAfterBuild(array $element, FormStateInterface $form_state): array {
    // The case of the form being built initially, and the text editor plugin in
    // use is already CKEditor 5.
    if (!$form_state->isProcessingInput()) {
      $editor = $form_state->get('editor');
      $already_using_ckeditor5 = $editor && $editor->getEditor() === 'ckeditor5';
    }
    else {
      // Whenever there is user input, this cannot be the initial build of the
      // form and hence we need to inspect user input.
      $already_using_ckeditor5 = FALSE;
      NestedArray::getValue($form_state->getUserInput(), ['editor', 'settings', 'is_already_using_ckeditor5'], $already_using_ckeditor5);
    }

    $form_state->set('ckeditor5_is_active', $already_using_ckeditor5);
    $form_state->set('ckeditor5_is_selected', $form_state->getValue(['editor', 'editor']) === 'ckeditor5');
    return $element;
  }

  /**
   * Validate callback to inform the user of CKEditor 5 compatibility problems.
   */
  public static function validateSwitchingToCKEditor5(array $form, FormStateInterface $form_state): void {
    if (!$form_state->get('ckeditor5_is_active') && $form_state->get('ckeditor5_is_selected')) {
      $minimal_ckeditor5_editor = Editor::create([
        'format' => NULL,
        'editor' => 'ckeditor5',
      ]);
      $submitted_filter_format = CKEditor5::getSubmittedFilterFormat($form_state);
      $fundamental_incompatibilities = CKEditor5::validatePair($minimal_ckeditor5_editor, $submitted_filter_format, FALSE);
      foreach ($fundamental_incompatibilities as $violation) {
        // @codingStandardsIgnoreLine
        $form_state->setErrorByName('editor][editor', t($violation->getMessageTemplate(), $violation->getParameters()));
      }
    }
  }

  /**
   * Value callback to set the CKEditor 5-generated "allowed_html" value.
   *
   * Used to set the value of filter_html's "allowed_html" form item if the form
   * has been validated and hence `ckeditor5_validated_pair` is available
   * in form state. This allows setting a guaranteed to be valid value.
   *
   * `ckeditor5_validated_pair` can be set from two places:
   * - When switching to CKEditor 5, this is populated by
   *   CKEditor5::buildConfigurationForm().
   * - When making filter or editor settings changes, it is populated by
   *  CKEditor5::validateConfigurationForm().
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param mixed $input
   *   The incoming input to populate the form element. If this is FALSE,
   *   the element's default value should be returned.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string
   *   The value to assign to the element.
   */
  public static function getGeneratedAllowedHtmlValue(array &$element, $input, FormStateInterface $form_state): string {
    if ($form_state->isValidationComplete()) {
      $validated_format = $form_state->get('ckeditor5_validated_pair')->getFilterFormat();
      $configuration = $validated_format->filters()->get('filter_html')->getConfiguration();
      return $configuration['settings']['allowed_html'];
    }
    else {
      if ($input !== FALSE) {
        return $input;
      }
      return $element['#default_value'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $json = $form_state->getValue(['toolbar', 'items']);
    $toolbar_items = Json::decode($json);

    // This basic validation must live in the form logic because it can only
    // occur in a form context.
    if (!$toolbar_items) {
      $form_state->setErrorByName('toolbar][items', $this->t('Invalid toolbar value.'));
      return;
    }

    // Construct a Text Editor config entity with the submitted values for
    // validation. Do this on a clone: do not manipulate form state.
    $submitted_editor = clone $form_state->get('editor');
    $settings = $submitted_editor->getSettings();
    // Update settings first to match the submitted toolbar items. This is
    // necessary for ::shouldHaveVisiblePluginSettingsForm() to work.
    $settings['toolbar']['items'] = $toolbar_items;
    $submitted_editor->setSettings($settings);
    $eventual_editor_and_format_for_plugin_settings_visibility = $this->getEventualEditorWithPrimedFilterFormat($form_state, $submitted_editor);
    $settings['plugins'] = [];
    foreach ($this->ckeditor5PluginManager->getDefinitions() as $plugin_id => $definition) {
      if (!$definition->isConfigurable()) {
        continue;
      }
      // Create a fresh instance of this CKEditor 5 plugin, not tied to a text
      // editor configuration entity.
      $plugin = $this->ckeditor5PluginManager->getPlugin($plugin_id, NULL);
      // If this plugin is configurable but it has empty default configuration,
      // that means the configuration must be stored out of band.
      // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\ImageUpload
      // @see editor_image_upload_settings_form()
      $default_configuration = $plugin->defaultConfiguration();
      $configuration_stored_out_of_band = empty($default_configuration);

      if ($form_state->hasValue(['plugins', $plugin_id])) {
        $subform = $form['plugins'][$plugin_id];
        $subform_state = SubformState::createForSubform($subform, $form, $form_state);
        $plugin->validateConfigurationForm($subform, $subform_state);
        $plugin->submitConfigurationForm($subform, $subform_state);

        // If the configuration is stored out of band, ::submitConfigurationForm
        // will already have stored it. If it is not stored out of band,
        // populate $settings, to populate $submitted_editor.
        if (!$configuration_stored_out_of_band) {
          $settings['plugins'][$plugin_id] = $plugin->getConfiguration();
        }
      }
      // @see \Drupal\ckeditor5\Plugin\Editor\CKEditor5::injectPluginSettingsForm()
      elseif ($this->shouldHaveVisiblePluginSettingsForm($definition, $eventual_editor_and_format_for_plugin_settings_visibility)) {
        if (!$configuration_stored_out_of_band) {
          $settings['plugins'][$plugin_id] = $default_configuration;
        }
      }
    }
    // All plugin settings have been collected, including defaults that depend
    // on visibility. Store the collected settings, throw away the interim state
    // that allowed determining which defaults to add.
    unset($eventual_editor_and_format_for_plugin_settings_visibility);
    $submitted_editor->setSettings($settings);

    // Validate the text editor + text format pair.
    // Note that the eventual pair is computed and validated, not the received
    // pair: if the filter_html filter is in use, the CKEditor 5 configuration
    // dictates the filter_html's filter plugin's "allowed_html" setting.
    // @see ckeditor5_form_filter_format_form_alter()
    // @see ::getGeneratedAllowedHtmlValue()
    $eventual_editor_and_format = $this->getEventualEditorWithPrimedFilterFormat($form_state, $submitted_editor);
    $violations = CKEditor5::validatePair($eventual_editor_and_format, $eventual_editor_and_format->getFilterFormat());
    foreach ($violations as $violation) {
      $form_item_name = static::mapPairViolationPropertyPathsToFormNames($violation->getPropertyPath(), $form);
      // When adding a toolbar item, it is possible that not all conditions for
      // using it have been met yet. FormBuilder refuses to rebuild forms when a
      // validation error is present. But to meet the condition for the toolbar
      // item, configuration must be set in a vertical tab that must still
      // appear. Work-around: reduce the validation error to a warning message.
      // @see \Drupal\ckeditor5\Plugin\Validation\Constraint\ToolbarItemConditionsMetConstraintValidator
      if ($form_state->isRedirectDisabled() && $form_item_name === 'editor][settings][toolbar][items') {
        $this->messenger()->addWarning($violation->getMessage());
        continue;
      }
      $form_state->getCompleteFormState()->setErrorByName($form_item_name, $violation->getMessage());
    }

    // Pass it on to ::submitConfigurationForm().
    $form_state->get('editor')->setSettings($settings);

    // Provide the validated eventual pair in form state to
    // ::getGeneratedAllowedHtmlValue(), to update filter_html's
    // "allowed_html".
    $form_state->set('ckeditor5_validated_pair', $eventual_editor_and_format);

    assert(TRUE === $this->checkConfigSchema(\Drupal::getContainer()->get('config.typed'), 'editor.editor.id_does_not_matter', $submitted_editor->toArray()), 'Schema errors: ' . print_r($this->checkConfigSchema(\Drupal::getContainer()->get('config.typed'), 'editor.editor.id_does_not_matter', $submitted_editor->toArray()), TRUE));
  }

  /**
   * Gets the submitted text format config entity from form state.
   *
   * Needed for validation.
   *
   * @param \Drupal\Core\Form\FormStateInterface $filter_format_form_state
   *   The text format configuration form's form state.
   *
   * @return \Drupal\filter\FilterFormatInterface
   *   A FilterFormat config entity representing the current filter form state.
   */
  protected static function getSubmittedFilterFormat(FormStateInterface $filter_format_form_state): FilterFormatInterface {
    $submitted_filter_format = clone $filter_format_form_state->getFormObject()->getEntity();
    assert($submitted_filter_format instanceof FilterFormatInterface);

    // Get only the values of the filter_format form state that are relevant for
    // checking compatibility. This logic is copied from FilterFormatFormBase.
    // @see \Drupal\ckeditor5\Plugin\Validation\Constraint\FundamentalCompatibilityConstraintValidator
    // @see \Drupal\filter\FilterFormatFormBase::submitForm()
    $filter_format_form_values = array_intersect_key(
      $filter_format_form_state->getValues(),
      array_flip(['filters', 'filter_settings']
    ));
    foreach ($filter_format_form_values as $key => $value) {
      if ($key !== 'filters') {
        $submitted_filter_format->set($key, $value);
      }
      else {
        foreach ($value as $instance_id => $config) {
          $submitted_filter_format->setFilterConfig($instance_id, $config);
        }
      }
    }

    return $submitted_filter_format;
  }

  /**
   * Gets the eventual text format config entity: from form state + editor.
   *
   * Needed for validation.
   *
   * @param \Drupal\Core\Form\SubformStateInterface $editor_form_state
   *   The text editor configuration form's form state.
   * @param \Drupal\editor\EditorInterface $submitted_editor
   *   The current text editor config entity.
   *
   * @return \Drupal\editor\EditorInterface
   *   A clone of the received Editor config entity , with a primed associated
   *   FilterFormat that corresponds to the current form state, to avoid the
   *   stored FilterFormat config entity being loaded.
   */
  protected function getEventualEditorWithPrimedFilterFormat(SubformStateInterface $editor_form_state, EditorInterface $submitted_editor): EditorInterface {
    $submitted_filter_format = static::getSubmittedFilterFormat($editor_form_state->getCompleteFormState());

    $pair = static::createEphemeralPairedEditor($submitted_editor, $submitted_filter_format);

    if ($pair->getFilterFormat()->filters('filter_html')->status) {
      // Compute elements provided by the current CKEditor 5 settings.
      $enabled_plugins = array_keys($this->ckeditor5PluginManager->getEnabledDefinitions($pair));
      $elements = $this->ckeditor5PluginManager->getProvidedElements($enabled_plugins, $pair);

      // Compute eventual filter_html setting. Eventual as in: this is the list
      // of eventually allowed HTML tags.
      // @see \Drupal\filter\FilterFormatFormBase::submitForm()
      // @see ckeditor5_form_filter_format_form_alter()
      $allowed_html = implode(' ', HTMLRestrictionsUtilities::toReadableElements($elements));
      $filter_html_config = $pair->getFilterFormat()->filters('filter_html')->getConfiguration();
      $filter_html_config['settings']['allowed_html'] = $allowed_html;
      $pair->getFilterFormat()->setFilterConfig('filter_html', $filter_html_config);
    }

    return $pair;
  }

  /**
   * Creates an ephemeral pair of text editor + text format config entity.
   *
   * Clones the given text editor config entity object and then overwrites its
   * $filterFormat property, to prevent loading the text format config entity
   * from entity storage in calls to Editor::hasAssociatedFilterFormat() and
   * Editor::getFilterFormat().
   * This is necessary to be able to evaluate unsaved text editor and format
   * config entities:
   * - for assessing which CKEditor 5 plugins are enabled and whose settings
   *   forms to show
   * - for validating them.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   The submitted text editor config entity, constructed from form values.
   * @param \Drupal\filter\FilterFormatInterface $filter_format
   *   The submitted text format config entity, constructed from form values.
   *
   * @return \Drupal\editor\EditorInterface
   *   A clone of the given text editor config entity, with its $filterFormat
   *   property set to a clone of the given text format config entity.
   *
   * @throws \ReflectionException
   *
   * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::isPluginDisabled()
   * @todo Remove this in https://www.drupal.org/project/drupal/issues/3231347
   */
  protected static function createEphemeralPairedEditor(EditorInterface $editor, FilterFormatInterface $filter_format): EditorInterface {
    $paired_editor = clone $editor;
    $reflector = new \ReflectionObject($paired_editor);
    $property = $reflector->getProperty('filterFormat');
    $property->setAccessible(TRUE);
    $property->setValue($paired_editor, clone $filter_format);
    return $paired_editor;
  }

  /**
   * Maps Text Editor config object property paths to form names.
   *
   * @param string $property_path
   *   A config object property path.
   * @param array $subform
   *   The subform being checked.
   *
   * @return string
   *   The corresponding form name in the subform.
   */
  protected static function mapViolationPropertyPathsToFormNames(string $property_path, array $subform): string {
    $parts = explode('.', $property_path);
    // The "settings" form element does exist, but one level above the Text
    // Editor-specific form. This is operating on a subform.
    $shifted = array_shift($parts);
    assert($shifted === 'settings');

    // It is not required (nor sensible) for the form structure to match the
    // config schema structure 1:1. Automatically identify the relevant form
    // name. Try to be specific. Worst case, an entire plugin settings vertical
    // tab is targeted. (Hence the minimum of 2 parts: the property path gets at
    // minimum mapped to 'toolbar.items' or 'plugins.<plugin ID>'.)
    while (count($parts) > 2 && !NestedArray::keyExists($subform, $parts)) {
      array_pop($parts);
    }
    assert(NestedArray::keyExists($subform, $parts));
    return implode('][', array_merge(['settings'], $parts));
  }

  /**
   * Maps Text Editor + Text Format pair property paths to form names.
   *
   * @param string $property_path
   *   A config object property path.
   * @param array $form
   *   The form being checked.
   *
   * @return string
   *   The corresponding form name in the complete form.
   */
  protected static function mapPairViolationPropertyPathsToFormNames(string $property_path, array $form): string {
    // Fundamental compatibility errors are at the root. Map these to the text
    // editor plugin dropdown.
    if ($property_path === '') {
      return 'editor][editor';
    }

    // Filters are top-level.
    if (preg_match('/^filters\..*/', $property_path)) {
      return implode('][', array_merge(explode('.', $property_path), ['settings']));
    }

    // Everything else is in the subform.
    return 'editor][' . static::mapViolationPropertyPathsToFormNames($property_path, $form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @see ::validateConfigurationForm()
    $editor = $form_state->get('editor');

    // Prepare the editor settings for editor_form_filter_admin_format_submit().
    // This strips away unwanted form values too, because those never can exist
    // in the already validated Editor config entity.
    $form_state->setValues($editor->getSettings());

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(Editor $editor) {
    $toolbar_items = $editor->getSettings()['toolbar']['items'];
    $plugin_config = $this->ckeditor5PluginManager->getCKEditor5PluginConfig($editor);

    $settings = [
      'toolbar' => [
        'items' => $toolbar_items,
        'shouldNotGroupWhenFull' => in_array('-', $toolbar_items, TRUE),
      ],
    ] + $plugin_config;

    if ($this->moduleHandler->moduleExists('locale')) {
      $ui_langcode = 'en';
      $ckeditor_langcodes = $this->getLangcodes();
      $language_interface = $this->languageManager->getCurrentLanguage();
      if (isset($ckeditor_langcodes[$language_interface->getId()])) {
        $ui_langcode = $ckeditor_langcodes[$language_interface->getId()];
      }
      $settings['language']['ui'] = $ui_langcode;
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    $plugin_libraries = $this->ckeditor5PluginManager->getEnabledLibraries($editor);

    if ($this->moduleHandler->moduleExists('locale')) {
      $ui_langcode = 'en';
      $ckeditor_langcodes = $this->getLangcodes();
      $language_interface = $this->languageManager->getCurrentLanguage();
      if (isset($ckeditor_langcodes[$language_interface->getId()])) {
        $ui_langcode = $ckeditor_langcodes[$language_interface->getId()];
      }
      $plugin_libraries[] = 'core/ckeditor5.translations.' . $ui_langcode;
    }

    return $plugin_libraries;
  }

  /**
   * Returns a list of language codes supported by CKEditor 5.
   *
   * @return array
   *   An associative array keyed by language codes.
   */
  protected function getLangcodes(): array {
    // Cache the file system based language list calculation because this would
    // be expensive to calculate all the time. The cache is cleared on core
    // upgrades which is the only situation the CKEditor file listing should
    // change.
    $langcode_cache = $this->cache->get('ckeditor5.langcodes');
    if (!empty($langcode_cache)) {
      $langcodes = $langcode_cache->data;
    }
    if (empty($langcodes)) {
      $langcodes = [];
      // Collect languages included with CKEditor 5 based on file listing.
      $files = scandir('core/assets/vendor/ckeditor5/translations');
      foreach ($files as $file) {
        if ($file[0] !== '.' && preg_match('/\.js$/', $file)) {
          $langcode = basename($file, '.js');
          $langcodes[$langcode] = $langcode;
        }
      }
      $this->cache->set('ckeditor5.langcodes', $langcodes);
    }

    // Get language mapping if available to map to Drupal language codes.
    // This is configurable in the user interface and not expensive to get, so
    // we don't include it in the cached language list.
    $language_mappings = $this->moduleHandler->moduleExists('language') ? language_get_browser_drupal_langcode_mappings() : [];
    foreach ($langcodes as $langcode) {
      // If this language code is available in a Drupal mapping, use that to
      // compute a possibility for matching from the Drupal langcode to the
      // CKEditor langcode.
      // For instance, CKEditor uses the langcode 'no' for Norwegian, Drupal
      // uses 'nb'. This would then remove the 'no' => 'no' mapping and replace
      // it with 'nb' => 'no'. Now Drupal knows which CKEditor translation to
      // load.
      if (isset($language_mappings[$langcode]) && !isset($langcodes[$language_mappings[$langcode]])) {
        $langcodes[$language_mappings[$langcode]] = $langcode;
        unset($langcodes[$langcode]);
      }
    }

    return $langcodes;
  }

}
