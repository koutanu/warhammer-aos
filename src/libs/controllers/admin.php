<?php

class Admin extends Controller
{
    private $class_name = 'admin';
    public function __construct()
    {
        parent::__construct();
    }

    function logout()
    {
        Session::destroy();
        header('location: ' . URL . 'login');
        exit;
    }
}
