<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\file\Upload\ContentDispositionFilenameParser;
use Drupal\file\Upload\FileUploadLocationTrait;
use Drupal\file\Upload\InputStreamFileWriterInterface;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\file\Validation\FileValidatorSettingsTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpFoundation\File\Exception\NoFileException;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3445266
 */
class TemporaryJsonapiFileFieldUploader {

  use FileValidatorSettingsTrait;
  use FileUploadLocationTrait {
    getUploadLocation as getUploadDestination;
  }

  /**
   * The regex used to extract the filename from the content disposition header.
   *
   * @var string
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\file\Upload\ContentDispositionFilenameParser::REQUEST_HEADER_FILENAME_REGEX
   *   instead.
   *
   * @see https://www.drupal.org/node/3380380
   */
  const REQUEST_HEADER_FILENAME_REGEX = '@\bfilename(?<star>\*?)=\"(?<filename>.+)\"@';

  /**
   * The amount of bytes to read in each iteration when streaming file data.
   *
   * @var int
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   * \Drupal\file\Upload\InputStreamFileWriterInterface::DEFAULT_BYTES_TO_READ
   * instead.
   *
   * @see https://www.drupal.org/node/3380607
   */
  const BYTES_TO_READ = 8192;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The MIME type guesser.
   *
   * @var \Symfony\Component\Mime\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * The token replacement instance.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * System file configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $systemFileConfig;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The file validator.
   *
   * @var \Drupal\file\Validation\FileValidatorInterface
   */
  protected FileValidatorInterface $fileValidator;

  /**
   * The input stream file writer.
   */
  protected InputStreamFileWriterInterface $inputStreamFileWriter;

  /**
   * Constructs a FileUploadResource instance.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Symfony\Component\Mime\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME type guesser.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement instance.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|null $event_dispatcher
   *   (optional) The event dispatcher.
   * @param \Drupal\file\Validation\FileValidatorInterface|null $file_validator
   *   The file validator.
   * @param \Drupal\file\Upload\InputStreamFileWriterInterface|null $input_stream_file_writer
   *   The stream file uploader.
   */
  public function __construct(LoggerInterface $logger, FileSystemInterface $file_system, $mime_type_guesser, Token $token, LockBackendInterface $lock, ConfigFactoryInterface $config_factory, ?EventDispatcherInterface $event_dispatcher = NULL, ?FileValidatorInterface $file_validator = NULL, ?InputStreamFileWriterInterface $input_stream_file_writer = NULL) {
    @\trigger_error(__CLASS__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3445266', E_USER_DEPRECATED);
    $this->logger = $logger;
    $this->fileSystem = $file_system;
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->token = $token;
    $this->lock = $lock;
    $this->systemFileConfig = $config_factory->get('system.file');
    if (!$event_dispatcher) {
      $event_dispatcher = \Drupal::service('event_dispatcher');
    }
    $this->eventDispatcher = $event_dispatcher;
    if (!$file_validator) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $file_validator argument is deprecated in drupal:10.2.0 and is required in drupal:11.0.0. See https://www.drupal.org/node/3363700', E_USER_DEPRECATED);
      $file_validator = \Drupal::service('file.validator');
    }
    $this->fileValidator = $file_validator;
    if (!$input_stream_file_writer) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $input_stream_file_writer argument is deprecated in drupal:10.3.0 and is required in drupal:11.0.0. See https://www.drupal.org/node/3380607', E_USER_DEPRECATED);
      $input_stream_file_writer = \Drupal::service('file.input_stream_file_writer');
    }
    $this->inputStreamFileWriter = $input_stream_file_writer;
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
   * Validates and extracts the filename from the Content-Disposition header.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return string
   *   The filename extracted from the header.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when the 'Content-Disposition' request header is invalid.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\file\Upload\ContentDispositionFilenameParser::parseFilename()
   *   instead.
   *
   * @see https://www.drupal.org/node/3380380
   */
  public function validateAndParseContentDispositionHeader(Request $request) {
    @trigger_error('Calling ' . __METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\file\Upload\ContentDispositionFilenameParser::parseFilename() instead. See https://www.drupal.org/node/3380380', E_USER_DEPRECATED);
    return ContentDispositionFilenameParser::parseFilename($request);
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
  public static function checkFileUploadAccess(AccountInterface $account, FieldDefinitionInterface $field_definition, ?EntityInterface $entity = NULL) {
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
   * Determines the URI for a file field.
   *
   * @param array $settings
   *   The array of field settings.
   *
   * @return string
   *   An un-sanitized file directory URI with tokens replaced. The result of
   *   the token replacement is then converted to plain text and returned.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\file\Upload\FileUploadLocationTrait::getUploadLocation() instead.
   *
   * @see https://www.drupal.org/node/3406099
   */
  protected function getUploadLocation(array $settings) {
    @\trigger_error(__METHOD__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\file\Upload\FileUploadLocationTrait::getUploadLocation() instead. See https://www.drupal.org/node/3406099', E_USER_DEPRECATED);
    $destination = trim($settings['file_directory'], '/');

    // Replace tokens. As the tokens might contain HTML we convert it to plain
    // text.
    $destination = PlainTextOutput::renderFromHtml($this->token->replace($destination, [], [], new BubbleableMetadata()));
    return $settings['uri_scheme'] . '://' . $destination;
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
