<?php

namespace SlimAPI\Controllers;

use SlimController\SlimController;

class HomeController extends SlimController {

    public function indexAction() {

        $pdo = $this->app->getPDOConnection();

        $this->render(200, array(
            "test" => array(
                "now" => gmdate("Y-m-d H:i:s"),
                "user" => $pdo->select()
                    ->from('extui_user')
                    ->where("id", "=", $this->app->auth_data["user"]["id"])
                    ->execute()
                    ->fetch(),
                "auth_data" => $this->app->auth_data,
            )
        ));

    }

}
