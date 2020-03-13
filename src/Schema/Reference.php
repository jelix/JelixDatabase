<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2010-2020 Laurent Jouanneau
 *
 * @see        http://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

/**
 * used to declare a foreign key.
 */
class Reference extends AbstractConstraint
{
    /**
     * name of the foreign table.
     *
     * @var string
     */
    public $fTable = '';

    /**
     * list of foreign columns.
     *
     * @var string[]
     */
    public $fColumns = array();

    public $onUpdate = '';
    public $onDelete = '';

    /**
     * Reference constructor.
     *
     * Note: all parameters are optional, to be compatible with Jelix < 1.6.16
     * where parameters didn't exist
     *
     * @param string          $name
     * @param string|string[] $columns
     * @param string          $foreignTable
     * @param string|string[] $foreignColumns
     */
    public function __construct($name = '', $columns = array(), $foreignTable = '', $foreignColumns = array())
    {
        parent::__construct($name, $columns);
        $this->fTable = $foreignTable;
        if (is_string($foreignColumns)) {
            $this->fColumns = array($foreignColumns);
        } else {
            $this->fColumns = $foreignColumns;
        }
    }
}
