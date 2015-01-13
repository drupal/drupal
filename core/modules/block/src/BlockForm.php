<?php

/**
 * @file
 * Contains \Drupal\block\BlockForm.
 */

namespace Drupal\block;

use Drupal\block\Event\BlockContextEvent;
use Drupal\block\Event\BlockEvents;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides form for block instance forms.
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
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

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
   * Constructs a BlockForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $manager
   *   The ConditionManager for building the visibility UI.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The EventDispatcher for gathering administrative contexts.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language
   *   The language manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   * The theme handler.
   */
  public function __construct(EntityManagerInterface $entity_manager, ExecutableManagerInterface $manager, EventDispatcherInterface $dispatcher, LanguageManagerInterface $language, ThemeHandlerInterface $theme_handler) {
    $this->storage = $entity_manager->getStorage('block');
    $this->manager = $manager;
    $this->dispatcher = $dispatcher;
    $this->language = $language;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.condition'),
      $container->get('event_dispatcher'),
      $container->get('language_manager'),
      $container->get('theme_handler')
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
    $form_state->setTemporaryValue('gathered_contexts', $this->dispatcher->dispatch(BlockEvents::ADMINISTRATIVE_CONTEXT, new BlockContextEvent())->getContexts());

    $form['#tree'] = TRUE;
    $form['settings'] = $entity->getPlugin()->buildConfigurationForm(array(), $form_state);
    $form['visibility'] = $this->buildVisibilityInterface([], $form_state);

    // If creating a new block, calculate a safe default machine name.
    $form['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this block instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => !$entity->isNew() ? $entity->id() : $this->getUniqueMachineName($entity),
      '#machine_name' => array(
        'exists' => '\Drupal\block\Entity\Block::load',
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => array('settings', 'label'),
      ),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
    );

    // Theme settings.
    if ($entity->getTheme()) {
      $form['theme'] = array(
        '#type' => 'value',
        '#value' => $theme,
      );
    }
    else {
      $theme_options = array();
      foreach ($this->themeHandler->listInfo() as $theme_name => $theme_info) {
        if (!empty($theme_info->status)) {
          $theme_options[$theme_name] = $theme_info->info['name'];
        }
      }
      $form['theme'] = array(
        '#type' => 'select',
        '#options' => $theme_options,
        '#title' => t('Theme'),
        '#default_value' => $theme,
        '#ajax' => array(
          'callback' => '::themeSwitch',
          'wrapper' => 'edit-block-region-wrapper',
        ),
      );
    }

    // Region settings.
    $form['region'] = array(
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#description' => $this->t('Select the region where this block should be displayed.'),
      '#default_value' => $entity->getRegion(),
      '#empty_value' => BlockInterface::BLOCK_REGION_NONE,
      '#options' => system_region_list($theme, REGIONS_VISIBLE),
      '#prefix' => '<div id="edit-block-region-wrapper">',
      '#suffix' => '</div>',
    );
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
    //   https://drupal.org/node/2284687.
    $visibility = $this->entity->getVisibility();
    foreach ($this->manager->getDefinitions() as $condition_id => $definition) {
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
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    // The Block Entity form puts all block plugin form elements in the
    // settings form element, so just pass that to the block for validation.
    $settings = (new FormState())->setValues($form_state->getValue('settings'));
    // Call the plugin validate handler.
    $this->entity->getPlugin()->validateConfigurationForm($form, $settings);
    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());
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
      // Allow the condition to validate the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition_values = (new FormState())
        ->setValues($values);
      $condition->validateConfigurationForm($form, $condition_values);
      // Update the original form values.
      $form_state->setValue(['visibility', $condition_id], $condition_values->getValues());
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
    // @todo Find a way to avoid this manipulation.
    $settings = (new FormState())->setValues($form_state->getValue('settings'));

    // Call the plugin submit handler.
    $entity->getPlugin()->submitConfigurationForm($form, $settings);
    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());

    // Submit visibility condition settings.
    foreach ($form_state->getValue('visibility') as $condition_id => $values) {
      // Allow the condition to submit the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition_values = (new FormState())
        ->setValues($values);
      $condition->submitConfigurationForm($form, $condition_values);
      if ($condition instanceof ContextAwarePluginInterface) {
        $context_mapping = isset($values['context_mapping']) ? $values['context_mapping'] : [];
        $condition->setContextMapping($context_mapping);
      }
      // Update the original form values.
      $condition_configuration = $condition->getConfiguration();
      $form_state->setValue(['visibility', $condition_id], $condition_configuration);
      // Update the visibility conditions on the block.
      $entity->getVisibilityConditions()->addInstanceId($condition_id, $condition_configuration);
    }

    // Save the settings of the plugin.
    $entity->save();

    drupal_set_message($this->t('The block configuration has been saved.'));
    $form_state->setRedirect(
      'block.admin_display_theme',
      array(
        'theme' => $form_state->getValue('theme'),
      ),
      array('query' => array('block-placement' => Html::getClass($this->entity->id())))
    );
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
    // 'plugin', 'plugin_2', 'plugin_3'...
    $count = 1;
    $machine_default = $suggestion;
    while (in_array($machine_default, $block_ids)) {
      $machine_default = $suggestion . '_' . ++$count;
    }
    return $machine_default;
  }

}
