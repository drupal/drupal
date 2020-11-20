<?php
namespace Composer\Installers\Test;

use Composer\Installers\SyDESInstaller;
use Composer\Package\Package;
use Composer\Composer;
use PHPUnit\Framework\TestCase as BaseTestCase;

class SyDESInstallerTest extends BaseTestCase
{
    /**
     * @var SyDESInstaller
     */
    private $installer;

    public function setUp()
    {
        $this->installer = new SyDESInstaller(
            new Package('NyanCat', '4.2', '4.2'),
            new Composer()
        );
    }

    /**
     * @dataProvider packageNameInflectionProvider
     */
    public function testInflectPackageVars($type, $name, $expected)
    {
        $this->assertEquals(
            array('name' => $expected, 'type' => $type),
            $this->installer->inflectPackageVars(array('name' => $name, 'type' => $type))
        );
    }

    public function packageNameInflectionProvider()
    {
        return array(
            // modules
			array(
                'sydes-module',
                'name',
                'Name'
            ),
            array(
                'sydes-module',
                'sample-name',
                'SampleName'
            ),
            array(
                'sydes-module',
                'sydes-name',
                'Name'
            ),
            array(
                'sydes-module',
                'sample-name-module',
                'SampleName',
            ),
            array(
                'sydes-module',
                'sydes-sample-name-module',
                'SampleName'
            ),
			// themes
            array(
                'sydes-theme',
                'some-theme-theme',
                'some-theme',
            ),
            array(
                'sydes-theme',
                'sydes-sometheme',
                'sometheme',
            ),
            array(
                'sydes-theme',
                'Sample-Name',
                'sample-name'
            ),
        );
    }
}
