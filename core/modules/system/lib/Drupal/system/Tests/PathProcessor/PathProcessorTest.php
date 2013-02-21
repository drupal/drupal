<?php

/**
 * @file
 * Contains Drupal\system\Tests\PathProcessor\PathProcessorTest.
 */

namespace Drupal\system\Tests\PathProcessor;

use Drupal\system\Tests\Path\PathUnitTestBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Path\Path;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\PathProcessor\PathProcessorDecode;
use Drupal\Core\PathProcessor\PathProcessorFront;
use Drupal\Core\PathProcessor\PathProcessorManager;
use Drupal\language\HttpKernel\PathProcessorLanguage;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests path processor functionality.
 */
class PathProcessorTest extends PathUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => t('Path Processor Unit Tests'),
      'description' => t('Tests processing of the inbound path.'),
      'group' => t('Path API'),
    );
  }

  public function setUp() {
    parent::setUp();
    $this->fixtures = new PathProcessorFixtures();
  }

  /**
   * Tests resolving the inbound path to the system path.
   */
  function testProcessInbound() {

    // Ensure all tables needed for these tests are created.
    $connection = Database::getConnection();
    $this->fixtures->createTables($connection);

    // Create dependecies needed by various path processors.
    $alias_manager = new AliasManager($connection, $this->container->get('state'), $this->container->get('language_manager'));
    $module_handler = $this->container->get('module_handler');

    // Create the processors.
    $alias_processor = new PathProcessorAlias($alias_manager);
    $decode_processor = new PathProcessorDecode();
    $front_processor = new PathProcessorFront($this->container->get('config.factory'));
    $language_processor = new PathProcessorLanguage($module_handler);

    // Add a url alias for testing the alias-based processor.
    $path_crud = new Path($connection, $alias_manager);
    $path_crud->save('user/1', 'foo');

    // Add a language for testing the language-based processor.
    $module_handler->setModuleList(array('language' => 'core/modules/language/language.module'));
    $module_handler->load('language');
    $language = new \stdClass();
    $language->langcode = 'fr';
    $language->name = 'French';
    language_save($language);

    // First, test the processor manager with the processors in the incorrect
    // order. The alias processor will run before the language processor, meaning
    // aliases will not be found.
    $priorities = array(
      1000 => $alias_processor,
      500 => $decode_processor,
      300 => $front_processor,
      200 => $language_processor,
    );

    // Create the processor manager and add the processors.
    $processor_manager = new PathProcessorManager();
    foreach ($priorities as $priority => $processor) {
      $processor_manager->addInbound($processor, $priority);
    }

    // Test resolving the French homepage using the incorrect processor order.
    $test_path = 'fr';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEqual($processed, '', 'Processing in the incorrect order fails to resolve the system path from the empty path');

    // Test resolving an existing alias using the incorrect processor order.
    $test_path = 'fr/foo';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEqual($processed, 'foo', 'Processing in the incorrect order fails to resolve the system path from an alias');

    // Now create a new processor manager and add the processors, this time in
    // the correct order.
    $processor_manager = new PathProcessorManager();
    $priorities = array(
      1000 => $decode_processor,
      500 => $language_processor,
      300 => $front_processor,
      200 => $alias_processor,
    );
    foreach ($priorities as $priority => $processor) {
      $processor_manager->addInbound($processor, $priority);
    }

    // Test resolving the French homepage using the correct processor order.
    $test_path = 'fr';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEqual($processed, 'user', 'Processing in the correct order resolves the system path from the empty path.');

    // Test resolving an existing alias using the correct processor order.
    $test_path = 'fr/foo';
    $request = Request::create($test_path);
    $processed = $processor_manager->processInbound($test_path, $request);
    $this->assertEqual($processed, 'user/1', 'Processing in the correct order resolves the system path from an alias.');
  }

}
