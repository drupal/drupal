<?php

namespace Drupal\system\Tests\Plugin\Condition;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\simpletest\KernelTestBase;
use Drupal\system\Tests\Routing\MockAliasManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests that the Request Path Condition, provided by the system module, is
 * working properly.
 *
 * @group Plugin
 */
class RequestPathTest extends KernelTestBase {

  /**
   * The condition plugin manager under test.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $pluginManager;

  /**
   * The path alias manager used for testing.
   *
   * @var \Drupal\system\Tests\Routing\MockAliasManager
   */
  protected $aliasManager;

  /**
   * The request stack used for testing.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user', 'field', 'path');

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentPath;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', array('sequences'));

    $this->pluginManager = $this->container->get('plugin.manager.condition');

    // Set a mock alias manager in the container.
    $this->aliasManager = new MockAliasManager();
    $this->container->set('path.alias_manager', $this->aliasManager);

    // Set the test request stack in the container.
    $this->requestStack = new RequestStack();
    $this->container->set('request_stack', $this->requestStack);

    $this->currentPath = new CurrentPathStack($this->requestStack);
    $this->container->set('path.current', $this->currentPath);
  }

  /**
   * Tests the request path condition.
   */
  public function testConditions() {

    // Get the request path condition and test and configure it to check against
    // different patterns and requests.

    $pages = "/my/pass/page\r\n/my/pass/page2\r\n/foo";

    $request = Request::create('/my/pass/page2');
    $this->requestStack->push($request);

    /* @var \Drupal\system\Plugin\Condition\RequestPath $condition */
    $condition = $this->pluginManager->createInstance('request_path');
    $condition->setConfig('pages', $pages);

    $this->aliasManager->addAlias('/my/pass/page2', '/my/pass/page2');

    $this->assertTrue($condition->execute(), 'The request path matches a standard path');
    $this->assertEqual($condition->summary(), 'Return true on the following pages: /my/pass/page, /my/pass/page2, /foo', 'The condition summary matches for a standard path');

    // Test an aliased path.
    $this->currentPath->setPath('/my/aliased/page', $request);
    $this->requestStack->pop();
    $this->requestStack->push($request);

    $this->aliasManager->addAlias('/my/aliased/page', '/my/pass/page');

    $this->assertTrue($condition->execute(), 'The request path matches an aliased path');
    $this->assertEqual($condition->summary(), 'Return true on the following pages: /my/pass/page, /my/pass/page2, /foo', 'The condition summary matches for an aliased path');

    // Test a wildcard path.
    $this->aliasManager->addAlias('/my/pass/page3', '/my/pass/page3');
    $this->currentPath->setPath('/my/pass/page3', $request);
    $this->requestStack->pop();
    $this->requestStack->push($request);

    $condition->setConfig('pages', '/my/pass/*');

    $this->assertTrue($condition->evaluate(), 'The system_path my/pass/page3 passes for wildcard paths.');
    $this->assertEqual($condition->summary(), 'Return true on the following pages: /my/pass/*', 'The condition summary matches for a wildcard path');

    // Test a missing path.
    $this->requestStack->pop();
    $this->requestStack->push($request);
    $this->currentPath->setPath('/my/fail/page4', $request);

    $condition->setConfig('pages', '/my/pass/*');

    $this->aliasManager->addAlias('/my/fail/page4', '/my/fail/page4');

    $this->assertFalse($condition->evaluate(), 'The system_path /my/pass/page4 fails for a missing path.');

  }
}
