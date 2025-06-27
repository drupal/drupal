<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\RenderElementBase
 * @group Render
 */
class ModernRenderElementTest extends UnitTestCase {

  public function testChildren(): void {
    $factory = $this->createMock(FactoryInterface::class);
    $elementInfoManager = new class ($factory) extends ElementInfoManager {

      public function __construct(protected $factory) {}

    };
    $factory->expects($this->any())
      ->method('createInstance')
      ->willReturnCallback(fn () => new Textfield([], '', NULL, $elementInfoManager));
    // If the type is not given ::fromRenderable presumes "form" and uses the
    // plugin discovery to find which class provides the form element. This
    // test does not set up discovery so some type must be provided.
    $element = ['#type' => 'ignored by the mock factory'];
    $elementObject = $elementInfoManager->fromRenderable($element);
    for ($i = 0; $i <= 2; $i++) {
      $child = [
        '#type' => 'ignored by the mock factory',
        '#test' => $i,
      ];
      $elementObject->addChild("test$i", $child);
      // addChild() takes the $child render array by reference and stores a
      // reference to it in the render object. To avoid modifying the
      // previously created render object when reusing the $child variable,
      // unset() it to break the reference before reassigning.
      unset($child);
    }
    foreach ([1 => ['test0', 'test1', 'test2'], 2 => ['test0', 'test2']] as $delta => $expectedChildrenKeys) {
      $i = 0;
      foreach ($elementObject->getChildren() as $name => $child) {
        $this->assertSame($name, "test$i");
        $this->assertSame($i, $child->test);
        $i += $delta;
      }
      $this->assertSame(Element::children($elementObject->toRenderable()), $expectedChildrenKeys);
      // The first iteration tests removing an existing child. The second
      // iteration tests removing a nonexistent child.
      $elementObject->removeChild('test1');
    }
  }

}
