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
     * @param string          $name       the unprefixed table name
     * @param Column[]     $columns    list of columns
     * @param string|string[] $primaryKey the name of the column which contains the primary key
     * @param array           $attributes some table attributes specific to the database
     *
     * @return AbstractTable the object corresponding to the created table
     */
    public function createTable($name, $columns, $primaryKey, $attributes = array());

    /**
     * load informations of the given.
     *
     * @param string $name the unprefixed table name
     *
     * @return AbstractTable ready to make change
     */
    public function getTable($name);

    /**
     * @return AbstractTable[]
     */
    public function getTables();

    /**
     * @param AbstractTable|string $table the table object or the unprefixed table name
     */
    public function dropTable($table);

    /**
     * @param string $oldName Unprefixed name of the table to rename
     * @param string $newName The new unprefixed name of the table
     *
     * @return null|AbstractTable
     */
    public function renameTable($oldName, $newName);

    /**
     * fill correctly some properties of the column, depending of its type
     * and other properties.
     *
     * @param Column $col
     */
    public function normalizeColumn($col);

}
