<?php

namespace Drupal\file\Plugin\rest\resource;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use Drupal\file\Upload\ContentDispositionFilenameParser;
use Drupal\file\Upload\FileUploadLocationTrait;
use Drupal\file\Upload\InputStreamFileWriterInterface;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\file\Validation\FileValidatorSettingsTrait;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\rest\RequestHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpFoundation\File\Exception\NoFileException;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * File upload resource.
 *
 * This is implemented as a field-level resource for the following reasons:
 *   - Validation for uploaded files is tied to fields (allowed extensions, max
 *     size, etc..).
 *   - The actual files do not need to be stored in another temporary location,
 *     to be later moved when they are referenced from a file field.
 *   - Permission to upload a file can be determined by a users field level
 *     create access to the file field.
 */
#[RestResource(
  id: "file:upload",
  label: new TranslatableMarkup("File Upload"),
  serialization_class: File::class,
  uri_paths: [
    "create" => "/file/upload/{entity_type_id}/{bundle}/{field_name}",
  ]
)]
class FileUploadResource extends ResourceBase {

  use FileValidatorSettingsTrait;
  use EntityResourceValidationTrait {
    validate as resourceValidate;
  }
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
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The currently authenticated user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $systemFileConfig;

  /**
   * The event dispatcher to dispatch the filename sanitize event.
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
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The currently authenticated user.
   * @param \Symfony\Component\Mime\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME type guesser.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement instance.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   * @param \Drupal\Core\Config\Config $system_file_config
   *   The system file configuration.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\file\Validation\FileValidatorInterface|null $file_validator
   *   The file validator service.
   * @param \Drupal\file\Upload\InputStreamFileWriterInterface|null $input_stream_file_writer
   *   The input stream file writer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $serializer_formats, LoggerInterface $logger, FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, AccountInterface $current_user, $mime_type_guesser, Token $token, LockBackendInterface $lock, Config $system_file_config, EventDispatcherInterface $event_dispatcher, FileValidatorInterface $file_validator = NULL, InputStreamFileWriterInterface $input_stream_file_writer = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->currentUser = $current_user;
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->token = $token;
    $this->lock = $lock;
    $this->systemFileConfig = $system_file_config;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('file_system'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('current_user'),
      $container->get('file.mime_type.guesser'),
      $container->get('token'),
      $container->get('lock'),
      $container->get('config.factory')->get('system.file'),
      $container->get('event_dispatcher'),
      $container->get('file.validator'),
      $container->get('file.input_stream_file_writer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    // Access to this resource depends on field-level access so no explicit
    // permissions are required.
    // @see \Drupal\file\Plugin\rest\resource\FileUploadResource::validateAndLoadFieldDefinition()
    // @see \Drupal\rest\Plugin\rest\resource\EntityResource::permissions()
    return [];
  }

  /**
   * Creates a file from an endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The entity bundle. This will be the same as $entity_type_id for entity
   *   types that don't support bundles.
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   A 201 response, on success.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Thrown when temporary files cannot be written, a lock cannot be acquired,
   *   or when temporary files cannot be moved to their new location.
   */
  public function post(Request $request, $entity_type_id, $bundle, $field_name) {
    $filename = ContentDispositionFilenameParser::parseFilename($request);

    $field_definition = $this->validateAndLoadFieldDefinition($entity_type_id, $bundle, $field_name);

    $destination = $this->getUploadDestination($field_definition);

    // Check the destination file path is writable.
    if (!$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
      throw new HttpException(500, 'Destination file path is not writable');
    }

    $validators = $this->getFileUploadValidators($field_definition->getSettings());

    $prepared_filename = $this->prepareFilename($filename, $validators);

    // Create the file.
    $file_uri = "{$destination}/{$prepared_filename}";

    $temp_file_path = $this->streamUploadData();

    $file_uri = $this->fileSystem->getDestinationFilename($file_uri, FileExists::Rename);

    // Lock based on the prepared file URI.
    $lock_id = $this->generateLockIdFromFileUri($file_uri);

    if (!$this->lock->acquire($lock_id)) {
      throw new HttpException(503, sprintf('File "%s" is already locked for writing', $file_uri), NULL, ['Retry-After' => 1]);
    }

    // Begin building file entity.
    $file = File::create([]);
    $file->setOwnerId($this->currentUser->id());
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
      $message = "Unprocessable Entity: file validation failed.\n";
      $errors = [];
      foreach ($violations as $violation) {
        $errors[] = PlainTextOutput::renderFromHtml($violation->getMessage());
      }
      $message .= implode("\n", $errors);

      throw new UnprocessableEntityHttpException($message);
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
    $this->resourceValidate($file);

    $file->save();

    $this->lock->release($lock_id);

    // 201 Created responses return the newly created entity in the response
    // body. These responses are not cacheable, so we add no cacheability
    // metadata here.
    return new ModifiedResourceResponse($file, 201);
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
  protected function streamUploadData(): string {
    // Catch and throw the exceptions that REST expects.
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
  protected function validateAndParseContentDispositionHeader(Request $request) {
    @trigger_error('Calling ' . __METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\file\Upload\ContentDispositionFilenameParser::parseFilename() instead. See https://www.drupal.org/node/3380380', E_USER_DEPRECATED);
    return ContentDispositionFilenameParser::parseFilename($request);
  }

  /**
   * Validates and loads a field definition instance.
   *
   * @param string $entity_type_id
   *   The entity type ID the field is attached to.
   * @param string $bundle
   *   The bundle the field is attached to.
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when the field does not exist.
   * @throws \Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException
   *   Thrown when the target type of the field is not a file, or the current
   *   user does not have 'edit' access for the field.
   */
  protected function validateAndLoadFieldDefinition($entity_type_id, $bundle, $field_name) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    if (!isset($field_definitions[$field_name])) {
      throw new NotFoundHttpException(sprintf('Field "%s" does not exist', $field_name));
    }

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = $field_definitions[$field_name];
    if ($field_definition->getSetting('target_type') !== 'file') {
      throw new AccessDeniedHttpException(sprintf('"%s" is not a file field', $field_name));
    }

    $entity_access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity_type_id);
    $bundle = $this->entityTypeManager->getDefinition($entity_type_id)->hasKey('bundle') ? $bundle : NULL;
    $access_result = $entity_access_control_handler->createAccess($bundle, NULL, [], TRUE)
      ->andIf($entity_access_control_handler->fieldAccess('edit', $field_definition, NULL, NULL, TRUE));
    if (!$access_result->isAllowed()) {
      throw new AccessDeniedHttpException($access_result->getReason());
    }

    return $field_definition;
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
    // \Drupal\file\Plugin\rest\resource\FileUploadResource::validate().
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
   * \Drupal\file\Upload\FileUploadLocationTrait::getUploadLocation() instead.
   *
   * @see https://www.drupal.org/node/3406099
   */
  protected function getUploadLocation(array $settings) {
    @\trigger_error(__METHOD__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\file\Upload\FileUploadLocationTrait::getUploadLocation() instead. See https://www.drupal.org/node/3406099', E_USER_DEPRECATED);
    $destination = trim($settings['file_directory'], '/');

    // Replace tokens. As the tokens might contain HTML we convert it to plain
    // text.
    $destination = PlainTextOutput::renderFromHtml($this->token->replace($destination, []));
    return $settings['uri_scheme'] . '://' . $destination;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    return new Route($canonical_path, [
      '_controller' => RequestHandler::class . '::handleRaw',
    ],
      $this->getBaseRouteRequirements($method),
      [],
      '',
      [],
      // The HTTP method is a requirement for this route.
      [$method]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRouteRequirements($method) {
    $requirements = parent::getBaseRouteRequirements($method);

    // Add the content type format access check. This will enforce that all
    // incoming requests can only use the 'application/octet-stream'
    // Content-Type header.
    $requirements['_content_type_format'] = 'bin';

    return $requirements;
  }

  /**
   * Generates a lock ID based on the file URI.
   *
   * @param $file_uri
   *   The file URI.
   *
   * @return string
   *   The generated lock ID.
   */
  protected static function generateLockIdFromFileUri($file_uri) {
    return 'file:rest:' . Crypt::hashBase64($file_uri);
  }

}
