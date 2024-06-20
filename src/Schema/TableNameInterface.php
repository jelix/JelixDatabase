<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2024 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

interface TableNameInterface
{

    /**
     * @return string the table name
     */
    public function getTableName();

    /**
     * @return string the schema name
     */
    public function getSchemaName();

    /**
     * @return string the full name of the table, including the schema name if set and supported
     */
    public function getFullName();

    /**
     * @return string the full name, with delimiter around names
     */
    public function getEnclosedFullName();
}