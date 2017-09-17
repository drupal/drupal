<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Template\TwigSandboxTest.
 */

namespace Drupal\Tests\Core\Template;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Template\TwigSandboxPolicy;
use Drupal\Core\Template\Loader\StringLoader;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the twig sandbox policy.
 *
 * @group Template
 * @group legacy
 *
 * @coversDefaultClass \Drupal\Core\Template\TwigSandboxPolicy
 */
class TwigSandboxTest extends UnitTestCase {

  /**
   * The Twig environment loaded with the sandbox extension.
   *
   * @var \Twig_Environment
   */
  protected $twig;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $loader = new StringLoader();
    $this->twig = new \Twig_Environment($loader);
    $policy = new TwigSandboxPolicy();
    $sandbox = new \Twig_Extension_Sandbox($policy, TRUE);
    $this->twig->addExtension($sandbox);
  }

  /**
   * Tests that dangerous methods cannot be called in entity objects.
   *
   * @dataProvider getTwigEntityDangerousMethods
   */
  public function testEntityDangerousMethods($template) {
    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $this->setExpectedException(\Twig_Sandbox_SecurityError::class);
    $this->twig->render($template, ['entity' => $entity]);
  }

  /**
   * Data provider for ::testEntityDangerousMethods.
   *
   * @return array
   */
  public function getTwigEntityDangerousMethods() {
    return [
      ['{{ entity.delete }}'],
      ['{{ entity.save }}'],
      ['{{ entity.create }}'],
    ];
  }

  /**
   * Tests that white listed classes can be extended.
   */
  public function testExtendedClass() {
    $this->assertEquals(' class=&quot;kitten&quot;', $this->twig->render('{{ attribute.addClass("kitten") }}', ['attribute' => new TestAttribute()]));
  }

  /**
   * Tests that prefixed methods can be called from within Twig templates.
   *
   * Currently "get", "has", and "is" are the only allowed prefixes.
   */
  public function testEntitySafePrefixes() {
    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->atLeastOnce())
      ->method('hasLinkTemplate')
      ->with('test')
      ->willReturn(TRUE);
    $result = $this->twig->render('{{ entity.hasLinkTemplate("test") }}', ['entity' => $entity]);
    $this->assertTrue((bool) $result, 'Sandbox policy allows has* functions to be called.');

    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->atLeastOnce())
      ->method('isNew')
      ->willReturn(TRUE);
    $result = $this->twig->render('{{ entity.isNew }}', ['entity' => $entity]);
    $this->assertTrue((bool) $result, 'Sandbox policy allows is* functions to be called.');

    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->atLeastOnce())
      ->method('getEntityType')
      ->willReturn('test');
    $result = $this->twig->render('{{ entity.getEntityType }}', ['entity' => $entity]);
    $this->assertEquals($result, 'test', 'Sandbox policy allows get* functions to be called.');
  }

  /**
   * Tests that valid methods can be called from within Twig templates.
   *
   * Currently the following methods are whitelisted: id, label, bundle, and
   * get.
   */
  public function testEntitySafeMethods() {
    $entity = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->atLeastOnce())
      ->method('get')
      ->with('title')
      ->willReturn('test');
    $result = $this->twig->render('{{ entity.get("title") }}', ['entity' => $entity]);
    $this->assertEquals($result, 'test', 'Sandbox policy allows get() to be called.');

    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn('1234');
    $result = $this->twig->render('{{ entity.id }}', ['entity' => $entity]);
    $this->assertEquals($result, '1234', 'Sandbox policy allows get() to be called.');

    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->atLeastOnce())
      ->method('label')
      ->willReturn('testing');
    $result = $this->twig->render('{{ entity.label }}', ['entity' => $entity]);
    $this->assertEquals($result, 'testing', 'Sandbox policy allows get() to be called.');

    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->atLeastOnce())
      ->method('bundle')
      ->willReturn('testing');
    $result = $this->twig->render('{{ entity.bundle }}', ['entity' => $entity]);
    $this->assertEquals($result, 'testing', 'Sandbox policy allows get() to be called.');
  }

  /**
   * Tests that safe methods inside Url objects can be called.
   */
  public function testUrlSafeMethods() {
    $url = $this->getMockBuilder('Drupal\Core\Url')
      ->disableOriginalConstructor()
      ->getMock();
    $url->expects($this->once())
      ->method('toString')
      ->willReturn('http://kittens.cat/are/cute');
    $result = $this->twig->render('{{ url.toString }}', ['url' => $url]);
    $this->assertEquals($result, 'http://kittens.cat/are/cute', 'Sandbox policy allows toString() to be called.');
  }

}

class TestAttribute extends Attribute {}
