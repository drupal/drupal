<?php

namespace Drupal\dynamic_page_cache\PageCache\RequestPolicy;

use Drupal\Core\PageCache\ChainRequestPolicy;
use Drupal\Core\PageCache\RequestPolicy\CommandLineOrUnsafeMethod;

/**
 * The default Dynamic Page Cache request policy.
 *
 * Delivery of cached pages is denied if either the application is running from
 * the command line or the request was not initiated with a safe method (GET or
 * HEAD).
 */
class DefaultRequestPolicy extends ChainRequestPolicy {

  /**
   * Constructs the default Dynamic Page Cache request policy.
   */
  public function __construct() {
    $this->addPolicy(new CommandLineOrUnsafeMethod());
  }

}
