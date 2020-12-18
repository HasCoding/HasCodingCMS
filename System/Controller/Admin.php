<?php

class Admin extends Has_Controller
{

    public function index()
    {
        $this->view("Admin/index");
    }

}