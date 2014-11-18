<?php

namespace Vectorface\SnappyRouter\Di;

use \Exception;
use Vectorface\SnappyRouter\Exception\ServiceNotRegisteredException;

/**
 * A service provider providing dependency injection capabilities to the
 * router specifically for the list of services.
 * @copyright Copyright (c) 2014, VectorFace, Inc.
 * @author Dan Bruce <dbruce@vectorface.com>
 */
class ServiceProvider extends Di
{

    /** The default mode. Retrieve services from an explicit list. */
    const PROVISIONING_MODE_SERVICE_LIST = 1;
    /** The mode for retrieving services from a list of namespaces. */
    const PROVISIONING_MODE_NAMESPACES   = 2;
    /** The mode for retrieving services from a folder recursively. */
    const PROVISIONING_MODE_FOLDERS      = 3;

    const KEY_NAMESPACES = 'serviceNamespaces';
    const KEY_FOLDERS    = 'serviceFolders';

    // the private provisioning mode
    private $provisioningMode = self::PROVISIONING_MODE_SERVICE_LIST;

    // a cache of service instances
    private $instanceCache;

    /**
     * Returns the array of all services.
     * @return The array of all services.
     */
    public function getServices()
    {
        return $this->allRegisteredElements();
    }

    /**
     * Returns the specified service path for the given key.
     * @param string $key The key to lookup.
     * @return Returns the path to the specified service for the given key.
     * @throws ServiceNotFoundForKeyException Throws this exception if the key isn't associated
     * with any registered service.
     */
    public function getService($key)
    {
        return $this->get($key);
    }

    /**
     * Specifies the mapping between the given key and service.
     * @param string $key The key to assign.
     * @param string $service The service to be assigned to the key.
     * @return Returns $this.
     */
    public function setService($key, $service)
    {
        $this->set($key, $service);
        unset($this->instanceCache[$key]);
        return $this;
    }

    /**
     * Returns an instance of the specified service.
     * @param string $key The key to lookup.
     * @param boolean $useCache An optional flag indicating whether we should
     *        use the cache. True by default.
     * @return Returns an instance of the specified service.
     * @throws ServiceNotFoundForKeyException Throws this exception if the key isn't associated
     * with any registered service.
     */
    public function getServiceInstance($key, $useCache = true)
    {
        // retrieve the service from the instance cache if it exists
        if ($useCache && isset($this->instanceCache[$key])) {
            return $this->instanceCache[$key];
        }

        // handle the non-standard provisioning modes
        switch ($this->provisioningMode) {
            case self::PROVISIONING_MODE_NAMESPACES:
                $this->instanceCache[$key] = $this->getServiceFromNamespaces($key);
                return $this->instanceCache[$key];
            case self::PROVISIONING_MODE_FOLDERS:
                $this->instanceCache[$key] = $this->getServiceFromFolder($key);
                return $this->instanceCache[$key];
        }

        // default provisioning mode uses a hardcoded list of services
        $serviceClass = $this->get($key);
        if (is_string($serviceClass) && !class_exists($serviceClass)) {
                require_once $serviceClass;
                $serviceClass = $key;
        } elseif (is_array($serviceClass)) {
            if (isset($serviceClass['file'])) {
                require_once $serviceClass['file'];
            }
            if (isset($serviceClass['class'])) {
                $serviceClass = $serviceClass['class'];
            }
        }

        $instance = new $serviceClass();

        if ($useCache) {
            $this->instanceCache[$key] = $instance;
        }
        return $instance;
    }

    /**
     * Sets the list of namespaces and switches to namespace provisioning mode.
     * @param array An array of namespaces.
     * @return ServiceProvider Returns $this.
     */
    public function setNamespaces($namespaces)
    {
        $this->set(self::KEY_NAMESPACES, $namespaces);
        $this->provisioningMode = self::PROVISIONING_MODE_NAMESPACES;
        return $this;
    }

    /**
     * Sets the list of folders and switches to folder provisioning mode.
     * @param array An array of folders.
     * @return ServiceProvider Returns $this.
     */
    public function setFolders($folders)
    {
        $this->set(self::KEY_FOLDERS, $folders);
        $this->provisioningMode = self::PROVISIONING_MODE_FOLDERS;
        return $this;
    }

    /**
     * Returns an instance of the specified controller from the list of
     * namespaces.
     */
    private function getServiceFromNamespaces($controllerClass)
    {
        foreach ($this->get(self::KEY_NAMESPACES) as $namespace) {
            $fullClass = sprintf('%s\\%s', $namespace, $controllerClass);
            if (class_exists($fullClass)) {
                return new $fullClass();
            }
        }
        throw new Exception('Controller class '.$controllerClass.' was not found in any listed namespace.');
    }

    /**
     * Returns an instance of the specified controller from the list of
     * namespaces.
     * @param string $controllerClass The controller class file we are looking for.
     * @return AbstractController Returns an instance of the controller.
     */
    private function getServiceFromFolder($controllerClass)
    {
        foreach ($this->get(self::KEY_FOLDERS) as $folder) {
            $path = $this->findFileInFolderRecursively($controllerClass.'.php', $folder);
            if (false !== $path) {
                require_once $path;
                return new $controllerClass();
            }
        }
        throw new Exception('Controller class '.$controllerClass.' not found in any listed folder.');
    }

    /**
     * Scan for the specific file recursively.
     * @param string $file The file to search for.
     * @param string $folder The folder to search inside.
     * @return mixed Returns either the full path string to the file or false
     *         if the file was not found.
     */
    private function findFileInFolderRecursively($file, $folder)
    {
        $dir = dir($folder);
        while (false !== ($item = $dir->read())) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $fullPath = $folder.DIRECTORY_SEPARATOR.$item;
            if (0 === strcasecmp($item, $file)) {
                return $fullPath;
            } elseif (is_dir($fullPath)) {
                $fullPath = $this->findFileInFolderRecursively($file, $fullPath);
                if (false !== $fullPath) {
                    return $fullPath;
                }
            }
        }
        return false;
    }
}
