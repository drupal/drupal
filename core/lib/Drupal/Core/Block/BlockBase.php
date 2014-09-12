<?php

/**
 * @file
 * Contains \Drupal\Core\Block\BlockBase.
 */

namespace Drupal\Core\Block;

use Drupal\block\BlockInterface;
use Drupal\block\Event\BlockConditionContextEvent;
use Drupal\block\Event\BlockEvents;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Condition\ConditionPluginBag;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Cache\Cache;
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
abstract class BlockBase extends ContextAwarePluginBase implements BlockPluginInterface {

  use ConditionAccessResolverTrait;

  /**
   * The condition plugin bag.
   *
   * @var \Drupal\Core\Condition\ConditionPluginBag
   */
  protected $conditionBag;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionPluginManager;

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
    // @see \Drupal\Core\StringTranslation\TranslationWrapper
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
    return array(
      'visibility' => $this->getVisibilityConditions()->getConfiguration(),
    ) + $this->configuration;
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
    // @todo Allow list of conditions to be configured in
    //   https://drupal.org/node/2284687.
    $visibility = array_map(function ($definition) {
      return array('id' => $definition['id']);
    }, $this->conditionPluginManager()->getDefinitions());
    unset($visibility['current_theme']);

    return array(
      'id' => $this->getPluginId(),
      'label' => '',
      'provider' => $this->pluginDefinition['provider'],
      'label_display' => BlockInterface::BLOCK_LABEL_VISIBLE,
      'cache' => array(
        'max_age' => 0,
        'contexts' => array(),
      ),
      'visibility' => $visibility,
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
    // @todo Add in a context mapping until the UI supports configuring them,
    //   see https://drupal.org/node/2284687.
    $mappings['user_role']['current_user'] = 'user';

    $conditions = $this->getVisibilityConditions();
    $contexts = $this->getConditionContexts();
    foreach ($conditions as $condition_id => $condition) {
      if ($condition instanceof ContextAwarePluginInterface) {
        if (!isset($mappings[$condition_id])) {
          $mappings[$condition_id] = array();
        }
        $this->contextHandler()->applyContextMapping($condition, $contexts, $mappings[$condition_id]);
      }
    }
    if ($this->resolveConditions($conditions, 'and', $contexts, $mappings) === FALSE) {
      return FALSE;
    }
    return $this->blockAccess($account);
  }

  /**
   * Gets the values for all defined contexts.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   An array of set contexts, keyed by context name.
   */
  protected function getConditionContexts() {
    $conditions = $this->getVisibilityConditions();
    $this->eventDispatcher()->dispatch(BlockEvents::CONDITION_CONTEXT, new BlockConditionContextEvent($conditions));
    return $conditions->getConditionContexts();
  }

  /**
   * Indicates whether the block should be shown.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return bool
   *   TRUE if the block should be shown, or FALSE otherwise.
   *
   * @see self::access()
   */
  protected function blockAccess(AccountInterface $account) {
    // By default, the block is visible.
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
    $period = array_map(array(\Drupal::service('date.formatter'), 'formatInterval'), array_combine($period, $period));
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

    $form['visibility_tabs'] = array(
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Visibility'),
      '#parents' => array('visibility_tabs'),
      '#attached' => array(
        'library' => array(
          'block/drupal.block',
        ),
      ),
    );
    foreach ($this->getVisibilityConditions() as $condition_id => $condition) {
      $condition_form = $condition->buildConfigurationForm(array(), $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'visibility_tabs';
      $form['visibility'][$condition_id] = $condition_form;
    }

    // @todo Determine if there is a better way to rename the conditions.
    if (isset($form['visibility']['node_type'])) {
      $form['visibility']['node_type']['#title'] = $this->t('Content types');
      $form['visibility']['node_type']['bundles']['#title'] = $this->t('Content types');
      $form['visibility']['node_type']['negate']['#type'] = 'value';
      $form['visibility']['node_type']['negate']['#title_display'] = 'invisible';
      $form['visibility']['node_type']['negate']['#value'] = $form['visibility']['node_type']['negate']['#default_value'];
    }
    if (isset($form['visibility']['user_role'])) {
      $form['visibility']['user_role']['#title'] = $this->t('Roles');
      unset($form['visibility']['user_role']['roles']['#description']);
      $form['visibility']['user_role']['negate']['#type'] = 'value';
      $form['visibility']['user_role']['negate']['#value'] = $form['visibility']['user_role']['negate']['#default_value'];
    }
    if (isset($form['visibility']['request_path'])) {
      $form['visibility']['request_path']['#title'] = $this->t('Pages');
      $form['visibility']['request_path']['negate']['#type'] = 'radios';
      $form['visibility']['request_path']['negate']['#title_display'] = 'invisible';
      $form['visibility']['request_path']['negate']['#default_value'] = (int) $form['visibility']['request_path']['negate']['#default_value'];
      $form['visibility']['request_path']['negate']['#options'] = array(
        $this->t('Show for the listed pages'),
        $this->t('Hide for the listed pages'),
      );
    }
    if (isset($form['visibility']['language'])) {
      $form['visibility']['language']['negate']['#type'] = 'value';
      $form['visibility']['language']['negate']['#value'] = $form['visibility']['language']['negate']['#default_value'];
    }

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
   * for a specific block type, override BlockBase::blockValdiate().
   *
   * @see \Drupal\Core\Block\BlockBase::blockValidate()
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Remove the admin_label form item element value so it will not persist.
    $form_state->unsetValue('admin_label');

    // Transform the #type = checkboxes value to a numerically indexed array.
    $contexts = $form_state->getValue(array('cache', 'contexts'));
    $form_state->setValue(array('cache', 'contexts'), array_values(array_filter($contexts)));

    foreach ($this->getVisibilityConditions() as $condition_id => $condition) {
      // Allow the condition to validate the form.
      $condition_values = (new FormState())
        ->setValues($form_state->getValue(['visibility', $condition_id]));
      $condition->validateConfigurationForm($form, $condition_values);
      // Update the original form values.
      $form_state->setValue(['visibility', $condition_id], $condition_values->getValues());
    }

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
      $this->configuration['cache'] = $form_state->getValue('cache');
      foreach ($this->getVisibilityConditions() as $condition_id => $condition) {
        // Allow the condition to submit the form.
        $condition_values = (new FormState())
          ->setValues($form_state->getValue(['visibility', $condition_id]));
        $condition->submitConfigurationForm($form, $condition_values);
        // Update the original form values.
        $form_state->setValue(['visibility', $condition_id], $condition_values->getValues());
      }
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

    $replace_pattern = '[^a-z0-9_.]+';

    $transliterated = Unicode::strtolower($transliterated);

    if (isset($replace_pattern)) {
      $transliterated = preg_replace('@' . $replace_pattern . '@', '', $transliterated);
    }

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

  /**
   * {@inheritdoc}
   */
  public function getVisibilityConditions() {
    if (!isset($this->conditionBag)) {
      $this->conditionBag = new ConditionPluginBag($this->conditionPluginManager(), $this->configuration['visibility']);
    }
    return $this->conditionBag;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibilityCondition($instance_id) {
    return $this->getVisibilityConditions()->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setVisibilityConfig($instance_id, array $configuration) {
    $this->getVisibilityConditions()->setInstanceConfiguration($instance_id, $configuration);
    return $this;
  }

  /**
   * Gets the condition plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The condition plugin manager.
   */
  protected function conditionPluginManager() {
    if (!isset($this->conditionPluginManager)) {
      $this->conditionPluginManager = \Drupal::service('plugin.manager.condition');
    }
    return $this->conditionPluginManager;
  }

  /**
   * Wraps the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher.
   */
  protected function eventDispatcher() {
    return \Drupal::service('event_dispatcher');
  }

  /**
   * Wraps the context handler.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected function contextHandler() {
    return \Drupal::service('context.handler');
  }

}
