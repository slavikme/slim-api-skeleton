<?php
/**
 * Created by IntelliJ IDEA.
 * User: webpick
 * Date: 7/14/15
 * Time: 8:03 PM
 */

namespace SlimAPI;

use Slim\Slim;

class ApiView extends \JsonApiView {

    public function render($status=200, $data = NULL) {
        $app = Slim::getInstance();

        $status = intval($status);

        $response = $this->all();

        //append error bool
        if (!$this->has('error')) {
            $response['error'] = false;
        }

        if ( $app->isAuthenticated() ) {
            $response['token'] = $app->generateToken(null, true);
        }

        //append status code
        $response['status'] = $status;

        //add flash messages
        if(isset($this->data->flash) && is_object($this->data->flash)){
            $flash = $this->data->flash->getMessages();
            if (count($flash)) {
                $response['flash'] = $flash;
            } else {
                unset($response['flash']);
            }
        }

        $app->response()->status($status);
        $app->response()->header('Content-Type', $this->contentType);

        $jsonp_callback = $app->request->get('callback', null);

        if($jsonp_callback !== null){
            $app->response()->body($jsonp_callback.'('.json_encode($response, $this->encodingOptions).')');
        } else {
            $app->response()->body(json_encode($response, $this->encodingOptions));
        }

        $app->stop();
    }

}