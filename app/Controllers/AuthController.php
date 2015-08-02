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
        // You can use the authentication data stored in $auth variable
        $auth = $this->app->container["settings"]["auth"];

        $request = $this->app->request;
        if ( $request->isFormData() ) {
            $username = $request->post("username");
            $password = $request->post("password");
        }
        if ( strtolower($request->getContentType()) == "application/json" ) {
            $json = json_decode($request->getBody(), true);
            if ( $json !== NULL ) {
                $username = $json["username"];
                $password = $json["password"];
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

        if ( empty($user) ) {
            $this->renderUnauthorized();
            return;
        }

        $pdo->update(array("lastlogin_time"=>gmdate("Y-m-d H:i:s")))
            ->table("tbl_user")
            ->where("id", "=", $user["id"])
            ->execute();

        $this->app->setAuthData(Factory::createAuthData($user));

        $this->render(200);
    }

}
