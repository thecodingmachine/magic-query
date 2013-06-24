<?php 
namespace SQLParser\Node;

/**
 * This class represents a regexp operation in an SQL expression. 
 * 
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Regexp extends AbstractTwoOperandsOperator {
	/**
	 * Returns the symbol for this operator.
	 * @return string
	 */
	protected function getOperatorSymbol() {
		return 'REGEXP';
	}
}