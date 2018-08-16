<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests ContentEntityNullStorage entity query support.
 *
 * @see \Drupal\Core\Entity\ContentEntityNullStorage
 * @see \Drupal\Core\Entity\Query\Null\Query
 *
 * @group Entity
 */
class ContentEntityNullStorageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'contact', 'user'];

  /**
   * Tests using entity query with ContentEntityNullStorage.
   *
   * @see \Drupal\Core\Entity\Query\Null\Query
   */
  public function testEntityQuery() {
    $this->assertSame(0, \Drupal::entityQuery('contact_message')->count()->execute(), 'Counting a null storage returns 0.');
    $this->assertSame([], \Drupal::entityQuery('contact_message')->execute(), 'Querying a null storage returns an empty array.');
    $this->assertSame([], \Drupal::entityQuery('contact_message')->condition('contact_form', 'test')->execute(), 'Querying a null storage returns an empty array and conditions are ignored.');
    $this->assertSame([], \Drupal::entityQueryAggregate('contact_message')->aggregate('name', 'AVG')->execute(), 'Aggregate querying a null storage returns an empty array');

  }

  /**
   * Tests deleting a contact form entity via a configuration import.
   *
   * @see \Drupal\Core\Entity\Event\BundleConfigImportValidate
   */
  public function testDeleteThroughImport() {
    $this->installConfig(['system']);
    $contact_form = ContactForm::create(['id' => 'test']);
    $contact_form->save();

    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.sync'),
      $this->container->get('config.storage'),
      $this->container->get('config.manager')
    );
    $config_importer = new ConfigImporter(
      $storage_comparer->createChangelist(),
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation')
    );

    // Delete the contact message in sync.
    $sync = $this->container->get('config.storage.sync');
    $sync->delete($contact_form->getConfigDependencyName());

    // Import.
    $config_importer->reset()->import();
    $this->assertNull(ContactForm::load($contact_form->id()), 'The contact form has been deleted.');
  }

}
