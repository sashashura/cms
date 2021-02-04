<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db\mysql;

use Craft;
use craft\db\Connection;
use yii\base\NotSupportedException;

/**
 * @inheritdoc
 * @property Connection $db Connection the DB connection that this command is associated with.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class QueryBuilder extends \yii\db\mysql\QueryBuilder
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Use LONGBLOB for "binary" columns rather than BLOB
        $this->typeMap[Schema::TYPE_BINARY] = 'longblob';
    }

    /**
     * @inheritdoc
     * @param string $table the name of the table to be created. The name will be properly quoted by the method.
     * @param array $columns the columns (name => definition) in the new table.
     * @param string|null $options additional SQL fragment that will be appended to the generated SQL.
     * @return string the SQL statement for creating a new DB table.
     */
    public function createTable($table, $columns, $options = null): string
    {
        // Default to InnoDb
        if ($options === null || !preg_match('/\bENGINE\b/i', $options) === false) {
            $options = ($options !== null ? $options . ' ' : '') . 'ENGINE = InnoDb';
        }

        // Use the default charset and collation
        $dbConfig = Craft::$app->getConfig()->getDb();
        if (!preg_match('/\bCHARACTER +SET\b/i', $options)) {
            $options .= " DEFAULT CHARACTER SET = $dbConfig->charset";
        }
        if ($dbConfig->collation !== null && !preg_match('/\bCOLLATE\b/i', $options)) {
            $options .= " DEFAULT COLLATE = $dbConfig->collation";
        }

        return parent::createTable($table, $columns, $options);
    }

    /**
     * Builds a SQL statement for renaming a DB sequence.
     *
     * @param string $oldName the sequence to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new sequence name. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB table.
     * @throws NotSupportedException
     */
    public function renameSequence(string $oldName, string $newName): string
    {
        throw new NotSupportedException($this->db->getDriverName() . ' does not support renaming sequences.');
    }

    /**
     * Builds a SQL statement for dropping a DB table if it exists.
     *
     * @param string $table The table to be dropped. The name will be properly quoted by the method.
     * @return string The SQL statement for dropping a DB table.
     */
    public function dropTableIfExists(string $table): string
    {
        return 'DROP TABLE IF EXISTS ' . $this->db->quoteTableName($table);
    }

    /**
     * Builds a SQL statement for replacing some text with other text in a given table column.
     *
     * @param string $table The table to be updated.
     * @param string $column The column to be searched.
     * @param string $find The text to be searched for.
     * @param string $replace The replacement text.
     * @param array|string $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     * @return string The SQL statement for replacing some text in a given table.
     */
    public function replace(string $table, string $column, string $find, string $replace, $condition, array &$params): string
    {
        $column = $this->db->quoteColumnName($column);

        $findPhName = self::PARAM_PREFIX . count($params);
        $params[$findPhName] = $find;

        $replacePhName = self::PARAM_PREFIX . count($params);
        $params[$replacePhName] = $replace;

        $sql = "UPDATE {$table} SET {$column} = REPLACE({$column}, {$findPhName}, {$replacePhName})";
        $where = $this->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    /**
     * Builds the SQL expression used to return a DB result in a fixed order.
     *
     * @param string $column The column name that contains the values.
     * @param array $values The column values, in the order in which the rows should be returned in.
     * @return string The SQL expression.
     */
    public function fixedOrder(string $column, array $values): string
    {
        $sql = 'FIELD(' . $this->db->quoteColumnName($column);
        foreach ($values as $value) {
            $sql .= ',' . $this->db->quoteValue($value);
        }
        $sql .= ')';

        return $sql;
    }

    /**
     * Builds the SQL expression used to delete duplicate rows from a table.
     *
     * @param string $table The table to be updated.
     * @param string[] $columns The column names that contain duplicate data
     * @param string $pk The primary key column name
     * @return string The SQL expression
     * @since 3.5.2
     */
    public function deleteDuplicates(string $table, array $columns, string $pk = 'id'): string
    {
        $table = $this->db->quoteTableName($table);
        $pk = $this->db->quoteColumnName($pk);
        $a = $this->db->quoteColumnName('a');
        $b = $this->db->quoteColumnName('b');

        $sql = "DELETE $a FROM $table $a" .
            " INNER JOIN $table $b" .
            " WHERE $a.$pk > $b.$pk";

        foreach ($columns as $column) {
            $column = $this->db->quoteColumnName($column);
            $sql .= " AND $a.$column = $b.$column";
        }

        return $sql;
    }
}
