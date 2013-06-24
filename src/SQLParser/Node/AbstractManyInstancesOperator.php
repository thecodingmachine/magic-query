<?php 
namespace SQLParser\Node;

use Mouf\Database\DBConnection\ConnectionInterface;

use Mouf\MoufManager;
use Mouf\MoufInstanceDescriptor;


/**
 * This class represents an operator with many operators (AND, OR...) in an SQL expression. 
 * 
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
abstract class AbstractManyInstancesOperator implements NodeInterface {

	private $operands;
	
	public function getOperands() {
		return $this->operands;
	}
	
	/**
	 * Sets the operands
	 *
	 * @Important
	 * //@param array<array<NodeInterface>> $operands
	 * @param array<NodeInterface> $operands
	 */
	public function setOperands($operands) {
		if (!is_array($operands)) {
			$operands = array($operands);
		}
		$this->operands = $operands;
	}
	
	/**
	 * Returns a Mouf instance descriptor describing this object.
	 * 
	 * @param MoufManager $moufManager
	 * @return MoufInstanceDescriptor
	 */
	public function toInstanceDescriptor(MoufManager $moufManager) {
		$instanceDescriptor = $moufManager->createInstance(get_called_class());
		$instanceDescriptor->getProperty("operands")->setValue(NodeFactory::nodeToInstanceDescriptor($this->operands, $moufManager));
		return $instanceDescriptor;
	}
	
	/**
	 * Renders the object as a SQL string
	 *
	 * @param ConnectionInterface $dbConnection
	 * @return string
	 */
	public function toSql(ConnectionInterface $dbConnection = null) {
		$sqlOperands = array_map(function($item) use ($dbConnection) {
			return NodeFactory::toSql($item, $dbConnection, ' ', true);
		}, $this->operands);
		
		return implode("\n  ".$this->getOperatorSymbol().' ', $sqlOperands);
	}
	
	/**
	 * Returns the symbol for this operator.
	 * @return string
	 */
	abstract protected function getOperatorSymbol();
}