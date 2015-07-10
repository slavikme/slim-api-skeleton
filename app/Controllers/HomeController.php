<?php

namespace SlimAPI\Controllers;

use \SlimController\SlimController;

class HomeController extends SlimController {

    public function indexAction() {

        $this->render(200, array(
            "test" => array(
                "now" => date("Y-m-d H:i:s")
            )
        ));

    }

    public function productAction($id) {

        $this->render(200, array(
            "id" => $id
        ));

    }

}
