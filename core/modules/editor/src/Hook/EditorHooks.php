<?php

namespace Drupal\editor\Hook;

use Drupal\editor\Entity\Editor;
use Drupal\filter\FilterFormatInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for editor.
 */
class EditorHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.editor':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Text Editor module provides a framework that other modules (such as <a href=":ckeditor5">CKEditor5 module</a>) can use to provide toolbars and other functionality that allow users to format text more easily than typing HTML tags directly. For more information, see the <a href=":documentation">online documentation for the Text Editor module</a>.', [
          ':documentation' => 'https://www.drupal.org/documentation/modules/editor',
          ':ckeditor5' => \Drupal::moduleHandler()->moduleExists('ckeditor5') ? Url::fromRoute('help.page', [
            'name' => 'ckeditor5',
          ])->toString() : '#',
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Installing text editors') . '</dt>';
        $output .= '<dd>' . t('The Text Editor module provides a framework for managing editors. To use it, you also need to install a text editor. This can either be the core <a href=":ckeditor5">CKEditor5 module</a>, which can be installed on the <a href=":extend">Extend page</a>, or a contributed module for any other text editor. When installing a contributed text editor module, be sure to check the installation instructions, because you will most likely need to download an external library as well as the Drupal module.', [
          ':ckeditor5' => \Drupal::moduleHandler()->moduleExists('ckeditor5') ? Url::fromRoute('help.page', [
            'name' => 'ckeditor5',
          ])->toString() : '#',
          ':extend' => Url::fromRoute('system.modules_list')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Enabling a text editor for a text format') . '</dt>';
        $output .= '<dd>' . t('On the <a href=":formats">Text formats and editors page</a> you can see which text editor is associated with each text format. You can change this by clicking on the <em>Configure</em> link, and then choosing a text editor or <em>none</em> from the <em>Text editor</em> drop-down list. The text editor will then be displayed with any text field for which this text format is chosen.', [':formats' => Url::fromRoute('filter.admin_overview')->toString()]) . '</dd>';
        $output .= '<dt>' . t('Configuring a text editor') . '</dt>';
        $output .= '<dd>' . t('Once a text editor is associated with a text format, you can configure it by clicking on the <em>Configure</em> link for this format. Depending on the specific text editor, you can configure it for example by adding buttons to its toolbar. Typically these buttons provide formatting or editing tools, and they often insert HTML tags into the field source. For details, see the help page of the specific text editor.') . '</dd>';
        $output .= '<dt>' . t('Using different text editors and formats') . '</dt>';
        $output .= '<dd>' . t('If you change the text format on a text field, the text editor will change as well because the text editor configuration is associated with the individual text format. This allows the use of the same text editor with different options for different text formats. It also allows users to choose between text formats with different text editors if they are installed.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
  }

  /**
   * Implements hook_menu_links_discovered_alter().
   *
   * Rewrites the menu entries for filter module that relate to the configuration
   * of text editors.
   */
  #[Hook('menu_links_discovered_alter')]
  public function menuLinksDiscoveredAlter(array &$links): void {
    $links['filter.admin_overview']['title'] = new TranslatableMarkup('Text formats and editors');
    $links['filter.admin_overview']['description'] = new TranslatableMarkup('Select and configure text editors, and how content is filtered when displayed.');
  }

  /**
   * Implements hook_element_info_alter().
   *
   * Extends the functionality of text_format elements (provided by Filter
   * module), so that selecting a text format notifies a client-side text editor
   * when it should be enabled or disabled.
   *
   * @see \Drupal\filter\Element\TextFormat
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(&$types): void {
    $types['text_format']['#pre_render'][] = 'element.editor:preRenderTextFormat';
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_filter_admin_overview_alter')]
  public function formFilterAdminOverviewAlter(&$form, FormStateInterface $form_state) : void {
    // @todo Cleanup column injection: https://www.drupal.org/node/1876718.
    // Splice in the column for "Text editor" into the header.
    $position = array_search('name', $form['formats']['#header']) + 1;
    $start = array_splice($form['formats']['#header'], 0, $position, ['editor' => t('Text editor')]);
    $form['formats']['#header'] = array_merge($start, $form['formats']['#header']);
    // Then splice in the name of each text editor for each text format.
    $editors = \Drupal::service('plugin.manager.editor')->getDefinitions();
    foreach (Element::children($form['formats']) as $format_id) {
      $editor = editor_load($format_id);
      $editor_name = $editor && isset($editors[$editor->getEditor()]) ? $editors[$editor->getEditor()]['label'] : '—';
      $editor_column['editor'] = ['#markup' => $editor_name];
      $position = array_search('name', array_keys($form['formats'][$format_id])) + 1;
      $start = array_splice($form['formats'][$format_id], 0, $position, $editor_column);
      $form['formats'][$format_id] = array_merge($start, $form['formats'][$format_id]);
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for \Drupal\filter\FilterFormatEditForm.
   */
  #[Hook('form_filter_format_form_alter')]
  public function formFilterFormatFormAlter(&$form, FormStateInterface $form_state) : void {
    $editor = $form_state->get('editor');
    if ($editor === NULL) {
      $format = $form_state->getFormObject()->getEntity();
      $format_id = $format->isNew() ? NULL : $format->id();
      $editor = editor_load($format_id);
      $form_state->set('editor', $editor);
    }
    // Associate a text editor with this text format.
    $manager = \Drupal::service('plugin.manager.editor');
    $editor_options = $manager->listOptions();
    $form['editor'] = ['#weight' => -9];
    $form['editor']['editor'] = [
      '#type' => 'select',
      '#title' => t('Text editor'),
      '#options' => $editor_options,
      '#empty_option' => t('None'),
      '#default_value' => $editor ? $editor->getEditor() : '',
      '#ajax' => [
        'trigger_as' => [
          'name' => 'editor_configure',
        ],
        'callback' => 'editor_form_filter_admin_form_ajax',
        'wrapper' => 'editor-settings-wrapper',
      ],
      '#weight' => -10,
    ];
    $form['editor']['configure'] = [
      '#type' => 'submit',
      '#name' => 'editor_configure',
      '#value' => t('Configure'),
      '#limit_validation_errors' => [
              [
                'editor',
              ],
      ],
      '#submit' => [
        'editor_form_filter_admin_format_editor_configure',
      ],
      '#ajax' => [
        'callback' => 'editor_form_filter_admin_form_ajax',
        'wrapper' => 'editor-settings-wrapper',
      ],
      '#weight' => -10,
      '#attributes' => [
        'class' => [
          'js-hide',
        ],
      ],
    ];
    // If there aren't any options (other than "None"), disable the select list.
    if (empty($editor_options)) {
      $form['editor']['editor']['#disabled'] = TRUE;
      $form['editor']['editor']['#description'] = t('This option is disabled because no modules that provide a text editor are currently enabled.');
    }
    $form['editor']['settings'] = [
      '#tree' => TRUE,
      '#weight' => -8,
      '#type' => 'container',
      '#id' => 'editor-settings-wrapper',
    ];
    // Add editor-specific validation and submit handlers.
    if ($editor) {
      /** @var \Drupal\editor\Plugin\EditorPluginInterface $plugin */
      $plugin = $manager->createInstance($editor->getEditor());
      $form_state->set('editor_plugin', $plugin);
      $form['editor']['settings']['subform'] = [];
      $subform_state = SubformState::createForSubform($form['editor']['settings']['subform'], $form, $form_state);
      $form['editor']['settings']['subform'] = $plugin->buildConfigurationForm($form['editor']['settings']['subform'], $subform_state);
      $form['editor']['settings']['subform']['#parents'] = ['editor', 'settings'];
    }
    $form['#validate'][] = 'editor_form_filter_admin_format_validate';
    $form['actions']['submit']['#submit'][] = 'editor_form_filter_admin_format_submit';
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity) {
    // Only act on content entities.
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    $referenced_files_by_field = _editor_get_file_uuids_by_field($entity);
    foreach ($referenced_files_by_field as $uuids) {
      _editor_record_file_usage($uuids, $entity);
    }
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity) {
    // Only act on content entities.
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    // On new revisions, all files are considered to be a new usage and no
    // deletion of previous file usages are necessary.
    if (!empty($entity->original) && $entity->getRevisionId() != $entity->original->getRevisionId()) {
      $referenced_files_by_field = _editor_get_file_uuids_by_field($entity);
      foreach ($referenced_files_by_field as $uuids) {
        _editor_record_file_usage($uuids, $entity);
      }
    }
    else {
      $original_uuids_by_field = empty($entity->original) ? [] : _editor_get_file_uuids_by_field($entity->original);
      $uuids_by_field = _editor_get_file_uuids_by_field($entity);
      // Detect file usages that should be incremented.
      foreach ($uuids_by_field as $field => $uuids) {
        $original_uuids = $original_uuids_by_field[$field] ?? [];
        if ($added_files = array_diff($uuids_by_field[$field], $original_uuids)) {
          _editor_record_file_usage($added_files, $entity);
        }
      }
      // Detect file usages that should be decremented.
      foreach ($original_uuids_by_field as $field => $uuids) {
        $removed_files = array_diff($original_uuids_by_field[$field], $uuids_by_field[$field]);
        _editor_delete_file_usage($removed_files, $entity, 1);
      }
    }
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity) {
    // Only act on content entities.
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    $referenced_files_by_field = _editor_get_file_uuids_by_field($entity);
    foreach ($referenced_files_by_field as $uuids) {
      _editor_delete_file_usage($uuids, $entity, 0);
    }
  }

  /**
   * Implements hook_entity_revision_delete().
   */
  #[Hook('entity_revision_delete')]
  public function entityRevisionDelete(EntityInterface $entity) {
    // Only act on content entities.
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    $referenced_files_by_field = _editor_get_file_uuids_by_field($entity);
    foreach ($referenced_files_by_field as $uuids) {
      _editor_delete_file_usage($uuids, $entity, 1);
    }
  }

  /**
   * Implements hook_file_download().
   *
   * @see file_file_download()
   * @see file_get_file_references()
   */
  #[Hook('file_download')]
  public function fileDownload($uri) {
    // Get the file record based on the URI. If not in the database just return.
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $file = $file_repository->loadByUri($uri);
    if (!$file) {
      return;
    }
    // Temporary files are handled by file_file_download(), so nothing to do here
    // about them.
    // @see file_file_download()
    // Find out if any editor-backed field contains the file.
    $usage_list = \Drupal::service('file.usage')->listUsage($file);
    // Stop processing if there are no references in order to avoid returning
    // headers for files controlled by other modules. Make an exception for
    // temporary files where the host entity has not yet been saved (for example,
    // an image preview on a node creation form) in which case, allow download by
    // the file's owner.
    if (empty($usage_list['editor']) && ($file->isPermanent() || $file->getOwnerId() != \Drupal::currentUser()->id())) {
      return;
    }
    // Editor.module MUST NOT call $file->access() here (like file_file_download()
    // does) as checking the 'download' access to a file entity would end up in
    // FileAccessControlHandler->checkAccess() and ->getFileReferences(), which
    // calls file_get_file_references(). This latter one would allow downloading
    // files only handled by the file.module, which is exactly not the case right
    // here. So instead we must check if the current user is allowed to view any
    // of the entities that reference the image using the 'editor' module.
    if ($file->isPermanent()) {
      $referencing_entity_is_accessible = FALSE;
      $references = empty($usage_list['editor']) ? [] : $usage_list['editor'];
      foreach ($references as $entity_type => $entity_ids_usage_count) {
        $referencing_entities = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple(array_keys($entity_ids_usage_count));
        /** @var \Drupal\Core\Entity\EntityInterface $referencing_entity */
        foreach ($referencing_entities as $referencing_entity) {
          if ($referencing_entity->access('view', NULL, TRUE)->isAllowed()) {
            $referencing_entity_is_accessible = TRUE;
            break 2;
          }
        }
      }
      if (!$referencing_entity_is_accessible) {
        return -1;
      }
    }
    // Access is granted.
    $headers = file_get_content_headers($file);
    return $headers;
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   *
   * Synchronizes the editor status to its paired text format status.
   *
   * @todo remove in https://www.drupal.org/project/drupal/issues/3231354.
   */
  #[Hook('filter_format_presave')]
  public function filterFormatPresave(FilterFormatInterface $format) {
    // The text format being created cannot have a text editor yet.
    if ($format->isNew()) {
      return;
    }
    /** @var \Drupal\filter\FilterFormatInterface $original */
    $original = \Drupal::entityTypeManager()->getStorage('filter_format')->loadUnchanged($format->getOriginalId());
    // If the text format status is the same, return early.
    if (($status = $format->status()) === $original->status()) {
      return;
    }
    /** @var \Drupal\editor\EditorInterface $editor */
    if ($editor = Editor::load($format->id())) {
      $editor->setStatus($status)->save();
    }
  }

}
