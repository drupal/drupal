<?php

/**
 * @file
 * Contains \Drupal\block\BlockFormController.
 */

namespace Drupal\block;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Language\Language;

/**
 * Provides form controller for block instance forms.
 */
class BlockFormController extends EntityFormController {

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $entity = $this->entity;
    $form['#tree'] = TRUE;
    $form['id'] = array(
      '#type' => 'value',
      '#value' => $entity->id(),
    );
    $form['settings'] = $entity->getPlugin()->form(array(), $form_state);

    $form['machine_name'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine name'),
      '#maxlength' => 64,
      '#description' => t('A unique name to save this block configuration. Must be alpha-numeric and be underscore separated.'),
      '#default_value' => $entity->id(),
      '#machine_name' => array(
        'exists' => 'block_load',
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => array('settings', 'label'),
      ),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
    );

    // Visibility settings.
    $form['visibility'] = array(
      '#type' => 'vertical_tabs',
      '#title' => t('Visibility settings'),
      '#attached' => array(
        'js' => array(drupal_get_path('module', 'block') . '/block.js'),
      ),
      '#tree' => TRUE,
      '#weight' => 10,
      '#parents' => array('visibility'),
    );

    // Per-path visibility.
    $form['visibility']['path'] = array(
      '#type' => 'details',
      '#title' => t('Pages'),
      '#collapsed' => TRUE,
      '#group' => 'visibility',
      '#weight' => 0,
    );

    // @todo remove this access check and inject it in some other way. In fact
    //   this entire visibility settings section probably needs a separate user
    //   interface in the near future.
    $visibility = $entity->get('visibility');
    $access = user_access('use PHP for settings');
    if (!empty($visibility['path']['visibility']) && $visibility['path']['visibility'] == BLOCK_VISIBILITY_PHP && !$access) {
      $form['visibility']['path']['visibility'] = array(
        '#type' => 'value',
        '#value' => BLOCK_VISIBILITY_PHP,
      );
      $form['visibility']['path']['pages'] = array(
        '#type' => 'value',
        '#value' => !empty($visibility['path']['pages']) ? $visibility['path']['pages'] : '',
      );
    }
    else {
      $options = array(
        BLOCK_VISIBILITY_NOTLISTED => t('All pages except those listed'),
        BLOCK_VISIBILITY_LISTED => t('Only the listed pages'),
      );
      $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %user for the current user's page and %user-wildcard for every user page. %front is the front page.", array('%user' => 'user', '%user-wildcard' => 'user/*', '%front' => '<front>'));

      if (module_exists('php') && $access) {
        $options += array(BLOCK_VISIBILITY_PHP => t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'));
        $title = t('Pages or PHP code');
        $description .= ' ' . t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', array('%php' => '<?php ?>'));
      }
      else {
        $title = t('Pages');
      }
      $form['visibility']['path']['visibility'] = array(
        '#type' => 'radios',
        '#title' => t('Show block on specific pages'),
        '#options' => $options,
        '#default_value' => !empty($visibility['path']['visibility']) ? $visibility['path']['visibility'] : BLOCK_VISIBILITY_NOTLISTED,
      );
      $form['visibility']['path']['pages'] = array(
        '#type' => 'textarea',
        '#title' => '<span class="element-invisible">' . $title . '</span>',
        '#default_value' => !empty($visibility['path']['pages']) ? $visibility['path']['pages'] : '',
        '#description' => $description,
      );
    }

    // Configure the block visibility per language.
    if (module_exists('language') && language_multilingual()) {
      $configurable_language_types = language_types_get_configurable();

      // Fetch languages.
      $languages = language_list(Language::STATE_ALL);
      foreach ($languages as $language) {
        // @todo $language->name is not wrapped with t(), it should be replaced
        //   by CMI translation implementation.
        $langcodes_options[$language->langcode] = $language->name;
      }
      $form['visibility']['language'] = array(
        '#type' => 'details',
        '#title' => t('Languages'),
        '#collapsed' => TRUE,
        '#group' => 'visibility',
        '#weight' => 5,
      );
      // If there are multiple configurable language types, let the user pick
      // which one should be applied to this visibility setting. This way users
      // can limit blocks by interface language or content language for exmaple.
      $language_types = language_types_info();
      $language_type_options = array();
      foreach ($configurable_language_types as $type_key) {
        $language_type_options[$type_key] = $language_types[$type_key]['name'];
      }
      $form['visibility']['language']['language_type'] = array(
        '#type' => 'radios',
        '#title' => t('Language type'),
        '#options' => $language_type_options,
        '#default_value' => !empty($visibility['language']['language_type']) ? $visibility['language']['language_type'] : $configurable_language_types[0],
        '#access' => count($language_type_options) > 1,
      );
      $form['visibility']['language']['langcodes'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Show this block only for specific languages'),
        '#default_value' => !empty($visibility['language']['langcodes']) ? $visibility['language']['langcodes'] : array(),
        '#options' => $langcodes_options,
        '#description' => t('Show this block only for the selected language(s). If you select no languages, the block will be visibile in all languages.'),
      );
    }

    // Per-role visibility.
    $role_options = array_map('check_plain', user_role_names());
    $form['visibility']['role'] = array(
      '#type' => 'details',
      '#title' => t('Roles'),
      '#collapsed' => TRUE,
      '#group' => 'visibility',
      '#weight' => 10,
    );
    $form['visibility']['role']['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Show block for specific roles'),
      '#default_value' => !empty($visibility['role']['roles']) ? $visibility['role']['roles'] : array(),
      '#options' => $role_options,
      '#description' => t('Show this block only for the selected role(s). If you select no roles, the block will be visible to all users.'),
    );

    // Region settings.
    $form['region'] = array(
      '#type' => 'select',
      '#title' => t('Region'),
      '#description' => t('Select the region where this block should be displayed.'),
      '#default_value' => $entity->get('region'),
      '#empty_value' => BLOCK_REGION_NONE,
      '#options' => system_region_list($entity->get('theme'), REGIONS_VISIBLE),
    );
    return $form;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save block');
    return $actions;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    $entity = $this->entity;
    if ($entity->isNew()) {
      form_set_value($form['id'], $entity->get('theme') . '.' . $form_state['values']['machine_name'], $form_state);
    }
    if (!empty($form['machine_name']['#disabled'])) {
      $config_id = explode('.', $form_state['values']['machine_name']);
      $form_state['values']['machine_name'] = array_pop($config_id);
    }
    $form_state['values']['visibility']['role']['roles'] = array_filter($form_state['values']['visibility']['role']['roles']);
    // The Block Entity form puts all block plugin form elements in the
    // settings form element, so just pass that to the block for validation.
    $settings = array(
      'values' => &$form_state['values']['settings']
    );
    // Call the plugin validate handler.
    $entity->getPlugin()->validate($form, $settings);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);

    $entity = $this->entity;
    // The Block Entity form puts all block plugin form elements in the
    // settings form element, so just pass that to the block for submission.
    $settings = array(
      'values' => &$form_state['values']['settings']
    );
    // Call the plugin submit handler.
    $entity->getPlugin()->submit($form, $settings);

    // Save the settings of the plugin.
    $entity->save();

    drupal_set_message(t('The block configuration has been saved.'));
    cache_invalidate_tags(array('content' => TRUE));
    $form_state['redirect'] = 'admin/structure/block/list/block_plugin_ui:' . $entity->get('theme');
  }

}
