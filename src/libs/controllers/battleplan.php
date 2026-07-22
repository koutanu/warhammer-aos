<?php

class Battleplan extends Controller
{
	private $class_name = 'battleplan';

	public function __construct()
	{
		parent::__construct();
		Auth::handleLogin();
	}

	function index()
	{
		$data = [
			'token'       => Session::setToken($this->class_name . '/index'),
			'battleplans' => $this->model->getAll(),
		];
		$this->view->render($this->class_name, 'index', 'バトルプラン', $data);
	}

	function detail($id = '')
	{
		$id = (int)$id;
		$battleplan = $this->model->getById($id);
		if (!$battleplan) {
			header('Location: ' . URL . 'battleplan');
			exit;
		}

		$data = [
			'token'      => Session::setToken($this->class_name . '/detail'),
			'battleplan' => $battleplan,
			'abilities'  => $this->model->getAbilitiesByBattleplanId($id),
		];
		$this->view->render(
			$this->class_name,
			'detail',
			$battleplan['name'] ?? 'バトルプラン',
			$data
		);
	}
}
