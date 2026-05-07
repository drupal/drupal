<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the deprecation of global rendering functions.
 */
#[Group("Render")]
#[Group("legacy")]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class ProceduralApiDeprecationTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    include_once $this->root . '/core/includes/common.inc';
  }

  /**
   * Tests the deprecation of the global hide() function.
   */
  public function testHideDeprecation(): void {
    $element = [];
    $this->expectUserDeprecationMessage("The global hide() function is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. To hide form elements, use ['#access'] = FALSE. For render elements, use ['#printed'] = TRUE. See https://www.drupal.org/node/3261271");
    $this->assertEquals(['#printed' => TRUE], hide($element));
  }

  /**
   * Tests the deprecation of the global show() function.
   */
  public function testShowDeprecation(): void {
    $element = [];
    $this->expectUserDeprecationMessage("The global show() function is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. To show form elements, use ['#access'] = TRUE. For render elements, use ['#printed'] = FALSE. See https://www.drupal.org/node/3261271");
    $this->assertEquals(['#printed' => FALSE], show($element));
  }

}
