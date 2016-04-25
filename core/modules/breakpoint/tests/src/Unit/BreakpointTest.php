<?php

namespace Drupal\Tests\breakpoint\Unit;

use Drupal\breakpoint\Breakpoint;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * @coversDefaultClass \Drupal\breakpoint\Breakpoint
 * @group Breakpoint
 */
class BreakpointTest extends UnitTestCase {

  /**
   * The used plugin ID.
   *
   * @var string
   */
  protected $pluginId = 'breakpoint';

  /**
   * The used plugin definition.
   *
   * @var array
   */
  protected $pluginDefinition = array(
    'id' => 'breakpoint',
  );

  /**
   * The breakpoint under test.
   *
   * @var \Drupal\breakpoint\Breakpoint
   */
  protected $breakpoint;

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stringTranslation;

  protected function setUp() {
    parent::setUp();

    $this->stringTranslation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');
  }

  /**
   * Sets up the breakpoint defaults.
   */
  protected function setupBreakpoint() {
    $this->breakpoint = new Breakpoint(array(), $this->pluginId, $this->pluginDefinition);
    $this->breakpoint->setStringTranslation($this->stringTranslation);
  }

  /**
   * @covers ::getLabel
   */
  public function testGetLabel() {
    $this->pluginDefinition['label'] = 'Test label';
    $this->setupBreakpoint();
    $this->assertEquals(new TranslatableMarkup('Test label', array(), array('context' => 'breakpoint'), $this->stringTranslation), $this->breakpoint->getLabel());
  }

  /**
   * @covers ::getWeight
   */
  public function testGetWeight() {
    $this->pluginDefinition['weight'] = '4';
    $this->setupBreakpoint();
    // Assert that the type returned in an integer.
    $this->assertSame(4, $this->breakpoint->getWeight());
  }

  /**
   * @covers ::getMediaQuery
   */
  public function testGetMediaQuery() {
    $this->pluginDefinition['mediaQuery'] = 'only screen and (min-width: 1220px)';
    $this->setupBreakpoint();
    $this->assertEquals('only screen and (min-width: 1220px)', $this->breakpoint->getMediaQuery());
  }

  /**
   * @covers ::getMultipliers
   */
  public function testGetMultipliers() {
    $this->pluginDefinition['multipliers'] = array('1x', '2x');
    $this->setupBreakpoint();
    $this->assertSame(array('1x', '2x'), $this->breakpoint->getMultipliers());
  }

  /**
   * @covers ::getProvider
   */
  public function testGetProvider() {
    $this->pluginDefinition['provider'] = 'Breakpoint';
    $this->setupBreakpoint();
    $this->assertEquals('Breakpoint', $this->breakpoint->getProvider());
  }

  /**
   * @covers ::getGroup
   */
  public function testGetGroup() {
    $this->pluginDefinition['group'] = 'Breakpoint';
    $this->setupBreakpoint();
    $this->assertEquals('Breakpoint', $this->breakpoint->getGroup());
  }

}
