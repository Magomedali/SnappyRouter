<?php

namespace Vectorface\SnappyRouterTests;

use Vectorface\SnappyRouter\SnappyRouter;
use Vectorface\SnappyRouter\Config\Config;
use Vectorface\SnappyRouter\Plugin\PluginInterface;
use Vectorface\SnappyRouter\Handler\AbstractHandler;
use Vectorface\SnappyRouter\Handler\ControllerHandler;

use \PHPUnit_Framework_TestCase;

/**
 * Tests the main SnappyRouter class.
 * @copyright Copyright (c) 2014, VectorFace, Inc.
 * @author Dan Bruce <dbruce@vectorface.com>
 */
class SnappyRouterTest extends PHPUnit_Framework_TestCase
{
    /**
     * Returns a standard router config array.
     * @return array A standard router config.
     */
    private function getStandardConfig()
    {
        return array(
            SnappyRouter::KEY_DI => 'Vectorface\SnappyRouter\Di\Di',
            SnappyRouter::KEY_HANDLERS => array(
                'BogusCliHandler' => array(
                    AbstractHandler::KEY_CLASS => 'Vectorface\SnappyRouter\Handler\CliTaskHandler'
                ),
                'ControllerHandler' => array(
                    AbstractHandler::KEY_CLASS => 'Vectorface\SnappyRouter\Handler\ControllerHandler',
                    AbstractHandler::KEY_OPTIONS => array(
                        ControllerHandler::KEY_BASE_PATH => '/',
                        AbstractHandler::KEY_SERVICES => array(
                            'TestController' => 'Vectorface\SnappyRouterTests\Controller\TestDummyController'
                        ),
                        AbstractHandler::KEY_PLUGINS => array(
                            'TestPlugin'     => array(
                                AbstractHandler::KEY_CLASS => 'Vectorface\SnappyRouterTests\Plugin\TestPlugin',
                                AbstractHandler::KEY_OPTIONS => array()
                            ),
                            'AnotherPlugin'  => 'Vectorface\SnappyRouterTests\Plugin\TestPlugin'
                        )
                    )
                ),
                'CliHandler' => array(
                    AbstractHandler::KEY_CLASS => 'Vectorface\SnappyRouter\Handler\CliTaskHandler',
                    AbstractHandler::KEY_OPTIONS => array(
                        'tasks' => array(
                            'TestTask' => 'Vectorface\SnappyRouterTests\Task\DummyTestTask'
                        )
                    )
                )
            )
        );
    }

    /**
     * An overview of how to use the SnappyRouter class.
     * @test
     */
    public function synopsis()
    {
        // an example configuration of the router
        $config = $this->getStandardConfig();
        // instantiate the router
        $router = new SnappyRouter(new Config($config));

        // an example MVC request
        $_SERVER['REQUEST_URI'] = '/Test/test';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['param'] = 'value';
        $response = $router->handleRoute('apache2handler');

        $expectedResponse = 'This is a test service.';
        $this->assertEquals($expectedResponse, $response);

        unset($_SERVER['REQUEST_URI']);
        $_GET = array();
        $_POST = array();
    }

    /**
     * Tests that the router handles a generic exception.
     */
    public function testGenericException()
    {
        $config = $this->getStandardConfig();
        $router = new SnappyRouter(new Config($config));

        // an example MVC request
        $path = '/Test/genericException';
        $query = array('jsoncall' => 'testMethod');
        $response = $router->handleHttpRoute($path, $query, '', 'get');

        $expectedResponse = 'A generic exception.';
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * Tests that an empty config array results in no handler being found.
     */
    public function testNoHandlerFoundException()
    {
        $router = new SnappyRouter(new Config(array()));

        // an example MVC request
        $path = '/Test/test';
        $query = array('jsoncall' => 'testMethod');
        $response = $router->handleHttpRoute($path, $query, '', 'get');
        $this->assertEquals('', $response);
    }

    /**
     * Tests that an exception is thrown if a handler class does not exist.
     * @expectedException Exception
     * @expectedExceptionMessage Cannot instantiate instance of Vectorface\SnappyRouter\Handler\NonexistantHandler
     */
    public function testInvalidHandlerClass()
    {
        $config = $this->getStandardConfig();
        $config[SnappyRouter::KEY_HANDLERS]['InvalidHandler'] = array(
            'class' => 'Vectorface\SnappyRouter\Handler\NonexistantHandler'
        );
        $router = new SnappyRouter(new Config($config));

        // an example MVC request
        $path = '/Test/test';
        $query = array('jsoncall' => 'testMethod');
        $response = $router->handleHttpRoute($path, $query, '', 'get');

        $expectedResponse = 'No handler responded to request.';
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * Tests that the CLI routing functionality works.
     */
    public function testStandardCliRoute()
    {
        $config = $this->getStandardConfig();
        $router = new SnappyRouter(new Config($config));

        $_SERVER['argv'] = array(
            'dummyScript.php',
            '--task',
            'TestTask',
            '--action',
            'testMethod'
        );
        $_SERVER['argc'] = count($_SERVER['argv']);
        $response = $router->handleRoute();

        $expected = 'Hello World'.PHP_EOL;
        $this->assertEquals($expected, $response);
    }

    /**
     * Tests a CLI route that throws an exception.
     */
    public function testCliRouteWithException()
    {
        $config = $this->getStandardConfig();
        $router = new SnappyRouter(new Config($config));

        $_SERVER['argv'] = array(
            'dummyScript.php',
            '--task',
            'TestTask',
            '--action',
            'throwsException'
        );
        $_SERVER['argc'] = count($_SERVER['argv']);
        $response = $router->handleRoute();

        $expected = 'An exception was thrown.'.PHP_EOL;
        $this->assertEquals($expected, $response);
    }

    /**
     * Tests that a CLI route with no appropriate handlers throws an
     * exception.
     */
    public function testCliRouteWithNoHandler()
    {
        $config = $this->getStandardConfig();
        $router = new SnappyRouter(new Config($config));

        $_SERVER['argv'] = array(
            'dummyScript.php',
            '--task',
            'NotDefinedTask',
            '--action',
            'anyAction'
        );
        $_SERVER['argc'] = count($_SERVER['argv']);
        $response = $router->handleRoute();

        $expected = 'No CLI handler registered.'.PHP_EOL;
        $this->assertEquals($expected, $response);
    }
}
