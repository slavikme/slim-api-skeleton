<?php

namespace SlimAPI;

use BurningDiode\Slim\Config\Yaml;
use Slim\Middleware\JwtAuthentication;
use Slim\PDO\Database;
use SlimController\Slim;

/**
 * Class Factory
 * @package SlimAPI
 */
class Factory extends Slim
{
    /**
     * @var Factory
     */
    protected static $instance = 0;

    /**
     * @var Database
     */
    protected static $pdo;

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
     * @var \stdClass
     */
    public $auth_data;

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
        return __DIR__ . "/../";
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
     * @param string $name
     * @return mixed
     */
    private function getConfig($name = null)
    {
        if ( !self::$config_file_loaded ) {
            self::addConfigFromYamlFile(self::getConfigPathFile());
            self::$config_file_loaded = true;
        }
        if ( !is_null($name) ) {
            return $this->config($name);
        }
        return $this->container['settings'];
    }

    /**
     * @param $name
     * @param $value
     */
    private function setConfig($name, $value)
    {
        if ( isset($this->container['settings'][$name]) )
        {
            $this->container['settings'][$name] = $value;
        }
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
        $this->view(new ApiView());
    }

    public function setAuthData($auth_data)
    {
        if ( is_object($auth_data) || is_array($auth_data) ) {
            $this->auth_data = json_decode(json_encode($auth_data),true);
        }
    }

    public function getAuthData()
    {
        return $this->auth_data;
    }

    private function getAuthConfigurations()
    {
        // Make sure there is an HTTP_AUTHORIZATION, PHP_AUTH_USER and PHP_AUTH_PW environment variable
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?: $_SERVER['HTTP_AUTHORIZATION'];
        if ( preg_match("/Basic\\s+(.*)$/", $_SERVER['HTTP_AUTHORIZATION'], $matches) ) {
            list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode($matches[1]));
        }

        // Authorization
        $auth = $this->getConfig("auth");
        $auth["rules"] = [
            new JwtAuthentication\RequestPathRule([
                "passthrough" => $auth["passthrough"],
            ]),
            new JwtAuthentication\RequestMethodRule([
                "passthrough" => ["OPTIONS"],
            ]),
        ];
        $auth["callback"] = function($arguments) {
            $token_data = $arguments["decoded"];
            if ( $token_data->exptime < time() ) {
                return false;
            }
            $this->setAuthData($token_data);
        };
        return $auth;
    }

    /**
     * @return Factory
     */
    private function parseMiddlewares()
    {
        // JSON API response
        $this->add(new \JsonApiMiddleware());

        // JSON Web Token Authentication
        $this->add(new JwtAuthentication($this->getAuthConfigurations()));
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

    private function createDatabaseInstance()
    {
        $config = $this->getConfig();
        $dbconf = $config["databases"]["default"];

        $driver = $dbconf["driver"];
        $host = $dbconf["hostname"];
        $port = $dbconf["port"];
        $user = $dbconf["username"];
        $pass = $dbconf["password"];
        $dbname = $dbconf["database"];
        $charset = $dbconf["charset"];

        $dsn = "$driver:host=$host;port=$port;dbname=$dbname;charset=$charset";
        return new Database($dsn, $user, $pass);
    }

    public function getPDOConnection()
    {
        if ( !self::$pdo ) {
            try {
                $pdo = $this->createDatabaseInstance();
                self::$pdo = $pdo;
            } catch (\PDOException $e) {
                $this->render(500, [
                    "msg" => $e->getMessage()
                ]);
            }
        }
        return self::$pdo;
    }

    /**
     * @param array $user_data
     * @param int $exptime The expiration timestamp of the token.
     * @return array|null
     */
    public static function createAuthData(array $user_data, $exptime)
    {
        $now = time();
        if ( empty($user_data) || !is_numeric($exptime) || $exptime < $now ) {
            return null;
        }
        return array(
            "time" => $now,
            "exptime" => $exptime,
            "user" => array_intersect_key(
                $user_data,
                array_flip(["id","username","name","role","email","status","lastlogin_time"])
            ),
        );
    }

    /**
     * @param array $user_data
     * @param bool $is_save
     * @return string
     */
    public function generateToken(array $user_data = null, $is_save = false)
    {
        if ( $this->isAuthenticated() )
        {
            if ( empty($user_data) ) {
                $user_data = $this->getAuthData()["user"];
            }
            $exptime = $this->getAuthData()["exptime"];
        } else {
            $exptime = strtotime($this->getConfig("auth")["lifetime"]);
        }
        $auth_data = self::createAuthData($user_data, $exptime);
        $token = \JWT::encode($auth_data, $this->getConfig("auth")["secret"]);
        if ( $is_save ) {
            $this->setAuthData($auth_data);
        }
        return $token;
    }

    public function isAuthenticated()
    {
        $auth_data = $this->getAuthData();
        return !empty($auth_data);
    }

    public function __construct()
    {
        parent::__construct();

        $this->parseConfig();
        $this->setDefaultView();
        $this->parseMiddlewares();
        $this->parseRouting();
        $this->run();
    }
}