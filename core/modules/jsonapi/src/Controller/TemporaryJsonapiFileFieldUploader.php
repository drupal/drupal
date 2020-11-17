<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\Validation\DrupalTranslator;
use Drupal\file\FileInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Symfony\Component\Validator\ConstraintViolation;

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

  /**
   * The regex used to extract the filename from the content disposition header.
   *
   * @var string
   */
  const REQUEST_HEADER_FILENAME_REGEX = '@\bfilename(?<star>\*?)=\"(?<filename>.+)\"@';

  /**
   * The amount of bytes to read in each iteration when streaming file data.
   *
   * @var int
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
   */
  public function __construct(LoggerInterface $logger, FileSystemInterface $file_system, $mime_type_guesser, Token $token, LockBackendInterface $lock, ConfigFactoryInterface $config_factory) {
    $this->logger = $logger;
    $this->fileSystem = $file_system;
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->token = $token;
    $this->lock = $lock;
    $this->systemFileConfig = $config_factory->get('system.file');
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
    $destination = $this->getUploadLocation($field_definition->getSettings());

    // Check the destination file path is writable.
    if (!$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
      throw new HttpException(500, 'Destination file path is not writable');
    }

    $validators = $this->getUploadValidators($field_definition);

    $prepared_filename = $this->prepareFilename($filename, $validators);

    // Create the file.
    $file_uri = "{$destination}/{$prepared_filename}";

    $temp_file_path = $this->streamUploadData();

    $file_uri = $this->fileSystem->getDestinationFilename($file_uri, FileSystemInterface::EXISTS_RENAME);

    // Lock based on the prepared file URI.
    $lock_id = $this->generateLockIdFromFileUri($file_uri);

    if (!$this->lock->acquire($lock_id)) {
      throw new HttpException(503, sprintf('File "%s" is already locked for writing.'), NULL, ['Retry-After' => 1]);
    }

    // Begin building file entity.
    $file = File::create([]);
    $file->setOwnerId($owner->id());
    $file->setFilename($prepared_filename);
    if ($this->mimeTypeGuesser instanceof MimeTypeGuesserInterface) {
      $file->setMimeType($this->mimeTypeGuesser->guessMimeType($prepared_filename));
    }
    else {
      @trigger_error('\Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Implement \Symfony\Component\Mime\MimeTypeGuesserInterface instead. See https://www.drupal.org/node/3133341', E_USER_DEPRECATED);
      $file->setMimeType($this->mimeTypeGuesser->guess($prepared_filename));
    }
    $file->setFileUri($file_uri);
    // Set the size. This is done in File::preSave() but we validate the file
    // before it is saved.
    $file->setSize(@filesize($temp_file_path));

    // Validate the file entity against entity-level validation and field-level
    // validators.
    $violations = $this->validate($file, $validators);
    if ($violations->count() > 0) {
      return $violations;
    }

    // Move the file to the correct location after validation. Use
    // FileSystemInterface::EXISTS_ERROR as the file location has already been
    // determined above in FileSystem::getDestinationFilename().
    try {
      $this->fileSystem->move($temp_file_path, $file_uri, FileSystemInterface::EXISTS_ERROR);
    }
    catch (FileException $e) {
      throw new HttpException(500, 'Temporary file could not be moved to file location');
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
   */
  public function validateAndParseContentDispositionHeader(Request $request) {
    // First, check the header exists.
    if (!$request->headers->has('content-disposition')) {
      throw new BadRequestHttpException('"Content-Disposition" header is required. A file name in the format "filename=FILENAME" must be provided.');
    }

    $content_disposition = $request->headers->get('content-disposition');

    // Parse the header value. This regex does not allow an empty filename.
    // i.e. 'filename=""'. This also matches on a word boundary so other keys
    // like 'not_a_filename' don't work.
    if (!preg_match(static::REQUEST_HEADER_FILENAME_REGEX, $content_disposition, $matches)) {
      throw new BadRequestHttpException('No filename found in "Content-Disposition" header. A file name in the format "filename=FILENAME" must be provided.');
    }

    // Check for the "filename*" format. This is currently unsupported.
    if (!empty($matches['star'])) {
      throw new BadRequestHttpException('The extended "filename*" format is currently not supported in the "Content-Disposition" header.');
    }

    // Don't validate the actual filename here, that will be done by the upload
    // validators in validate().
    // @see \Drupal\file\Plugin\rest\resource\FileUploadResource::validate()
    $filename = $matches['filename'];

    // Make sure only the filename component is returned. Path information is
    // stripped as per https://tools.ietf.org/html/rfc6266#section-4.3.
    return $this->fileSystem->basename($filename);
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
   *   file will be checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The file upload access result.
   */
  public static function checkFileUploadAccess(AccountInterface $account, FieldDefinitionInterface $field_definition, EntityInterface $entity = NULL) {
    assert(is_null($entity) || $field_definition->getTargetEntityTypeId() === $entity->getEntityTypeId() && $field_definition->getTargetBundle() === $entity->bundle());
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
    // 'rb' is needed so reading works correctly on Windows environments too.
    $file_data = fopen('php://input', 'rb');

    $temp_file_path = $this->fileSystem->tempnam('temporary://', 'file');
    if ($temp_file_path === FALSE) {
      $this->logger->error('Temporary file could not be created for file upload.');
      throw new HttpException(500, 'Temporary file could not be created');
    }
    $temp_file = fopen($temp_file_path, 'wb');

    if ($temp_file) {
      while (!feof($file_data)) {
        $read = fread($file_data, static::BYTES_TO_READ);

        if ($read === FALSE) {
          // Close the file streams.
          fclose($temp_file);
          fclose($file_data);
          $this->logger->error('Input data could not be read');
          throw new HttpException(500, 'Input file data could not be read.');
        }

        if (fwrite($temp_file, $read) === FALSE) {
          // Close the file streams.
          fclose($temp_file);
          fclose($file_data);
          $this->logger->error('Temporary file data for "%path" could not be written', ['%path' => $temp_file_path]);
          throw new HttpException(500, 'Temporary file data could not be written.');
        }
      }

      // Close the temp file stream.
      fclose($temp_file);
    }
    else {
      // Close the input file stream since we can't proceed with the upload.
      // Don't try to close $temp_file since it's FALSE at this point.
      fclose($file_data);
      $this->logger->error('Temporary file "%path" could not be opened for file upload.', ['%path' => $temp_file_path]);
      throw new HttpException(500, 'Temporary file could not be opened');
    }

    // Close the input stream.
    fclose($file_data);

    return $temp_file_path;
  }

  /**
   * Validates the file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to validate.
   * @param array $validators
   *   An array of upload validators to pass to file_validate().
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
    $errors = file_validate($file, $validators);
    if (!empty($errors)) {
      $translator = new DrupalTranslator();
      foreach ($errors as $error) {
        $violation = new ConstraintViolation($translator->trans($error),
          $error,
          [],
          EntityAdapter::createFromEntity($file),
          '',
          NULL
        );
        $violations->add($violation);
      }
    }

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
    //  Don't rename if 'allow_insecure_uploads' evaluates to TRUE.
    if (!$this->systemFileConfig->get('allow_insecure_uploads')) {
      if (!empty($validators['file_validate_extensions'][0])) {
        // If there is a file_validate_extensions validator and a list of
        // valid extensions, munge the filename to protect against possible
        // malicious extension hiding within an unknown file type. For example,
        // "filename.html.foo".
        $filename = file_munge_filename($filename, $validators['file_validate_extensions'][0]);
      }

      // Rename potentially executable files, to help prevent exploits (i.e.
      // will rename filename.php.foo and filename.php to filename._php._foo.txt
      // and filename._php.txt, respectively).
      if (preg_match(FILE_INSECURE_EXTENSION_REGEX, $filename)) {
        // If the file will be rejected anyway due to a disallowed extension, it
        // should not be renamed; rather, we'll let file_validate_extensions()
        // reject it below.
        $passes_validation = FALSE;
        if (!empty($validators['file_validate_extensions'][0])) {
          $file = File::create([]);
          $file->setFilename($filename);
          $passes_validation = empty(file_validate_extensions($file, $validators['file_validate_extensions'][0]));
        }
        if (empty($validators['file_validate_extensions'][0]) || $passes_validation) {
          if (substr($filename, -4) != '.txt') {
            // The destination filename will also later be used to create the URI.
            $filename .= '.txt';
          }
          $filename = file_munge_filename($filename, $validators['file_validate_extensions'][0] ?? '');

          // The .txt extension may not be in the allowed list of extensions. We
          // have to add it here or else the file upload will fail.
          if (!empty($validators['file_validate_extensions'][0])) {
            $validators['file_validate_extensions'][0] .= ' txt';
          }
        }
      }
    }

    return $filename;
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
   */
  protected function getUploadLocation(array $settings) {
    $destination = trim($settings['file_directory'], '/');

    // Replace tokens. As the tokens might contain HTML we convert it to plain
    // text.
    $destination = PlainTextOutput::renderFromHtml($this->token->replace($destination, [], [], new BubbleableMetadata()));
    return $settings['uri_scheme'] . '://' . $destination;
  }

  /**
   * Retrieves the upload validators for a field definition.
   *
   * This is copied from \Drupal\file\Plugin\Field\FieldType\FileItem as there
   * is no entity instance available here that a FileItem would exist for.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition for which to get validators.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  protected function getUploadValidators(FieldDefinitionInterface $field_definition) {
    $validators = [
      // Add in our check of the file name length.
      'file_validate_name_length' => [],
    ];
    $settings = $field_definition->getSettings();

    // Cap the upload size according to the PHP limit.
    $max_filesize = Bytes::toNumber(Environment::getUploadMaxSize());
    if (!empty($settings['max_filesize'])) {
      $max_filesize = min($max_filesize, Bytes::toNumber($settings['max_filesize']));
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = [$max_filesize];

    // Add the extension check if necessary.
    if (!empty($settings['file_extensions'])) {
      $validators['file_validate_extensions'] = [$settings['file_extensions']];
    }

    return $validators;
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
