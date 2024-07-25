<?php

namespace Drupal\file\Upload;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\Exception\FileExistsException;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockAcquiringException;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Validation\BasicRecursiveValidatorFactory;
use Drupal\file\Entity\File;
use Drupal\file\FileRepositoryInterface;
use Drupal\file\Validation\FileValidatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Handles validating and creating file entities from file uploads.
 */
class FileUploadHandler {

  /**
   * The default extensions if none are provided.
   */
  const DEFAULT_EXTENSIONS = 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp';

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
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The current user.
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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The file Repository.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * The file validator.
   *
   * @var \Drupal\file\Validation\FileValidatorInterface
   */
  protected FileValidatorInterface $fileValidator;

  public function __construct(
    FileSystemInterface $fileSystem,
    EntityTypeManagerInterface $entityTypeManager,
    StreamWrapperManagerInterface $streamWrapperManager,
    EventDispatcherInterface $eventDispatcher,
    MimeTypeGuesserInterface $mimeTypeGuesser,
    AccountInterface $currentUser,
    RequestStack $requestStack,
    FileRepositoryInterface $fileRepository,
    FileValidatorInterface $file_validator,
    protected LockBackendInterface $lock,
    protected BasicRecursiveValidatorFactory $validatorFactory,
  ) {
    $this->fileSystem = $fileSystem;
    $this->entityTypeManager = $entityTypeManager;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->eventDispatcher = $eventDispatcher;
    $this->mimeTypeGuesser = $mimeTypeGuesser;
    $this->currentUser = $currentUser;
    $this->requestStack = $requestStack;
    $this->fileRepository = $fileRepository;
    $this->fileValidator = $file_validator;
  }

  /**
   * Creates a file from an upload.
   *
   * @param \Drupal\file\Upload\UploadedFileInterface $uploadedFile
   *   The uploaded file object.
   * @param array $validators
   *   The validators to run against the uploaded file.
   * @param string $destination
   *   The destination directory.
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   The behavior when the destination file already exists.
   *
   * @return \Drupal\file\Upload\FileUploadResult
   *   The created file entity.
   *
   * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
   *    Thrown when a file upload error occurred and $throws is TRUE.
   * @throws \Drupal\Core\File\Exception\FileWriteException
   *    Thrown when there is an error moving the file and $throws is TRUE.
   * @throws \Drupal\Core\File\Exception\FileException
   *    Thrown when a file system error occurs and $throws is TRUE.
   * @throws \Drupal\file\Upload\FileValidationException
   *    Thrown when file validation fails and $throws is TRUE.
   * @throws \Drupal\Core\Lock\LockAcquiringException
   *   Thrown when a lock cannot be acquired.
   * @throws \ValueError
   *   Thrown if $fileExists is a legacy int and not a valid value.
   */
  public function handleFileUpload(UploadedFileInterface $uploadedFile, array $validators = [], string $destination = 'temporary://', /*FileExists*/$fileExists = FileExists::Replace): FileUploadResult {
    if (!$fileExists instanceof FileExists) {
      // @phpstan-ignore staticMethod.deprecated
      $fileExists = FileExists::fromLegacyInt($fileExists, __METHOD__);
    }
    $result = new FileUploadResult();

    $violations = $uploadedFile->validate($this->validatorFactory->createValidator());
    if (count($violations) > 0) {
      $result->addViolations($violations);
      return $result;
    }

    $originalName = $uploadedFile->getClientOriginalName();
    $extensions = $this->handleExtensionValidation($validators);

    // Assert that the destination contains a valid stream.
    $destinationScheme = $this->streamWrapperManager::getScheme($destination);
    if (!$this->streamWrapperManager->isValidScheme($destinationScheme)) {
      throw new InvalidStreamWrapperException(sprintf('The file could not be uploaded because the destination "%s" is invalid.', $destination));
    }

    // A file URI may already have a trailing slash or look like "public://".
    if (!str_ends_with($destination, '/')) {
      $destination .= '/';
    }

    // Call an event to sanitize the filename and to attempt to address security
    // issues caused by common server setups.
    $event = new FileUploadSanitizeNameEvent($originalName, $extensions);
    $this->eventDispatcher->dispatch($event);
    $filename = $event->getFilename();

    $mimeType = $this->mimeTypeGuesser->guessMimeType($filename);
    $destinationFilename = $this->fileSystem->getDestinationFilename($destination . $filename, $fileExists);
    if ($destinationFilename === FALSE) {
      throw new FileExistsException(sprintf('Destination file "%s" exists', $destinationFilename));
    }

    // Lock based on the prepared file URI.
    $lock_id = $this->generateLockId($destinationFilename);

    try {
      if (!$this->lock->acquire($lock_id)) {
        throw new LockAcquiringException(
          sprintf(
            'File "%s" is already locked for writing.',
            $destinationFilename
          )
        );
      }

      $file = File::create([
        'uid' => $this->currentUser->id(),
        'status' => 0,
        'uri' => $uploadedFile->getRealPath(),
      ]);

      // This will be replaced later with a filename based on the destination.
      $file->setFilename($filename);
      $file->setMimeType($mimeType);
      $file->setSize($uploadedFile->getSize());

      // Add in our check of the file name length.
      $validators['FileNameLength'] = [];

      // Call the validation functions specified by this function's caller.
      $violations = $this->fileValidator->validate($file, $validators);
      if (count($violations) > 0) {
        $result->addViolations($violations);

        return $result;
      }

      $file->setFileUri($destinationFilename);

      if (!$this->moveUploadedFile($uploadedFile, $file->getFileUri())) {
        throw new FileWriteException(
          'File upload error. Could not move uploaded file.'
        );
      }

      // Update the filename with any changes as a result of security or
      // renaming due to an existing file.
      $file->setFilename($this->fileSystem->basename($file->getFileUri()));

      if ($fileExists === FileExists::Replace) {
        $existingFile = $this->fileRepository->loadByUri($file->getFileUri());
        if ($existingFile) {
          $file->fid = $existingFile->id();
          $file->setOriginalId($existingFile->id());
        }
      }

      $result->setOriginalFilename($originalName)
        ->setSanitizedFilename($filename)
        ->setFile($file);

      // If the filename has been modified, let the user know.
      if ($event->isSecurityRename()) {
        $result->setSecurityRename();
      }

      // Set the permissions on the new file.
      $this->fileSystem->chmod($file->getFileUri());

      // We can now validate the file object itself before it's saved.
      $violations = $file->validate();
      if (count($violations) > 0) {
        $result->addViolations($violations);

        return $result;
      }

      // If we made it this far it's safe to record this file in the database.
      $file->save();

      // Allow an anonymous user who creates a non-public file to see it. See
      // \Drupal\file\FileAccessControlHandler::checkAccess().
      if ($this->currentUser->isAnonymous() && $destinationScheme !== 'public') {
        $session = $this->requestStack->getCurrentRequest()->getSession();
        $allowed_temp_files = $session->get('anonymous_allowed_file_ids', []);
        $allowed_temp_files[$file->id()] = $file->id();
        $session->set('anonymous_allowed_file_ids', $allowed_temp_files);
      }
    }
    finally {
      $this->lock->release($lock_id);
    }
    return $result;
  }

  /**
   * Move the uploaded file from the temporary path to the destination.
   *
   * @param \Drupal\file\Upload\UploadedFileInterface $uploadedFile
   *   The uploaded file.
   * @param string $uri
   *   The destination URI.
   *
   * @return bool
   *   Returns FALSE if moving failed.
   *
   * @see https://www.drupal.org/project/drupal/issues/2940383
   */
  protected function moveUploadedFile(UploadedFileInterface $uploadedFile, string $uri): bool {
    if ($uploadedFile instanceof FormUploadedFile) {
      return $this->fileSystem->moveUploadedFile($uploadedFile->getRealPath(), $uri);
    }
    // We use FileExists::Error) as the file location has already
    // been determined above in FileSystem::getDestinationFilename().
    return $this->fileSystem->move($uploadedFile->getRealPath(), $uri, FileExists::Error);
  }

  /**
   * Gets the list of allowed extensions and updates the validators.
   *
   * This will add an extension validator to the list of validators if one is
   * not set.
   *
   * If the extension validator is set, but no extensions are specified, it
   * means all extensions are allowed, so the validator is removed from the list
   * of validators.
   *
   * @param array $validators
   *   The file validators in use.
   *
   * @return string
   *   The space delimited list of allowed file extensions.
   */
  protected function handleExtensionValidation(array &$validators): string {
    // No validator was provided, so add one using the default list.
    // Build a default non-munged safe list for
    // \Drupal\system\EventSubscriber\SecurityFileUploadEventSubscriber::sanitizeName().
    if (!isset($validators['FileExtension'])) {
      $validators['FileExtension'] = ['extensions' => self::DEFAULT_EXTENSIONS];
      return self::DEFAULT_EXTENSIONS;
    }

    // Check if we want to allow all extensions.
    if (!isset($validators['FileExtension']['extensions'])) {
      // If 'FileExtension' is set and the list is empty then the caller wants
      // to allow any extension. In this case we have to remove the validator
      // or else it will reject all extensions.
      unset($validators['FileExtension']);
      return '';
    }

    return $validators['FileExtension']['extensions'];
  }

  /**
   * Generates a lock ID based on the file URI.
   */
  protected static function generateLockId(string $fileUri): string {
    return 'file:upload:' . Crypt::hashBase64($fileUri);
  }

}
