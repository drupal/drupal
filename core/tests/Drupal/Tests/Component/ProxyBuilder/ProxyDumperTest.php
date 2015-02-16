<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\ProxyBuilder\ProxyDumperTest.
 */

namespace Drupal\Tests\Component\ProxyBuilder;

use Drupal\Component\ProxyBuilder\ProxyDumper;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @coversDefaultClass \Drupal\Component\ProxyBuilder\ProxyDumper
 * @group proxy_builder
 */
class ProxyDumperTest extends UnitTestCase {

  /**
   * The mocked proxy builder.
   *
   * @var \Drupal\Component\ProxyBuilder\ProxyBuilder|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $proxyBuilder;

  /**
   * The tested proxy dumper.
   *
   * @var \Drupal\Component\ProxyBuilder\ProxyDumper
   */
  protected $proxyDumper;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->proxyBuilder = $this->getMockBuilder('Drupal\Component\ProxyBuilder\ProxyBuilder')
      ->disableOriginalConstructor()
      ->setMethods(['build'])
      ->getMock();
    $this->proxyDumper = new ProxyDumper($this->proxyBuilder);
  }

  /**
   * @dataProvider providerTestIsProxyCandidate
   * @covers ::isProxyCandidate
   */
  public function testIsProxyCandidate(Definition $definition, $expected) {
    $this->assertSame($expected, $this->proxyDumper->isProxyCandidate($definition));
  }

  public function providerTestIsProxyCandidate() {
    // Not lazy service.
    $data = [];
    $definition = new Definition('Drupal\Tests\Component\ProxyBuilder\TestService');
    $data[] = [$definition, FALSE];
    // Not existing service.
    $definition = new Definition('Drupal\Tests\Component\ProxyBuilder\TestNotExistingService');
    $definition->setLazy(TRUE);
    $data[] = [$definition, FALSE];
    // Existing and lazy service.
    $definition = new Definition('Drupal\Tests\Component\ProxyBuilder\TestService');
    $definition->setLazy(TRUE);
    $data[] = [$definition, TRUE];

    return $data;
  }

  public function testGetProxyFactoryCode() {
    $definition = new Definition('Drupal\Tests\Component\ProxyBuilder\TestService');
    $definition->setLazy(TRUE);

    $result = $this->proxyDumper->getProxyFactoryCode($definition, 'test_service');

    $expected = <<<'EOS'
        if ($lazyLoad) {
            return $this->services['test_service'] = new Drupal_Tests_Component_ProxyBuilder_TestService_Proxy($this, 'test_service');
        }

EOS;

    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::getProxyCode
   */
  public function testGetProxyCode() {
    $definition = new Definition('Drupal\Tests\Component\ProxyBuilder\TestService');
    $definition->setLazy(TRUE);

    $class = 'class Drupal_Tests_Component_ProxyBuilder_TestService_Proxy {}';
    $this->proxyBuilder->expects($this->once())
      ->method('build')
      ->with('Drupal\Tests\Component\ProxyBuilder\TestService')
      ->willReturn($class);

    $result = $this->proxyDumper->getProxyCode($definition);
    $this->assertEquals($class, $result);
  }

  /**
   * @covers ::getProxyCode
   */
  public function testGetProxyCodeWithSameClassMultipleTimes() {
    $definition = new Definition('Drupal\Tests\Component\ProxyBuilder\TestService');
    $definition->setLazy(TRUE);

    $class = 'class Drupal_Tests_Component_ProxyBuilder_TestService_Proxy {}';
    $this->proxyBuilder->expects($this->once())
      ->method('build')
      ->with('Drupal\Tests\Component\ProxyBuilder\TestService')
      ->willReturn($class);

    $result = $this->proxyDumper->getProxyCode($definition);
    $this->assertEquals($class, $result);

    $result = $this->proxyDumper->getProxyCode($definition);
    $this->assertEquals('', $result);
  }

}

class TestService {

}
