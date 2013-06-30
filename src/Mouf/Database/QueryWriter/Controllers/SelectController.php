<?php
namespace Mouf\Database\QueryWriter\Controllers;

use SQLParser\Query\StatementFactory;

use SQLParser\SQLParser;

use Mouf\Controllers\AbstractMoufInstanceController;

use Mouf\Database\TDBM\Utils\TDBMDaoGenerator;

use Mouf\MoufManager;

use Mouf\Mvc\Splash\Controllers\Controller;

use Mouf\Reflection\MoufReflectionProxy;

use Mouf\Html\HtmlElement\HtmlBlock;

/**
 * The controller to generate automatically the Beans, Daos, etc...
 * Sweet!
 * 
 * @Component
 */
class SelectController extends AbstractMoufInstanceController {
	
	/**
	 *
	 * @var HtmlBlock
	 */
	public $content;

	protected $sql;
	
	/**
	 * Admin page used to display the DAO generation form.
	 *
	 * @Action
	 * //@Admin
	 */
	public function defaultAction($name, $selfedit="false") {
		$this->initController($name, $selfedit);
		
		$select = MoufManager::getMoufManagerHiddenInstance()->getInstance($name);
		$this->sql = $select->toSql(null, array(), 0, true); 
		
		$this->content->addFile(dirname(__FILE__)."/../../../../views/parseSql.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * This action generates the objects from the SQL query and applies it to the existing SELECT object. 
	 * 
	 * @Action
	 * @param string $name
	 * @param string $sql
	 * @param string $selfedit
	 */
	public function parse($name, $sql,$selfedit="false") {
		$this->initController($name, $selfedit);
		
		$parser = new SQLParser();
		$parsed = $parser->parse($sql);
		//print_r($parsed);
		$select = StatementFactory::toObject($parsed);
		//var_dump(StatementFactory::toObject($parsed));
		/*var_export($select);
		var_dump($select->toInstanceDescriptor(MoufManager::getMoufManager()));
		exit;*/
		//var_export($parsed);exit;
		$moufManager = MoufManager::getMoufManagerHiddenInstance();
		$select->overwriteInstanceDescriptor($name, $moufManager);
		$moufManager->rewriteMouf();
				
		header("Location: ".ROOT_URL."ajaxinstance/?name=".urlencode($name)."&selfedit=".$selfedit);
	}
	
	/**
	 * Admin page used to create a new SQL query.
	 *
	 * @Action
	 */
	public function createQuery($selfedit="false") {	
		$this->content->addFile(dirname(__FILE__)."/../../../../views/createQuery.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * This action generates the objects from the SQL query and creates a new SELECT instance.
	 *
	 * @Action
	 * @param string $name
	 * @param string $sql
	 * @param string $selfedit
	 */
	public function doCreateQuery($name, $sql,$selfedit="false") {
		$parser = new SQLParser();
		$parsed = $parser->parse($sql);
		$select = StatementFactory::toObject($parsed);
		
		$moufManager = MoufManager::getMoufManagerHiddenInstance();
		$instanceDescriptor = $select->toInstanceDescriptor($moufManager);
		$instanceDescriptor->setName($name);
		$moufManager->rewriteMouf();
		
		header("Location: ".ROOT_URL."ajaxinstance/?name=".urlencode($name)."&selfedit=".$selfedit);
	}
}