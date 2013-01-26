<?php

/**
 * @file
 * Definition of Drupal\node\NodeFormController.
 */

namespace Drupal\node;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

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
   * Overrides Drupal\Core\Entity\EntityFormController::prepareEntity().
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
    $node->setNewRevision(in_array('revision', $node_options));

    node_invoke($node, 'prepare');
    module_invoke_all('node_prepare', $node);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $node) {

    // Visual representation of the node content form depends on following
    // parameters:
    // - the current user has access to view the administration theme.
    // - the current path is an admin path.
    // - the node/add / edit pages are configured to be represented in the
    //   administration theme.
    $container_type = 'vertical_tabs';
    $request = drupal_container()->get('request');
    $path = $request->attributes->get('system_path');
    if (user_access('view the administration theme') && path_is_admin($path)) {
      $container_type = 'container';
    }

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
    $function = node_hook($node->type, 'form');
    if ($function && ($extra = $function($node, $form_state))) {
      $form = NestedArray::mergeDeep($form, $extra);
    }
    // If the node type has a title, and the node type form defined no special
    // weight for it, we default to a weight of -5 for consistency.
    if (isset($form['title']) && !isset($form['title']['#weight'])) {
      $form['title']['#weight'] = -5;
    }

    $language_configuration = module_invoke('language', 'get_default_configuration', 'node', $node->type);
    $form['langcode'] = array(
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $node->langcode,
      '#languages' => LANGUAGE_ALL,
      '#access' => isset($language_configuration['language_show']) && $language_configuration['language_show'],
    );

    $form['advanced'] = array(
      '#type' => $container_type,
      '#attributes' => array('class' => array('entity-meta')),
      '#weight' => 99,
    );
    $form['meta'] = array (
      '#type' => 'fieldset',
      '#attributes' => array('class' => array('entity-meta-header')),
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -100,
      '#access' => $container_type == 'container',
      // @todo Geez. Any .status is styled as OK icon? Really?
      'published' => array(
        '#type' => 'item',
        '#wrapper_attributes' => array('class' => array('published')),
        '#markup' => !empty($node->status) ? t('Published') : t('Not published'),
        '#access' => !empty($node->nid),
      ),
      'changed' => array(
        '#type' => 'item',
        '#wrapper_attributes' => array('class' => array('changed', 'container-inline')),
        '#title' => t('Last saved'),
        '#markup' => !$node->isNew() ? format_date($node->changed, 'short') : t('Not saved yet'),
      ),
      'author' => array(
        '#type' => 'item',
        '#wrapper_attributes' => array('class' => array('author', 'container-inline')),
        '#title' => t('Author'),
        '#markup' => user_format_name(user_load($node->uid)),
      ),
    );

    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    $form['revision_information'] = array(
      '#type' => $container_type == 'container' ? 'container' : 'details',
      '#group' => $container_type == 'container' ? 'meta' : 'advanced',
      '#title' => t('Revision information'),
      // Collapsed by default when "Create new revision" is unchecked.
      '#collapsed' => !$node->isNewRevision(),
      '#attributes' => array(
        'class' => array('node-form-revision-information'),
      ),
      '#attached' => array(
        'js' => array(drupal_get_path('module', 'node') . '/node.js'),
      ),
      '#weight' => 20,
      '#access' => $node->isNewRevision() || user_access('administer nodes'),
    );

    $form['revision_information']['revision']['revision'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => $node->isNewRevision(),
      '#access' => user_access('administer nodes'),
    );

    $form['revision_information']['revision']['log'] = array(
      '#type' => 'textarea',
      '#title' => t('Revision log message'),
      '#rows' => 4,
      '#default_value' => !empty($node->log) ? $node->log : '',
      '#description' => t('Briefly describe the changes you have made.'),
      '#states' => array(
        'visible' => array(
          ':input[name="revision"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Node author information for administrators.
    $form['author'] = array(
      '#type' => 'details',
      '#access' => user_access('administer nodes'),
      '#title' => t('Authoring information'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'advanced',
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
      '#type' => 'details',
      '#access' => user_access('administer nodes'),
      '#title' => t('Promotion options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('node-form-options'),
      ),
      '#attached' => array(
        'js' => array(drupal_get_path('module', 'node') . '/node.js'),
      ),
      '#weight' => 95,
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
   * Overrides Drupal\entity\EntityFormController::actionsElement().
   */
  protected function actionsElement(array $form, array &$form_state) {
    $element = parent::actionsElement($form, $form_state);
    $node = $this->getEntity($form_state);

    // Because some of the 'links' are actually submit buttons, we have to
    // manually wrap each item in <li> and the whole list in <ul>. The
    // <ul> is added with a #theme_wrappers function.
    $element['operations'] = array(
      '#type' => 'operations',
      '#subtype' => 'node',
      '#attached' => array (
        'css' => array(
          drupal_get_path('module', 'node') . '/node.admin.css',
        ),
      ),
    );

    $element['operations']['actions'] = array(
      '#theme_wrappers' => array('dropbutton_list_wrapper')
    );

    // Depending on the state of the node (published or unpublished) and
    // whether the current user has the permission to change the status, the
    // labels and order of the buttons will vary.
    if (user_access('administer nodes')) {
      $element['operations']['actions']['publish'] = array(
        '#type' => 'submit',
        '#value' => t('Save and publish'),
        '#submit' => array(array($this, 'publish'), array($this, 'submit'), array($this, 'save')),
        '#validate' => array(array($this, 'validate')),
        '#button_type' => $node->status ? 'primary' : '',
        '#weight' => 0,
        '#prefix' => '<li class="publish">',
        '#suffix' => '</li>',
      );
      $element['operations']['actions']['unpublish'] = array(
        '#type' => 'submit',
        '#value' => t('Save as unpublished'),
        '#submit' => array(array($this, 'unpublish'), array($this, 'submit'), array($this, 'save')),
        '#validate' => array(array($this, 'validate')),
        '#button_type' => empty($node->status) ? 'primary' : '',
        '#weight' => $node->status ? 1 : -1,
        '#prefix' => '<li class="unpublish">',
        "#suffix" => '</li>',
      );

      if (!empty($node->nid)) {
        if ($node->status) {
          $publish_label = t('Save and keep published');
          $unpublish_label = t('Save and unpublish');
        }
        else {
          $publish_label = t('Save and publish');
          $unpublish_label = t('Save and keep unpublished');
        }
        $element['operations']['actions']['publish']['#value'] = $publish_label;
        $element['operations']['actions']['unpublish']['#value'] = $unpublish_label;
      }
    }
    // The user has no permission to change the status of the node. Just
    // show a save button without the 'publish' or 'unpublish' callback in
    // the #submit definition.
    else {
      $element['operations']['actions']['save'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
        '#submit' => array(array($this, 'submit'), array($this, 'save')),
        '#validate' => array(array($this, 'validate')),
        '#button_type' => 'primary',
        '#weight' => 1,
        '#prefix' => '<li class="save">',
        "#suffix" => '</li>',
      );
    }

    unset($element['submit']);

    return $element;
  }

  /*
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
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
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
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
    $date = new DrupalDateTime($node->date);
    if ($date->hasErrors()) {
      form_set_error('date', t('You have to specify a valid date.'));
    }

    // Invoke hook_validate() for node type specific validation and
    // hook_node_validate() for miscellaneous validation needed by modules.
    // Can't use node_invoke() or module_invoke_all(), because $form_state must
    // be receivable by reference.
    if ($function = node_hook($node->type, 'validate')) {
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
   * Overrides Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    // Build the node object from the submitted values.
    $node = parent::submit($form, $form_state);

    // Save as a new revision if requested to do so.
    if (!empty($form_state['values']['revision'])) {
      $node->setNewRevision();
    }

    node_submit($node);
    foreach (module_implements('node_submit') as $module) {
      $function = $module . '_node_submit';
      $function($node, $form, $form_state);
    }

    return $node;
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
   * Form submission handler for the 'publish' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function publish(array $form, array &$form_state) {
    $node = $this->getEntity($form_state);
    $node->status = TRUE;
    return $node;
  }

  /**
   * Form submission handler for the 'unpublish' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function unpublish(array $form, array &$form_state) {
    $node = $this->getEntity($form_state);
    $node->status = FALSE;
    return $node;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $node = $this->getEntity($form_state);
    $insert = empty($node->nid);
    $node->save();
    $node_link = l(t('view'), 'node/' . $node->nid);
    $watchdog_args = array('@type' => $node->type, '%title' => $node->label());
    $t_args = array('@type' => node_get_type_label($node), '%title' => $node->label());

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
      $form_state['redirect'] = node_access('view', $node) ? 'node/' . $node->nid : '<front>';
    }
    else {
      // In the unlikely case something went wrong on save, the node will be
      // rebuilt and node form redisplayed the same way as in preview.
      drupal_set_message(t('The post could not be saved.'), 'error');
      $form_state['rebuild'] = TRUE;
    }

    // Clear the page and block caches.
    cache_invalidate_tags(array('content' => TRUE));
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
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
