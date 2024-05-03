<?php

namespace Drupal\file\Plugin\rest\resource;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\MimeType\MimeTypeGuesser;
use Drupal\Core\Lock\LockAcquiringException;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use Drupal\file\Upload\ContentDispositionFilenameParser;
use Drupal\file\Upload\FileUploadHandler;
use Drupal\file\Upload\FileUploadLocationTrait;
use Drupal\file\Upload\InputStreamFileWriterInterface;
use Drupal\file\Upload\InputStreamUploadedFile;
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

  use DeprecatedServicePropertyTrait;
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
   * {@inheritdoc}
   */
  protected array $deprecatedProperties = [
    'currentUser' => 'current_user',
    'mimeTypeGuesser' => 'mime_type.guesser',
    'token' => 'token',
    'lock' => 'lock',
    'eventDispatcher' => 'event_dispatcher',
  ];

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    $serializer_formats,
    LoggerInterface $logger,
    protected FileSystemInterface $fileSystem,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected FileValidatorInterface | AccountInterface $fileValidator,
    protected InputStreamFileWriterInterface | MimeTypeGuesser $inputStreamFileWriter,
    protected FileUploadHandler | Token $fileUploadHandler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    if (!$fileValidator instanceof FileValidatorInterface) {
      @trigger_error('Passing a \Drupal\Core\Session\AccountInterface to ' . __METHOD__ . '() as argument 9 is deprecated in drupal:10.3.0 and will be removed before drupal:11.0.0. Pass a \Drupal\file\Validation\FileValidatorInterface instead. See https://www.drupal.org/node/3402032', E_USER_DEPRECATED);
      $this->fileValidator = \Drupal::service('file.validator');
    }
    if (!$inputStreamFileWriter instanceof InputStreamFileWriterInterface) {
      @trigger_error('Passing a \Drupal\Core\File\MimeType\MimeTypeGuesser to ' . __METHOD__ . '() as argument 10 is deprecated in drupal:10.3.0 and will be removed before drupal:11.0.0. Pass an \Drupal\file\Upload\InputStreamFileWriterInterface instead. See https://www.drupal.org/node/3402032', E_USER_DEPRECATED);
      $this->inputStreamFileWriter = \Drupal::service('file.input_stream_file_writer');
    }
    if (!$fileUploadHandler instanceof FileUploadHandler) {
      @trigger_error('Passing a \Drupal\Core\Utility\Token to ' . __METHOD__ . '() as argument 11 is deprecated in drupal:10.3.0 and will be removed before drupal:11.0.0. Pass an \Drupal\file\Upload\FileUploadHandler instead. See https://www.drupal.org/node/3402032', E_USER_DEPRECATED);
      $this->fileUploadHandler = \Drupal::service('file.upload_handler');
    }
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
      $container->get('file.validator'),
      $container->get('file.input_stream_file_writer'),
      $container->get('file.upload_handler'),
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
    $field_definition = $this->validateAndLoadFieldDefinition($entity_type_id, $bundle, $field_name);
    $destination = $this->getUploadDestination($field_definition);

    // Check the destination file path is writable.
    if (!$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
      throw new HttpException(500, 'Destination file path is not writable');
    }

    $settings = $field_definition->getSettings();
    $validators = $this->getFileUploadValidators($settings);
    if (!array_key_exists('FileExtension', $validators) && $settings['file_extensions'] === '') {
      // An empty string means 'all file extensions' but the FileUploadHandler
      // needs the FileExtension entry to be present and empty in order for this
      // to be respected. An empty array means 'all file extensions'.
      // @see \Drupal\file\Upload\FileUploadHandler::handleExtensionValidation
      $validators['FileExtension'] = [];
    }

    try {
      $filename = ContentDispositionFilenameParser::parseFilename($request);
      $tempPath = $this->inputStreamFileWriter->writeStreamToFile();
      $uploadedFile = new InputStreamUploadedFile($filename, $filename, $tempPath, @filesize($tempPath));

      $result = $this->fileUploadHandler->handleFileUpload($uploadedFile, $validators, $destination, FileExists::Rename, FALSE);
    }
    catch (LockAcquiringException $e) {
      throw new HttpException(503, $e->getMessage(), NULL, ['Retry-After' => 1]);
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
    catch (FileExistsException $e) {
      throw new HttpException(statusCode: 500, message: $e->getMessage(), previous: $e);
    }
    catch (FileException $e) {
      throw new HttpException(500, 'Temporary file could not be moved to file location');
    }

    if ($result->hasViolations()) {
      $message = "Unprocessable Entity: file validation failed.\n";
      $errors = [];
      foreach ($result->getViolations() as $violation) {
        $errors[] = PlainTextOutput::renderFromHtml($violation->getMessage());
      }
      $message .= implode("\n", $errors);

      throw new UnprocessableEntityHttpException($message);
    }
    // 201 Created responses return the newly created entity in the response
    // body. These responses are not cacheable, so we add no cacheability
    // metadata here.
    return new ModifiedResourceResponse($result->getFile(), 201);
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
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3402032
   */
  protected function streamUploadData(): string {
    @\trigger_error('Calling ' . __METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3402032', E_USER_DEPRECATED);
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
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3402032
   * @see https://www.drupal.org/node/3402032
   */
  protected function prepareFilename($filename, array &$validators) {
    @\trigger_error('Calling ' . __METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3402032', E_USER_DEPRECATED);
    $extensions = $validators['FileExtension']['extensions'] ?? '';
    $event = new FileUploadSanitizeNameEvent($filename, $extensions);
    // @phpstan-ignore-next-line
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
    // @phpstan-ignore-next-line
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
