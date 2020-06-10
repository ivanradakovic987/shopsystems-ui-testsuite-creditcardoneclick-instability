<?php

namespace Helper;

//namespace Codeception\Module;
use Codeception\Exception\ModuleException;
use Codeception\Module;
use PDO;

/**
 * Additional methods for DB module
 */
class DbHelper extends Module
{

    /**
     * Method getColumnFromDatabaseNoCriteria
     * @param string $table
     * @param string $column
     * @return array
     *
     * @throws ModuleException
     */
    public function getColumnFromDatabaseNoCriteria($table, $column): array
    {
        $dbh = $this->getModule('Db')->dbh;
        $query = 'select %s from %s';
        $query = sprintf($query, $column, $table);
        $this->debugSection('Query', $query);
        $sth = $dbh->prepare($query);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Delete entries from $table where $criteria conditions
     * Use: $I->deleteFromDatabase('users', ['id' => '111111', 'banned' => 'yes']);
     *
     * @param string $table
     * @param array $criteria
     * @return boolean
     * @throws ModuleException
     */
    public function deleteFromDatabase($table, $criteria)
    {
        $dbh = $this->getModule('Db')->dbh;
        $query = "delete from %s where %s";
        $params = [];
        $values = [];

        foreach ($criteria as $k => $v) {
            $params[] = "$k = ?";
            array_push($values, $v);
        }
        $params = implode(' AND ', $params);
        $query = sprintf($query, $table, $params);
        $this->debugSection('Query', $query, json_encode($criteria));
        $sth = $dbh->prepare($query);

        return $sth->execute(array_values($criteria));
    }
}
