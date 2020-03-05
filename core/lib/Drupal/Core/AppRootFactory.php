<?php

namespace Drupal\Core;

/**
 * Gets the app root from the kernel.
 */
class AppRootFactory {

  /**
   * The Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $drupalKernel;

  /**
   * Constructs an AppRootFactory instance.
   *
   * @param \Drupal\Core\DrupalKernelInterface $drupal_kernel
   *   The Drupal kernel.
   */
  public function __construct(DrupalKernelInterface $drupal_kernel) {
    $this->drupalKernel = $drupal_kernel;
  }

  /**
   * Gets the app root.
   *
   * @return string
   *   The app root.
   */
  public function get() {
    return $this->drupalKernel->getContainer()->getParameter('app.root');
  }

}
