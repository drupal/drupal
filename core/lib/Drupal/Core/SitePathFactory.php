<?php

namespace Drupal\Core;

/**
 * Gets the site path from the kernel.
 */
class SitePathFactory {

  /**
   * The Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $drupalKernel;

  /**
   * Constructs an SitePathFactory instance.
   *
   * @param \Drupal\Core\DrupalKernelInterface $drupal_kernel
   *   The Drupal kernel.
   */
  public function __construct(DrupalKernelInterface $drupal_kernel) {
    $this->drupalKernel = $drupal_kernel;
  }

  /**
   * Gets the site path.
   *
   * @return string
   *   The site path.
   */
  public function get() {
    return $this->drupalKernel->getSitePath();
  }

}

