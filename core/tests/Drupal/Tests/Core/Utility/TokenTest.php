<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Utility\TokenTest.
 */

namespace Drupal\Tests\Core\Utility;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Utility\Token;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Utility\Token
 */
class TokenTest extends UnitTestCase {

  /**
   * The cache used for testing.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * The language manager used for testing.
   *
   * @var \Drupal\Core\Language\LanguageManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The module handler service used for testing.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The token service under test.
   *
   * @var \Drupal\Core\Utility\Token|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\Tests\Core\Utility\Token unit test',
      'group' => 'System',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->cache = $this->getMock('\Drupal\Core\Cache\CacheBackendInterface');

    $this->languageManager = $this->getMockBuilder('\Drupal\Core\Language\LanguageManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->moduleHandler = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');

    $this->token = new Token($this->moduleHandler, $this->cache, $this->languageManager);
  }

  /**
   * @covers ::getInfo
   */
  public function testGetInfo() {
    $token_info = array(
      'types' => array(
        'foo' => array(
          'name' => $this->randomName(),
        ),
      ),
    );

    $language = $this->getMock('\Drupal\Core\Language\Language');
    $language->id = $this->randomName();

    $this->languageManager->expects($this->once())
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_CONTENT)
      ->will($this->returnValue($language));

    // The persistent cache must only be hit once, after which the info is
    // cached statically.
    $this->cache->expects($this->once())
      ->method('get');
    $this->cache->expects($this->once())
      ->method('set')
      ->with('token_info:' . $language->id, $token_info);

    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('token_info')
      ->will($this->returnValue($token_info));
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('token_info', $token_info);

    // Get the information for the first time. The cache should be checked, the
    // hooks invoked, and the info should be set to the cache should.
    $this->token->getInfo();
    // Get the information for the second time. The data must be returned from
    // the static cache, so the persistent cache must not be accessed and the
    // hooks must not be invoked.
    $this->token->getInfo();
  }

}
