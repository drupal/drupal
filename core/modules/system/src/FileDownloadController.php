<?php

namespace Drupal\system;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * System file controller.
 */
class FileDownloadController extends ControllerBase {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * FileDownloadController constructor.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager.
   */
  public function __construct(StreamWrapperManagerInterface $streamWrapperManager) {
    $this->streamWrapperManager = $streamWrapperManager;
  }

  /**
   * Handles private file transfers.
   *
   * Call modules that implement hook_file_download() to find out if a file is
   * accessible and what headers it should be transferred with. If one or more
   * modules returned headers the download will start with the returned headers.
   * If a module returns -1 an AccessDeniedHttpException will be thrown. If the
   * file exists but no modules responded an AccessDeniedHttpException will be
   * thrown. If the file does not exist a NotFoundHttpException will be thrown.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $scheme
   *   The file scheme, defaults to 'private'.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The transferred file as response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the requested file does not exist.
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user does not have access to the file.
   *
   * @see hook_file_download()
   */
  public function download(Request $request, $scheme = 'private') {
    $target = $request->query->get('file');
    // Merge remaining path arguments into relative file path.
    $uri = $this->streamWrapperManager->normalizeUri($scheme . '://' . $target);

    if ($this->streamWrapperManager->isValidScheme($scheme) && is_file($uri)) {
      // Let other modules provide headers and controls access to the file.
      $headers = $this->moduleHandler()->invokeAll('file_download', [$uri]);

      foreach ($headers as $result) {
        if ($result == -1) {
          throw new AccessDeniedHttpException();
        }
      }

      if (count($headers)) {
        // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
        // sets response as not cacheable if the Cache-Control header is not
        // already modified. We pass in FALSE for non-private schemes for the
        // $public parameter to make sure we don't change the headers.
        return new BinaryFileResponse($uri, 200, $headers, $scheme !== 'private');
      }

      throw new AccessDeniedHttpException();
    }

    throw new NotFoundHttpException();
  }

}
