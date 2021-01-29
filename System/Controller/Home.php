<?php

class Home extends Has_Controller {

    public function __construct()
    {
        $this->model("Home_Model");
    }


    public function index(){
		$this->view("Home/index");
    }



}
?>