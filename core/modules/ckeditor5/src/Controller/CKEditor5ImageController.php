<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Controller;

use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\editor\Entity\Editor;
use Drupal\file\Upload\FileUploadHandler;
use Drupal\file\Upload\FormUploadedFile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Mime\MimeTypes;

/**
 * Returns response for CKEditor 5 Simple image upload adapter.
 *
 * @internal
 *   Controller classes are internal.
 */
class CKEditor5ImageController extends ControllerBase {

  /**
   * Constructs a new CKEditor5ImageController.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\file\Upload\FileUploadHandler $fileUploadHandler
   *   The file upload handler.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface $pluginManager
   *   The CKEditor 5 plugin manager.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
    protected FileUploadHandler $fileUploadHandler,
    protected LockBackendInterface $lock,
    protected CKEditor5PluginManagerInterface $pluginManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('file.upload_handler'),
      $container->get('lock'),
      $container->get('plugin.manager.ckeditor5.plugin')
    );
  }

  /**
   * Uploads and saves an image from a CKEditor 5 POST.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON object including the file URL.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Thrown when file system errors occur.
   * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
   *   Thrown when validation errors occur.
   */
  public function upload(Request $request): Response {
    // Getting the UploadedFile directly from the request.
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $upload */
    $upload = $request->files->get('upload');
    $filename = $upload->getClientOriginalName();

    /** @var \Drupal\editor\EditorInterface $editor */
    $editor = $request->attributes->get('editor');
    $settings = $editor->getImageUploadSettings();
    $destination = $settings['scheme'] . '://' . $settings['directory'];

    // Check the destination file path is writable.
    if (!$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
      throw new HttpException(500, 'Destination file path is not writable');
    }

    $validators = $this->getImageUploadValidators($settings);

    $file_uri = "{$destination}/{$filename}";
    $file_uri = $this->fileSystem->getDestinationFilename($file_uri, FileExists::Rename);

    // Lock based on the prepared file URI.
    $lock_id = $this->generateLockIdFromFileUri($file_uri);

    if (!$this->lock->acquire($lock_id)) {
      throw new HttpException(503, sprintf('File "%s" is already locked for writing.', $file_uri), NULL, ['Retry-After' => 1]);
    }

    try {
      $uploadedFile = new FormUploadedFile($upload);
      $uploadResult = $this->fileUploadHandler->handleFileUpload($uploadedFile, $validators, $destination, FileExists::Rename);
      if ($uploadResult->hasViolations()) {
        throw new UnprocessableEntityHttpException((string) $uploadResult->getViolations());
      }
    }
    catch (FileException $e) {
      throw new HttpException(500, 'File could not be saved');
    }
    catch (LockAcquiringException $e) {
      throw new HttpException(503, sprintf('File "%s" is already locked for writing.', $upload->getClientOriginalName()), NULL, ['Retry-After' => 1]);
    }

    $this->lock->release($lock_id);

    $file = $uploadResult->getFile();
    return new JsonResponse([
      'url' => $file->createFileUrl(),
      'uuid' => $file->uuid(),
      'entity_type' => $file->getEntityTypeId(),
    ], 201);
  }

  /**
   * Gets the image upload validators.
   */
  protected function getImageUploadValidators(array $settings): array {
    $max_filesize = $settings['max_size']
      ? Bytes::toNumber($settings['max_size'])
      : Environment::getUploadMaxSize();
    $max_dimensions = 0;
    if (!empty($settings['max_dimensions']['width']) || !empty($settings['max_dimensions']['height'])) {
      $max_dimensions = $settings['max_dimensions']['width'] . 'x' . $settings['max_dimensions']['height'];
    }

    $mimetypes = MimeTypes::getDefault();
    $imageUploadPlugin = $this->pluginManager->getDefinition('ckeditor5_imageUpload')->toArray();
    $allowed_extensions = [];
    foreach ($imageUploadPlugin['ckeditor5']['config']['image']['upload']['types'] as $mime_type) {
      $allowed_extensions = array_merge($allowed_extensions, $mimetypes->getExtensions('image/' . $mime_type));
    }

    return [
      'FileExtension' => [
        'extensions' => implode(' ', $allowed_extensions),
      ],
      'FileSizeLimit' => [
        'fileLimit' => $max_filesize,
      ],
      'FileImageDimensions' => [
        'maxDimensions' => $max_dimensions,
      ],
    ];
  }

  /**
   * Access check based on whether image upload is enabled or not.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   The text editor for which an image upload is occurring.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function imageUploadEnabledAccess(Editor $editor) {
    if ($editor->getEditor() !== 'ckeditor5') {
      return AccessResult::forbidden();
    }
    if ($editor->getImageUploadSettings()['status'] !== TRUE) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
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
    return 'file:ckeditor5:' . Crypt::hashBase64($file_uri);
  }

}
