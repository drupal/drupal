<?php

namespace Drupal\field_ui\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field_ui\Plugin\Derivative\FieldUiLocalTask;
use Drupal\Core\Entity\EntityFormModeInterface;
use Drupal\Core\Entity\EntityViewModeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field_ui\Form\FieldStorageConfigEditForm;
use Drupal\field_ui\Form\FieldConfigEditForm;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for field_ui.
 */
class FieldUiHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.field_ui':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Field UI module provides an administrative user interface (UI) for managing and displaying fields. Fields can be attached to most content entity sub-types. Different field types, widgets, and formatters are provided by the modules installed on your site, and managed by the Field module. For background information and terminology related to fields and entities, see the <a href=":field">Field module help page</a>. For more information about the Field UI, see the <a href=":field_ui_docs">online documentation for the Field UI module</a>.', [
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
          ':field_ui_docs' => 'https://www.drupal.org/docs/8/core/modules/field-ui',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Creating a field') . '</dt>';
        $output .= '<dd>' . $this->t('On the <em>Manage fields</em> page for your entity type or sub-type, you can add, configure, and delete fields for that entity type or sub-type. Each field has a <em>machine name</em>, which is used internally to identify the field and must be unique across an entity type; once a field is created, you cannot change the machine name. Most fields have two types of settings. The field-level settings depend on the field type, and affect how the data in the field is stored. Once they are set, they can no longer be changed; examples include how many data values are allowed for the field and where files are stored. The sub-type-level settings are specific to each entity sub-type the field is used on, and they can be changed later; examples include the field label, help text, default value, and whether the field is required or not. You can return to these settings by choosing the <em>Edit</em> link for the field from the <em>Manage fields</em> page.');
        $output .= '<dt>' . $this->t('Re-using fields') . '</dt>';
        $output .= '<dd>' . $this->t('Once you have created a field, you can use it again in other sub-types of the same entity type. For instance, if you create a field for the article content type, you can also use it for the page content type, but you cannot use it for content blocks or taxonomy terms. If there are fields available for re-use, after clicking <em>Add field</em> from the <em>Manage fields</em> page, you will see a list of available fields for re-use. After selecting a field for re-use, you can configure the sub-type-level settings.') . '</dd>';
        $output .= '<dt>' . $this->t('Configuring field editing') . '</dt>';
        $output .= '<dd>' . $this->t('On the <em>Manage form display</em> page of your entity type or sub-type, you can configure how the field data is edited by default and in each form mode. If your entity type has multiple form modes (on most sites, most entities do not), you can toggle between the form modes at the top of the page, and you can toggle whether each form mode uses the default settings or custom settings in the <em>Custom display settings</em> section. For each field in each form mode, you can select the widget to use for editing; some widgets have additional configuration options, such as the size for a text field, and these can be edited using the Edit button (which looks like a wheel). You can also change the order of the fields on the form. You can exclude a field from a form by choosing <em>Hidden</em> from the widget drop-down list, or by dragging it into the <em>Disabled</em> section.') . '</dd>';
        $output .= '<dt>' . $this->t('Configuring field display') . '</dt>';
        $output .= '<dd>' . $this->t('On the <em>Manage display</em> page of your entity type or sub-type, you can configure how each field is displayed by default and in each view mode. If your entity type has multiple view modes, you can toggle between the view modes at the top of the page, and you can toggle whether each view mode uses the default settings or custom settings in the <em>Custom display settings</em> section. For each field in each view mode, you can choose whether and how to display the label of the field from the <em>Label</em> drop-down list. You can also select the formatter to use for display; some formatters have configuration options, which you can edit using the Edit button (which looks like a wheel). You can also change the display order of fields. You can exclude a field from a specific view mode by choosing <em>Hidden</em> from the formatter drop-down list, or by dragging it into the <em>Disabled</em> section.') . '</dd>';
        $output .= '<dt>' . $this->t('Configuring view and form modes') . '</dt>';
        $output .= '<dd>' . $this->t('You can add, edit, and delete view modes for entities on the <a href=":view_modes">View modes page</a>, and you can add, edit, and delete form modes for entities on the <a href=":form_modes">Form modes page</a>. Once you have defined a view mode or form mode for an entity type, it will be available on the Manage display or Manage form display page for each sub-type of that entity.', [
          ':view_modes' => Url::fromRoute('entity.entity_view_mode.collection')->toString(),
          ':form_modes' => Url::fromRoute('entity.entity_form_mode.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Listing fields') . '</dt>';
        $output .= '<dd>' . $this->t('There are two reports available that list the fields defined on your site. The <a href=":entity-list" title="Entities field list report">Entities</a> report lists all your fields, showing the field machine names, types, and the entity types or sub-types they are used on (each sub-type links to the Manage fields page). If the <a href=":views">Views</a> and <a href=":views-ui">Views UI</a> modules are installed, the <a href=":views-list" title="Used in views field list report">Used in views</a> report lists each field that is used in a view, with a link to edit that view.', [
          ':entity-list' => Url::fromRoute('entity.field_storage_config.collection')->toString(),
          ':views-list' => \Drupal::moduleHandler()->moduleExists('views_ui') ? Url::fromRoute('views_ui.reports_fields')->toString() : '#',
          ':views' => \Drupal::moduleHandler()->moduleExists('views') ? Url::fromRoute('help.page', [
            'name' => 'views',
          ])->toString() : '#',
          ':views-ui' => \Drupal::moduleHandler()->moduleExists('views_ui') ? Url::fromRoute('help.page', [
            'name' => 'views_ui',
          ])->toString() : '#',
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'entity.field_storage_config.collection':
        return '<p>' . $this->t('This list shows all fields currently in use for easy reference.') . '</p>';
    }
    return NULL;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'field_ui_table' => [
        'variables' => [
          'header' => NULL,
          'rows' => NULL,
          'footer' => NULL,
          'attributes' => [],
          'caption' => NULL,
          'colgroups' => [],
          'sticky' => FALSE,
          'responsive' => TRUE,
          'empty' => '',
        ],
      ],
      // Provide a dedicated template for new storage options as their styling
      // is quite different from a typical form element, so it works best to not
      // include default form element classes.
      'form_element__new_storage_type' => [
        'base hook' => 'form_element',
        'render element' => 'element',
      ],
    ];
  }

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types): void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types['field_config']->setFormClass('edit', 'Drupal\field_ui\Form\FieldConfigEditForm');
    $entity_types['field_config']->setFormClass('default', FieldConfigEditForm::class);
    $entity_types['field_config']->setFormClass('delete', 'Drupal\field_ui\Form\FieldConfigDeleteForm');
    $entity_types['field_config']->setListBuilderClass('Drupal\field_ui\FieldConfigListBuilder');
    $entity_types['field_storage_config']->setFormClass('edit', 'Drupal\field_ui\Form\FieldStorageConfigEditForm');
    $entity_types['field_storage_config']->setFormClass('default', FieldStorageConfigEditForm::class);
    $entity_types['field_storage_config']->setListBuilderClass('Drupal\field_ui\FieldStorageConfigListBuilder');
    $entity_types['field_storage_config']->setLinkTemplate('collection', '/admin/reports/fields');
    $entity_types['entity_form_display']->setFormClass('edit', 'Drupal\field_ui\Form\EntityFormDisplayEditForm');
    $entity_types['entity_view_display']->setFormClass('edit', 'Drupal\field_ui\Form\EntityViewDisplayEditForm');
    $form_mode = $entity_types['entity_form_mode'];
    $form_mode->setListBuilderClass('Drupal\field_ui\EntityFormModeListBuilder');
    $form_mode->setFormClass('add', 'Drupal\field_ui\Form\EntityFormModeAddForm');
    $form_mode->setFormClass('edit', 'Drupal\field_ui\Form\EntityDisplayModeEditForm');
    $form_mode->setFormClass('delete', 'Drupal\field_ui\Form\EntityDisplayModeDeleteForm');
    $form_mode->set('admin_permission', 'administer display modes');
    $form_mode->setLinkTemplate('delete-form', '/admin/structure/display-modes/form/manage/{entity_form_mode}/delete');
    $form_mode->setLinkTemplate('edit-form', '/admin/structure/display-modes/form/manage/{entity_form_mode}');
    $form_mode->setLinkTemplate('add-form', '/admin/structure/display-modes/form/add/{entity_type_id}');
    $form_mode->setLinkTemplate('collection', '/admin/structure/display-modes/form');
    $view_mode = $entity_types['entity_view_mode'];
    $view_mode->setListBuilderClass('Drupal\field_ui\EntityDisplayModeListBuilder');
    $view_mode->setFormClass('add', 'Drupal\field_ui\Form\EntityDisplayModeAddForm');
    $view_mode->setFormClass('edit', 'Drupal\field_ui\Form\EntityDisplayModeEditForm');
    $view_mode->setFormClass('delete', 'Drupal\field_ui\Form\EntityDisplayModeDeleteForm');
    $view_mode->set('admin_permission', 'administer display modes');
    $view_mode->setLinkTemplate('delete-form', '/admin/structure/display-modes/view/manage/{entity_view_mode}/delete');
    $view_mode->setLinkTemplate('edit-form', '/admin/structure/display-modes/view/manage/{entity_view_mode}');
    $view_mode->setLinkTemplate('add-form', '/admin/structure/display-modes/view/add/{entity_type_id}');
    $view_mode->setLinkTemplate('collection', '/admin/structure/display-modes/view');
  }

  /**
   * Implements hook_entity_bundle_create().
   */
  #[Hook('entity_bundle_create')]
  public function entityBundleCreate($entity_type, $bundle): void {
    // When a new bundle is created, the menu needs to be rebuilt to add our
    // menu item tabs.
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity): array {
    $operations = [];
    $info = $entity->getEntityType();
    // Add manage fields and display links if this entity type is the bundle
    // of another and that type has field UI enabled.
    if (($bundle_of = $info->getBundleOf()) && \Drupal::entityTypeManager()->getDefinition($bundle_of)->get('field_ui_base_route')) {
      $account = \Drupal::currentUser();
      if ($account->hasPermission('administer ' . $bundle_of . ' fields')) {
        $operations['manage-fields'] = [
          'title' => $this->t('Manage fields'),
          'weight' => 15,
          'url' => Url::fromRoute("entity.{$bundle_of}.field_ui_fields", [
            $entity->getEntityTypeId() => $entity->id(),
          ]),
        ];
      }
      if ($account->hasPermission('administer ' . $bundle_of . ' form display')) {
        $operations['manage-form-display'] = [
          'title' => $this->t('Manage form display'),
          'weight' => 20,
          'url' => Url::fromRoute("entity.entity_form_display.{$bundle_of}.default", [
            $entity->getEntityTypeId() => $entity->id(),
          ]),
        ];
      }
      if ($account->hasPermission('administer ' . $bundle_of . ' display')) {
        $operations['manage-display'] = [
          'title' => $this->t('Manage display'),
          'weight' => 25,
          'url' => Url::fromRoute("entity.entity_view_display.{$bundle_of}.default", [
            $entity->getEntityTypeId() => $entity->id(),
          ]),
        ];
      }
    }
    return $operations;
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('entity_view_mode_presave')]
  public function entityViewModePresave(EntityViewModeInterface $view_mode): void {
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('entity_form_mode_presave')]
  public function entityFormModePresave(EntityFormModeInterface $form_mode): void {
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('entity_view_mode_delete')]
  public function entityViewModeDelete(EntityViewModeInterface $view_mode): void {
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('entity_form_mode_delete')]
  public function entityFormModeDelete(EntityFormModeInterface $form_mode): void {
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * Implements hook_local_tasks_alter().
   */
  #[Hook('local_tasks_alter')]
  public function localTasksAlter(&$local_tasks): void {
    $container = \Drupal::getContainer();
    $local_task = FieldUiLocalTask::create($container, 'field_ui.fields');
    $local_task->alterLocalTasks($local_tasks);
  }

  /**
   * Implements hook_form_FORM_ID_alter() for 'field_ui_field_storage_add_form'.
   */
  #[Hook('form_field_ui_field_storage_add_form_alter')]
  public function formFieldUiFieldStorageAddFormAlter(array &$form) : void {
    $optgroup = (string) $this->t('Reference');
    // Move the "Entity reference" option to the end of the list and rename it
    // to "Other".
    unset($form['add']['new_storage_type']['#options'][$optgroup]['entity_reference']);
    $form['add']['new_storage_type']['#options'][$optgroup]['entity_reference'] = $this->t('Other…');
  }

  /**
   * Implements hook_form_alter().
   *
   * Adds a button 'Save and manage fields' to forms.
   *
   * @see \Drupal\node\Form\NodeTypeForm
   * @see \Drupal\comment\CommentTypeForm
   * @see \Drupal\media\MediaTypeForm
   * @see \Drupal\block_content\BlockContentTypeForm
   * @see field_ui_form_manage_field_form_submit()
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    $forms = [
      'node_type_add_form',
      'comment_type_add_form',
      'media_type_add_form',
      'block_content_type_add_form',
    ];
    if (!in_array($form_id, $forms)) {
      return;
    }
    if ($form_state->getFormObject()->getEntity()->isNew()) {
      $form['actions']['save_continue'] = $form['actions']['submit'];
      unset($form['actions']['submit']['#button_type']);
      $form['actions']['save_continue']['#value'] = $this->t('Save and manage fields');
      $form['actions']['save_continue']['#weight'] = $form['actions']['save_continue']['#weight'] - 5;
      $form['actions']['save_continue']['#submit'][] = 'field_ui_form_manage_field_form_submit';
    }
  }

}
