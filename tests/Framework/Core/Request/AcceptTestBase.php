<?php

namespace Facula\Tests\Framework\Core\Request;

use Facula\Base\Entity\Core\Request as Target;
use Facula\Framework;
use PHPUnit_Framework_TestCase;

abstract class AcceptTestBase extends PHPUnit_Framework_TestCase
{
    protected $serverBackup = array();

    protected $config = array();
    protected $configCommon = array();
    protected $frameworkInstance = array();

    /**
     * Create testing environment
     */
    protected function setUp()
    {
        global $_SERVER;

        $this->serverBackup = $_SERVER;

        $_SERVER = array();

        $this->frameworkInstance = Framework::run();
    }

    /**
     * Remove testing environment
     */
    protected function tearDown()
    {
        global $_SERVER;

        $_SERVER = $this->serverBackup;
    }

    /**
     * Get a new instance of Request function core
     * with default setting.
     */
    protected function getTestInstance()
    {
        $request = new Target(
            $this->config,
            $this->configCommon,
            $this->frameworkInstance
        );

        $request->inited();

        return $request;
    }

    /**
     * Compare the array, data sensitivity
     */
    protected function checkResultArray(array $assert, array $result)
    {
        foreach ($assert as $assertKey => $assertVal) {
            if ($assert[$assertKey] !== $result[$assertKey]) {
                return false;
            }
        }

        return true;
    }
}
