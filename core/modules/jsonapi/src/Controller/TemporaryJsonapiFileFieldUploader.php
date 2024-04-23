<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\file\Upload\FileUploadLocationTrait;
use Drupal\file\Upload\InputStreamFileWriterInterface;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\file\Validation\FileValidatorSettingsTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpFoundation\File\Exception\NoFileException;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Reads data from an upload stream and creates a corresponding file entity.
 *
 * This is implemented at the field level for the following reasons:
 *   - Validation for uploaded files is tied to fields (allowed extensions, max
 *     size, etc..).
 *   - The actual files do not need to be stored in another temporary location,
 *     to be later moved when they are referenced from a file field.
 *   - Permission to upload a file can be determined by a user's field- and
 *     entity-level access.
 *
 * @internal This will be removed once https://www.drupal.org/project/drupal/issues/2940383 lands.
 */
class TemporaryJsonapiFileFieldUploader {

  use FileValidatorSettingsTrait;
  use FileUploadLocationTrait {
    getUploadLocation as getUploadDestination;
  }

  public function __construct(protected LoggerInterface $logger, protected FileSystemInterface $fileSystem, protected MimeTypeGuesserInterface $mimeTypeGuesser, protected Token $token, protected LockBackendInterface $lock, protected ConfigFactoryInterface $configFactory, protected EventDispatcherInterface $eventDispatcher, protected FileValidatorInterface $fileValidator, protected InputStreamFileWriterInterface $inputStreamFileWriter) {
  }

  /**
   * Creates and validates a file entity for a file field from a file stream.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition of the field for which the file is to be uploaded.
   * @param string $filename
   *   The name of the file.
   * @param \Drupal\Core\Session\AccountInterface $owner
   *   The owner of the file. Note, it is the responsibility of the caller to
   *   enforce access.
   *
   * @return \Drupal\file\FileInterface|\Drupal\Core\Entity\EntityConstraintViolationListInterface
   *   The newly uploaded file entity, or a list of validation constraint
   *   violations
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Thrown when temporary files cannot be written, a lock cannot be acquired,
   *   or when temporary files cannot be moved to their new location.
   */
  public function handleFileUploadForField(FieldDefinitionInterface $field_definition, $filename, AccountInterface $owner) {
    assert(is_a($field_definition->getClass(), FileFieldItemList::class, TRUE));
    $settings = $field_definition->getSettings();
    $destination = $this->getUploadDestination($field_definition);

    // Check the destination file path is writable.
    if (!$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
      throw new HttpException(500, 'Destination file path is not writable');
    }

    $validators = $this->getFileUploadValidators($field_definition->getSettings());

    $prepared_filename = $this->prepareFilename($filename, $validators);

    // Create the file.
    $file_uri = "{$destination}/{$prepared_filename}";
    if ($destination === $settings['uri_scheme'] . '://') {
      $file_uri = "{$destination}{$prepared_filename}";
    }

    $temp_file_path = $this->streamUploadData();

    $file_uri = $this->fileSystem->getDestinationFilename($file_uri, FileExists::Rename);

    // Lock based on the prepared file URI.
    $lock_id = $this->generateLockIdFromFileUri($file_uri);

    if (!$this->lock->acquire($lock_id)) {
      throw new HttpException(503, sprintf('File "%s" is already locked for writing.', $file_uri), NULL, ['Retry-After' => 1]);
    }

    // Begin building file entity.
    $file = File::create([]);
    $file->setOwnerId($owner->id());
    $file->setFilename($prepared_filename);
    $file->setMimeType($this->mimeTypeGuesser->guessMimeType($prepared_filename));
    $file->setFileUri($temp_file_path);
    // Set the size. This is done in File::preSave() but we validate the file
    // before it is saved.
    $file->setSize(@filesize($temp_file_path));

    // Validate the file against field-level validators first while the file is
    // still a temporary file. Validation is split up in 2 steps to be the same
    // as in \Drupal\file\Upload\FileUploadHandler::handleFileUpload().
    // For backwards compatibility this part is copied from ::validate() to
    // leave that method behavior unchanged.
    // @todo Improve this with a file uploader service in
    //   https://www.drupal.org/project/drupal/issues/2940383
    $violations = $this->fileValidator->validate($file, $validators);
    if (count($violations) > 0) {
      return $violations;
    }

    $file->setFileUri($file_uri);

    // Update the filename with any changes as a result of security or renaming
    // due to an existing file.
    // @todo Remove this duplication by replacing with FileUploadHandler. See
    // https://www.drupal.org/project/drupal/issues/3401734
    $file->setFilename($this->fileSystem->basename($file->getFileUri()));

    // Move the file to the correct location after validation. Use
    // FileExists::Error as the file location has already been
    // determined above in FileSystem::getDestinationFilename().
    try {
      $this->fileSystem->move($temp_file_path, $file_uri, FileExists::Error);
    }
    catch (FileException $e) {
      throw new HttpException(500, 'Temporary file could not be moved to file location');
    }

    // Second step of the validation on the file object itself now.
    $violations = $file->validate();

    // Remove violations of inaccessible fields as they cannot stem from our
    // changes.
    $violations->filterByFieldAccess();
    if ($violations->count() > 0) {
      return $violations;
    }

    $file->save();

    $this->lock->release($lock_id);

    return $file;
  }

  /**
   * Checks if the current user has access to upload the file.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which file upload access should be checked.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition for which to get validators.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   (optional) The entity to which the file is to be uploaded, if it exists.
   *   If the entity does not exist and it is not given, create access to the
   *   entity the file is attached to will be checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The file upload access result.
   */
  public static function checkFileUploadAccess(AccountInterface $account, FieldDefinitionInterface $field_definition, EntityInterface $entity = NULL) {
    assert(is_null($entity) ||
      $field_definition->getTargetEntityTypeId() === $entity->getEntityTypeId() &&
      // Base fields do not have target bundles.
      (is_null($field_definition->getTargetBundle()) || $field_definition->getTargetBundle() === $entity->bundle())
    );
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_access_control_handler = $entity_type_manager->getAccessControlHandler($field_definition->getTargetEntityTypeId());
    $bundle = $entity_type_manager->getDefinition($field_definition->getTargetEntityTypeId())->hasKey('bundle') ? $field_definition->getTargetBundle() : NULL;
    $entity_access_result = $entity
      ? $entity_access_control_handler->access($entity, 'update', $account, TRUE)
      : $entity_access_control_handler->createAccess($bundle, $account, [], TRUE);
    $field_access_result = $entity_access_control_handler->fieldAccess('edit', $field_definition, NULL, NULL, TRUE);
    return $entity_access_result->andIf($field_access_result);
  }

  /**
   * Streams file upload data to temporary file and moves to file destination.
   *
   * @return string
   *   The temp file path.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Thrown when input data cannot be read, the temporary file cannot be
   *   opened, or the temporary file cannot be written.
   */
  protected function streamUploadData() {
    // Catch and throw the exceptions that JSON API module expects.
    try {
      $temp_file_path = $this->inputStreamFileWriter->writeStreamToFile();
    }
    catch (UploadException $e) {
      $this->logger->error('Input data could not be read');
      throw new HttpException(500, 'Input file data could not be read', $e);
    }
    catch (CannotWriteFileException $e) {
      $this->logger->error('Temporary file data for could not be written');
      throw new HttpException(500, 'Temporary file data could not be written', $e);
    }
    catch (NoFileException $e) {
      $this->logger->error('Temporary file could not be opened for file upload');
      throw new HttpException(500, 'Temporary file could not be opened', $e);
    }
    return $temp_file_path;
  }

  /**
   * Validates the file.
   *
   * @todo this method is unused in this class because file validation needs to
   *   be split up in 2 steps in ::handleFileUploadForField(). Add a deprecation
   *   notice as soon as a central core file upload service can be used in this
   *   class. See https://www.drupal.org/project/drupal/issues/2940383
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to validate.
   * @param array $validators
   *   An array of upload validators to pass to FileValidator.
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationListInterface
   *   The list of constraint violations, if any.
   */
  protected function validate(FileInterface $file, array $validators) {
    $violations = $file->validate();

    // Remove violations of inaccessible fields as they cannot stem from our
    // changes.
    $violations->filterByFieldAccess();

    // Validate the file based on the field definition configuration.
    $violations->addAll($this->fileValidator->validate($file, $validators));

    return $violations;
  }

  /**
   * Prepares the filename to strip out any malicious extensions.
   *
   * @param string $filename
   *   The file name.
   * @param array $validators
   *   The array of upload validators.
   *
   * @return string
   *   The prepared/munged filename.
   */
  protected function prepareFilename($filename, array &$validators) {
    // The actual extension validation occurs in
    // \Drupal\jsonapi\Controller\TemporaryJsonapiFileFieldUploader::validate().
    $extensions = $validators['FileExtension']['extensions'] ?? '';
    $event = new FileUploadSanitizeNameEvent($filename, $extensions);
    $this->eventDispatcher->dispatch($event);
    return $event->getFilename();
  }

  /**
   * Generates a lock ID based on the file URI.
   *
   * @param string $file_uri
   *   The file URI.
   *
   * @return string
   *   The generated lock ID.
   */
  protected static function generateLockIdFromFileUri($file_uri) {
    return 'file:jsonapi:' . Crypt::hashBase64($file_uri);
  }

}
