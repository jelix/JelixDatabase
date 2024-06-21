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
     * @return string the real table name, i.e., with prefix
     */
    public function getRealTableName();

    /**
     * @return string the table name, without prefixe
     */
    public function getTableName();

    /**
     * @return string the schema name
     */
    public function getSchemaName();

    /**
     * @return string the full name of the table, including the schema name if set and supported, and the prefix
     */
    public function getFullName();

    /**
     * @return string the full name, with delimiter around names
     */
    public function getEnclosedFullName();

    /**
     * Prefix added to the name, when all table should be prefixed
     * @return string the prefix
     */
    public function getPrefix();
}