<?php

/**
 * @file
 * Contains \Drupal\php\Tests\Condition\PhpConditionTest.
 */

namespace Drupal\php\Tests\Condition;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the php condition.
 */
class PhpConditionTest extends DrupalUnitTestBase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'php');

  public static function getInfo() {
    return array(
      'name' => 'PHP Condition Plugin',
      'description' => 'Tests that the PHP Condition, provided by the php module, is working properly.',
      'group' => 'Condition API',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->manager = $this->container->get('plugin.manager.condition');
  }

  /**
   * Tests conditions.
   */
  public function testConditions() {
    // Grab the PHP condition and configure it to check against a php snippet.
    $condition = $this->manager->createInstance('php')
      ->setConfig('php', '<?php return TRUE; ?>');
    $this->assertTrue($condition->execute(), 'PHP condition passes as expected.');
    // Check for the proper summary.
    $this->assertEqual($condition->summary(), 'When the given PHP evaluates as TRUE.');

    // Set the PHP snippet to return FALSE.
    $condition->setConfig('php', '<?php return FALSE; ?>');
    $this->assertFalse($condition->execute(), 'PHP condition fails as expected.');

    // Negate the condition.
    $condition->setConfig('negate', TRUE);
    // Check for the proper summary.
    $this->assertEqual($condition->summary(), 'When the given PHP evaluates as FALSE.');

    // Reverse the negation.
    $condition->setConfig('negate', FALSE);
    // Set and empty snippet.
    $condition->setConfig('php', FALSE);
    // Check for the proper summary.
    $this->assertEqual($condition->summary(), 'No PHP code has been provided.');
  }

}
