<?php

declare(strict_types=1);

namespace Drupal\file\Upload;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\file\FileInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\UploadedFilesExtractor;
use Drupal\Core\Lock\LockAcquiringException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Helper class for multiple form file uploads.
 *
 * This class provides API-level methods for uploading multiple files from a
 * form.
 *
 * For Form API elements or user-facing Messenger messages, use
 * \Drupal\file\Upload\ManagedFileElementHelper instead.
 *
 * @see \Drupal\file\Upload\ManagedFileElementHelper
 */
class FormFileUploader {
  use StringTranslationTrait;

  public function __construct(
    protected readonly UploadedFilesExtractor $uploadedFilesExtractor,
    protected readonly FileUploadHandlerInterface $fileUploadedHandler,
    #[Autowire(service: 'cache.memory')]
    protected readonly MemoryCacheInterface $memoryCache,
    #[AutowireServiceClosure('logger.channel.file')]
    protected readonly \Closure $logger,
  ) {}

  /**
   * Saves file uploads to a new location.
   *
   * The files will be added to the {file_managed} table as temporary files.
   * Temporary files are periodically cleaned. Use the 'file.usage' service to
   * register the usage of the file which will automatically mark it as
   * permanent.
   *
   * Note that this function does not support correct form error handling. The
   * file upload widgets in core do support this. It is advised to use these in
   * any custom form, instead of calling this function.
   *
   * @param string $formFieldName
   *   A string that is the associative array key of the upload form element in
   *   the form array.
   * @param array $validators
   *   (optional) An associative array of Validation Constraint plugins used to
   *   validate the file.
   *   If the array is empty, 'FileExtension' will be used by default with a
   *   safe list of extensions, as follows: "jpg jpeg gif png txt doc xls pdf
   *   ppt pps odt ods odp". To allow all extensions, you must explicitly set
   *   this array to ['FileExtension' => []]. (Beware: this is not safe and
   *   should only be allowed for trusted users, if at all.)
   * @param string $destination
   *   (optional) A string containing the URI that the file should be copied
   *   to. This must be a stream wrapper URI. temporary:// is the default.
   * @param null|int $delta
   *   (optional) The delta of the file to return the file entity.
   *   Defaults to NULL.
   * @param \Drupal\Core\File\FileExists $fileExists
   *   (optional) The replace behavior when the destination file already
   *   exists.
   *
   * @return array|\Drupal\file\FileInterface|null|false
   *   An array of file entities or a single file entity if $delta != NULL.
   *   Each array element contains the file entity if the upload succeeded or
   *   FALSE if there was an error. Function returns NULL if no file was
   *   uploaded.
   *
   * @see ManagedFileElementHelper::saveFileUploads()
   */
  public function saveFormUploadedFiles(
    string $formFieldName,
    array $validators = [],
    string $destination = 'temporary://',
    ?int $delta = NULL,
    FileExists $fileExists = FileExists::Rename,
  ): array|FileInterface|null|false {
    $cid = 'file:uploads:' . $formFieldName;

    $uploadedFiles = $this->uploadedFilesExtractor->extractUploadedFiles($formFieldName);
    if (empty($uploadedFiles)) {
      return NULL;
    }

    // Return cached objects without processing since the file will have
    // already been processed and the paths in $_FILES will be invalid.
    if ($cached = $this->memoryCache->get($cid)) {
      $files = $cached->data;
      return isset($delta) ? $files[$delta] : $files;
    }

    $files = [];
    foreach ($uploadedFiles as $i => $uploadedFile) {
      try {
        $formUploadedFile = new FormUploadedFile($uploadedFile);
        $result = $this->fileUploadedHandler->handleFileUpload($formUploadedFile, $validators, $destination, $fileExists);
        if ($result->hasViolations()) {
          $errors = [];
          foreach ($result->getViolations() as $violation) {
            $errors[] = $violation->getMessage();
          }
          $message = [
            'error' => [
              '#markup' => $this->t('The specified file %name could not be uploaded.', ['%name' => $uploadedFile->getClientOriginalName()]),
            ],
            'item_list' => [
              '#theme' => 'item_list',
              '#items' => $errors,
            ],
          ];
          // @todo Add support for render arrays in
          // \Drupal\Core\Messenger\MessengerInterface::addMessage()?
          // @see https://www.drupal.org/node/2505497.
          \Drupal::messenger()->addError(\Drupal::service('renderer')->renderInIsolation($message));
          $files[$i] = FALSE;
          continue;
        }
        $file = $result->getFile();
        // Log security renames to help detect potential malicious uploads.
        if ($result->isSecurityRename()) {
          ($this->logger)()->notice('For security reasons, the uploaded file %original_filename has been renamed to %filename.', [
            '%original_filename' => $result->getOriginalFilename(),
            '%filename' => $file->getFilename(),
          ]);
        }
        $files[$i] = $file;
      }
      catch (FileExistsException) {
        \Drupal::messenger()->addError($this->t('Destination file "%file" exists', ['%file' => $destination . $uploadedFile->getFilename()]));
        $files[$i] = FALSE;
      }
      catch (InvalidStreamWrapperException) {
        \Drupal::messenger()->addError($this->t('The file could not be uploaded because the destination "%destination" is invalid.', ['%destination' => $destination]));
        $files[$i] = FALSE;
      }
      catch (FileWriteException) {
        \Drupal::messenger()->addError($this->t('File upload error. Could not move uploaded file.'));
        ($this->logger)()->notice('Upload error. Could not move uploaded file %file to destination %destination.', [
          '%file' => $uploadedFile->getClientOriginalName(),
          '%destination' => $destination . '/' . $uploadedFile->getClientOriginalName(),
        ]);
        $files[$i] = FALSE;
      }
      catch (FileException) {
        \Drupal::messenger()->addError($this->t('The file %filename could not be uploaded because the name is invalid.', ['%filename' => $uploadedFile->getClientOriginalName()]));
        $files[$i] = FALSE;
      }
      catch (LockAcquiringException) {
        \Drupal::messenger()->addError($this->t('File already locked for writing.'));
        $files[$i] = FALSE;
      }
    }

    // Add files to the cache.
    $this->memoryCache->set($cid, $files, Cache::PERMANENT);

    return isset($delta) ? $files[$delta] : $files;
  }

}
