<?php

namespace Drupal\image\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\system\FileDownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Defines a controller to serve image styles.
 */
class ImageStyleDownloadController extends FileDownloadController {

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs an ImageStyleDownloadController object.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The system service.
   */
  public function __construct(LockBackendInterface $lock, ImageFactory $image_factory, StreamWrapperManagerInterface $stream_wrapper_manager, FileSystemInterface $file_system = NULL) {
    parent::__construct($stream_wrapper_manager);
    $this->lock = $lock;
    $this->imageFactory = $image_factory;
    $this->logger = $this->getLogger('image');

    if (!isset($file_system)) {
      @trigger_error('Not defining the $file_system argument to ' . __METHOD__ . ' is deprecated in drupal:9.1.0 and will throw an error in drupal:10.0.0.', E_USER_DEPRECATED);
      $file_system = \Drupal::service('file_system');
    }
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lock'),
      $container->get('image.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('file_system')
    );
  }

  /**
   * Generates a derivative, given a style and image path.
   *
   * After generating an image, transfer it to the requesting agent.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $scheme
   *   The file scheme, defaults to 'private'.
   * @param \Drupal\image\ImageStyleInterface $image_style
   *   The image style to deliver.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
   *   The transferred file as response or some error response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the file request is invalid.
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user does not have access to the file.
   * @throws \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
   *   Thrown when the file is still being generated.
   */
  public function deliver(Request $request, $scheme, ImageStyleInterface $image_style) {
    $target = $request->query->get('file');
    $image_uri = $scheme . '://' . $target;

    // Check that the style is defined and the scheme is valid.
    $valid = !empty($image_style) && $this->streamWrapperManager->isValidScheme($scheme);

    // Also validate the derivative token. Sites which require image
    // derivatives to be generated without a token can set the
    // 'image.settings:allow_insecure_derivatives' configuration to TRUE to
    // bypass this check, but this will increase the site's vulnerability
    // to denial-of-service attacks. To prevent this variable from leaving the
    // site vulnerable to the most serious attacks, a token is always required
    // when a derivative of a style is requested.
    // The $target variable for a derivative of a style has
    // styles/<style_name>/... as structure, so we check if the $target variable
    // starts with styles/.
    $token = $request->query->get(IMAGE_DERIVATIVE_TOKEN, '');
    $token_is_valid = hash_equals($image_style->getPathToken($image_uri), $token);
    if (!$this->config('image.settings')->get('allow_insecure_derivatives') || strpos(ltrim($target, '\/'), 'styles/') === 0) {
      $valid = $valid && $token_is_valid;
    }

    if (!$valid) {
      // Return a 404 (Page Not Found) rather than a 403 (Access Denied) as the
      // image token is for DDoS protection rather than access checking. 404s
      // are more likely to be cached (e.g. at a proxy) which enhances
      // protection from DDoS.
      throw new NotFoundHttpException();
    }

    $derivative_uri = $image_style->buildUri($image_uri);
    $derivative_scheme = $this->streamWrapperManager->getScheme($derivative_uri);

    if ($token_is_valid) {
      $is_public = ($scheme !== 'private');
    }
    else {
      $core_schemes = ['public', 'private', 'temporary'];
      $additional_public_schemes = array_diff(Settings::get('file_additional_public_schemes', []), $core_schemes);
      $public_schemes = array_merge(['public'], $additional_public_schemes);
      $is_public = in_array($derivative_scheme, $public_schemes, TRUE);
    }

    $headers = [];

    // If not using a public scheme, let other modules provide headers and
    // control access to the file.
    if (!$is_public) {
      $headers = $this->moduleHandler()->invokeAll('file_download', [$image_uri]);
      if (in_array(-1, $headers) || empty($headers)) {
        throw new AccessDeniedHttpException();
      }
    }

    // Don't try to generate file if source is missing.
    if (!$this->sourceImageExists($image_uri, $token_is_valid)) {
      // If the image style converted the extension, it has been added to the
      // original file, resulting in filenames like image.png.jpeg. So to find
      // the actual source image, we remove the extension and check if that
      // image exists.
      $path_info = pathinfo(StreamWrapperManager::getTarget($image_uri));
      $converted_image_uri = sprintf('%s://%s%s%s', $this->streamWrapperManager->getScheme($derivative_uri), $path_info['dirname'], DIRECTORY_SEPARATOR, $path_info['filename']);
      if (!$this->sourceImageExists($converted_image_uri, $token_is_valid)) {
        $this->logger->notice('Source image at %source_image_path not found while trying to generate derivative image at %derivative_path.', ['%source_image_path' => $image_uri, '%derivative_path' => $derivative_uri]);
        return new Response($this->t('Error generating image, missing source file.'), 404);
      }
      else {
        // The converted file does exist, use it as the source.
        $image_uri = $converted_image_uri;
      }
    }

    // Don't start generating the image if the derivative already exists or if
    // generation is in progress in another thread.
    if (!file_exists($derivative_uri)) {
      $lock_name = 'image_style_deliver:' . $image_style->id() . ':' . Crypt::hashBase64($image_uri);
      $lock_acquired = $this->lock->acquire($lock_name);
      if (!$lock_acquired) {
        // Tell client to retry again in 3 seconds. Currently no browsers are
        // known to support Retry-After.
        throw new ServiceUnavailableHttpException(3, 'Image generation in progress. Try again shortly.');
      }
    }

    // Try to generate the image, unless another thread just did it while we
    // were acquiring the lock.
    $success = file_exists($derivative_uri) || $image_style->createDerivative($image_uri, $derivative_uri);

    if (!empty($lock_acquired)) {
      $this->lock->release($lock_name);
    }

    if ($success) {
      $image = $this->imageFactory->get($derivative_uri);
      $uri = $image->getSource();
      $headers += [
        'Content-Type' => $image->getMimeType(),
        'Content-Length' => $image->getFileSize(),
      ];
      // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
      // sets response as not cacheable if the Cache-Control header is not
      // already modified. When $is_public is TRUE, the following sets the
      // Cache-Control header to "public".
      return new BinaryFileResponse($uri, 200, $headers, $is_public);
    }
    else {
      $this->logger->notice('Unable to generate the derived image located at %path.', ['%path' => $derivative_uri]);
      return new Response($this->t('Error generating image.'), 500);
    }
  }

  /**
   * Checks whether the provided source image exists.
   *
   * @param string $image_uri
   *   The URI for the source image.
   * @param bool $token_is_valid
   *   Whether a valid image token was supplied.
   *
   * @return bool
   *   Whether the source image exists.
   */
  private function sourceImageExists(string $image_uri, bool $token_is_valid): bool {
    $exists = file_exists($image_uri);

    // If the file doesn't exist, we can stop here.
    if (!$exists) {
      return FALSE;
    }

    if ($token_is_valid) {
      return TRUE;
    }

    if (StreamWrapperManager::getScheme($image_uri) !== 'public') {
      return TRUE;
    }

    $image_path = $this->fileSystem->realpath($image_uri);
    $private_path = Settings::get('file_private_path');
    if ($private_path) {
      $private_path = realpath($private_path);
      if ($private_path && strpos($image_path, $private_path) === 0) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
