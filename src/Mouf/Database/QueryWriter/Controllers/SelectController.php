<?php
namespace Mouf\Database\QueryWriter\Controllers;

use Mouf\Database\QueryWriter\Utils\FindParametersService;

use Mouf\MoufPropertyDescriptor;

use Mouf\Reflection\MoufReflectionClass;

use Mouf\MoufInstanceDescriptor;

use SQLParser\Query\StatementFactory;

use SQLParser\SQLParser;

use Mouf\Controllers\AbstractMoufInstanceController;

use Mouf\Database\TDBM\Utils\TDBMDaoGenerator;

use Mouf\MoufManager;

use Mouf\Mvc\Splash\Controllers\Controller;

use Mouf\Reflection\MoufReflectionProxy;

use Mouf\Html\HtmlElement\HtmlBlock;
use Mouf\InstanceProxy;
use Mouf\Html\Utils\WebLibraryManager\WebLibrary;
use Mouf\Html\Widgets\EvoluGrid\EvoluGridResultSet;

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
	
	/**
	 * @var string
	 */
	protected $sql;
	
	/**
	 * List of available parameters.
	 * @var string[]
	 */
	protected $parameters;
	
	protected $parseError = false;
	
	/**
	 * Admin page used to edit the SQL of a SELECT instance.
	 *
	 * @Action
	 * //@Admin
	 */
	public function defaultAction($name, $sql = null,  $selfedit="false") {
		$this->initController($name, $selfedit);
		
		$select = MoufManager::getMoufManagerHiddenInstance()->getInstance($name);
		if ($sql != null) {
			$this->sql = $sql;
		} else {
			$this->sql = $select->toSql(null, array(), 0, true);
		} 
		
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
		
		if ($parsed == false) {
			$this->parseError = true;
			$this->defaultAction($name, $sql, $selfedit);
			return;
		}
		
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
				
		header("Location: ".ROOT_URL."parseselect/tryQuery?name=".urlencode($name)."&selfedit=".$selfedit);
	}
	
	/**
	 * Admin page used to create a new SQL query.
	 *
	 * @Action
	 */
	public function createQuery($name = null, $sql = null, $selfedit="false") {
		$this->instanceName = $name;
		$this->sql = $sql;
			
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
		
		if ($parsed == false) {
			$this->parseError = true;
			$this->createQuery($name, $sql, $selfedit);
			return;
		}
		
		$select = StatementFactory::toObject($parsed);
		
		$moufManager = MoufManager::getMoufManagerHiddenInstance();
		$instanceDescriptor = $select->toInstanceDescriptor($moufManager);
		$instanceDescriptor->setName($name);
		$moufManager->rewriteMouf();
		
		header("Location: ".ROOT_URL."parseselect/tryQuery?name=".urlencode($name)."&selfedit=".$selfedit);
	}
	
	
	/**
	 * @Action
	 * @param string $name
	 * @param string $selfedit
	 */
	public function tryQuery($name,$selfedit="false") {
		$this->instanceName = $name;
		
		$moufManager = MoufManager::getMoufManagerHiddenInstance();
		$instanceDescriptor = $moufManager->getInstanceDescriptor($name);
		$this->parameters = FindParametersService::findParameters($instanceDescriptor);
		
		$select = MoufManager::getMoufManagerHiddenInstance()->getInstance($name);
		$this->sql = $select->toSql(null, array(), 0, true);
		
		$this->template->getWebLibraryManager()->addLibrary(new WebLibrary(
			array("../../../vendor/mouf/html.widgets.evolugrid/js/evolugrid.js")
		));
		
		$this->content->addFile(dirname(__FILE__)."/../../../../views/tryQuery.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * @Action
	 * @param string $name
	 * @param string $selfedit
	 */
	public function getParameterizedQuery($name,$parameters,$selfedit="false") {
		$select = new InstanceProxy($name);
		echo $select->toSql(null, $parameters);
	}
	
	/**
	 * 
	 * @Action
	 * @param string $name
	 * @param int $offset
	 * @param int $limit
	 */
	public function runQuery($name, $parameters, $offset = null, $limit = null) {
		$select = new InstanceProxy($name);
		$sql = $select->toSql(null, $parameters);
		
		// TODO: point to the right dbConnection
		$dbConnection = new InstanceProxy("dbConnection");
		$results = $dbConnection->getAll($sql, \PDO::FETCH_ASSOC, "stdClass", $offset, $limit);
		
		$evolugridResultSet = new EvoluGridResultSet();
		$evolugridResultSet->setResults($results);
		
		$evolugridResultSet->output(EvoluGridResultSet::FORMAT_JSON);
	}
}