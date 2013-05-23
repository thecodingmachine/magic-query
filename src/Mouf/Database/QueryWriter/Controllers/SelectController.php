<?php
namespace Mouf\Database\QueryWriter\Controllers;

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
		
	/**
	 * Admin page used to display the DAO generation form.
	 *
	 * @Action
	 * //@Admin
	 */
	public function defaultAction($name, $selfedit="false") {
		$this->initController($name, $selfedit);
		require_once __DIR__.'/../../../../php-sql-parser/php-sql-parser.php';
		
		$parser = new PHPSQLParser();
		$parsed = $parser->parse("SELECT toto as tata FROM table");
		print_r($parsed);
		exit;
		
		$this->content->addFile(dirname(__FILE__)."/../../../../views/parseSql.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * This action generates the DAOs and Beans for the TDBM service passed in parameter. 
	 * 
	 * @Action
	 * @param string $name
	 * @param bool $selfedit
	 */
	public function generate($name, $sourcedirectory, $daonamespace, $beannamespace, $daofactoryclassname, $daofactoryinstancename, $keepSupport = 0,$selfedit="false") {
		$this->initController($name, $selfedit);

				
		// TODO: better: we should redirect to a screen that list the number of DAOs generated, etc...
		header("Location: ".ROOT_URL."ajaxinstance/?name=".urlencode($name)."&selfedit=".$selfedit);
	}
	
}