<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2012 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  +------------------------------------------------------------------------+
*/

use Phalcon\Tag;
use Phalcon\Web\Tools;
use Phalcon\Builder\BuilderException;

class ModelsController extends ControllerBase
{

	public function indexAction()
	{

		$config = Tools::getConfig();
		$connection = Tools::getConnection();

		$tables = array('all' => 'All');
		$result = $connection->query("SHOW TABLES");
		$result->setFetchMode(Phalcon\DB::FETCH_NUM);
		while($table = $result->fetchArray($result)){
			$tables[$table[0]] = $table[0];
		}

		$this->view->setVar('tables', $tables);
		$this->view->setVar('databaseName', $config->database->name);

	}

	/**
	 * Generate models
	 */
	public function createAction()
	{

		if ($this->request->isPost()) {

			$force = $this->request->getPost('force', 'int');
			$schema = $this->request->getPost('schema');
			$tableName = $this->request->getPost('tableName');
			$genSettersGetters = $this->request->getPost('genSettersGetters', 'int');
			$foreignKeys = $this->request->getPost('foreignKeys', 'int');
			$defineRelations = $this->request->getPost('defineRelations', 'int');

			try {

				$component = '\Phalcon\Builder\Model';
				if ($tableName == 'all') {
					$component = '\Phalcon\Builder\AllModels';
				}

				$modelBuilder = $component(array(
					'name' 					=> $tableName,
					'force' 				=> $force,
					'modelsDir' 			=> $this->_settings->phalcon->modelsDir,
					'directory' 			=> Phalcon_WebTools::getPath(),
					'foreignKeys' 			=> $foreignKeys,
					'defineRelations' 		=> $defineRelations,
					'genSettersGetters' 	=> $genSettersGetters

				));

				$modelBuilder->build();

				if ($tableName == 'all') {
					$this->flash->success('Models were created successfully');
				} else {
					$this->flash->success('Model "'.$tableName.'" was created successfully');
				}

			}
			catch(BuilderException $e){
				$this->flash->error($e->getMessage());
			}

		}

		return $this->_forward('models/index');

	}

	public function listAction()
	{
		$this->view->setVar('modelsDir', Tools::getConfig()->application->modelsDir);
	}

	public function editAction($fileName){

		$fileName = str_replace('..', '', $fileName);

		$modelsDir = Phalcon_WebTools::getPath('public/'.$this->_settings->phalcon->modelsDir);
		if(!file_exists($modelsDir.'/'.$fileName)){
			Phalcon_Flash::error('MOdel could not be found', 'alert alert-error');
			return $this->_forward('models/list');
		}

		Phalcon_Tag::setDefault('code', file_get_contents($modelsDir.'/'.$fileName));
		Phalcon_Tag::setDefault('name', $fileName);
		$this->view->setVar('name', $fileName);

	}

	public function saveAction(){

		if($this->request->isPost()){

			$fileName = $this->request->getPost('name', 'string');

			$fileName = str_replace('..', '', $fileName);

			$modelsDir = Phalcon_WebTools::getPath('public/'.$this->_settings->phalcon->modelsDir);
			if(!file_exists($modelsDir.'/'.$fileName)){
				Phalcon_Flash::error('model could not be found', 'alert alert-error');
				return $this->_forward('models/list');
			}

			if(!is_writable($modelsDir.'/'.$fileName)){
				Phalcon_Flash::error('model file does not have write access', 'alert alert-error');
				return $this->_forward('models/list');
			}

			file_put_contents($modelsDir.'/'.$fileName, $this->request->getPost('code'));

			Phalcon_Flash::success('The model "'.$fileName.'" was saved successfully', 'alert alert-success');

		}

		return $this->_forward('models/list');

	}

}