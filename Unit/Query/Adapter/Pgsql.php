<?php

/**
 * Facula Framework Struct Manage Unit
 *
 * Facula Framework 2013 (C) Rain Lee
 *
 * Facula Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, version 3.
 *
 * Facula Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Rain Lee <raincious@gmail.com>
 * @copyright  2013 Rain Lee
 * @package    FaculaFramework
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit\Query\Adapter;

class Pgsql implements \Facula\Unit\Query\AdapterImplement
{
    private $table = '';
    private $query = array();

    public function __construct($tableName, &$querySet)
    {
        $this->table = $tableName;
        $this->query = $querySet;

        return true;
    }

    public function build()
    {
        switch ($this->query['Action']) {
            case 'select':
                return $this->buildSelect();
                break;

            case 'insert':
                return $this->buildInsert();
                break;

            case 'update':
                return $this->buildUpdate();
                break;

            case 'delete':
                return $this->buildDelete();
                break;

            default:
                \Facula\Framework::core('debug')->exception('ERROR_QUERY_MYSQL_UNKONWN_ACTION_TYPE|' . $this->query['Action'], 'query', true);
                break;
        }

        return false;
    }

    private function buildSelect()
    {
        $sql = 'SELECT';

        // Select
        if (!empty($this->query['Fields'])) {
            $sql .= ' ' . $this->doFields();
        }

        // From
        $sql .= ' FROM "' . $this->table . '"';

        // Where
        if (!empty($this->query['Where'])) {
            $sql .= ' WHERE ' . $this->doCondition('Where');
        }

        // Group by
        if (!empty($this->query['Group'])) {
            $sql .= ' GROUP BY ' . $this->doGroup();
        }

        // Having
        if (!empty($this->query['Having'])) {
            $sql .= ' HAVING ' . $this->doCondition('Having');
        }

        // Order by
        if (!empty($this->query['Order'])) {
            $sql .= ' ORDER BY ' . $this->doOrder();
        }

        // Limit
        if (!empty($this->query['Limit'])) {
            $sql .= ' ' . $this->doLimit();
        }

        return $sql;
    }

    private function buildInsert()
    {
        $sql = 'INSERT';

        // Into
        $sql .= ' INTO "' . $this->table . '"';

        // Field
        if (!empty($this->query['Fields'])) {
             $sql .= ' (' . $this->doFields() . ')';
        }

        if (!empty($this->query['Values'])) {
             $sql .= ' VALUES ' . $this->doValues();
        }

        return $sql;
    }

    private function buildUpdate()
    {
        $sql = 'UPDATE "' . $this->table . '"';

        // Sets
        if (!empty($this->query['Sets'])) {
             $sql .= ' SET ' . $this->doSets();
        }

        // Where
        if (!empty($this->query['Where'])) {
             $sql .= ' WHERE ' . $this->doCondition('Where');
        }

        // Order
        if (!empty($this->query['Order'])) {
             $sql .= ' ORDER BY ' . $this->doOrder();
        }

        // Limit
        if (!empty($this->query['Limit'])) {
             $sql .= ' ' . $this->doLimit(true);
        }

        return $sql;
    }

    private function buildDelete()
    {
        $sql = 'DELETE FROM "' . $this->table . '"';

        // Where
        if (!empty($this->query['Where'])) {
             $sql .= ' WHERE ' . $this->doCondition('Where');
        }

        // Order
        if (!empty($this->query['Order'])) {
             $sql .= ' ORDER BY ' . $this->doOrder();
        }

        // Limit
        if (!empty($this->query['Limit'])) {
             $sql .= ' ' . $this->doLimit(true);
        }

        return $sql;
    }

    // Builder Functions
    public function doFields()
    {
        return '"' . implode('", "', array_keys($this->query['Fields'])) . '"';
    }

    public function doCondition($name)
    {
        $sql = '';

        // Unset the logic setting for the very first item in where
        if (isset($this->query[$name][0])) {
            $this->query[$name][0]['Logic'] = '';
        }

        foreach ($this->query[$name] as $key => $where) {
            $sql .= ($sql ? ' ' : '');

            switch ($where['Logic']) {
                case 'AND':
                    $sql .= 'AND ';
                    break;

                case 'OR':
                    $sql .= 'OR ';
                    break;
            }

            switch ($where['Operator']) {
                case '=':
                    $sql .= '("' . $where['Field'] . '" = ' . $where['Value'] . ')';
                    break;

                case '>':
                    $sql .= '("' . $where['Field'] . '" > ' . $where['Value'] . ')';
                    break;

                case '<':
                    $sql .= '("' . $where['Field'] . '" < ' . $where['Value'] . ')';
                    break;

                case '<>':
                    $sql .= '("' . $where['Field'] . '" <> ' . $where['Value'] . ')';
                    break;

                case '<=>':
                    $sql .= '("' . $where['Field'] . '" = ' . $where['Value'] . ' OR "' . $where['Field'] . '" IS NULL)';
                    break;

                case '<=':
                    $sql .= '("' . $where['Field'] . '" <= ' . $where['Value'] . ')';
                    break;

                case '>=':
                    $sql .= '("' . $where['Field'] . '" >= ' . $where['Value'] . ')';
                    break;

                case 'IS':
                    $sql .= '("' . $where['Field'] . '" IS ' . $where['Value'] . ')';
                    break;

                case 'IS NOT':
                    $sql .= '("' . $where['Field'] . '" IS ' . $where['Value'] . ')';
                    break;

                case 'LIKE':
                    $sql .= '("' . $where['Field'] . '" LIKE ' . $where['Value'] . ')';
                    break;

                case 'NOT LIKE':
                    $sql .= '("' . $where['Field'] . '" LIKE ' . $where['Value'] . ')';
                    break;

                case 'BETWEEN':
                    $sql .= '("' . $where['Field'] . '" BETWEEN ' . $where['Value'][0] . ' AND ' . $where['Value'][1] . ')';
                    break;

                case 'NOT BETWEEN':
                    $sql .= '("' . $where['Field'] . '" NOT BETWEEN ' . $where['Value'][0] . ' AND ' . $where['Value'][1] . ')';
                    break;

                case 'IN':
                    $sql .= '("' . $where['Field'] . '" IN (' . implode(', ', $where['Value']) . '))';
                    break;

                case 'NOT IN':
                    $sql .= '("' . $where['Field'] . '" NOT IN (' . implode(', ', $where['Value']) . '))';
                    break;

                case 'IS NULL':
                    $sql .= '("' . $where['Field'] . '" IS NULL)';
                    break;

                case 'IS NOT NULL':
                    $sql .= '("' . $where['Field'] . '" IS NOT NULL)';
                    break;
            }
        }

        return $sql;
    }

    private function doOrder()
    {
        $sql = '';

        foreach ($this->query['Order'] as $order) {
            $sql .= $sql ? ', ' : '';

            $sql .= '"' . $order['Field'] . '"';

            switch ($order['Sort']) {
                case 'DESC':
                    $sql .= ' ' . $order['Sort'];
                    break;

                case 'ASC':
                    $sql .= ' ' . $order['Sort'];
                    break;
            }
        }

        return $sql;
    }

    private function doGroup()
    {
        $sql = '';

        foreach ($this->query['Group'] as $field) {
            $sql .= $sql ? ', ' : '';

            $sql .= '"' . $field . '"';
        }

        return $sql;
    }

    private function doLimit($disOnly = false)
    {
        return 'LIMIT ' . $this->query['Limit']['Distance'] . ' OFFSET ' . $this->query['Limit']['Offset'];
    }

    private function doValues()
    {
        $sql = '';

        foreach ($this->query['Values'] as $value) {
            $sql .= $sql ? ', ' : '';

            $sql .= '(' . implode(', ', $value) . ')';
        }

        return $sql;
    }

    private function doSets()
    {
        $sql = '';

        foreach ($this->query['Sets'] as $set) {
            $sql .= $sql ? ', ' : '';

            $sql .= '"' . $set['Field'] . '" = ' . $set['Value'];
        }

        return $sql;
    }

    // Query resulting methods
    public function fetch($statement)
    {
        $data = array();

        while ($row = $statement->fetch()) {
            $data[] = $row;
        }

        return $data;
    }

    public function insert($statement, $primaryKey)
    {
        $seqFullName = '"' . $this->table . '_' . $primaryKey . '_seq' . '"';

        return $statement->connection->lastInsertId($seqFullName);
    }

    public function update($statement)
    {
        return $statement->rowCount();
    }

    public function delete($statement)
    {
        return $statement->rowCount();
    }
}
