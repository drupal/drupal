<?php

namespace Drupal\block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for block instance forms.
 *
 * @internal
 */
class BlockForm extends EntityForm {

  /**
   * The block entity.
   *
   * @var \Drupal\block\BlockInterface
   */
  protected $entity;

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * Constructs a BlockForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $manager
   *   The ConditionManager for building the visibility UI.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language
   *   The language manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, ExecutableManagerInterface $manager, ContextRepositoryInterface $context_repository, LanguageManagerInterface $language, ThemeHandlerInterface $theme_handler, PluginFormFactoryInterface $plugin_form_manager) {
    $this->storage = $entity_manager->getStorage('block');
    $this->manager = $manager;
    $this->contextRepository = $context_repository;
    $this->language = $language;
    $this->themeHandler = $theme_handler;
    $this->pluginFormFactory = $plugin_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('language_manager'),
      $container->get('theme_handler'),
      $container->get('plugin_form.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Store theme settings in $form_state for use below.
    if (!$theme = $entity->getTheme()) {
      $theme = $this->config('system.theme')->get('default');
    }
    $form_state->set('block_theme', $theme);

    // Store the gathered contexts in the form state for other objects to use
    // during form building.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    $form['#tree'] = TRUE;
    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->getPluginForm($entity->getPlugin())->buildConfigurationForm($form['settings'], $subform_state);
    $form['visibility'] = $this->buildVisibilityInterface([], $form_state);

    // If creating a new block, calculate a safe default machine name.
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this block instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => !$entity->isNew() ? $entity->id() : $this->getUniqueMachineName($entity),
      '#machine_name' => [
        'exists' => '\Drupal\block\Entity\Block::load',
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => ['settings', 'label'],
      ],
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
    ];

    // Theme settings.
    if ($entity->getTheme()) {
      $form['theme'] = [
        '#type' => 'value',
        '#value' => $theme,
      ];
    }
    else {
      $theme_options = [];
      foreach ($this->themeHandler->listInfo() as $theme_name => $theme_info) {
        if (!empty($theme_info->status)) {
          $theme_options[$theme_name] = $theme_info->info['name'];
        }
      }
      $form['theme'] = [
        '#type' => 'select',
        '#options' => $theme_options,
        '#title' => t('Theme'),
        '#default_value' => $theme,
        '#ajax' => [
          'callback' => '::themeSwitch',
          'wrapper' => 'edit-block-region-wrapper',
        ],
      ];
    }

    // Hidden weight setting.
    $weight = $entity->isNew() ? $this->getRequest()->query->get('weight', 0) : $entity->getWeight();
    $form['weight'] = [
      '#type' => 'hidden',
      '#default_value' => $weight,
    ];

    // Region settings.
    $entity_region = $entity->getRegion();
    $region = $entity->isNew() ? $this->getRequest()->query->get('region', $entity_region) : $entity_region;
    $form['region'] = [
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#description' => $this->t('Select the region where this block should be displayed.'),
      '#default_value' => $region,
      '#required' => TRUE,
      '#options' => system_region_list($theme, REGIONS_VISIBLE),
      '#prefix' => '<div id="edit-block-region-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['#attached']['library'][] = 'block/drupal.block.admin';
    return $form;
  }

  /**
   * Handles switching the available regions based on the selected theme.
   */
  public function themeSwitch($form, FormStateInterface $form_state) {
    $form['region']['#options'] = system_region_list($form_state->getValue('theme'), REGIONS_VISIBLE);
    return $form['region'];
  }

  /**
   * Helper function for building the visibility UI form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form array with the visibility UI added in.
   */
  protected function buildVisibilityInterface(array $form, FormStateInterface $form_state) {
    $form['visibility_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Visibility'),
      '#parents' => ['visibility_tabs'],
      '#attached' => [
        'library' => [
          'block/drupal.block',
        ],
      ],
    ];
    // @todo Allow list of conditions to be configured in
    //   https://www.drupal.org/node/2284687.
    $visibility = $this->entity->getVisibility();
    $definitions = $this->manager->getFilteredDefinitions('block_ui', $form_state->getTemporaryValue('gathered_contexts'), ['block' => $this->entity]);
    foreach ($definitions as $condition_id => $definition) {
      // Don't display the current theme condition.
      if ($condition_id == 'current_theme') {
        continue;
      }
      // Don't display the language condition until we have multiple languages.
      if ($condition_id == 'language' && !$this->language->isMultilingual()) {
        continue;
      }
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->manager->createInstance($condition_id, isset($visibility[$condition_id]) ? $visibility[$condition_id] : []);
      $form_state->set(['conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'visibility_tabs';
      $form[$condition_id] = $condition_form;
    }

    if (isset($form['node_type'])) {
      $form['node_type']['#title'] = $this->t('Content types');
      $form['node_type']['bundles']['#title'] = $this->t('Content types');
      $form['node_type']['negate']['#type'] = 'value';
      $form['node_type']['negate']['#title_display'] = 'invisible';
      $form['node_type']['negate']['#value'] = $form['node_type']['negate']['#default_value'];
    }
    if (isset($form['user_role'])) {
      $form['user_role']['#title'] = $this->t('Roles');
      unset($form['user_role']['roles']['#description']);
      $form['user_role']['negate']['#type'] = 'value';
      $form['user_role']['negate']['#value'] = $form['user_role']['negate']['#default_value'];
    }
    if (isset($form['request_path'])) {
      $form['request_path']['#title'] = $this->t('Pages');
      $form['request_path']['negate']['#type'] = 'radios';
      $form['request_path']['negate']['#default_value'] = (int) $form['request_path']['negate']['#default_value'];
      $form['request_path']['negate']['#title_display'] = 'invisible';
      $form['request_path']['negate']['#options'] = [
        $this->t('Show for the listed pages'),
        $this->t('Hide for the listed pages'),
      ];
    }
    if (isset($form['language'])) {
      $form['language']['negate']['#type'] = 'value';
      $form['language']['negate']['#value'] = $form['language']['negate']['#default_value'];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save block');
    $actions['delete']['#title'] = $this->t('Remove block');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $form_state->setValue('weight', (int) $form_state->getValue('weight'));
    // The Block Entity form puts all block plugin form elements in the
    // settings form element, so just pass that to the block for validation.
    $this->getPluginForm($this->entity->getPlugin())->validateConfigurationForm($form['settings'], SubformState::createForSubform($form['settings'], $form, $form_state));
    $this->validateVisibility($form, $form_state);
  }

  /**
   * Helper function to independently validate the visibility UI.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function validateVisibility(array $form, FormStateInterface $form_state) {
    // Validate visibility condition settings.
    foreach ($form_state->getValue('visibility') as $condition_id => $values) {
      // All condition plugins use 'negate' as a Boolean in their schema.
      // However, certain form elements may return it as 0/1. Cast here to
      // ensure the data is in the expected type.
      if (array_key_exists('negate', $values)) {
        $form_state->setValue(['visibility', $condition_id, 'negate'], (bool) $values['negate']);
      }

      // Allow the condition to validate the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->validateConfigurationForm($form['visibility'][$condition_id], SubformState::createForSubform($form['visibility'][$condition_id], $form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $entity = $this->entity;
    // The Block Entity form puts all block plugin form elements in the
    // settings form element, so just pass that to the block for submission.
    $sub_form_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    // Call the plugin submit handler.
    $block = $entity->getPlugin();
    $this->getPluginForm($block)->submitConfigurationForm($form, $sub_form_state);
    // If this block is context-aware, set the context mapping.
    if ($block instanceof ContextAwarePluginInterface && $block->getContextDefinitions()) {
      $context_mapping = $sub_form_state->getValue('context_mapping', []);
      $block->setContextMapping($context_mapping);
    }

    $this->submitVisibility($form, $form_state);

    // Save the settings of the plugin.
    $entity->save();

    $this->messenger()->addStatus($this->t('The block configuration has been saved.'));
    $form_state->setRedirect(
      'block.admin_display_theme',
      [
        'theme' => $form_state->getValue('theme'),
      ],
      ['query' => ['block-placement' => Html::getClass($this->entity->id())]]
    );
  }

  /**
   * Helper function to independently submit the visibility UI.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function submitVisibility(array $form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('visibility') as $condition_id => $values) {
      // Allow the condition to submit the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->submitConfigurationForm($form['visibility'][$condition_id], SubformState::createForSubform($form['visibility'][$condition_id], $form, $form_state));

      // Setting conditions' context mappings is the plugins' responsibility.
      // This code exists for backwards compatibility, because
      // \Drupal\Core\Condition\ConditionPluginBase::submitConfigurationForm()
      // did not set its own mappings until Drupal 8.2
      // @todo Remove the code that sets context mappings in Drupal 9.0.0.
      if ($condition instanceof ContextAwarePluginInterface) {
        $context_mapping = isset($values['context_mapping']) ? $values['context_mapping'] : [];
        $condition->setContextMapping($context_mapping);
      }

      $condition_configuration = $condition->getConfiguration();
      // Update the visibility conditions on the block.
      $this->entity->getVisibilityConditions()->addInstanceId($condition_id, $condition_configuration);
    }
  }

  /**
   * Generates a unique machine name for a block.
   *
   * @param \Drupal\block\BlockInterface $block
   *   The block entity.
   *
   * @return string
   *   Returns the unique name.
   */
  public function getUniqueMachineName(BlockInterface $block) {
    $suggestion = $block->getPlugin()->getMachineNameSuggestion();

    // Get all the blocks which starts with the suggested machine name.
    $query = $this->storage->getQuery();
    $query->condition('id', $suggestion, 'CONTAINS');
    $block_ids = $query->execute();

    $block_ids = array_map(function ($block_id) {
      $parts = explode('.', $block_id);
      return end($parts);
    }, $block_ids);

    // Iterate through potential IDs until we get a new one. E.g.
    // 'plugin', 'plugin_2', 'plugin_3', etc.
    $count = 1;
    $machine_default = $suggestion;
    while (in_array($machine_default, $block_ids)) {
      $machine_default = $suggestion . '_' . ++$count;
    }
    return $machine_default;
  }

  /**
   * Retrieves the plugin form for a given block and operation.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the block.
   */
  protected function getPluginForm(BlockPluginInterface $block) {
    if ($block instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($block, 'configure');
    }
    return $block;
  }

}
