<?php

namespace Drupal\Tests\language\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the language select widget.
 *
 * @group language
 */
class LanguageSelectWidgetTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'language',
    'user',
    'system',
  ];

  /**
   * The entity form display.
   *
   * @var \Drupal\Core\Entity\Entity\EntityFormDisplay
   */
  protected $entityFormDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');

    $storage = $this->container->get('entity_type.manager')->getStorage('entity_form_display');
    $this->entityFormDisplay = $storage->create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
  }

  /**
   * Tests the widget with the locked languages.
   */
  public function testWithIncludedLockedLanguage() {
    $this->entityFormDisplay->setComponent('langcode', [
      'type' => 'language_select',
    ])->save();
    $entity = EntityTest::create(['name' => $this->randomString()]);
    $form = $this->container->get('entity.form_builder')->getForm($entity);
    $options = array_keys($form['langcode']['widget'][0]['value']['#options']);
    $this->assertSame(['en', 'und', 'zxx'], $options);
  }

  /**
   * Test the widget without the locked languages.
   */
  public function testWithoutIncludedLockedLanguage() {
    $this->entityFormDisplay->setComponent('langcode', [
      'type' => 'language_select',
      'settings' => ['include_locked' => FALSE],
    ])->save();
    $entity = EntityTest::create(['name' => $this->randomString()]);
    $form = $this->container->get('entity.form_builder')->getForm($entity);
    $options = array_keys($form['langcode']['widget'][0]['value']['#options']);
    $this->assertSame(['en'], $options);
  }

}
