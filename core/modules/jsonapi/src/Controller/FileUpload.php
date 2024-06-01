<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockAcquiringException;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\file\Upload\ContentDispositionFilenameParser;
use Drupal\file\Upload\FileUploadHandler;
use Drupal\file\Upload\FileUploadLocationTrait;
use Drupal\file\Upload\FileUploadResult;
use Drupal\file\Upload\InputStreamFileWriterInterface;
use Drupal\file\Upload\InputStreamUploadedFile;
use Drupal\file\Validation\FileValidatorSettingsTrait;
use Drupal\jsonapi\Entity\EntityValidationTrait;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceType;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpFoundation\File\Exception\NoFileException;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Handles file upload requests.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class FileUpload {

  use EntityValidationTrait;
  use FileUploadLocationTrait;
  use FileValidatorSettingsTrait;

  public function __construct(
    protected AccountInterface $currentUser,
    protected EntityFieldManagerInterface $fieldManager,
    protected FileUploadHandler $fileUploadHandler,
    protected HttpKernelInterface $httpKernel,
    protected InputStreamFileWriterInterface $inputStreamFileWriter,
    protected FileSystemInterface $fileSystem,
  ) {
  }

  /**
   * Handles JSON:API file upload requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type for the current request.
   * @param string $file_field_name
   *   The file field for which the file is to be uploaded.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which the file is to be uploaded.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
   *   Thrown when there are validation errors.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the upload's target resource could not be saved.
   * @throws \Exception
   *   Thrown if an exception occurs during a subrequest to fetch the newly
   *   created file entity.
   */
  public function handleFileUploadForExistingResource(Request $request, ResourceType $resource_type, string $file_field_name, FieldableEntityInterface $entity): Response {
    $result = $this->handleFileUploadForResource($request, $resource_type, $file_field_name, $entity);
    $file = $result->getFile();

    if ($resource_type->getFieldByInternalName($file_field_name)->hasOne()) {
      $entity->{$file_field_name} = $file;
    }
    else {
      $entity->get($file_field_name)->appendItem($file);
    }
    static::validate($entity, [$file_field_name]);
    $entity->save();

    $route_parameters = ['entity' => $entity->uuid()];
    $route_name = sprintf('jsonapi.%s.%s.related', $resource_type->getTypeName(), $resource_type->getPublicName($file_field_name));
    $related_url = Url::fromRoute($route_name, $route_parameters)->toString(TRUE);
    $request = Request::create($related_url->getGeneratedUrl(), 'GET', [], $request->cookies->all(), [], $request->server->all());
    return $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);
  }

  /**
   * Handles JSON:API file upload requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type for the current request.
   * @param string $file_field_name
   *   The file field for which the file is to be uploaded.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
   *   Thrown when there are validation errors.
   */
  public function handleFileUploadForNewResource(Request $request, ResourceType $resource_type, string $file_field_name): ResourceResponse {
    $result = $this->handleFileUploadForResource($request, $resource_type, $file_field_name);
    $file = $result->getFile();

    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/drupal/issues/2878463.
    $self_link = new Link(new CacheableMetadata(), Url::fromRoute('jsonapi.file--file.individual', ['entity' => $file->uuid()]), 'self');
    /* $self_link = new Link(new CacheableMetadata(), $this->entity->toUrl('jsonapi'), ['self']); */
    $links = new LinkCollection(['self' => $self_link]);

    $relatable_resource_types = $resource_type->getRelatableResourceTypesByField($resource_type->getPublicName($file_field_name));
    $file_resource_type = reset($relatable_resource_types);
    $resource_object = ResourceObject::createFromEntity($file_resource_type, $file);
    return new ResourceResponse(new JsonApiDocumentTopLevel(new ResourceObjectData([$resource_object], 1), new NullIncludedData(), $links), 201, []);
  }

  /**
   * Handles JSON:API file upload requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type for the current request.
   * @param string $file_field_name
   *   The file field for which the file is to be uploaded.
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *   (optional) The entity for which the file is to be uploaded.
   *
   * @return \Drupal\file\Upload\FileUploadResult
   *   The file upload result.
   */
  protected function handleFileUploadForResource(Request $request, ResourceType $resource_type, string $file_field_name, ?FieldableEntityInterface $entity = NULL): FileUploadResult {
    $file_field_name = $resource_type->getInternalName($file_field_name);
    $field_definition = $this->validateAndLoadFieldDefinition($resource_type->getEntityTypeId(), $resource_type->getBundle(), $file_field_name);

    static::ensureFileUploadAccess($this->currentUser, $field_definition, $entity);

    $filename = ContentDispositionFilenameParser::parseFilename($request);
    $tempPath = $this->inputStreamFileWriter->writeStreamToFile();
    $uploadedFile = new InputStreamUploadedFile($filename, $filename, $tempPath, @filesize($tempPath));

    $settings = $field_definition->getSettings();
    $validators = $this->getFileUploadValidators($settings);
    if (!array_key_exists('FileExtension', $validators) && $settings['file_extensions'] === '') {
      // An empty string means 'all file extensions' but the FileUploadHandler
      // needs the FileExtension entry to be present and empty in order for this
      // to be respected. An empty array means 'all file extensions'.
      // @see \Drupal\file\Upload\FileUploadHandler::handleExtensionValidation
      $validators['FileExtension'] = [];
    }

    $destination = $this->getUploadLocation($field_definition);

    // Check the destination file path is writable.
    if (!$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
      throw new HttpException(500, 'Destination file path is not writable');
    }

    try {
      $result = $this->fileUploadHandler->handleFileUpload($uploadedFile, $validators, $destination, FileExists::Rename, FALSE);
    }
    catch (LockAcquiringException $e) {
      throw new HttpException(503, $e->getMessage(), NULL, ['Retry-After' => 1]);
    }
    catch (UploadException $e) {
      throw new HttpException(500, 'Input file data could not be read', $e);
    }
    catch (CannotWriteFileException $e) {
      throw new HttpException(500, 'Temporary file data could not be written', $e);
    }
    catch (NoFileException $e) {
      throw new HttpException(500, 'Temporary file could not be opened', $e);
    }
    catch (FileExistsException $e) {
      throw new HttpException(500, $e->getMessage(), $e);
    }
    catch (FileException $e) {
      throw new HttpException(500, 'Temporary file could not be moved to file location');
    }

    if ($result->hasViolations()) {
      $message = "Unprocessable Entity: file validation failed.\n";
      $message .= implode("\n", array_map(function (ConstraintViolationInterface $violation) {
        return PlainTextOutput::renderFromHtml($violation->getMessage());
      }, (array) $result->getViolations()->getIterator()));
      throw new UnprocessableEntityHttpException($message);
    }

    return $result;
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
   * Ensures that the given account is allowed to upload a file.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which access should be checked.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field for which the file is to be uploaded.
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *   The entity, if one exists, for which the file is to be uploaded.
   */
  protected static function ensureFileUploadAccess(AccountInterface $account, FieldDefinitionInterface $field_definition, ?FieldableEntityInterface $entity = NULL) {
    $access_result = $entity
      ? static::checkFileUploadAccess($account, $field_definition, $entity)
      : static::checkFileUploadAccess($account, $field_definition);
    if (!$access_result->isAllowed()) {
      $reason = 'The current user is not permitted to upload a file for this field.';
      if ($access_result instanceof AccessResultReasonInterface) {
        $reason .= ' ' . $access_result->getReason();
      }
      throw new AccessDeniedHttpException($reason);
    }
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
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the field does not exist.
   * @throws \Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException
   *   Thrown when the target type of the field is not a file, or the current
   *   user does not have 'edit' access for the field.
   */
  protected function validateAndLoadFieldDefinition($entity_type_id, $bundle, $field_name) {
    $field_definitions = $this->fieldManager->getFieldDefinitions($entity_type_id, $bundle);
    if (!isset($field_definitions[$field_name])) {
      throw new NotFoundHttpException(sprintf('Field "%s" does not exist.', $field_name));
    }

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = $field_definitions[$field_name];
    if ($field_definition->getSetting('target_type') !== 'file') {
      throw new AccessDeniedException(sprintf('"%s" is not a file field', $field_name));
    }

    return $field_definition;
  }

}
