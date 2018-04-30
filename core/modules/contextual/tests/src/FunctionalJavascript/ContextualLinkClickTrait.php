<?php

namespace Drupal\Tests\contextual\FunctionalJavascript;

/**
 * Functions for testing contextual links.
 */
trait ContextualLinkClickTrait {

  /**
   * Clicks a contextual link.
   *
   * @param string $selector
   *   The selector for the element that contains the contextual link.
   * @param string $link_locator
   *   The link id, title, or text.
   * @param bool $force_visible
   *   If true then the button will be forced to visible so it can be clicked.
   */
  protected function clickContextualLink($selector, $link_locator, $force_visible = TRUE) {
    $page = $this->getSession()->getPage();
    $page->waitFor(10, function () use ($page, $selector) {
      return $page->find('css', "$selector .contextual-links");
    });

    if ($force_visible) {
      $this->toggleContextualTriggerVisibility($selector);
    }

    $element = $this->getSession()->getPage()->find('css', $selector);
    $element->find('css', '.contextual button')->press();
    $element->findLink($link_locator)->click();

    if ($force_visible) {
      $this->toggleContextualTriggerVisibility($selector);
    }
  }

  /**
   * Toggles the visibility of a contextual trigger.
   *
   * @param string $selector
   *   The selector for the element that contains the contextual link.
   */
  protected function toggleContextualTriggerVisibility($selector) {
    // Hovering over the element itself with should be enough, but does not
    // work. Manually remove the visually-hidden class.
    $this->getSession()->executeScript("jQuery('{$selector} .contextual .trigger').toggleClass('visually-hidden');");
  }

}
