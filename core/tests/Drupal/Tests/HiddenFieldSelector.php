<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Behat\Mink\Selector\PartialNamedSelector;

/**
 * Extends PartialNamedSelector to allow retrieval of hidden fields.
 *
 * @see \Behat\Mink\Selector\PartialNamedSelector
 */
class HiddenFieldSelector extends PartialNamedSelector {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $xpath = ".//input[%lowercaseType% = 'hidden' and (%idOrNameMatch% or %valueMatch%)]";
    $this->registerNamedXpath('hidden_field', $xpath);
    parent::__construct();
  }

}
