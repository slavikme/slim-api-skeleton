<?php

namespace SlimAPI\Controllers;

use \SlimController\SlimController;

class ProductController extends SlimController {

    public function getAction($id) {

        $this->render(200, array(
            "id" => $id,
            "name" => "Product Name",
        ));

    }

}
