<?php

namespace SlimAPI\Controllers;

use SlimAPI\Factory;
use SlimController\SlimController;

class AuthController extends SlimController {

    private function renderUnauthorized () {
        $this->render(401, array(
            "error" => true,
            "msg" => "Unauthorized access"
        ));
    }

    public function loginAction() {
        // You can use the auth config data stored in $auth variable
        $auth = $this->app->container["settings"]["auth"];

        // Note: There is no point to check whether the user is authenticated, as there is no authentication
        //       check for this route as defined in the config.yml file under the auth.passthrough parameter.

        $request = $this->app->request;
        $max_exptime = strtotime($auth["maxlifetime"]);
        $default_exptime = strtotime($auth["lifetime"]);
        $exptime = $default_exptime;

        if ( $request->isFormData() )
        {
            $username = $request->post("username");
            $password = $request->post("password");
            $exptime = self::getExpirationTime($request->post("expiration"), $default_exptime, $max_exptime);
        }

        if ( preg_match("/^application\\/json/i", $request->getContentType()) )
        {
            $json = json_decode($request->getBody(), true);
            if ( $json !== NULL )
            {
                $username = $json["username"];
                $password = $json["password"];
                $exptime = self::getExpirationTime($json["expiration"], $default_exptime, $max_exptime);
            }
        }

        if ( empty($username) || empty($password) ) {
            $this->renderUnauthorized();
            return;
        }

        /**
         * @var \PDO
         */
        $pdo = $this->app->getPDOConnection();
        $user = $pdo->select()
            ->from("tbl_user")
            ->where("username", "=", $username)
            ->where("password", "=", sha1($password))
            ->where("status", ">", 0)
            ->execute()
            ->fetch();

        if ( empty($user) )
        {
            $this->renderUnauthorized();
            return;
        }

        $pdo->update(array("lastlogin_time"=>gmdate("Y-m-d H:i:s")))
            ->table("tbl_user")
            ->where("id", "=", $user["id"])
            ->execute();

        $this->app->setAuthData(Factory::createAuthData($user, $exptime));

        $this->render(200);
    }

    /**
     * @param mixed $value
     * @param int $default
     * @param int $max
     * @return int
     */
    private static function getExpirationTime($value, $default = 0, $max = 0)
    {
        $exptime = $default;
        $now = time();
        if ( !empty($value) )
        {
            if ( is_string($value) ) {
                $exptime = strtotime($value);
            }
            if ( is_numeric($value) ) {
                $exptime = $value < $now ? $now + $value : (int)$value;
            }
        }
        if ( !is_numeric($exptime) || $exptime < $now ) {
            $exptime = $default;
        }
        if ( $exptime > $max ) {
            $exptime = $max;
        }
        return $exptime;
    }

}
