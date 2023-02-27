<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Form\EditorMediaDialog;

/**
 * Tests the deprecations in Drupal\media.
 *
 * @group media
 * @group legacy
 */
class MediaDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media'];

  /**
   * Tests the deprecation of the Drupal\media\Form\EditorMediaDialog class.
   *
   * @see EditorMediaDialog
   */
  public function testEditorLinkDialog(): void {
    $this->expectDeprecation('Drupal\media\Form\EditorMediaDialog is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/project/drupal/issues/3291493');
    new EditorMediaDialog($this->createMock('\Drupal\Core\Entity\EntityRepository'), $this->createMock('\Drupal\Core\Entity\EntityDisplayRepository'));
  }

}
