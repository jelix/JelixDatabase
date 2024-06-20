<?php
/**
 * @author     Laurent Jouanneau
 * @contributor Aurélien Marcel
 *
 * @copyright  2017-2020 Laurent Jouanneau, 2011 Aurélien Marcel
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

use Jelix\Database\ConnectionInterface;

interface SchemaInterface
{

    public function __construct(ConnectionInterface $conn);

    /**
     * @return ConnectionInterface
     */
    public function getConn();

    /**
     * create the given table if it does not exist.
     *
     * @param string       $name       the unprefixed table name
     * @param Column[]     $columns    list of columns
     * @param string|string[] $primaryKey the name of the column which contains the primary key
     * @param array           $attributes some table attributes specific to the database
     *
     * @return TableInterface the object corresponding to the created table
     */
    public function createTable($name, $columns, $primaryKey, $attributes = array());

    /**
     * load informations of the given.
     *
     * @param string $name the unprefixed table name
     *
     * @return TableInterface ready to make change
     */
    public function getTable($name);

    /**
     * @return TableInterface[]
     */
    public function getTables();

    /**
     * @param TableInterface|string $table the table object or the unprefixed table name
     */
    public function dropTable($table);

    /**
     * @param string $oldName Unprefixed name of the table to rename
     * @param string $newName The new unprefixed name of the table
     *
     * @return null|TableInterface
     */
    public function renameTable($oldName, $newName);

    /**
     * fill correctly some properties of the column, depending on its type
     * and other properties.
     *
     * @param Column $col
     */
    public function normalizeColumn($col);

    /**
     * return the SQL string corresponding to the given column.
     *
     * @param Column $col                the column
     * @param mixed     $isPrimaryKey
     * @param mixed     $isSinglePrimaryKey
     *
     * @return string the sql string
     */
    public function prepareSqlColumn($col, $isPrimaryKey = false, $isSinglePrimaryKey = false);

}
