<?php

namespace Drupal\Tests\system\Kernel\Form;

use Drupal\form_test\Form\FormTestFileForm;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the 'file' form element.
 *
 * @group Form
 */
class FileElementTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * Tests that file elements are built and processed correctly.
   */
  public function testFileElement() {
    $form = $this->container->get('form_builder')
      ->getForm(FormTestFileForm::class);

    $this->assertSame('file', $form['file']['#type']);
    $this->assertTrue($form['file']['#multiple']);
    $this->assertContains('some-class', $form['file']['#attributes']['class']);
  }

}
