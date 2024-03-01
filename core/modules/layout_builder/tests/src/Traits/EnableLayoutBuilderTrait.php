<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Traits;

use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;

/**
 * Test trait to enable or disable Layout Builder on an entity view display.
 */
trait EnableLayoutBuilderTrait {

  /**
   * Enables Layout Builder on an entity view display.
   *
   * @param \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $display
   *   The entity view display.
   */
  protected function enableLayoutBuilder(LayoutEntityDisplayInterface $display): void {
    $display
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * Enables Layout Builder using the UI.
   *
   * @param string $bundle
   *   The bundle that Layout Builder is being enabled on.
   * @param string $viewMode
   *   The view mode that Layout Builder is being enabled on.
   * @param bool $allowCustom
   *   Whether custom layouts per entity should be allowed.
   */
  protected function enableLayoutBuilderFromUi(string $bundle, string $viewMode, bool $allowCustom = TRUE): void {
    $path = sprintf('admin/structure/types/manage/%s/display/%s', $bundle, $viewMode);
    $page = $this->getSession()->getPage();
    $this->drupalGet($path);
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    if ($allowCustom) {
      $page->checkField('layout[allow_custom]');
      $page->pressButton('Save');
    }
  }

  /**
   * Disables Layout Builder using the UI.
   *
   * @param string $bundle
   *   The bundle that Layout Builder is being disabled on.
   * @param string $viewMode
   *   The view mode that Layout Builder is being disabled on.
   */
  protected function disableLayoutBuilderFromUi(string $bundle, string $viewMode): void {
    $path = sprintf('admin/structure/types/manage/%s/display/%s', $bundle, $viewMode);
    $page = $this->getSession()->getPage();
    $this->drupalGet($path);
    $page->uncheckField('layout[enabled]');
    $page->pressButton('Save');
    $page->pressButton('Confirm');
  }

}
