<?php

class Failure extends Controller
{
	private $class_name = 'failure';
	public function __construct()
	{
		parent::__construct();
	}

	function index()
	{
		$this->view->render($this->class_name, 'index', 'エラー');
	}
}
