<?php

declare(strict_types=1);

namespace Drupal\file\Upload;

use Drupal\file\FileInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\UploadedFilesExtractor;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Helper class for ManagedFile element form file uploads.
 *
 * This class handles the Form API and user-facing messages.
 *
 * For API-level form file uploading, use
 * \Drupal\file\Upload\FormFileUploader instead.
 *
 * @see \Drupal\file\Upload\FormFileUploader
 * @see \Drupal\file\Element\ManagedFile::valueCallback()
 */
class ManagedFileElementHelper {
  use StringTranslationTrait;

  public function __construct(
    protected readonly MessengerInterface $messenger,
    protected readonly RendererInterface $renderer,
    protected readonly FormFileUploader $formFileUploader,
    protected readonly UploadedFilesExtractor $uploadedFilesExtractor,
    protected readonly FileSystemInterface $fileSystem,
    #[AutowireServiceClosure('logger.channel.file')]
    protected readonly \Closure $logger,
  ) {}

  /**
   * Saves form file uploads.
   *
   * The files will be added to the {file_managed} table as temporary files.
   * Temporary files are periodically cleaned. Use the 'file.usage' service to
   * register the usage of the file which will automatically mark it as
   * permanent.
   *
   * @param array $element
   *   The FAPI element whose values are being saved.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   * @param null|int $delta
   *   (optional) The delta of the file to return the file entity.
   *   Defaults to NULL.
   * @param \Drupal\Core\File\FileExists $fileExists
   *   (optional) The replace behavior when the destination file already exists.
   *
   * @return array|\Drupal\file\FileInterface|null|false
   *   An array of file entities or a single file entity if $delta != NULL.
   *   Each array element contains the file entity if the upload succeeded or
   *   FALSE if there was an error. Function returns NULL if no file was
   *   uploaded.
   *
   * @see https://www.drupal.org/project/drupal/issues/3069020
   * @see https://www.drupal.org/project/drupal/issues/2482783
   */
  public function saveFileUploads(
    array $element,
    FormStateInterface $formState,
    ?int $delta = NULL,
    FileExists $fileExists = FileExists::Rename,
  ): array|FileInterface|null|false {
    // Get all errors set before calling this method. This will also clear them
    // from the messenger service.
    $errorsBefore = $this->messenger->deleteByType(MessengerInterface::TYPE_ERROR);

    $uploadLocation = $element['#upload_location'] ?? 'temporary://';
    $uploadName = implode('_', $element['#parents']);
    $uploadValidators = $element['#upload_validators'] ?? [];

    if ($uploadLocation === FALSE) {
      $uploadLocation = 'temporary://';
    }

    $result = $this->formFileUploader->saveFormUploadedFiles($uploadName, $uploadValidators, $uploadLocation, $delta, $fileExists);

    // Get new errors that are generated while trying to save the upload. This
    // will also clear them from the messenger service.
    $errorsNew = $this->messenger->deleteByType(MessengerInterface::TYPE_ERROR);
    if (!empty($errorsNew)) {

      if (count($errorsNew) > 1) {
        // Render multiple errors into a single message.
        // This is needed because only one error per element is supported.
        $render_array = [
          'error' => [
            '#markup' => $this->t('One or more files could not be uploaded.'),
          ],
          'item_list' => [
            '#theme' => 'item_list',
            '#items' => $errorsNew,
          ],
        ];
        $errorMessage = $this->renderer->renderInIsolation($render_array);
      }
      else {
        $errorMessage = reset($errorsNew);
      }

      $formState->setError($element, $errorMessage);
    }

    // Ensure that errors set prior to calling this method are still shown to
    // the user.
    if (!empty($errorsBefore)) {
      foreach ($errorsBefore as $error) {
        $this->messenger->addError($error);
      }
    }

    return $result;
  }

  /**
   * Saves any files that have been uploaded into a managed_file element.
   *
   * @param array $element
   *   The FAPI element whose values are being saved.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   *
   * @return array<int, FileInterface>|false
   *   An array of file entities for each file that was saved, keyed by its file
   *   ID. Each array element contains a file entity. Function returns FALSE if
   *   upload directory could not be created or no files were uploaded.
   */
  public function managedFileSaveUpload(array $element, FormStateInterface $formState): array|false {
    $uploadName = implode('_', $element['#parents']);
    $fileUpload = $this->uploadedFilesExtractor->extractUploadedFiles($uploadName);

    $destination = $element['#upload_location'] ?? NULL;
    if (isset($destination) && !$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
      ($this->logger)()->notice('The upload directory %directory for the file field %name could not be created or is not accessible. A newly uploaded file could not be saved in this directory as a consequence, and the upload was canceled.', [
        '%directory' => $destination,
        '%name' => $element['#field_name'],
      ]);
      $formState->setError($element, $this->t('The file could not be uploaded.'));
      return FALSE;
    }

    // Save attached files to the database.
    $filesUploaded = $element['#multiple'] && count(array_filter($fileUpload)) > 0;
    $filesUploaded |= !$element['#multiple'] && !empty($fileUpload);
    if ($filesUploaded) {
      if (!$files = $this->saveFileUploads($element, $formState)) {
        ($this->logger)()->notice('The file upload failed. %upload', ['%upload' => $uploadName]);
        return [];
      }

      // Value callback expects FIDs to be keys.
      $files = array_filter($files);
      $fids = array_map(function ($file) {
        return $file->id();
      }, $files);

      return empty($files) ? [] : array_combine($fids, $files);
    }

    return [];
  }

}
