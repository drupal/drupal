<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\Plugin\argument_default\QueryParameterTest.
 */

namespace Drupal\Tests\views\Unit\Plugin\argument_default;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\argument_default\QueryParameter;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\argument_default\QueryParameter
 * @group views
 */
class QueryParameterTest extends UnitTestCase {

  /**
   * Test the getArgument() method.
   *
   * @covers ::getArgument
   * @dataProvider providerGetArgument
   */
  public function testGetArgument($options, Request $request, $expected) {
    $view = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
    $view->setRequest($request);
    $display_plugin = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $raw = new QueryParameter(array(), 'query_parameter', array());
    $raw->init($view, $display_plugin, $options);
    $this->assertEquals($expected, $raw->getArgument());
  }

  /**
   * Provides data for testGetArgument().
   *
   * @return array
   *   An array of test data, with the following entries:
   *   - first entry: the options for the plugin.
   *   - second entry: the request object to test with.
   *   - third entry: the expected default argument value.
   */
  public function providerGetArgument() {
    $data = array();

    $single[] = array(
      'query_param' => 'test',
    );
    $single[] = new Request(array('test' => 'data'));
    $single[] = 'data';
    $data[] = $single;

    $single[] = array(
      'query_param' => 'test',
      'multiple' => 'AND'
    );
    $single[] = new Request(array('test' => array('data1', 'data2')));
    $single[] = 'data1+data2';
    $data[] = $single;

    $single[] = array(
      'query_param' => 'test',
      'multiple' => 'OR'
    );
    $single[] = new Request(array('test' => array('data1', 'data2')));
    $single[] = 'data1,data2';
    $data[] = $single;

    $single[] = array(
      'query_param' => 'test',
      'fallback' => 'blub',
    );
    $single[] = new Request(array());
    $single[] = 'blub';
    $data[] = $single;

    return $data;
  }

}

