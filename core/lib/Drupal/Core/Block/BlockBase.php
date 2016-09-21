<?php

namespace Drupal\Core\Block;

use Drupal\block\BlockInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Transliteration\TransliterationInterface;

/**
 * Defines a base block implementation that most blocks plugins will extend.
 *
 * This abstract class provides the generic block configuration form, default
 * block settings, and handling for general user-defined block visibility
 * settings.
 *
 * @ingroup block_api
 */
abstract class BlockBase extends ContextAwarePluginBase implements BlockPluginInterface, PluginWithFormsInterface {

  use ContextAwarePluginAssignmentTrait;
  use PluginWithFormsTrait;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * {@inheritdoc}
   */
  public function label() {
    if (!empty($this->configuration['label'])) {
      return $this->configuration['label'];
    }

    $definition = $this->getPluginDefinition();
    // Cast the admin label to a string since it is an object.
    // @see \Drupal\Core\StringTranslation\TranslatableMarkup
    return (string) $definition['admin_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->baseConfigurationDefaults(),
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * Returns generic default configuration for block plugins.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  protected function baseConfigurationDefaults() {
    return array(
      'id' => $this->getPluginId(),
      'label' => '',
      'provider' => $this->pluginDefinition['provider'],
      'label_display' => BlockInterface::BLOCK_LABEL_VISIBLE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationValue($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = $this->blockAccess($account);
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Indicates whether the block should be shown.
   *
   * Blocks with specific access checking should override this method rather
   * than access(), in order to avoid repeating the handling of the
   * $return_as_object argument.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @see self::access()
   */
  protected function blockAccess(AccountInterface $account) {
    // By default, the block is visible.
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Creates a generic configuration form for all block types. Individual
   * block plugins can add elements to this form by overriding
   * BlockBase::blockForm(). Most block plugins should not override this
   * method unless they need to alter the generic form elements.
   *
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $definition = $this->getPluginDefinition();
    $form['provider'] = array(
      '#type' => 'value',
      '#value' => $definition['provider'],
    );

    $form['admin_label'] = array(
      '#type' => 'item',
      '#title' => $this->t('Block description'),
      '#plain_text' => $definition['admin_label'],
    );
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $this->label(),
      '#required' => TRUE,
    );
    $form['label_display'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display title'),
      '#default_value' => ($this->configuration['label_display'] === BlockInterface::BLOCK_LABEL_VISIBLE),
      '#return_value' => BlockInterface::BLOCK_LABEL_VISIBLE,
    );

    // Add context mapping UI form elements.
    $contexts = $form_state->getTemporaryValue('gathered_contexts') ?: [];
    $form['context_mapping'] = $this->addContextAssignmentElement($this, $contexts);
    // Add plugin-specific settings for this block type.
    $form += $this->blockForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   *
   * Most block plugins should not override this method. To add validation
   * for a specific block type, override BlockBase::blockValidate().
   *
   * @see \Drupal\Core\Block\BlockBase::blockValidate()
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Remove the admin_label form item element value so it will not persist.
    $form_state->unsetValue('admin_label');

    $this->blockValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   *
   * Most block plugins should not override this method. To add submission
   * handling for a specific block type, override BlockBase::blockSubmit().
   *
   * @see \Drupal\Core\Block\BlockBase::blockSubmit()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Process the block's submission handling if no errors occurred only.
    if (!$form_state->getErrors()) {
      $this->configuration['label'] = $form_state->getValue('label');
      $this->configuration['label_display'] = $form_state->getValue('label_display');
      $this->configuration['provider'] = $form_state->getValue('provider');
      $this->blockSubmit($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    $definition = $this->getPluginDefinition();
    $admin_label = $definition['admin_label'];

    // @todo This is basically the same as what is done in
    //   \Drupal\system\MachineNameController::transliterate(), so it might make
    //   sense to provide a common service for the two.
    $transliterated = $this->transliteration()->transliterate($admin_label, LanguageInterface::LANGCODE_DEFAULT, '_');
    $transliterated = Unicode::strtolower($transliterated);

    $transliterated = preg_replace('@[^a-z0-9_.]+@', '', $transliterated);

    return $transliterated;
  }

  /**
   * Wraps the transliteration service.
   *
   * @return \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected function transliteration() {
    if (!$this->transliteration) {
      $this->transliteration = \Drupal::transliteration();
    }
    return $this->transliteration;
  }

  /**
   * Sets the transliteration service.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function setTransliteration(TransliterationInterface $transliteration) {
    $this->transliteration = $transliteration;
  }

}
