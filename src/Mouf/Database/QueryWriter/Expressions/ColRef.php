<?php
/*
 Copyright (C) 2006-2011 David Négrier - THE CODING MACHINE

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Mouf\Database\QueryWriter\Filters;

use Mouf\Database\QueryWriter\Expressions\ExpressionInterface;

use Mouf\Utils\Value\ValueUtils;

use Mouf\Utils\Value\ScalarValueInterface;

use Mouf\Database\DBConnection\ConnectionInterface;

/**
 * The GreaterFilter class translates into an ">" SQL statement.
 * 
 * @Component
 * @author David Négrier
 */
class ColRef implements ExpressionInterface {
	private $tableName;
	private $columnName;
	
	/**
	 * The table name (or alias if any) to use in the filter.
	 * 
	 * @Property
	 * @Compulsory
	 * @param string $tableName
	 */
	public function setTableName($tableName) {
		$this->tableName = $tableName;
	}

	/**
	 * The column name (or alias if any) to use in the filter.
	 * 
	 * @Property
	 * @Compulsory
	 * @param string $columnName
	 */
	public function setColumnName($columnName) {
		$this->columnName = $columnName;
	}
	
	/**
	 * Default constructor to build the filter.
	 * All parameters are optional and can later be set using the setters.
	 * 
	 * @Important $tableName
	 * @Important $columnName
	 * @param string $tableName
	 * @param string $columnName
	 */
	public function __construct($tableName=null, $columnName=null) {
		$this->tableName = $tableName;
		$this->columnName = $columnName;
	}

	/**
	 * Returns the SQL of the filter (the SQL WHERE clause).
	 *
	 * @param ConnectionInterface $dbConnection
	 * @return string
	 */
	public function toSql(ConnectionInterface $dbConnection) {
		return $dbConnection->escapeDBItem($this->tableName).'.'.$dbConnection->escapeDBItem($this->columnName);
	}

	/**
	 * Returns the tables used in the filter in an array.
	 *
	 * @return array<string>
	 */
	public function getUsedTables() {
		return array($this->tableName);
	}
}
