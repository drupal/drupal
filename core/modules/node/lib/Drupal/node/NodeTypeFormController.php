<?php

/**
 * @file
 * Contains \Drupal\node\NodeTypeFormController.
 */

namespace Drupal\node;

use Drupal\Core\Entity\EntityFormController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\String;

/**
 * Form controller for node type forms.
 */
class NodeTypeFormController extends EntityFormController {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);

    $type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = String::checkPlain($this->t('Add content type'));
    }
    elseif ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit %label content type', array('%label' => $type->label()));
    }

    $node_settings = $type->getModuleSettings('node');
    // Ensure default settings.
    $node_settings += array(
      'options' => array('status', 'promote'),
      'preview' => DRUPAL_OPTIONAL,
      'submitted' => TRUE,
    );

    $form['name'] = array(
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->name,
      '#description' => t('The human-readable name of this content type. This text will be displayed as part of the list on the <em>Add new content</em> page. It is recommended that this name begin with a capital letter and contain only letters, numbers, and spaces. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['type'] = array(
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => 32,
      '#disabled' => $type->isLocked(),
      '#machine_name' => array(
        'exists' => 'node_type_load',
        'source' => array('name'),
      ),
      '#description' => t('A unique machine-readable name for this content type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %node-add page, in which underscores will be converted into hyphens.', array(
        '%node-add' => t('Add new content'),
      )),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->description,
      '#description' => t('Describe this content type. The text will be displayed on the <em>Add new content</em> page.'),
    );

    $form['additional_settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'library' => array(array('node', 'drupal.content_types')),
      ),
    );

    $form['submission'] = array(
      '#type' => 'details',
      '#title' => t('Submission form settings'),
      '#group' => 'additional_settings',
    );
    $form['submission']['title_label'] = array(
      '#title' => t('Title field label'),
      '#type' => 'textfield',
      '#default_value' => $type->title_label,
      '#required' => TRUE,
    );
    if (!$type->has_title) {
      // Avoid overwriting a content type that intentionally does not have a
      // title field.
      $form['submission']['title_label']['#attributes'] = array('disabled' => 'disabled');
      $form['submission']['title_label']['#description'] = t('This content type does not have a title field.');
      $form['submission']['title_label']['#required'] = FALSE;
    }
    $form['submission']['preview'] = array(
      '#type' => 'radios',
      '#title' => t('Preview before submitting'),
      '#parents' => array('settings', 'node', 'preview'),
      '#default_value' => $node_settings['preview'],
      '#options' => array(
        DRUPAL_DISABLED => t('Disabled'),
        DRUPAL_OPTIONAL => t('Optional'),
        DRUPAL_REQUIRED => t('Required'),
      ),
    );
    $form['submission']['help']  = array(
      '#type' => 'textarea',
      '#title' => t('Explanation or submission guidelines'),
      '#default_value' => $type->help,
      '#description' => t('This text will be displayed at the top of the page when creating or editing content of this type.'),
    );
    $form['workflow'] = array(
      '#type' => 'details',
      '#title' => t('Publishing options'),
      '#collapsed' => TRUE,
      '#group' => 'additional_settings',
    );
    $form['workflow']['options'] = array('#type' => 'checkboxes',
      '#title' => t('Default options'),
      '#parents' => array('settings', 'node', 'options'),
      '#default_value' => $node_settings['options'],
      '#options' => array(
        'status' => t('Published'),
        'promote' => t('Promoted to front page'),
        'sticky' => t('Sticky at top of lists'),
        'revision' => t('Create new revision'),
      ),
      '#description' => t('Users with the <em>Administer content</em> permission will be able to override these options.'),
    );
    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = array(
        '#type' => 'details',
        '#title' => t('Language settings'),
        '#collapsed' => TRUE,
        '#group' => 'additional_settings',
      );

      $language_configuration = language_get_default_configuration('node', $type->id());
      $form['language']['language_configuration'] = array(
        '#type' => 'language_configuration',
        '#entity_information' => array(
          'entity_type' => 'node',
          'bundle' => $type->id(),
        ),
        '#default_value' => $language_configuration,
      );
    }
    $form['display'] = array(
      '#type' => 'details',
      '#title' => t('Display settings'),
      '#collapsed' => TRUE,
      '#group' => 'additional_settings',
    );
    $form['display']['submitted'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display author and date information.'),
      '#parents' => array('settings', 'node', 'submitted'),
      '#default_value' => $node_settings['submitted'],
      '#description' => t('Author username and publish date will be displayed.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save content type');
    $actions['delete']['#value'] = t('Delete content type');
    $actions['delete']['#access'] = $this->entity->access('delete');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    $id = trim($form_state['values']['type']);
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      form_set_error('type', t("Invalid machine-readable name. Enter a name other than %invalid.", array('%invalid' => $id)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $type = $this->entity;
    $type->type = trim($type->id());
    $type->name = trim($type->name);

    // title_label is required in core; has_title will always be TRUE, unless a
    // module alters the title field.
    $type->has_title = ($type->title_label != '');

    $variables = $form_state['values'];

    // Do not save settings from vertical tabs.
    // @todo Fix vertical_tabs.
    unset($variables['additional_settings__active_tab']);

    // @todo Remove the entire following code after converting node settings of
    //   Comment and Menu module. https://drupal.org/node/2026165
    // Remove all node type entity properties.
    foreach (get_class_vars(get_class($type)) as $key => $value) {
      unset($variables[$key]);
    }
    // Save or reset persistent variable values.
    foreach ($variables as $key => $value) {
      $variable_new = $key . '_' . $type->id();
      $variable_old = $key . '_' . $type->getOriginalID();
      if (is_array($value)) {
        $value = array_keys(array_filter($value));
      }
      variable_set($variable_new, $value);
      if ($variable_new != $variable_old) {
        variable_del($variable_old);
      }
    }
    // Saving the content type after saving the variables allows modules to act
    // on those variables via hook_node_type_insert().
    $status = $type->save();

    $t_args = array('%name' => $type->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The content type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The content type %name has been added.', $t_args));
      watchdog('node', 'Added content type %name.', $t_args, WATCHDOG_NOTICE, l(t('view'), 'admin/structure/types'));
    }

    $form_state['redirect'] = 'admin/structure/types';
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $form_state['redirect'] = 'admin/structure/types/manage/' . $this->entity->id() . '/delete';
  }

}
