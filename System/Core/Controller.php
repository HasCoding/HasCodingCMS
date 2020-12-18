<?php


class Has_Controller {
    protected $view;

    public function view($view_name, $model = []){
        $this->view = new View($view_name, $model);
        return $this->view->Render();
    }

    public function index()
    {

    }

    public function model($model_name)
    {
        include MODEL."/".$model_name.".php";
        $this->db = new $model_name();
    }

    public function Redirect($path)
    {
        header("Location: {$path}");
    }
}

?>