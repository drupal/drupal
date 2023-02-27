<?php

namespace Drupal\Tests\editor\Kernel;

use Drupal\editor\Form\EditorImageDialog;
use Drupal\editor\Form\EditorLinkDialog;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the deprecations in Drupal\editor.
 *
 * @group editor
 * @group legacy
 */
class EditorDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['editor'];

  /**
   * Tests the deprecation of the Drupal\editor\Form\EditorLinkDialog class.
   *
   * @see EditorLinkDialog
   */
  public function testEditorLinkDialog(): void {
    $this->expectDeprecation('Drupal\editor\Form\EditorLinkDialog is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/project/drupal/issues/3291493');
    new EditorLinkDialog();
  }

  /**
   * Tests the deprecation of the Drupal\editor\Form\EditorImageDialog class.
   *
   * @see EditorImageDialog
   */
  public function testEditorImageDialog(): void {
    $this->expectDeprecation('Drupal\editor\Form\EditorImageDialog is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/project/drupal/issues/3291493');
    new EditorImageDialog($this->createMock('\Drupal\file\FileStorage'));
  }

}
