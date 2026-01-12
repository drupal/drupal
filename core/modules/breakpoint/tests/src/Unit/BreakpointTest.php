<?php

declare(strict_types=1);

namespace Drupal\Tests\breakpoint\Unit;

use Drupal\breakpoint\Breakpoint;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\breakpoint\Breakpoint.
 */
#[CoversClass(Breakpoint::class)]
#[Group('Breakpoint')]
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
  protected $pluginDefinition = [
    'id' => 'breakpoint',
  ];

  /**
   * The breakpoint under test.
   *
   * @var \Drupal\breakpoint\Breakpoint
   */
  protected $breakpoint;

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->stringTranslation = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');
  }

  /**
   * Sets up the breakpoint defaults.
   */
  protected function setupBreakpoint(): void {
    $this->breakpoint = new Breakpoint([], $this->pluginId, $this->pluginDefinition);
    $this->breakpoint->setStringTranslation($this->stringTranslation);
  }

  /**
   * Tests get label.
   */
  public function testGetLabel(): void {
    $this->pluginDefinition['label'] = 'Test label';
    $this->setupBreakpoint();
    $this->assertEquals(new TranslatableMarkup('Test label', [], ['context' => 'breakpoint'], $this->stringTranslation), $this->breakpoint->getLabel());
  }

  /**
   * Tests get weight.
   */
  public function testGetWeight(): void {
    $this->pluginDefinition['weight'] = '4';
    $this->setupBreakpoint();
    // Assert that the type returned in an integer.
    $this->assertSame(4, $this->breakpoint->getWeight());
  }

  /**
   * Tests get media query.
   */
  public function testGetMediaQuery(): void {
    $this->pluginDefinition['mediaQuery'] = 'only screen and (min-width: 1220px)';
    $this->setupBreakpoint();
    $this->assertEquals('only screen and (min-width: 1220px)', $this->breakpoint->getMediaQuery());
  }

  /**
   * Tests get multipliers.
   */
  public function testGetMultipliers(): void {
    $this->pluginDefinition['multipliers'] = ['1x', '2x'];
    $this->setupBreakpoint();
    $this->assertSame(['1x', '2x'], $this->breakpoint->getMultipliers());
  }

  /**
   * Tests get provider.
   */
  public function testGetProvider(): void {
    $this->pluginDefinition['provider'] = 'Breakpoint';
    $this->setupBreakpoint();
    $this->assertEquals('Breakpoint', $this->breakpoint->getProvider());
  }

  /**
   * Tests get group.
   */
  public function testGetGroup(): void {
    $this->pluginDefinition['group'] = 'Breakpoint';
    $this->setupBreakpoint();
    $this->assertEquals('Breakpoint', $this->breakpoint->getGroup());
  }

}
