<?php

namespace Drupal\update;

use Drupal\Core\DrupalKernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Gets the root path used by the Update Manager to install or update projects.
 */
class UpdateRoot {

  /**
   * The Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $drupalKernel;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The update root.
   *
   * @var string
   */
  protected $updateRoot;

  /**
   * Constructs an UpdateRootFactory instance.
   *
   * @param \Drupal\Core\DrupalKernelInterface $drupal_kernel
   *   The Drupal kernel.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(DrupalKernelInterface $drupal_kernel, RequestStack $request_stack) {
    $this->drupalKernel = $drupal_kernel;
    $this->requestStack = $request_stack;
  }

  /**
   * Sets the root path under which projects are installed or updated.
   *
   * @param string $update_root
   *   The update root.
   */
  public function set(string $update_root): void {
    $this->updateRoot = $update_root;
  }

  /**
   * Gets the root path under which projects are installed or updated.
   *
   * The Update Manager will ensure that project files can only be copied to
   * specific subdirectories of this root path.
   *
   * @return string
   */
  public function __toString(): string {
    // Return the $updateRoot when it is set.
    if (isset($this->updateRoot)) {
      return $this->updateRoot;
    }

    // Normally the Update Manager's root path is the same as the app root (the
    // directory in which the Drupal site is installed).
    $root_path = $this->drupalKernel->getAppRoot();

    // When running in a test site, change the root path to be the testing site
    // directory. This ensures that it will always be writable by the webserver
    // (thereby allowing the actual extraction and installation of projects by
    // the Update Manager to be tested) and also ensures that new project files
    // added there won't be visible to the parent site and will be properly
    // cleaned up once the test finishes running. This is done here (rather
    // than having the tests install a module which overrides the update root
    // factory service) to ensure that the parent site is automatically kept
    // clean without relying on test authors to take any explicit steps. See
    // also \Drupal\update\Tests\Functional\UpdateTestBase::setUp().
    if (DRUPAL_TEST_IN_CHILD_SITE) {
      $kernel = $this->drupalKernel;
      $request = $this->requestStack->getCurrentRequest();
      $root_path .= '/' . $kernel::findSitePath($request);
    }

    return $root_path;
  }

}
