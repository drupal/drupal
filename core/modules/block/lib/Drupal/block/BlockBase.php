<?php

/**
 * @file
 * Contains \Drupal\block\BlockBase.
 */

namespace Drupal\block;

use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\block\BlockInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Language\Language;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a base block implementation that most blocks plugins will extend.
 *
 * This abstract class provides the generic block configuration form, default
 * block settings, and handling for general user-defined block visibility
 * settings.
 *
 * @ingroup block_api
 */
abstract class BlockBase extends ContextAwarePluginBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    if (!empty($this->configuration['label'])) {
      return $this->configuration['label'];
    }

    $definition = $this->getPluginDefinition();
    return $definition['admin_label'];
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
      'cache' => array(
        'max_age' => 0,
        'contexts' => array(),
      ),
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
    $form['provider'] = array(
      '#type' => 'value',
      '#value' => $definition['provider'],
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
      '#default_value' => $this->label(),
      '#required' => TRUE,
    );
    $form['label_display'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display title'),
      '#default_value' => ($this->configuration['label_display'] === BlockInterface::BLOCK_LABEL_VISIBLE),
      '#return_value' => BlockInterface::BLOCK_LABEL_VISIBLE,
    );
    // Identical options to the ones for page caching.
    // @see \Drupal\system\Form\PerformanceForm::buildForm()
    $period = array(0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400);
    $period = array_map('format_interval', array_combine($period, $period));
    $period[0] = '<' . t('no caching') . '>';
    $period[\Drupal\Core\Cache\Cache::PERMANENT] = t('Forever');
    $form['cache'] = array(
      '#type' => 'details',
      '#title' => t('Cache settings'),
    );
    $form['cache']['max_age'] = array(
      '#type' => 'select',
      '#title' => t('Maximum age'),
      '#description' => t('The maximum time this block may be cached.'),
      '#default_value' => $this->configuration['cache']['max_age'],
      '#options' => $period,
    );
    $contexts = \Drupal::service("cache_contexts")->getLabels();
    // Blocks are always rendered in a "per theme" cache context. No need to
    // show that option to the end user.
    unset($contexts['cache_context.theme']);
    $form['cache']['contexts'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Vary by context'),
      '#description' => t('The contexts this cached block must be varied by.'),
      '#default_value' => $this->configuration['cache']['contexts'],
      '#options' => $contexts,
      '#states' => array(
        'disabled' => array(
          ':input[name="settings[cache][max_age]"]' => array('value' => (string) 0),
        ),
      ),
    );
    if (count($this->getRequiredCacheContexts()) > 0) {
      // Remove the required cache contexts from the list of contexts a user can
      // choose to modify by: they must always be applied.
      $context_labels = array();
      foreach ($this->getRequiredCacheContexts() as $context) {
        $context_labels[] = $form['cache']['contexts']['#options'][$context];
        unset($form['cache']['contexts']['#options'][$context]);
      }
      $required_context_list = implode(', ', $context_labels);
      $form['cache']['contexts']['#description'] .= ' ' . t('This block is <em>always</em> varied by the following contexts: %required-context-list.', array('%required-context-list' => $required_context_list));
    }

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
    // Transform the #type = checkboxes value to a numerically indexed array.
    $form_state['values']['cache']['contexts'] = array_values(array_filter($form_state['values']['cache']['contexts']));

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
      $this->configuration['provider'] = $form_state['values']['provider'];
      $this->configuration['cache'] = $form_state['values']['cache'];
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

  /**
   * Returns the cache contexts required for this block.
   *
   * @return array
   *   The required cache contexts IDs.
   */
  protected function getRequiredCacheContexts() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKeys() {
    // Return the required cache contexts, merged with the user-configured cache
    // contexts, if any.
    return array_merge($this->getRequiredCacheContexts(), $this->configuration['cache']['contexts']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // If a block plugin's output changes, then it must be able to invalidate a
    // cache tag that affects all instances of this block: across themes and
    // across regions.
    $block_plugin_cache_tag = str_replace(':', '__', $this->getPluginID());
    return array('block_plugin' => array($block_plugin_cache_tag));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheBin() {
    return 'render';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return (int)$this->configuration['cache']['max_age'];
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    // Similar to the page cache, a block is cacheable if it has a max age.
    // Blocks that should never be cached can override this method to simply
    // return FALSE.
    $max_age = $this->getCacheMaxAge();
    return $max_age === Cache::PERMANENT || $max_age > 0;
  }

}
