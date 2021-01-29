<?php

class HasCodingSystem {

    protected $controller = "Home";
    protected $action = "index";
    protected $parameters = array();

    public function __construct(){
        $this->ParseURL();
        if(file_exists(CONTROLLER."/".$this->controller.".php")){
            require_once (CONTROLLER.$this->controller.".php");
            $this->controller = new $this->controller;
            if(method_exists($this->controller, $this->action)){
                call_user_func_array([$this->controller, $this->action], $this->parameters);
            } else {
                echo "No Such Action.";
            }
        } else {
            echo "There Is No Such Controller. <br>";
        }
    }

    protected function ParseURL(){
        $request = trim($_SERVER["REQUEST_URI"], "/");
        if (!empty($request)){
            $url = explode("/", $request);

            $this->controller = isset($url[0]) ? $url[0] : "Home";
            $this->action = isset($url[1]) ? $url[1] : "index";
            unset($url[0], $url[1]);
            $this->parameters = !empty($url) ? array_values($url) : array();
        } else {
            $this->controller = "Home";
            $this->action = "index";
            $this->parameters = array();
        }
    }


}

?>