<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Controller;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\editor\Entity\Editor;
use Drupal\Core\Validation\DrupalTranslator;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Returns response for CKEditor5 Simple image upload adapter.
 *
 * @internal
 *   Controller classes are internal.
 */
class CKEditor5ImageController extends ControllerBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

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
   * The lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new CKEditor5ImageController.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The currently authenticated user.
   * @param \Symfony\Component\Mime\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME type guesser.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(FileSystemInterface $file_system, AccountInterface $current_user, MimeTypeGuesserInterface $mime_type_guesser, LockBackendInterface $lock, EventDispatcherInterface $event_dispatcher) {
    $this->fileSystem = $file_system;
    $this->currentUser = $current_user;
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->lock = $lock;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('current_user'),
      $container->get('file.mime_type.guesser'),
      $container->get('lock'),
      $container->get('event_dispatcher'),
    );
  }

  /**
   * Uploads and saves an image from a CKEditor5 POST.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON object including the file url.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Thrown when file system errors occur.
   * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
   *   Thrown when validation errors occur.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when file entity could not be saved.
   */
  public function upload(Request $request) {
    // Getting the UploadedFile directly from the request.
    $upload = $request->files->get('upload');
    $filename = $upload->getClientOriginalName();

    $editor = $request->attributes->get('editor');
    $image_upload = $editor->getImageUploadSettings();
    $destination = $image_upload['scheme'] . '://' . $image_upload['directory'];

    // Check the destination file path is writable.
    if (!$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
      throw new HttpException(500, 'Destination file path is not writable');
    }

    $max_filesize = min(Bytes::toNumber($image_upload['max_size']), Environment::getUploadMaxSize());
    if (!empty($image_upload['max_dimensions']['width']) || !empty($image_upload['max_dimensions']['height'])) {
      $max_dimensions = $image_upload['max_dimensions']['width'] . 'x' . $image_upload['max_dimensions']['height'];
    }
    else {
      $max_dimensions = 0;
    }

    $validators = [
      'file_validate_extensions' => ['gif png jpg jpeg'],
      'file_validate_size' => [$max_filesize],
      'file_validate_image_resolution' => [$max_dimensions],
    ];

    $prepared_filename = $this->prepareFilename($filename, $validators);

    // Create the file.
    $file_uri = "{$destination}/{$prepared_filename}";

    // Using the UploadedFile method instead of streamUploadData.
    $temp_file_path = $upload->getRealPath();

    $file_uri = $this->fileSystem->getDestinationFilename($file_uri, FileSystemInterface::EXISTS_RENAME);

    // Lock based on the prepared file URI.
    $lock_id = $this->generateLockIdFromFileUri($file_uri);

    if (!$this->lock->acquire($lock_id)) {
      throw new HttpException(503, sprintf('File "%s" is already locked for writing.'), NULL, ['Retry-After' => 1]);
    }

    // Begin building file entity.
    $file = File::create([]);
    $file->setOwnerId($this->currentUser->id());
    $file->setFilename($prepared_filename);
    if ($this->mimeTypeGuesser instanceof MimeTypeGuesserInterface) {
      $file->setMimeType($this->mimeTypeGuesser->guessMimeType($prepared_filename));
    }
    else {
      $file->setMimeType($this->mimeTypeGuesser->guess($prepared_filename));
      @trigger_error('\Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Implement \Symfony\Component\Mime\MimeTypeGuesserInterface instead. See https://www.drupal.org/node/3133341', E_USER_DEPRECATED);
    }

    $file->setFileUri($file_uri);
    $file->setSize(@filesize($temp_file_path));

    $violations = $this->validate($file, $validators);
    if ($violations->count() > 0) {
      throw new UnprocessableEntityHttpException($violations->__toString());
    }

    try {
      $this->fileSystem->move($temp_file_path, $file_uri, FileSystemInterface::EXISTS_ERROR);
    }
    catch (FileException $e) {
      throw new HttpException(500, 'Temporary file could not be moved to file location');
    }

    $file->save();

    $this->lock->release($lock_id);

    return new JsonResponse([
      'url' => $file->createFileUrl(),
      'uuid' => $file->uuid(),
      'entity_type' => $file->getEntityTypeId(),
    ], 201);
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
          (string) $error,
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
    $extensions = $validators['file_validate_extensions'][0] ?? '';
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
    return 'file:ckeditor5:' . Crypt::hashBase64($file_uri);
  }

}
