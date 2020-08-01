<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2010-2020 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

interface TableInterface
{
    /**
     * @param string    $name   the table name
     * @param SchemaInterface $schema
     */
    public function __construct($name, SchemaInterface $schema);

    public function getName();

    /**
     * @return Column[]
     */
    public function getColumns();

    /**
     * @param string $name
     * @param bool $forChange
     * @return Column|null
     */
    public function getColumn($name, $forChange = false);

    /**
     * add a column.
     *
     * @return bool true if the column is added, false if not (already there)
     */
    public function addColumn(Column $column);

    /**
     * change a column definition. If the column does not exist,
     * it is created.
     *
     * @param Column $column      the colum with its new properties
     * @param string    $oldName     the name of the column to change (if the name is changed)
     * @param bool      $doNotCreate true if the column shoul dnot be created when it does not exist
     *
     * @return bool true if changed/created
     */
    public function alterColumn(Column $column, $oldName = '', $doNotCreate = false);

    public function dropColumn($name);

    /**
     *	@return false|PrimaryKey  false if there is no primary key
     */
    public function getPrimaryKey();

    public function setPrimaryKey(PrimaryKey $key);

    public function dropPrimaryKey();

    /**
     * @return Index[]
     */
    public function getIndexes();

    /**
     * @param mixed $name
     *
     * @return null|Index
     */
    public function getIndex($name);

    public function addIndex(Index $index);

    public function alterIndex(Index $index);

    public function dropIndex($indexName);

    /**
     * @return UniqueKey[]
     */
    public function getUniqueKeys();

    /**
     * @param mixed $name
     *
     * @return null|UniqueKey
     */
    public function getUniqueKey($name);

    public function addUniqueKey(UniqueKey $key);

    public function alterUniqueKey(UniqueKey $key);

    public function dropUniqueKey($indexName);

    /**
     * @return Reference[]
     */
    public function getReferences();

    /**
     * @param mixed $refName
     *
     * @return null|Reference
     */
    public function getReference($refName);
    public function addReference(Reference $reference);

    public function alterReference(Reference $reference);

    public function dropReference($refName);


}
