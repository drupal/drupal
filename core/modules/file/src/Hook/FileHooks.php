<?php

namespace Drupal\file\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for file.
 */
class FileHooks {

  // cspell:ignore widthx
  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): string|array|null {
    switch ($route_name) {
      case 'help.page.file':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The File module allows you to create fields that contain files. See the <a href=":field">Field module help</a> and the <a href=":field_ui">Field UI help</a> pages for general information on fields and how to create and manage them. For more information, see the <a href=":file_documentation">online documentation for the File module</a>.', [
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
          ':file_documentation' => 'https://www.drupal.org/documentation/modules/file',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Managing and displaying file fields') . '</dt>';
        $output .= '<dd>' . $this->t('The <em>settings</em> and the <em>display</em> of the file field can be configured separately. See the <a href=":field_ui">Field UI help</a> for more information on how to manage fields and their display.', [
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Allowing file extensions') . '</dt>';
        $output .= '<dd>' . $this->t('In the field settings, you can define the allowed file extensions (for example <em>pdf docx psd</em>) for the files that will be uploaded with the file field.') . '</dd>';
        $output .= '<dt>' . $this->t('Storing files') . '</dt>';
        $output .= '<dd>' . $this->t('Uploaded files can either be stored as <em>public</em> or <em>private</em>, depending on the <a href=":file-system">File system settings</a>. For more information, see the <a href=":system-help">System module help page</a>.', [
          ':file-system' => Url::fromRoute('system.file_system_settings')->toString(),
          ':system-help' => Url::fromRoute('help.page', [
            'name' => 'system',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Restricting the maximum file size') . '</dt>';
        $output .= '<dd>' . $this->t('The maximum file size that users can upload is limited by PHP settings of the server, but you can restrict by entering the desired value as the <em>Maximum upload size</em> setting. The maximum file size is automatically displayed to users in the help text of the file field.') . '</dd>';
        $output .= '<dt>' . $this->t('Displaying files and descriptions') . '<dt>';
        $output .= '<dd>' . $this->t('In the field settings, you can allow users to toggle whether individual files are displayed. In the display settings, you can then choose one of the following formats: <ul><li><em>Generic file</em> displays links to the files and adds icons that symbolize the file extensions. If <em>descriptions</em> are enabled and have been submitted, then the description is displayed instead of the file name.</li><li><em>URL to file</em> displays the full path to the file as plain text.</li><li><em>Table of files</em> lists links to the files and the file sizes in a table.</li><li><em>RSS enclosure</em> only displays the first file, and only in a RSS feed, formatted according to the RSS 2.0 syntax for enclosures.</li></ul> A file can still be linked to directly by its URI even if it is not displayed.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_field_widget_info_alter().
   */
  #[Hook('field_widget_info_alter')]
  public function fieldWidgetInfoAlter(array &$info): void {
    // Allows using the 'uri' widget for the 'file_uri' field type, which uses
    // it as the default widget.
    // @see \Drupal\file\Plugin\Field\FieldType\FileUriItem
    $info['uri']['field_types'][] = 'file_uri';
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      // From file.module.
      'file_link' => [
        'variables' => [
          'file' => NULL,
          'description' => NULL,
          'attributes' => [],
        ],
      ],
      'file_managed_file' => [
        'render element' => 'element',
      ],
      'file_audio' => [
        'variables' => [
          'files' => [],
          'attributes' => NULL,
        ],
      ],
      'file_video' => [
        'variables' => [
          'files' => [],
          'attributes' => NULL,
        ],
      ],
      'file_widget_multiple' => [
        'render element' => 'element',
      ],
      'file_upload_help' => [
        'variables' => [
          'description' => NULL,
          'upload_validators' => NULL,
          'cardinality' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for file entities.
   */
  #[Hook('file_predelete')]
  public function filePredelete(File $file): void {
    // @todo Remove references to a file that is in-use. See https://www.drupal.org/project/drupal/issues/1506314
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Injects the file sanitization options into /admin/config/media/file-system.
   *
   * These settings are enforced during upload by the FileEventSubscriber that
   * listens to the FileUploadSanitizeNameEvent event.
   *
   * @see \Drupal\system\Form\FileSystemForm
   * @see \Drupal\Core\File\Event\FileUploadSanitizeNameEvent
   * @see \Drupal\file\EventSubscriber\FileEventSubscriber
   */
  #[Hook('form_system_file_system_settings_alter')]
  public function formSystemFileSystemSettingsAlter(array &$form, FormStateInterface $form_state): void {
    $config = \Drupal::config('file.settings');
    $form['filename_sanitization'] = [
      '#type' => 'details',
      '#title' => $this->t('Sanitize filenames'),
      '#description' => $this->t('These settings only apply to new files as they are uploaded. Changes here do not affect existing file names.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['filename_sanitization']['replacement_character'] = [
      '#type' => 'select',
      '#title' => $this->t('Replacement character'),
      '#default_value' => $config->get('filename_sanitization.replacement_character'),
      '#options' => [
        '-' => $this->t('Dash (-)'),
        '_' => $this->t('Underscore (_)'),
      ],
      '#description' => $this->t('Used when replacing whitespace, replacing non-alphanumeric characters or transliterating unknown characters.'),
    ];
    $form['filename_sanitization']['transliterate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Transliterate'),
      '#default_value' => $config->get('filename_sanitization.transliterate'),
      '#description' => $this->t('Transliteration replaces any characters that are not alphanumeric, underscores, periods or hyphens with the replacement character. It ensures filenames only contain ASCII characters. It is recommended to keep transliteration enabled.'),
    ];
    $form['filename_sanitization']['replace_whitespace'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace whitespace with the replacement character'),
      '#default_value' => $config->get('filename_sanitization.replace_whitespace'),
    ];
    $form['filename_sanitization']['replace_non_alphanumeric'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace non-alphanumeric characters with the replacement character'),
      '#default_value' => $config->get('filename_sanitization.replace_non_alphanumeric'),
      '#description' => $this->t('Alphanumeric characters, dots <span aria-hidden="true">(.)</span>, underscores <span aria-hidden="true">(_)</span> and dashes <span aria-hidden="true">(-)</span> are preserved.'),
    ];
    $form['filename_sanitization']['deduplicate_separators'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace sequences of dots, underscores and/or dashes with the replacement character'),
      '#default_value' => $config->get('filename_sanitization.deduplicate_separators'),
    ];
    $form['filename_sanitization']['lowercase'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Convert to lowercase'),
      '#default_value' => $config->get('filename_sanitization.lowercase'),
    ];
    $form['#submit'][] = 'file_system_settings_submit';
  }

}
