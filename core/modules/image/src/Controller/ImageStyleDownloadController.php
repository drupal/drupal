<?php

namespace Drupal\image\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\image\ImageProcessor;
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
   * The image processor service.
   *
   * @var \Drupal\image\ImageProcessor
   */
  protected $imageProcessor;

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
   * @param \Drupal\image\ImageProcessor $image_processor
   *   The image processor service.
   */
  public function __construct(LockBackendInterface $lock, ImageFactory $image_factory, StreamWrapperManagerInterface $stream_wrapper_manager, FileSystemInterface $file_system = NULL, ImageProcessor $image_processor = NULL) {
    parent::__construct($stream_wrapper_manager);
    $this->lock = $lock;
    $this->imageFactory = $image_factory;
    $this->imageProcessor = $image_processor;
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
      $container->get('file_system'),
      $container->get('image.processor')
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
    // Check that the style is defined, return a 404 (Page Not Found) if
    // missing.
    if (empty($image_style)) {
      throw new NotFoundHttpException();
    }

    // Check that the URI scheme is valid, return a 404 (Page Not Found) if
    // invalid.
    if (!$this->streamWrapperManager->isValidScheme($scheme)) {
      throw new NotFoundHttpException();
    }

    // Check that the source image file exists. If the image style converts
    // the image format, the new format extension has been added to the original
    // filename, resulting in filenames like image.png.jpeg. So to find the real
    // source image, we remove the extension and check if that image exists.
    $target = $request->query->get('file');
    $request_image_uri = $scheme . '://' . $target;
    if (file_exists($request_image_uri)) {
      $image_uri = $request_image_uri;
    }
    else {
      $path_info = pathinfo(StreamWrapperManager::getTarget($request_image_uri));
      $dir_name = $path_info['dirname'] !== '.' ? $path_info['dirname'] . DIRECTORY_SEPARATOR : '';
      $original_image_uri = sprintf('%s://%s%s', $scheme, $dir_name, $path_info['filename']);
      if (file_exists($original_image_uri)) {
        $image_uri = $original_image_uri;
      }
    }
    // Don't try to generate file if source is missing.
    if (!isset($image_uri)) {
      $this->logger->notice('Source image at %source_image_path not found while trying to generate derivative image.', ['%source_image_path' => $request_image_uri]);
      return new Response($this->t('Error generating image, missing source file.'), 404);
    }

    // Create an image process pipeline.
    $pipeline = $this->imageProcessor->createInstance('derivative');
    $pipeline
      ->setImageStyle($image_style)
      ->setSourceImageUri($image_uri);

    // Check that the the image derivative token is valid.
    // Sites which require image derivatives to be generated without a token
    // can set the 'image.settings:allow_insecure_derivatives' configuration to
    // TRUE to bypass the latter check, but this will increase the site's
    // vulnerability to denial-of-service attacks. To prevent this variable
    // from leaving the site vulnerable to the most serious attacks, a token is
    // always required when a derivative of a style is requested.
    // The $target variable for a derivative of a style has
    // styles/<style_name>/... as structure, so we check if the $target variable
    // starts with styles/.
    $valid = TRUE;
    if (!$this->config('image.settings')->get('allow_insecure_derivatives') || strpos(ltrim($target, '\/'), 'styles/') === 0) {
      $valid &= hash_equals($pipeline->getDerivativeImageUrlSecurityToken(), $request->query->get(IMAGE_DERIVATIVE_TOKEN, ''));
    }
    if (!$valid) {
      // Return a 404 (Page Not Found) rather than a 403 (Access Denied) as the
      // image token is for DDoS protection rather than access checking. 404s
      // are more likely to be cached (e.g. at a proxy) which enhances
      // protection from DDoS.
      throw new NotFoundHttpException();
    }

    $derivative_uri = $pipeline->getDerivativeImageUri();
    $headers = [];

    // If using the private scheme, let other modules provide headers and
    // control access to the file.
    if ($scheme == 'private') {
      $headers = $this->moduleHandler()->invokeAll('file_download', [$image_uri]);
      if (in_array(-1, $headers) || empty($headers)) {
        throw new AccessDeniedHttpException();
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
    $success = file_exists($derivative_uri) || $pipeline->buildDerivativeImage();

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
      // already modified. We pass in FALSE for non-private schemes for the
      // $public parameter to make sure we don't change the headers.
      return new BinaryFileResponse($uri, 200, $headers, $scheme !== 'private');
    }
    else {
      $this->logger->notice('Unable to generate the derived image located at %path.', ['%path' => $derivative_uri]);
      return new Response($this->t('Error generating image.'), 500);
    }
  }

}
