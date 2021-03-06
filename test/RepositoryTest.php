<?php

namespace Drupal\ParseComposer;

use Symfony\Component\Process\ExecutableFinder;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use Composer\IO\NullIO;
use Composer\Config;

/**
 * Copied from Composer\Test\Repository\VcsRepositoryTest
 */

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    private static $composerHome;
    private static $gitRepoDir;
    private $skipped;

    protected function initialize()
    {
        self::$composerHome = sys_get_temp_dir() . '/composer-home-'.mt_rand().'/';
        self::$gitRepoDir = __DIR__ . '/../res';
    }

    public function setUp()
    {
        $this->initialize();

        if ($this->skipped) {
            $this->markTestSkipped($this->skipped);
        }
        $this->dumper = new ArrayDumper();
    }

    public function testLoadVersions()
    {
        $projects = array(
            'omega' => array(
                '7.4.2' => array(
                    'type' => 'drupal-theme'
                ),
                '7.3.1' => true,
                'dev-7.x-4.x' => true,
            ),
            'flood_sem' => array(
                'dev-7.x-1.x' => array(
                    'type' => 'drupal-module'
                )
            ),
            'config_devel' => array(
                'dev-8.x-1.x' => array(
                    'type' => 'drupal-module'
                ),
            ),
        );

        $config = new Config();
        $config->merge(array(
            'config' => array(
                'home' => self::$composerHome,
            ),
        ));
        foreach ($projects as $name => $expected) {
            $repo = new Repository(
                array('url' => self::$gitRepoDir.'/'.$name, 'type' => 'vcs'),
                new NullIO,
                $config
            );
            $packages = $repo->getPackages();
            foreach ($packages as $package) {
                if (isset($expected[$package->getPrettyVersion()])) {
                    if (is_array($p = $expected[$package->getPrettyVersion()])) {
                        $this->assertEmpty(array_diff_assoc(
                            $p, $this->dumper->dump($package)
                        ));
                    }
                    $this->assertEquals($package->getPrettyName(), 'drupal/'.$name);
                    unset($expected[$package->getPrettyVersion()]);
                }
            }
            $this->assertEmpty($expected, 'Missing versions: '.implode(', ', array_keys($expected)));
        }

    }
}
