<?php
/*
 Copyright (C) 2006-2013 David Négrier - THE CODING MACHINE

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

use Mouf\Utils\Value\ValueUtils;

use Mouf\Database\DBConnection\ConnectionInterface;

/**
 * The AndFilter class translates into an "AND" SQL statement between many filters.
 * 
 * @author David Négrier
 */
class SqlValueUtils {
	public static function toSql($value, ConnectionInterface $dbConnection) {
		if ($value instanceof Param) {
			return $param->toSql();
		} else {
			return $dbConnection->quoteSmart(ValueUtils::val($value));	
		}
	}
}
