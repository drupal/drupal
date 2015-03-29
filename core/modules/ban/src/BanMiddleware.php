<?php

/**
 * @file
 * Contains \Drupal\ban\BanMiddleware.
 */

namespace Drupal\ban;

use Drupal\Component\Utility\SafeMarkup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a HTTP middleware to implement IP based banning.
 */
class BanMiddleware implements HttpKernelInterface {

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The ban IP manager.
   *
   * @var \Drupal\ban\BanIpManagerInterface
   */
  protected $banIpManager;

  /**
   * Constructs a BanMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\ban\BanIpManagerInterface $manager
   *   The ban IP manager.
   */
  public function __construct(HttpKernelInterface $http_kernel, BanIpManagerInterface $manager) {
    $this->httpKernel = $http_kernel;
    $this->banIpManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $ip = $request->getClientIp();
    if ($this->banIpManager->isBanned($ip)) {
      return new Response(SafeMarkup::format('Sorry @ip has been banned', ['@ip' => $ip]), 403);
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

}
