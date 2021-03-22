<?php

namespace Mouf\Database\QueryWriter\Utils;

use Doctrine\DBAL\Connection;
use Mouf\MoufManager;

class DbHelper
{
    public static function getAll(string $sql, ?int $offset, ?int $limit)
    {
        /** @var Connection $dbalConnection */
        $dbalConnection = MoufManager::getMoufManager()->get('dbalConnection');
        $sql .= self::getFromLimitString($offset, $limit);
        $statement = $dbalConnection->executeQuery($sql);
        $results = $statement->fetchAll();

        $array = [];

        foreach ($results as $result) {
            $array[] = $result;
        }

        return $array;
    }

    public static function getFromLimitString(?int $from = null, ?int $limit = null): string
    {
        if ($limit !== null) {
            $queryStr = ' LIMIT '.$limit;

            if ($from !== null) {
                $queryStr .= ' OFFSET '.$from;
            }

            return $queryStr;
        } else {
            return '';
        }
    }
}
