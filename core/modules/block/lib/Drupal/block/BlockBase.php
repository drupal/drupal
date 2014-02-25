<?php

/**
 * @file
 * Contains \Drupal\block\BlockBase.
 */

namespace Drupal\block;

use Drupal\Core\Plugin\PluginBase;
use Drupal\block\BlockInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a base block implementation that most blocks plugins will extend.
 *
 * This abstract class provides the generic block configuration form, default
 * block settings, and handling for general user-defined block visibility
 * settings.
 */
abstract class BlockBase extends PluginBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration += $this->defaultConfiguration() + array(
      'label' => '',
      'module' => $plugin_definition['module'],
      'label_display' => BlockInterface::BLOCK_LABEL_VISIBLE,
      'cache' => DRUPAL_NO_CACHE,
    );
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
    $this->configuration = $configuration;
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
  public function access(AccountInterface $account) {
    // By default, the block is visible unless user-configured rules indicate
    // that it should be hidden.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * Creates a generic configuration form for all block types. Individual
   * block plugins can add elements to this form by overriding
   * BlockBase::blockForm(). Most block plugins should not override this
   * method unless they need to alter the generic form elements.
   *
   * @see \Drupal\block\BlockBase::blockForm()
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $definition = $this->getPluginDefinition();
    $form['module'] = array(
      '#type' => 'value',
      '#value' => $definition['module'],
    );

    $form['admin_label'] = array(
      '#type' => 'item',
      '#title' => t('Block description'),
      '#markup' => $definition['admin_label'],
    );
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => !empty($this->configuration['label']) ? $this->configuration['label'] : $definition['admin_label'],
      '#required' => TRUE,
    );
    $form['label_display'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display title'),
      '#default_value' => ($this->configuration['label_display'] === BlockInterface::BLOCK_LABEL_VISIBLE),
      '#return_value' => BlockInterface::BLOCK_LABEL_VISIBLE,
    );
    $form['cache'] = array(
      '#type' => 'value',
      '#value' => $this->configuration['cache'],
    );

    // Add plugin-specific settings for this block type.
    $form += $this->blockForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, &$form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   *
   * Most block plugins should not override this method. To add validation
   * for a specific block type, override BlockBase::blockValdiate().
   *
   * @see \Drupal\block\BlockBase::blockValidate()
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
    $this->blockValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, &$form_state) {}

  /**
   * {@inheritdoc}
   *
   * Most block plugins should not override this method. To add submission
   * handling for a specific block type, override BlockBase::blockSubmit().
   *
   * @see \Drupal\block\BlockBase::blockSubmit()
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    // Process the block's submission handling if no errors occurred only.
    if (!form_get_errors($form_state)) {
      $this->configuration['label'] = $form_state['values']['label'];
      $this->configuration['label_display'] = $form_state['values']['label_display'];
      $this->configuration['module'] = $form_state['values']['module'];
      $this->blockSubmit($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {}

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    $definition = $this->getPluginDefinition();
    $admin_label = $definition['admin_label'];

    // @todo This is basically the same as what is done in
    //   \Drupal\system\MachineNameController::transliterate(), so it might make
    //   sense to provide a common service for the two.
    $transliteration_service = \Drupal::transliteration();
    $transliterated = $transliteration_service->transliterate($admin_label, Language::LANGCODE_DEFAULT, '_');

    $replace_pattern = '[^a-z0-9_.]+';

    $transliterated = Unicode::strtolower($transliterated);

    if (isset($replace_pattern)) {
      $transliterated = preg_replace('@' . $replace_pattern . '@', '', $transliterated);
    }

    return $transliterated;
  }

}
