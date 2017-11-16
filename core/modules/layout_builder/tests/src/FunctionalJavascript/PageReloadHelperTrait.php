<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

/**
 * Provides a helper to determine if a page has been reloaded.
 *
 * @todo Move somewhere more generic in https://www.drupal.org/node/2909782.
 */
trait PageReloadHelperTrait {

  /**
   * A string used to mark the current page.
   *
   * @var string
   */
  protected $pageReloadMarker;

  /**
   * Marks the page to assist determining if the page has been reloaded.
   */
  protected function markCurrentPage() {
    $this->pageReloadMarker = $this->randomMachineName();
    $this->getSession()->executeScript('document.body.appendChild(document.createTextNode("' . $this->pageReloadMarker . '"));');
  }

  /**
   * Asserts that the page has not been reloaded.
   */
  protected function assertPageNotReloaded() {
    $this->assertSession()->pageTextContains($this->pageReloadMarker);
  }

  /**
   * Asserts that the page has been reloaded.
   */
  protected function assertPageReloaded() {
    $this->assertSession()->pageTextNotContains($this->pageReloadMarker);
  }

}
