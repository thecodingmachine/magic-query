<?php 
namespace SQLParser\Node;

use Mouf\Utils\Common\ConditionInterface\ConditionTrait;

use Mouf\Database\DBConnection\ConnectionInterface;

use Mouf\MoufManager;

use Mouf\MoufInstanceDescriptor;

/**
 * This class represents an operation that takes 2 operands (for instance =, <, >, etc...) in an SQL expression. 
 * 
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
abstract class AbstractTwoOperandsOperator implements NodeInterface {

	use ConditionTrait;
	
	private $leftOperand;
	
	public function getLeftOperand() {
		return $this->leftOperand;
	}
	
	/**
	 * Sets the leftOperand
	 *
	 * @Important
	 * @param NodeInterface|NodeInterface[]|string $leftOperand
	 */
	public function setLeftOperand($leftOperand) {
		$this->leftOperand = $leftOperand;
	}
	
	private $rightOperand;
	
	public function getRightOperand() {
		return $this->rightOperand;
	}
	
	/**
	 * Sets the rightOperand
	 *
	 * @Important
	 * @param string|NodeInterface|NodeInterface[] $rightOperand
	 */
	public function setRightOperand($rightOperand) {
		$this->rightOperand = $rightOperand;
	}
	
	/**
	 * Returns a Mouf instance descriptor describing this object.
	 *
	 * @param MoufManager $moufManager
	 * @return MoufInstanceDescriptor
	 */
	public function toInstanceDescriptor(MoufManager $moufManager) {
		$instanceDescriptor = $moufManager->createInstance(get_called_class());
		$instanceDescriptor->getProperty("leftOperand")->setValue(NodeFactory::nodeToInstanceDescriptor($this->leftOperand, $moufManager));
		$instanceDescriptor->getProperty("rightOperand")->setValue(NodeFactory::nodeToInstanceDescriptor($this->rightOperand, $moufManager));
		
		if ($this->leftOperand instanceof Parameter) {
			// Let's add a condition on the parameter.
			$conditionDescriptor = $moufManager->createInstance("Mouf\\Database\\QueryWriter\\Condition\\ParamAvailableCondition");
			$conditionDescriptor->getProperty("parameterName")->setValue($this->leftOperand->getName());
			$instanceDescriptor->getProperty("condition")->setValue($conditionDescriptor);
		}
		// TODO: manage cases where both leftOperand and rightOperand are parameters.
		if ($this->rightOperand instanceof Parameter) {
			// Let's add a condition on the parameter.
			$conditionDescriptor = $moufManager->createInstance("Mouf\\Database\\QueryWriter\\Condition\\ParamAvailableCondition");
			$conditionDescriptor->getProperty("parameterName")->setValue($this->rightOperand->getName());
			$instanceDescriptor->getProperty("condition")->setValue($conditionDescriptor);
		}
		
		return $instanceDescriptor;
	}
	
	/**
	 * Renders the object as a SQL string
	 * 
	 * @param ConnectionInterface $dbConnection
	 * @param array $parameters
	 * @param number $indent
	 * @param bool $ignoreConditions
	 * @return string
	 */
	public function toSql(ConnectionInterface $dbConnection = null, array $parameters = array(), $indent = 0, $ignoreConditions = false) {
		if ($ignoreConditions || $this->condition->isOk($parameters)) {		
			$sql = NodeFactory::toSql($this->leftOperand, $dbConnection, $parameters, ' ', false, $indent, $ignoreConditions);
			$sql .= ' '.$this->getOperatorSymbol().' ';
			$sql .= NodeFactory::toSql($this->rightOperand, $dbConnection, $parameters, ' ', false, $indent, $ignoreConditions);
		} else {
			$sql = null;
		}
		
		return $sql;
	}
	
	/**
	 * Returns the symbol for this operator.
	 */
	abstract protected function getOperatorSymbol();
}