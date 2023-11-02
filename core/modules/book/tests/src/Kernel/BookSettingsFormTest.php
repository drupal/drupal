<?php

namespace Drupal\Tests\book\Kernel;

use Drupal\book\Form\BookSettingsForm;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * @covers \Drupal\book\Form\BookSettingsForm
 * @group book
 */
class BookSettingsFormTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'book',
    'field',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['book', 'node']);
    $this->createContentType(['type' => 'chapter']);
    $this->createContentType(['type' => 'page']);
  }

  /**
   * Tests that submitted values are processed and saved correctly.
   */
  public function testConfigValuesSavedCorrectly(): void {
    $form_state = new FormState();
    $form_state->setValues([
      'book_allowed_types' => ['page', 'chapter', ''],
      'book_child_type' => 'page',
    ]);
    $this->container->get('form_builder')->submitForm(BookSettingsForm::class, $form_state);

    $config = $this->config('book.settings');
    $this->assertSame(['chapter', 'page'], $config->get('allowed_types'));
    $this->assertSame('page', $config->get('child_type'));
  }

}
