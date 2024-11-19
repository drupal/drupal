<?php

namespace Drupal\file\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for file.
 */
class FileHooks {
  // cspell:ignore widthx

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.file':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The File module allows you to create fields that contain files. See the <a href=":field">Field module help</a> and the <a href=":field_ui">Field UI help</a> pages for general information on fields and how to create and manage them. For more information, see the <a href=":file_documentation">online documentation for the File module</a>.', [
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
          ':file_documentation' => 'https://www.drupal.org/documentation/modules/file',
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Managing and displaying file fields') . '</dt>';
        $output .= '<dd>' . t('The <em>settings</em> and the <em>display</em> of the file field can be configured separately. See the <a href=":field_ui">Field UI help</a> for more information on how to manage fields and their display.', [
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
        ]) . '</dd>';
        $output .= '<dt>' . t('Allowing file extensions') . '</dt>';
        $output .= '<dd>' . t('In the field settings, you can define the allowed file extensions (for example <em>pdf docx psd</em>) for the files that will be uploaded with the file field.') . '</dd>';
        $output .= '<dt>' . t('Storing files') . '</dt>';
        $output .= '<dd>' . t('Uploaded files can either be stored as <em>public</em> or <em>private</em>, depending on the <a href=":file-system">File system settings</a>. For more information, see the <a href=":system-help">System module help page</a>.', [
          ':file-system' => Url::fromRoute('system.file_system_settings')->toString(),
          ':system-help' => Url::fromRoute('help.page', [
            'name' => 'system',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Restricting the maximum file size') . '</dt>';
        $output .= '<dd>' . t('The maximum file size that users can upload is limited by PHP settings of the server, but you can restrict by entering the desired value as the <em>Maximum upload size</em> setting. The maximum file size is automatically displayed to users in the help text of the file field.') . '</dd>';
        $output .= '<dt>' . t('Displaying files and descriptions') . '<dt>';
        $output .= '<dd>' . t('In the field settings, you can allow users to toggle whether individual files are displayed. In the display settings, you can then choose one of the following formats: <ul><li><em>Generic file</em> displays links to the files and adds icons that symbolize the file extensions. If <em>descriptions</em> are enabled and have been submitted, then the description is displayed instead of the file name.</li><li><em>URL to file</em> displays the full path to the file as plain text.</li><li><em>Table of files</em> lists links to the files and the file sizes in a table.</li><li><em>RSS enclosure</em> only displays the first file, and only in a RSS feed, formatted according to the RSS 2.0 syntax for enclosures.</li></ul> A file can still be linked to directly by its URI even if it is not displayed.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
  }

  /**
   * Implements hook_field_widget_info_alter().
   */
  #[Hook('field_widget_info_alter')]
  public function fieldWidgetInfoAlter(array &$info): void {
    // Allows using the 'uri' widget for the 'file_uri' field type, which uses it
    // as the default widget.
    // @see \Drupal\file\Plugin\Field\FieldType\FileUriItem
    $info['uri']['field_types'][] = 'file_uri';
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
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
   * Implements hook_file_download().
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
    // Find out if a temporary file is still used in the system.
    if ($file->isTemporary()) {
      $usage = \Drupal::service('file.usage')->listUsage($file);
      if (empty($usage) && $file->getOwnerId() != \Drupal::currentUser()->id()) {
        // Deny access to temporary files without usage that are not owned by the
        // same user. This prevents the security issue that a private file that
        // was protected by field permissions becomes available after its usage
        // was removed and before it is actually deleted from the file system.
        // Modules that depend on this behavior should make the file permanent
        // instead.
        return -1;
      }
    }
    // Find out which (if any) fields of this type contain the file.
    $references = file_get_file_references($file, NULL, EntityStorageInterface::FIELD_LOAD_CURRENT, NULL);
    // Stop processing if there are no references in order to avoid returning
    // headers for files controlled by other modules. Make an exception for
    // temporary files where the host entity has not yet been saved (for example,
    // an image preview on a node/add form) in which case, allow download by the
    // file's owner.
    if (empty($references) && ($file->isPermanent() || $file->getOwnerId() != \Drupal::currentUser()->id())) {
      return;
    }
    if (!$file->access('download')) {
      return -1;
    }
    // Access is granted.
    $headers = file_get_content_headers($file);
    return $headers;
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $age = \Drupal::config('system.file')->get('temporary_maximum_age');
    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    // Only delete temporary files if older than $age. Note that automatic cleanup
    // is disabled if $age set to 0.
    if ($age) {
      $fids = \Drupal::entityQuery('file')->accessCheck(FALSE)->condition('status', FileInterface::STATUS_PERMANENT, '<>')->condition('changed', \Drupal::time()->getRequestTime() - $age, '<')->range(0, 100)->execute();
      $files = $file_storage->loadMultiple($fids);
      foreach ($files as $file) {
        $references = \Drupal::service('file.usage')->listUsage($file);
        if (empty($references)) {
          if (!file_exists($file->getFileUri())) {
            if (!$stream_wrapper_manager->isValidUri($file->getFileUri())) {
              \Drupal::logger('file system')->warning('Temporary file "%path" that was deleted during garbage collection did not exist on the filesystem. This could be caused by a missing stream wrapper.', ['%path' => $file->getFileUri()]);
            }
            else {
              \Drupal::logger('file system')->warning('Temporary file "%path" that was deleted during garbage collection did not exist on the filesystem.', ['%path' => $file->getFileUri()]);
            }
          }
          // Delete the file entity. If the file does not exist, this will
          // generate a second notice in the watchdog.
          $file->delete();
        }
        else {
          \Drupal::logger('file system')->info('Did not delete temporary file "%path" during garbage collection because it is in use by the following modules: %modules.', [
            '%path' => $file->getFileUri(),
            '%modules' => implode(', ', array_keys($references)),
          ]);
        }
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for file entities.
   */
  #[Hook('file_predelete')]
  public function filePredelete(File $file) {
    // @todo Remove references to a file that is in-use.
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $token_service = \Drupal::token();
    $url_options = ['absolute' => TRUE];
    if (isset($options['langcode'])) {
      $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
      $langcode = $options['langcode'];
    }
    else {
      $langcode = NULL;
    }
    $replacements = [];
    if ($type == 'file' && !empty($data['file'])) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $data['file'];
      foreach ($tokens as $name => $original) {
        switch ($name) {
          // Basic keys and values.
          case 'fid':
            $replacements[$original] = $file->id();
            break;

          case 'uuid':
            $replacements[$original] = $file->uuid();
            break;

          // Essential file data
          case 'name':
            $replacements[$original] = $file->getFilename();
            break;

          case 'path':
            $replacements[$original] = $file->getFileUri();
            break;

          case 'mime':
            $replacements[$original] = $file->getMimeType();
            break;

          case 'size':
            $replacements[$original] = ByteSizeMarkup::create($file->getSize());
            break;

          case 'url':
            // Ideally, this would use return a relative URL, but because tokens
            // are also often used in emails, it's better to keep absolute file
            // URLs. The 'url.site' cache context is associated to ensure the
            // correct absolute URL is used in case of a multisite setup.
            $replacements[$original] = $file->createFileUrl(FALSE);
            $bubbleable_metadata->addCacheContexts(['url.site']);
            break;

          // These tokens are default variations on the chained tokens handled below.
          case 'created':
            $date_format = DateFormat::load('medium');
            $bubbleable_metadata->addCacheableDependency($date_format);
            $replacements[$original] = \Drupal::service('date.formatter')->format($file->getCreatedTime(), 'medium', '', NULL, $langcode);
            break;

          case 'changed':
            $date_format = DateFormat::load('medium');
            $bubbleable_metadata = $bubbleable_metadata->addCacheableDependency($date_format);
            $replacements[$original] = \Drupal::service('date.formatter')->format($file->getChangedTime(), 'medium', '', NULL, $langcode);
            break;

          case 'owner':
            $owner = $file->getOwner();
            $bubbleable_metadata->addCacheableDependency($owner);
            $name = $owner->label();
            $replacements[$original] = $name;
            break;
        }
      }
      if ($date_tokens = $token_service->findWithPrefix($tokens, 'created')) {
        $replacements += $token_service->generate('date', $date_tokens, ['date' => $file->getCreatedTime()], $options, $bubbleable_metadata);
      }
      if ($date_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
        $replacements += $token_service->generate('date', $date_tokens, ['date' => $file->getChangedTime()], $options, $bubbleable_metadata);
      }
      if (($owner_tokens = $token_service->findWithPrefix($tokens, 'owner')) && $file->getOwner()) {
        $replacements += $token_service->generate('user', $owner_tokens, ['user' => $file->getOwner()], $options, $bubbleable_metadata);
      }
    }
    return $replacements;
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo() {
    $types['file'] = [
      'name' => t("Files"),
      'description' => t("Tokens related to uploaded files."),
      'needs-data' => 'file',
    ];
    // File related tokens.
    $file['fid'] = [
      'name' => t("File ID"),
      'description' => t("The unique ID of the uploaded file."),
    ];
    $file['uuid'] = ['name' => t('UUID'), 'description' => t('The UUID of the uploaded file.')];
    $file['name'] = ['name' => t("File name"), 'description' => t("The name of the file on disk.")];
    $file['path'] = [
      'name' => t("Path"),
      'description' => t("The location of the file relative to Drupal root."),
    ];
    $file['mime'] = ['name' => t("MIME type"), 'description' => t("The MIME type of the file.")];
    $file['size'] = ['name' => t("File size"), 'description' => t("The size of the file.")];
    $file['url'] = ['name' => t("URL"), 'description' => t("The web-accessible URL for the file.")];
    $file['created'] = [
      'name' => t("Created"),
      'description' => t("The date the file created."),
      'type' => 'date',
    ];
    $file['changed'] = [
      'name' => t("Changed"),
      'description' => t("The date the file was most recently changed."),
      'type' => 'date',
    ];
    $file['owner'] = [
      'name' => t("Owner"),
      'description' => t("The user who originally uploaded the file."),
      'type' => 'user',
    ];
    return ['types' => $types, 'tokens' => ['file' => $file]];
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
  public function formSystemFileSystemSettingsAlter(array &$form, FormStateInterface $form_state) : void {
    $config = \Drupal::config('file.settings');
    $form['filename_sanitization'] = [
      '#type' => 'details',
      '#title' => t('Sanitize filenames'),
      '#description' => t('These settings only apply to new files as they are uploaded. Changes here do not affect existing file names.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['filename_sanitization']['replacement_character'] = [
      '#type' => 'select',
      '#title' => t('Replacement character'),
      '#default_value' => $config->get('filename_sanitization.replacement_character'),
      '#options' => [
        '-' => t('Dash (-)'),
        '_' => t('Underscore (_)'),
      ],
      '#description' => t('Used when replacing whitespace, replacing non-alphanumeric characters or transliterating unknown characters.'),
    ];
    $form['filename_sanitization']['transliterate'] = [
      '#type' => 'checkbox',
      '#title' => t('Transliterate'),
      '#default_value' => $config->get('filename_sanitization.transliterate'),
      '#description' => t('Transliteration replaces any characters that are not alphanumeric, underscores, periods or hyphens with the replacement character. It ensures filenames only contain ASCII characters. It is recommended to keep transliteration enabled.'),
    ];
    $form['filename_sanitization']['replace_whitespace'] = [
      '#type' => 'checkbox',
      '#title' => t('Replace whitespace with the replacement character'),
      '#default_value' => $config->get('filename_sanitization.replace_whitespace'),
    ];
    $form['filename_sanitization']['replace_non_alphanumeric'] = [
      '#type' => 'checkbox',
      '#title' => t('Replace non-alphanumeric characters with the replacement character'),
      '#default_value' => $config->get('filename_sanitization.replace_non_alphanumeric'),
      '#description' => t('Alphanumeric characters, dots <span aria-hidden="true">(.)</span>, underscores <span aria-hidden="true">(_)</span> and dashes <span aria-hidden="true">(-)</span> are preserved.'),
    ];
    $form['filename_sanitization']['deduplicate_separators'] = [
      '#type' => 'checkbox',
      '#title' => t('Replace sequences of dots, underscores and/or dashes with the replacement character'),
      '#default_value' => $config->get('filename_sanitization.deduplicate_separators'),
    ];
    $form['filename_sanitization']['lowercase'] = [
      '#type' => 'checkbox',
      '#title' => t('Convert to lowercase'),
      '#default_value' => $config->get('filename_sanitization.lowercase'),
    ];
    $form['#submit'][] = 'file_system_settings_submit';
  }

}
