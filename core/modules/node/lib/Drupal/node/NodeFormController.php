<?php

/**
 * @file
 * Definition of Drupal\node\NodeFormController.
 */

namespace Drupal\node;

use Drupal\entity\EntityInterface;
use Drupal\entity\EntityFormController;

/**
 * Form controller for the node edit forms.
 */
class NodeFormController extends EntityFormController {

  /**
   * Prepares the node object.
   *
   * Fills in a few default values, and then invokes hook_prepare() on the node
   * type module, and hook_node_prepare() on all modules.
   *
   * Overrides Drupal\entity\EntityFormController::prepareEntity().
   */
  protected function prepareEntity(EntityInterface $node) {
    // Set up default values, if required.
    $node_options = variable_get('node_options_' . $node->type, array('status', 'promote'));
    // If this is a new node, fill in the default values.
    if (!isset($node->nid) || isset($node->is_new)) {
      foreach (array('status', 'promote', 'sticky') as $key) {
        // Multistep node forms might have filled in something already.
        if (!isset($node->$key)) {
          $node->$key = (int) in_array($key, $node_options);
        }
      }
      global $user;
      $node->uid = $user->uid;
      $node->created = REQUEST_TIME;
    }
    else {
      $node->date = format_date($node->created, 'custom', 'Y-m-d H:i:s O');
      // Remove the log message from the original node entity.
      $node->log = NULL;
    }
    // Always use the default revision setting.
    $node->revision = in_array('revision', $node_options);

    node_invoke($node, 'prepare');
    module_invoke_all('node_prepare', $node);
  }

  /**
   * Overrides Drupal\entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $node) {
    $user_config = config('user.settings');
    // Some special stuff when previewing a node.
    if (isset($form_state['node_preview'])) {
      $form['#prefix'] = $form_state['node_preview'];
      $node->in_preview = TRUE;
    }
    else {
      unset($node->in_preview);
    }

    // Override the default CSS class name, since the user-defined node type
    // name in 'TYPE-node-form' potentially clashes with third-party class
    // names.
    $form['#attributes']['class'][0] = drupal_html_class('node-' . $node->type . '-form');

    // Basic node information.
    // These elements are just values so they are not even sent to the client.
    foreach (array('nid', 'vid', 'uid', 'created', 'type') as $key) {
      $form[$key] = array(
        '#type' => 'value',
        '#value' => isset($node->$key) ? $node->$key : NULL,
      );
    }

    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = array(
      '#type' => 'hidden',
      '#default_value' => isset($node->changed) ? $node->changed : NULL,
    );

    // Invoke hook_form() to get the node-specific bits. Can't use node_invoke()
    // because hook_form() needs to be able to receive $form_state by reference.
    // @todo hook_form() implementations are unable to add #validate or #submit
    //   handlers to the form buttons below. Remove hook_form() entirely.
    $function = node_type_get_base($node) . '_form';
    if (function_exists($function) && ($extra = $function($node, $form_state))) {
      $form = array_merge_recursive($form, $extra);
    }
    // If the node type has a title, and the node type form defined no special
    // weight for it, we default to a weight of -5 for consistency.
    if (isset($form['title']) && !isset($form['title']['#weight'])) {
      $form['title']['#weight'] = -5;
    }

    $form['langcode'] = array(
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $node->langcode,
      '#languages' => LANGUAGE_ALL,
      '#access' => !variable_get('node_type_language_hidden_' . $node->type, TRUE),
    );

    $form['additional_settings'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );

    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    $form['revision_information'] = array(
      '#type' => 'fieldset',
      '#title' => t('Revision information'),
      '#collapsible' => TRUE,
      // Collapsed by default when "Create new revision" is unchecked
      '#collapsed' => !$node->revision,
      '#group' => 'additional_settings',
      '#attributes' => array(
        'class' => array('node-form-revision-information'),
      ),
      '#attached' => array(
        'js' => array(drupal_get_path('module', 'node') . '/node.js'),
      ),
      '#weight' => 20,
      '#access' => $node->revision || user_access('administer nodes'),
    );

    $form['revision_information']['revision'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => $node->revision,
      '#access' => user_access('administer nodes'),
    );

    // Check the revision log checkbox when the log textarea is filled in.
    // This must not happen if "Create new revision" is enabled by default,
    // since the state would auto-disable the checkbox otherwise.
    if (!$node->revision) {
      $form['revision_information']['revision']['#states'] = array(
        'checked' => array(
          'textarea[name="log"]' => array('empty' => FALSE),
        ),
      );
    }

    $form['revision_information']['log'] = array(
      '#type' => 'textarea',
      '#title' => t('Revision log message'),
      '#rows' => 4,
      '#default_value' => !empty($node->log) ? $node->log : '',
      '#description' => t('Briefly describe the changes you have made.'),
    );

    // Node author information for administrators.
    $form['author'] = array(
      '#type' => 'fieldset',
      '#access' => user_access('administer nodes'),
      '#title' => t('Authoring information'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'additional_settings',
      '#attributes' => array(
        'class' => array('node-form-author'),
      ),
      '#attached' => array(
        'js' => array(
          drupal_get_path('module', 'node') . '/node.js',
          array(
            'type' => 'setting',
            'data' => array('anonymous' => $user_config->get('anonymous')),
          ),
        ),
      ),
      '#weight' => 90,
    );

    $form['author']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Authored by'),
      '#maxlength' => 60,
      '#autocomplete_path' => 'user/autocomplete',
      '#default_value' => !empty($node->name) ? $node->name : '',
      '#weight' => -1,
      '#description' => t('Leave blank for %anonymous.', array('%anonymous' => $user_config->get('anonymous'))),
    );

    $form['author']['date'] = array(
      '#type' => 'textfield',
      '#title' => t('Authored on'),
      '#maxlength' => 25,
      '#description' => t('Format: %time. The date format is YYYY-MM-DD and %timezone is the time zone offset from UTC. Leave blank to use the time of form submission.', array('%time' => !empty($node->date) ? date_format(date_create($node->date), 'Y-m-d H:i:s O') : format_date($node->created, 'custom', 'Y-m-d H:i:s O'), '%timezone' => !empty($node->date) ? date_format(date_create($node->date), 'O') : format_date($node->created, 'custom', 'O'))),
      '#default_value' => !empty($node->date) ? $node->date : '',
    );

    // Node options for administrators.
    $form['options'] = array(
      '#type' => 'fieldset',
      '#access' => user_access('administer nodes'),
      '#title' => t('Publishing options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'additional_settings',
      '#attributes' => array(
        'class' => array('node-form-options'),
      ),
      '#attached' => array(
        'js' => array(drupal_get_path('module', 'node') . '/node.js'),
      ),
      '#weight' => 95,
    );

    $form['options']['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Published'),
      '#default_value' => $node->status,
    );

    $form['options']['promote'] = array(
      '#type' => 'checkbox',
      '#title' => t('Promoted to front page'),
      '#default_value' => $node->promote,
    );

    $form['options']['sticky'] = array(
      '#type' => 'checkbox',
      '#title' => t('Sticky at top of lists'),
      '#default_value' => $node->sticky,
    );

    // This form uses a button-level #submit handler for the form's main submit
    // action. node_form_submit() manually invokes all form-level #submit
    // handlers of the form. Without explicitly setting #submit, Form API would
    // auto-detect node_form_submit() as submit handler, but that is the
    // button-level #submit handler for the 'Save' action.
    $form += array('#submit' => array());

    return parent::form($form, $form_state, $node);
  }

  /**
   * Overrides Drupal\entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $element = parent::actions($form, $form_state);
    $node = $this->getEntity($form_state);
    $preview_mode = variable_get('node_preview_' . $node->type, DRUPAL_OPTIONAL);

    $element['preview'] = array(
      '#access' => $preview_mode != DRUPAL_DISABLED,
      '#value' => t('Preview'),
      '#validate' => array(
        array($this, 'validate'),
      ),
      '#submit' => array(
        array($this, 'submit'),
        array($this, 'preview'),
      ),
    );

    $element['submit']['#access'] = $preview_mode != DRUPAL_REQUIRED || (!form_get_errors() && isset($form_state['node_preview']));
    $element['delete']['#access'] = node_access('delete', $node);

    return $element;
  }

  /**
   * Overrides Drupal\entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    $node = $this->buildEntity($form, $form_state);

    if (isset($node->nid) && (node_last_changed($node->nid) > $node->changed)) {
      form_set_error('changed', t('The content on this page has either been modified by another user, or you have already submitted modifications using this form. As a result, your changes cannot be saved.'));
    }

    // Validate the "authored by" field.
    if (!empty($node->name) && !($account = user_load_by_name($node->name))) {
      // The use of empty() is mandatory in the context of usernames
      // as the empty string denotes the anonymous user. In case we
      // are dealing with an anonymous user we set the user ID to 0.
      form_set_error('name', t('The username %name does not exist.', array('%name' => $node->name)));
    }

    // Validate the "authored on" field.
    if (!empty($node->date) && strtotime($node->date) === FALSE) {
      form_set_error('date', t('You have to specify a valid date.'));
    }

    // Invoke hook_validate() for node type specific validation and
    // hook_node_validate() for miscellaneous validation needed by modules.
    // Can't use node_invoke() or module_invoke_all(), because $form_state must
    // be receivable by reference.
    $function = node_type_get_base($node) . '_validate';
    if (function_exists($function)) {
      $function($node, $form, $form_state);
    }
    foreach (module_implements('node_validate') as $module) {
      $function = $module . '_node_validate';
      $function($node, $form, $form_state);
    }

    parent::validate($form, $form_state);
  }

  /**
   * Updates the node object by processing the submitted values.
   *
   * This function can be called by a "Next" button of a wizard to update the
   * form state's entity with the current step's values before proceeding to the
   * next step.
   *
   * Overrides Drupal\entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    $this->submitNodeLanguage($form, $form_state);

    // Build the node object from the submitted values.
    $node = parent::submit($form, $form_state);

    node_submit($node);
    foreach (module_implements('node_submit') as $module) {
      $function = $module . '_node_submit';
      $function($node, $form, $form_state);
    }

    return $node;
  }

  /**
   * Handle possible node language changes.
   */
  protected function submitNodeLanguage(array $form, array &$form_state) {
    if (field_has_translation_handler('node', 'node')) {
      $bundle = $form_state['values']['type'];
      $node_language = $form_state['values']['langcode'];

      foreach (field_info_instances('node', $bundle) as $instance) {
        $field_name = $instance['field_name'];
        $field = field_info_field($field_name);
        $previous_langcode = $form[$field_name]['#language'];

        // Handle a possible language change: new language values are inserted,
        // previous ones are deleted.
        if ($field['translatable'] && $previous_langcode != $node_language) {
          $form_state['values'][$field_name][$node_language] = $form_state['values'][$field_name][$previous_langcode];
          $form_state['values'][$field_name][$previous_langcode] = array();
        }
      }
    }
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function preview(array $form, array &$form_state) {
    drupal_set_title(t('Preview'), PASS_THROUGH);
    $form_state['node_preview'] = node_preview($this->getEntity($form_state));
    $form_state['rebuild'] = TRUE;
  }

  /**
   * Overrides Drupal\entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $node = $this->getEntity($form_state);
    $insert = empty($node->nid);
    $node->save();
    $node_link = l(t('view'), 'node/' . $node->nid);
    $watchdog_args = array('@type' => $node->type, '%title' => $node->label());
    $t_args = array('@type' => node_type_get_name($node), '%title' => $node->label());

    if ($insert) {
      watchdog('content', '@type: added %title.', $watchdog_args, WATCHDOG_NOTICE, $node_link);
      drupal_set_message(t('@type %title has been created.', $t_args));
    }
    else {
      watchdog('content', '@type: updated %title.', $watchdog_args, WATCHDOG_NOTICE, $node_link);
      drupal_set_message(t('@type %title has been updated.', $t_args));
    }

    if ($node->nid) {
      $form_state['values']['nid'] = $node->nid;
      $form_state['nid'] = $node->nid;
      $form_state['redirect'] = 'node/' . $node->nid;
    }
    else {
      // In the unlikely case something went wrong on save, the node will be
      // rebuilt and node form redisplayed the same way as in preview.
      drupal_set_message(t('The post could not be saved.'), 'error');
      $form_state['rebuild'] = TRUE;
    }

    // Clear the page and block caches.
    cache_invalidate(array('content' => TRUE));
  }

  /**
   * Overrides Drupal\entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    if (isset($_GET['destination'])) {
      $destination = drupal_get_destination();
      unset($_GET['destination']);
    }
    $node = $this->getEntity($form_state);
    $form_state['redirect'] = array('node/' . $node->nid . '/delete', array('query' => $destination));
  }
}
