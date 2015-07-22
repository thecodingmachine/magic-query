<?php

namespace Mouf\Database\QueryWriter\Utils;

use Mouf\MoufManager;

class DbHelper
{
    public static function getAll($sql, $offset, $limit)
    {
        $dbalConnection = MoufManager::getMoufManager()->get('dbalConnection');
        /* @var $dbalConnection \Doctrine\DBAL\Connection */
        $sql .= self::getFromLimitString($offset, $limit);
        $statement = $dbalConnection->executeQuery($sql);
        $results = $statement->fetchAll();

        $array = [];

        foreach ($results as $result) {
            $array[] = $result;
        }

        return $array;
    }

    public static function getFromLimitString($from = null, $limit = null)
    {
        if ($limit !== null) {
            $limitInt = (int) $limit;
            $queryStr = ' LIMIT '.$limitInt;

            if ($from !== null) {
                $fromInt = (int) $from;
                $queryStr .= ' OFFSET '.$fromInt;
            }

            return $queryStr;
        } else {
            return '';
        }
    }
}
