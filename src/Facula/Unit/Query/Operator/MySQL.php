<?php

/**
 * MySQL Operator for Query
 *
 * Facula Framework 2014 (C) Rain Lee
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
 * @copyright  2014 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit\Query\Operator;

use Facula\Unit\Query\Exception\Operator as Exception;
use Facula\Unit\Query\OperatorImplement;

/**
 * Query MySQL
 */
class MySQL implements OperatorImplement
{
    /** Table name */
    private $table = '';

    /** Query parameters */
    private $query = array();

    /**
     * Constructor
     *
     * @param string $tableName Name of the table
     * @param array $querySet Query setting
     *
     * @return void
     */
    public function __construct($tableName, array &$querySet)
    {
        $this->table = $tableName;
        $this->query = $querySet;
    }

    /**
     * Build the query in MySQL syntax
     *
     * @return string The SQL query
     */
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
                throw new Exception\MySQLUnknownActionType($this->query['Action']);

                break;
        }

        return false;
    }

    /**
     * Build the SELECT syntax
     *
     * @return string The SQL query
     */
    private function buildSelect()
    {
        $sql = 'SELECT';

        // Select
        if (!empty($this->query['Fields'])) {
            $sql .= ' ' . $this->doFields();
        }

        // From
        $sql .= ' FROM `' . $this->table . '`';

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
            $sql .= ' LIMIT ' . $this->doLimit();
        }

        return $sql;
    }

    /**
     * Build the INSERT syntax
     *
     * @return string The SQL query
     */
    private function buildInsert()
    {
        $sql = 'INSERT';

        // Into
        $sql .= ' INTO `' . $this->table . '`';

        // Field
        if (!empty($this->query['Fields'])) {
             $sql .= ' (' . $this->doFields() . ')';
        }

        if (!empty($this->query['Values'])) {
             $sql .= ' VALUES ' . $this->doValues();
        }

        return $sql;
    }

    /**
     * Build the UPDATE syntax
     *
     * @return string The SQL query
     */
    private function buildUpdate()
    {
        $sql = 'UPDATE `' . $this->table . '`';

        // Sets
        if (!empty($this->query['Sets']) || !empty($this->query['Changes'])) {
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
             $sql .= ' LIMIT ' . $this->doLimit(true);
        }

        return $sql;
    }

    /**
     * Build the DELETE syntax
     *
     * @return string The SQL query
     */
    private function buildDelete()
    {
        $sql = 'DELETE FROM `' . $this->table . '`';

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
             $sql .= ' LIMIT ' . $this->doLimit(true);
        }

        return $sql;
    }

    /**
     * Generate the field syntax
     *
     * @return string The SQL query
     */
    private function doFields()
    {
        return '`' . implode('`, `', array_keys($this->query['Fields'])) . '`';
    }

    /**
     * Generate the condition syntax
     *
     * @param string $name Type if the condition, WHERE or HAVING
     *
     * @return string The SQL query
     */
    private function doCondition($name)
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
                    $sql .= '(`' . $where['Field'] . '` = ' . $where['Value'] . ')';
                    break;

                case '>':
                    $sql .= '(`' . $where['Field'] . '` > ' . $where['Value'] . ')';
                    break;

                case '<':
                    $sql .= '(`' . $where['Field'] . '` < ' . $where['Value'] . ')';
                    break;

                case '<>':
                    $sql .= '(`' . $where['Field'] . '` <> ' . $where['Value'] . ')';
                    break;

                case '<=>':
                    $sql .= '(`' . $where['Field'] . '` <=> ' . $where['Value'] . ')';
                    break;

                case '<=':
                    $sql .= '(`' . $where['Field'] . '` <= ' . $where['Value'] . ')';
                    break;

                case '>=':
                    $sql .= '(`' . $where['Field'] . '` >= ' . $where['Value'] . ')';
                    break;

                case 'IS':
                    $sql .= '(`' . $where['Field'] . '` IS ' . $where['Value'] . ')';
                    break;

                case 'IS NOT':
                    $sql .= '(`' . $where['Field'] . '` IS NOT ' . $where['Value'] . ')';
                    break;

                case 'LIKE':
                    $sql .= '(`' . $where['Field'] . '` LIKE ' . $where['Value'] . ' ESCAPE \'!\')';
                    break;

                case 'NOT LIKE':
                    $sql .= '(`' . $where['Field'] . '` NOT LIKE ' . $where['Value'] . ' ESCAPE \'!\')';
                    break;

                case 'BETWEEN':
                    $sql .= '(`' . $where['Field'] . '` BETWEEN ' . $where['Value'][0]
                        . ' AND ' . $where['Value'][1] . ')';
                    break;

                case 'NOT BETWEEN':
                    $sql .= '(`' . $where['Field'] . '` NOT BETWEEN ' . $where['Value'][0]
                        . ' AND ' . $where['Value'][1] . ')';
                    break;

                case 'IN':
                    $sql .= '(`' . $where['Field'] . '` IN (' . implode(
                        ', ',
                        $where['Value']
                    ) . '))';
                    break;

                case 'NOT IN':
                    $sql .= '(`' . $where['Field'] . '` NOT IN (' . implode(
                        ', ',
                        $where['Value']
                    ) . '))';
                    break;

                case 'IS NULL':
                    $sql .= '(`' . $where['Field'] . '` IS NULL)';
                    break;

                case 'IS NOT NULL':
                    $sql .= '(`' . $where['Field'] . '` IS NOT NULL)';
                    break;
            }
        }

        return $sql;
    }

    /**
     * Generate the order syntax
     *
     * @return string The SQL query
     */
    private function doOrder()
    {
        $sql = '';

        foreach ($this->query['Order'] as $order) {
            $sql .= $sql ? ', ' : '';

            $sql .= '`' . $order['Field'] . '`';

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

    /**
     * Generate the group syntax
     *
     * @return string The SQL query
     */
    private function doGroup()
    {
        $sql = '';

        foreach ($this->query['Group'] as $field) {
            $sql .= $sql ? ', ' : '';

            $sql .= '`' . $field . '`';
        }

        return $sql;
    }

    /**
     * Generate the limit syntax
     *
     * @param bool $disOnly Is the Limit only has distance?
     *
     * @return string The SQL query
     */
    private function doLimit($disOnly = false)
    {
        return (!$disOnly ? $this->query['Limit']['Offset'] . ', ' : '')
                . $this->query['Limit']['Distance'];
    }

    /**
     * Generate the value syntax
     *
     * @return string The SQL query
     */
    private function doValues()
    {
        $sql = '';

        foreach ($this->query['Values'] as $value) {
            $sql .= $sql ? ', ' : '';

            $sql .= '(' . implode(', ', $value) . ')';
        }

        return $sql;
    }

    /**
     * Generate the set syntax
     *
     * @return string The SQL query
     */
    private function doSets()
    {
        $sql = '';

        foreach ($this->query['Sets'] as $set) {
            $sql .= $sql ? ', ' : '';

            $sql .= '`' . $set['Field'] . '` = ' . $set['Value'];
        }

        if (isset($this->query['Changes'])) {
            foreach ($this->query['Changes'] as $change) {
                $sql .= $sql ? ', ' : '';

                $sql .= '`' . $change['Field'] . '` = `'
                    . $change['Field'] . '` ' . $change['Operator']
                    . ' ' . $change['Value'];
            }
        }

        return $sql;
    }

    /**
     * Replace some specific string for query accuracy
     *
     * @param string $mode Escape mode
     * @param mixed $value Value to escape
     *
     * @return mixed Escaped value
     */
    public function escape($mode, $value)
    {
        switch ($mode) {
            case 'LIKE %':
                return str_replace(
                    array('!', '_', '%'),
                    array('!!', '!_', '!%'),
                    $value
                ) . '%';
                break;

            case '% LIKE':
                return '%' . str_replace(
                    array('!', '_', '%'),
                    array('!!', '!_', '!%'),
                    $value
                );
                break;

            case '% LIKE %':
                return '%' . str_replace(
                    array('!', '_', '%'),
                    array('!!', '!_', '!%'),
                    $value
                ) . '%';
                break;

            default:
                throw new Exception\UnsupportedEscapeMode($mode);
                break;
        }

        return false;
    }

    /**
     * Trim the PDO statement for SELECT
     *
     * @param $statement PDO
     *
     * @return array The result of fetch
     */
    public function fetch(\PDOStatement $statement)
    {
        return $statement;
    }

    /**
     * Trim the PDO statement for INSERT
     *
     * @param \PDOStatement $statement
     * @param string $primaryKey Primary field of the table
     *
     * @return mixed Return the last inserted ID
     */
    public function insert(\PDOStatement $statement, $primaryKey)
    {
        return $statement->connection->lastInsertId();
    }

    /**
     * Trim the PDO statement for UPDATE
     *
     * @param \PDOStatement $statement
     *
     * @return integer Return the number of affected rows
     */
    public function update(\PDOStatement $statement)
    {
        return $statement->rowCount();
    }

    /**
     * Trim the PDO statement for DELETE
     *
     * @param \PDOStatement $statement
     *
     * @return integer Return the number of affected rows
     */
    public function delete(\PDOStatement $statement)
    {
        return $statement->rowCount();
    }
}
