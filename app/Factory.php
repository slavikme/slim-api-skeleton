<?php

namespace SlimAPI;

use BurningDiode\Slim\Config\Yaml;
use SlimController\Slim;

/**
 * Class Factory
 * @package ExtAPI
 */
class Factory extends Slim
{
    /**
     * @var Factory
     */
    protected static $instance = 0;

    /**
     * @var string
     */
    private static $config_file = "config/config.yml";

    /**
     * @var string
     */
    private static $routing_file = "config/routes.yml";

    /**
     * @var bool
     */
    private static $config_file_loaded = false;

    /**
     * @var bool
     */
    private static $routing_file_loaded = false;

    /**
     * Create a new or return the current instance
     *
     * @return Factory
     */
    public static function getInstance()
    {
        $trace = debug_backtrace();
        if ( isset($trace[1]) && $trace[1]['class'] == 'Slim\\Slim' )
        {
            return parent::getInstance();
        }

        if (self::$instance === 0) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Shorthand for getInstance
     *
     * @return Factory
     */
    public static function _()
    {
        return self::getInstance();
    }

    /**
     * @return string
     */
    private static function getRootDir()
    {
        return __DIR__ . "/../../";
    }

    /**
     * @return string
     */
    protected static function getConfigPathFile()
    {
        return self::getRootDir() . self::$config_file;
    }

    /**
     * @return string
     */
    protected static function getRoutingPathFile()
    {
        return self::getRootDir() . self::$routing_file;
    }

    /**
     * @param $file
     * @return Factory
     * @throws \Exception
     */
    public function addConfigFromYamlFile($file)
    {
        Yaml::getInstance()->addFile($file);
        return self::$instance;
    }

    /**
     * @return mixed
     */
    private function getConfig()
    {
        if ( !self::$config_file_loaded ) {
            self::addConfigFromYamlFile(self::getConfigPathFile());
            self::$config_file_loaded = true;
        }
        return $this->container['settings'];
    }

    /**
     * @return mixed
     */
    private function getRouting()
    {
        if ( !self::$routing_file_loaded ) {
            self::addConfigFromYamlFile(self::getRoutingPathFile());
            self::$routing_file_loaded = true;
        }
        return $this->config('routes');
    }

    /**
     * @return Factory
     */
    private function setDefaultView()
    {
        $this->view(new \JsonApiView());
    }

    /**
     * @return Factory
     */
    private function parseMiddlewares()
    {
        $this->add(new \JsonApiMiddleware());
    }

    /**
     * @return Factory
     */
    private function parseConfig()
    {
        $this->getConfig();
    }

    /**
     * @return Factory
     */
    private function parseRouting()
    {
        $routing = $this->getRouting();
        foreach ( $routing as $key => $route )
        {
            $methods = isset($route['method']) ? $route['method'] : 'get';
            $path = $route['path'];
            $controller = $route['controller'];
            $conditions = (array)$route['conditions'];

            foreach ( (array)$methods as $method )
            {
                $this->addControllerRoute($path, $controller)
                    ->via(strtoupper($method))
                    ->name($key)
                    ->conditions($conditions);
            }
        }
    }

    /**
     * @return Factory
     */
    private function setConfigSiteUrl()
    {
        $request = $this->request();
        $site_url = $request->getUrl().$request->getRootUri();
        $this->config('app.site_url', $site_url);
    }

    public function __construct()
    {
        parent::__construct();

        $this->parseConfig();
        $this->setDefaultView();
        $this->parseMiddlewares();
        $this->parseRouting();
        $this->setConfigSiteUrl();
        $this->run();
    }
}