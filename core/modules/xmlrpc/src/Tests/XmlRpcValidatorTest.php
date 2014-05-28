<?php

/**
 * @file
 * Definition of Drupal\xmlrpc\Tests\XmlRpcValidatorTest.
 */

namespace Drupal\xmlrpc\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * XML-RPC validator1 specification.
 */
class XmlRpcValidatorTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('xmlrpc', 'xmlrpc_test');

  public static function getInfo() {
    return array(
      'name' => 'XML-RPC validator',
      'description' => 'See <a href="http://www.xmlrpc.com/validator1Docs">the xmlrpc validator1 specification</a>.',
      'group' => 'XML-RPC',
    );
  }

  /**
   * Run validator1 tests.
   */
  function testValidator() {
    $xml_url = url('xmlrpc.php', array('absolute' => TRUE));
    srand();
    mt_srand();

    $array_1 = array(array('curly' => mt_rand(-100, 100)),
                  array('curly' => mt_rand(-100, 100)),
                  array('larry' => mt_rand(-100, 100)),
                  array('larry' => mt_rand(-100, 100)),
                  array('moe' => mt_rand(-100, 100)),
                  array('moe' => mt_rand(-100, 100)),
                  array('larry' => mt_rand(-100, 100)));
    shuffle($array_1);
    $l_res_1 = xmlrpc_test_arrayOfStructsTest($array_1);
    $r_res_1 = xmlrpc($xml_url, array('validator1.arrayOfStructsTest' => array($array_1)));
    $this->assertIdentical($l_res_1, $r_res_1);

    $string_2 = 't\'&>>zf"md>yr>xlcev<h<"k&j<og"w&&>">>uai"np&s>>q\'&b<>"&&&';
    $l_res_2 = xmlrpc_test_countTheEntities($string_2);
    $r_res_2 = xmlrpc($xml_url, array('validator1.countTheEntities' => array($string_2)));
    $this->assertIdentical($l_res_2, $r_res_2);

    $struct_3 = array('moe' => mt_rand(-100, 100), 'larry' => mt_rand(-100, 100), 'curly' => mt_rand(-100, 100), 'homer' => mt_rand(-100, 100));
    $l_res_3 = xmlrpc_test_easyStructTest($struct_3);
    $r_res_3 = xmlrpc($xml_url, array('validator1.easyStructTest' => array($struct_3)));
    $this->assertIdentical($l_res_3, $r_res_3);

    $struct_4 = array('sub1' => array('bar' => 13),
                    'sub2' => 14,
                    'sub3' => array('foo' => 1, 'baz' => 2),
                    'sub4' => array('ss' => array('sss' => array('ssss' => 'sssss'))));
    $l_res_4 = xmlrpc_test_echoStructTest($struct_4);
    $r_res_4 = xmlrpc($xml_url, array('validator1.echoStructTest' => array($struct_4)));
    $this->assertIdentical($l_res_4, $r_res_4);

    $int_5     = mt_rand(-100, 100);
    $bool_5    = (($int_5 % 2) == 0);
    $string_5  = $this->randomName();
    $double_5  = (double)(mt_rand(-1000, 1000) / 100);
    $time_5    = REQUEST_TIME;
    $base64_5  = $this->randomName(100);
    $l_res_5 = xmlrpc_test_manyTypesTest($int_5, $bool_5, $string_5, $double_5, xmlrpc_date($time_5), $base64_5);
    // See http://drupal.org/node/37766 why this currently fails
    $l_res_5[5] = $l_res_5[5]->data;
    $r_res_5 = xmlrpc($xml_url, array('validator1.manyTypesTest' => array($int_5, $bool_5, $string_5, $double_5, xmlrpc_date($time_5), xmlrpc_base64($base64_5))));
    // @todo Contains objects, objects are not equal.
    $this->assertEqual($l_res_5, $r_res_5);

    $size = mt_rand(100, 200);
    $array_6 = array();
    for ($i = 0; $i < $size; $i++) {
      $array_6[] = $this->randomName(mt_rand(8, 12));
    }

    $l_res_6 = xmlrpc_test_moderateSizeArrayCheck($array_6);
    $r_res_6 = xmlrpc($xml_url, array('validator1.moderateSizeArrayCheck' => array($array_6)));
    $this->assertIdentical($l_res_6, $r_res_6);

    $struct_7 = array();
    for ($y = 2000; $y < 2002; $y++) {
      for ($m = 3; $m < 5; $m++) {
        for ($d = 1; $d < 6; $d++) {
          $ys = (string) $y;
          $ms = sprintf('%02d', $m);
          $ds = sprintf('%02d', $d);
          $struct_7[$ys][$ms][$ds]['moe']   = mt_rand(-100, 100);
          $struct_7[$ys][$ms][$ds]['larry'] = mt_rand(-100, 100);
          $struct_7[$ys][$ms][$ds]['curly'] = mt_rand(-100, 100);
        }
      }
    }
    $l_res_7 = xmlrpc_test_nestedStructTest($struct_7);
    $r_res_7 = xmlrpc($xml_url, array('validator1.nestedStructTest' => array($struct_7)));
    $this->assertIdentical($l_res_7, $r_res_7);


    $int_8 = mt_rand(-100, 100);
    $l_res_8 = xmlrpc_test_simpleStructReturnTest($int_8);
    $r_res_8 = xmlrpc($xml_url, array('validator1.simpleStructReturnTest' => array($int_8)));
    $this->assertIdentical($l_res_8, $r_res_8);

    /* Now test multicall */
    $x = array();
    $x['validator1.arrayOfStructsTest'] = array($array_1);
    $x['validator1.countTheEntities'] = array($string_2);
    $x['validator1.easyStructTest'] = array($struct_3);
    $x['validator1.echoStructTest'] = array($struct_4);
    $x['validator1.manyTypesTest'] = array($int_5, $bool_5, $string_5, $double_5, xmlrpc_date($time_5), xmlrpc_base64($base64_5));
    $x['validator1.moderateSizeArrayCheck'] = array($array_6);
    $x['validator1.nestedStructTest'] = array($struct_7);
    $x['validator1.simpleStructReturnTest'] = array($int_8);

    $a_l_res = array($l_res_1, $l_res_2, $l_res_3, $l_res_4, $l_res_5, $l_res_6, $l_res_7, $l_res_8);
    $a_r_res = xmlrpc($xml_url, $x);
    $this->assertEqual($a_l_res, $a_r_res);
  }
}
