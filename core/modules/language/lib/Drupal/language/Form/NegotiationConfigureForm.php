<?php

/**
 * @file
 * Contains \Drupal\Language\Form\NegotiationConfigureForm.
 */

namespace Drupal\language\Form;

use Drupal\block\Plugin\Type\BlockManager;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the selected language negotiation method for this site.
 */
class NegotiationConfigureForm extends FormBase {

  /**
   * Stores the configuration object for system.language.types.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $languageTypesConfig;

  /**
   * The block manager.
   *
   * @var \Drupal\block\Plugin\Type\BlockManager
   */
  protected $blockManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a NegotiationConfigureForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\block\Plugin\Type\BlockManager $block_manager
   *   The block manager, or NULL if not available.
   */
  public function __construct(ConfigFactory $config_factory, BlockManager $block_manager = NULL) {
    $this->languageTypesConfig = $config_factory->get('system.language.types');
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->has('plugin.manager.block') ? $container->get('plugin.manager.block') : NULL
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'language_negotiation_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    language_negotiation_include();

    $configurable = $this->languageTypesConfig->get('configurable');

    $form = array(
      '#theme' => 'language_negotiation_configure_form',
      '#language_types_info' => language_types_info(),
      '#language_negotiation_info' => language_negotiation_info(),
    );
    $form['#language_types'] = array();

    foreach ($form['#language_types_info'] as $type => $info) {
      // Show locked language types only if they are configurable.
      if (empty($info['locked']) || in_array($type, $configurable)) {
        $form['#language_types'][] = $type;
      }
    }

    foreach ($form['#language_types'] as $type) {
      $this->configureFormTable($form, $type);
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    language_negotiation_include();
    $configurable_types = $form['#language_types'];

    $stored_values = $this->languageTypesConfig->get('configurable');
    $customized = array();
    $method_weights_type = array();

    foreach ($configurable_types as $type) {
      $customized[$type] = in_array($type, $stored_values);
      $method_weights = array();
      $enabled_methods = $form_state['values'][$type]['enabled'];
      $enabled_methods[LANGUAGE_NEGOTIATION_SELECTED] = TRUE;
      $method_weights_input = $form_state['values'][$type]['weight'];
      if (isset($form_state['values'][$type]['configurable'])) {
        $customized[$type] = !empty($form_state['values'][$type]['configurable']);
      }

      foreach ($method_weights_input as $method_id => $weight) {
        if ($enabled_methods[$method_id]) {
          $method_weights[$method_id] = $weight;
        }
      }

      $method_weights_type[$type] = $method_weights;
      // @todo convert this to config.
      variable_set("language_negotiation_methods_weight_$type", $method_weights_input);
    }

    // Update non-configurable language types and the related language
    // negotiation configuration.
    language_types_set(array_keys(array_filter($customized)));

    // Update the language negotiations after setting the configurability.
    foreach ($method_weights_type as $type => $method_weights) {
      language_negotiation_set($type, $method_weights);
    }

    // Clear block definitions cache since the available blocks and their names
    // may have been changed based on the configurable types.
    if ($this->blockManager) {
      // If there is an active language switcher for a language type that has
      // been made not configurable, deactivate it first.
      $non_configurable = array_keys(array_diff($customized, array_filter($customized)));
      $this->disableLanguageSwitcher($non_configurable);
      $this->blockManager->clearCachedDefinitions();
    }

    $form_state['redirect_route']['route_name'] = 'language.negotiation';
    drupal_set_message($this->t('Language negotiation configuration saved.'));
  }

  /**
   * Builds a language negotiation method configuration table.
   *
   * @param array $form
   *   The language negotiation configuration form.
   * @param string $type
   *   The language type to generate the table for.
   */
  protected function configureFormTable(array &$form, $type)  {
    $info = $form['#language_types_info'][$type];

    $table_form = array(
      '#title' => $this->t('@type language detection', array('@type' => $info['name'])),
      '#tree' => TRUE,
      '#description' => $info['description'],
      '#language_negotiation_info' => array(),
      '#show_operations' => FALSE,
      'weight' => array('#tree' => TRUE),
    );
    // Only show configurability checkbox for the unlocked language types.
    if (empty($info['locked'])) {
      $configurable = $this->languageTypesConfig->get('configurable');
      $table_form['configurable'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Customize %language_name language detection to differ from User interface text language detection settings.', array('%language_name' => $info['name'])),
        '#default_value' => in_array($type, $configurable),
        '#attributes' => array('class' => array('language-customization-checkbox')),
        '#attached' => array(
          'library' => array(
            array('language', 'language.admin')
          ),
        ),
      );
    }

    $negotiation_info = $form['#language_negotiation_info'];
    $enabled_methods = variable_get("language_negotiation_$type", array());
    $methods_weight = variable_get("language_negotiation_methods_weight_$type", array());

    // Add missing data to the methods lists.
    foreach ($negotiation_info as $method_id => $method) {
      if (!isset($methods_weight[$method_id])) {
        $methods_weight[$method_id] = isset($method['weight']) ? $method['weight'] : 0;
      }
    }

    // Order methods list by weight.
    asort($methods_weight);

    foreach ($methods_weight as $method_id => $weight) {
      // A language method might be no more available if the defining module has
      // been disabled after the last configuration saving.
      if (!isset($negotiation_info[$method_id])) {
        continue;
      }

      $enabled = isset($enabled_methods[$method_id]);
      $method = $negotiation_info[$method_id];

      // List the method only if the current type is defined in its 'types' key.
      // If it is not defined default to all the configurable language types.
      $types = array_flip(isset($method['types']) ? $method['types'] : $form['#language_types']);

      if (isset($types[$type])) {
        $table_form['#language_negotiation_info'][$method_id] = $method;
        $method_name = String::checkPlain($method['name']);

        $table_form['weight'][$method_id] = array(
          '#type' => 'weight',
          '#title' => $this->t('Weight for !title language detection method', array('!title' => Unicode::strtolower($method_name))),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#attributes' => array('class' => array("language-method-weight-$type")),
          '#delta' => 20,
        );

        $table_form['title'][$method_id] = array('#markup' => $method_name);

        $table_form['enabled'][$method_id] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Enable !title language detection method', array('!title' => Unicode::strtolower($method_name))),
          '#title_display' => 'invisible',
          '#default_value' => $enabled,
        );
        if ($method_id === LANGUAGE_NEGOTIATION_SELECTED) {
          $table_form['enabled'][$method_id]['#default_value'] = TRUE;
          $table_form['enabled'][$method_id]['#attributes'] = array('disabled' => 'disabled');
        }

        $table_form['description'][$method_id] = array('#markup' => Xss::filterAdmin($method['description']));

        $config_op = array();
        if (isset($method['config'])) {
          $config_op['configure'] = array(
            'title' => $this->t('Configure'),
            'href' => $method['config'],
          );
          // If there is at least one operation enabled show the operation
          // column.
          $table_form['#show_operations'] = TRUE;
        }
        $table_form['operation'][$method_id] = array(
         '#type' => 'operations',
         '#links' => $config_op,
        );
      }
    }
    $form[$type] = $table_form;
  }

  /**
   * Disables the language switcher blocks.
   *
   * @param array $language_types
   *   An array containing all language types whose language switchers need to
   *   be disabled.
   */
  protected function disableLanguageSwitcher(array $language_types) {
    $blocks = _block_rehash();
    foreach ($language_types as $language_type) {
      foreach ($blocks as $block) {
        if (strpos($block->id, 'language_switcher_' . substr($language_type, 9)) !== FALSE) {
          $block->delete();
        }
      }
    }
  }

}
