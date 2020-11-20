<?php
namespace Composer\Installers\Test;

use Composer\Installers\OntoWikiInstaller;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Test for the OntoWikiInstaller
 * code was taken from DokuWikiInstaller
 */
class OntoWikiInstallerTest extends BaseTestCase
{
    /**
     * @var OntoWikiInstaller
     */
    private $installer;

    public function setUp()
    {
        $this->installer = new OntoWikiInstaller();
    }

    /**
     * @dataProvider packageNameInflectionProvider
     */
    public function testInflectPackageVars($type, $name, $expected)
    {
        $this->assertEquals(
            $this->installer->inflectPackageVars(array('name' => $name, 'type'=>$type)),
            array('name' => $expected, 'type'=>$type)
        );
    }

    public function packageNameInflectionProvider()
    {
        return array(
            array(
                'ontowiki-extension',
                'CSVImport.ontowiki',
                'csvimport',
            ),
            array(
                'ontowiki-extension',
                'csvimport',
                'csvimport',
            ),
            array(
                'ontowiki-extension',
                'some_ontowiki_extension',
                'some_ontowiki_extension',
            ),
            array(
                'ontowiki-extension',
                'some_ontowiki_extension.ontowiki',
                'some_ontowiki_extension',
            ),
            array(
                'ontowiki-translation',
                'de-translation.ontowiki',
                'de',
            ),
            array(
                'ontowiki-translation',
                'en-US-translation.ontowiki',
                'en-us',
            ),
            array(
                'ontowiki-translation',
                'en-US-translation',
                'en-us',
            ),
            array(
                'ontowiki-theme',
                'blue-theme.ontowiki',
                'blue',
            ),
            array(
                'ontowiki-theme',
                'blue-theme',
                'blue',
            ),
        );
    }
}
