<?php

namespace Vectorface\SnappyRouter\Handler;

use Vectorface\SnappyRouter\Encoder\JsonEncoder;

/**
 * Handles REST-like URLs like '/api/v2/users/1/details'.
 * @copyright Copyright (c) 2014, VectorFace, Inc.
 * @author Dan Bruce <dbruce@vectorface.com>
 */
class RestHandler extends ControllerHandler
{
    /** The route parameter key for the API version */
    const KEY_API_VERSION = 'apiVersion';

    // the array of route parameters
    private $apiVersion;

    /** Constants indicating the type of route */
    const MATCHES_ID = 8;
    const MATCHES_CONTROLLER_AND_ID = 9;
    const MATCHES_CONTROLLER_ACTION_AND_ID = 11;

    /**
     * Returns true if the handler determines it should handle this request and false otherwise.
     * @param string $path The URL path for the request.
     * @param array $query The query parameters.
     * @param array $post The post data.
     * @param string $verb The HTTP verb used in the request.
     * @return boolean Returns true if this handler will handle the request and false otherwise.
     */
    public function isAppropriate($path, $query, $post, $verb)
    {
        // use the parent method to match the routes
        if (false === parent::isAppropriate($path, $query, $post, $verb)) {
            return false;
        }

        // determine the route information from the path
        $routeInfo = $this->getRouteInfo($verb, $path, true);
        $this->apiVersion = $routeInfo[2]['version'];
        if ($routeInfo[1] & self::MATCHES_ID) {
            $this->routeParams = array(intval($routeInfo[2]['objectId']));
        }
        return true;
    }

    /**
     * Performs the actual routing.
     * @return mixed Returns the result of the route.
     */
    public function performRoute()
    {
        $this->routeParams[self::KEY_API_VERSION] = $this->apiVersion;
        return parent::performRoute();
    }

    /**
     * Returns the active response encoder.
     * @return EncoderInterface Returns the response encoder.
     */
    public function getEncoder()
    {
        return new JsonEncoder();
    }

    /**
     * Returns the array of routes.
     * @return array The array of routes.
     */
    protected function getRoutes()
    {
        return array(
            '/v{version}/{controller}' => self::MATCHES_CONTROLLER,
            '/v{version}/{controller}/' => self::MATCHES_CONTROLLER,
            '/v{version}/{controller}/{action:[a-zA-Z]+}' => self::MATCHES_CONTROLLER_AND_ACTION,
            '/v{version}/{controller}/{action:[a-zA-Z]+}/' => self::MATCHES_CONTROLLER_AND_ACTION,
            '/v{version}/{controller}/{objectId:[0-9]+}' => self::MATCHES_CONTROLLER_AND_ID,
            '/v{version}/{controller}/{objectId:[0-9]+}/' => self::MATCHES_CONTROLLER_AND_ID,
            '/v{version}/{controller}/{action:[a-zA-Z]+}/{objectId:[0-9]+}' => self::MATCHES_CONTROLLER_ACTION_AND_ID,
            '/v{version}/{controller}/{action:[a-zA-Z]+}/{objectId:[0-9]+}/' => self::MATCHES_CONTROLLER_ACTION_AND_ID,
            '/v{version}/{controller}/{objectId:[0-9]+}/{action:[a-zA-Z]+}' => self::MATCHES_CONTROLLER_ACTION_AND_ID,
            '/v{version}/{controller}/{objectId:[0-9]+}/{action:[a-zA-Z]+}/' => self::MATCHES_CONTROLLER_ACTION_AND_ID,
        );
    }
}
