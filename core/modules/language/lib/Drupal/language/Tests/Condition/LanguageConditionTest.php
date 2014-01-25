<?php

/**
 * @file
 * Contains \Drupal\language\Tests\Condition\LanguageConditionTest.
 */

namespace Drupal\language\Tests\Condition;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Language\Language;

/**
 * Tests the language condition.
 */
class LanguageConditionTest extends DrupalUnitTestBase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'language');

  public static function getInfo() {
    return array(
      'name' => 'Language Condition Plugin',
      'description' => 'Tests that the language condition, provided by the language module, is working properly.',
      'group' => 'Condition API',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->installConfig(array('language'));

    // Setup English.
    language_save(language_default());

    // Setup Italian.
    $language = new Language(array(
      'id' => 'it',
      'name' => 'Italian',
      'direction' => '0',
    ));
    language_save($language);

    $this->manager = $this->container->get('plugin.manager.condition');
  }

  /**
   * Test the language condition.
   */
  public function testConditions() {
    // Grab the language condition and configure it to check the content
    // language.
    $language = language_load('en');
    $condition = $this->manager->createInstance('language')
      ->setConfig('langcodes', array('en' => 'en', 'it' => 'it'))
      ->setContextValue('language', $language);
    $this->assertTrue($condition->execute(), 'Language condition passes as expected.');
    // Check for the proper summary.
    $this->assertEqual($condition->summary(), 'The language is English, Italian.');

    // Change to Italian only.
    $condition->setConfig('langcodes', array('it' => 'it'));
    $this->assertFalse($condition->execute(), 'Language condition fails as expected.');
    // Check for the proper summary.
    $this->assertEqual($condition->summary(), 'The language is Italian.');

    // Negate the condition
    $condition->setConfig('negate', TRUE);
    $this->assertTrue($condition->execute(), 'Language condition passes as expected.');
    // Check for the proper summary.
    $this->assertEqual($condition->summary(), 'The language is not Italian.');

    // Change the default language to Italian.
    $language = language_load('it');

    $condition = $this->manager->createInstance('language')
      ->setConfig('langcodes', array('en' => 'en', 'it' => 'it'))
      ->setContextValue('language', $language);

    $this->assertTrue($condition->execute(), 'Language condition passes as expected.');
    // Check for the proper summary.
    $this->assertEqual($condition->summary(), 'The language is English, Italian.');

    // Change to Italian only.
    $condition->setConfig('langcodes', array('it' => 'it'));
    $this->assertTrue($condition->execute(), 'Language condition passes as expected.');
    // Check for the proper summary.
    $this->assertEqual($condition->summary(), 'The language is Italian.');

    // Negate the condition
    $condition->setConfig('negate', TRUE);
    $this->assertFalse($condition->execute(), 'Language condition fails as expected.');
    // Check for the proper summary.
    $this->assertEqual($condition->summary(), 'The language is not Italian.');
  }

}
