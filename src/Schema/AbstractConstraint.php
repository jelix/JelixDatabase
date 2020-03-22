<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2010-2020 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

abstract class AbstractConstraint
{
    /**
     * @var string Constraint name
     */
    public $name;

    /**
     * list of columns on which there is the constraint.
     *
     * @var string[]
     */
    public $columns = array();

    /**
     * AbstractConstraint constructor.
     *
     * @param string          $name
     * @param string|string[] $columns
     */
    public function __construct($name, $columns)
    {
        $this->name = $name;
        if (is_string($columns)) {
            $this->columns = array($columns);
        } else {
            $this->columns = $columns;
        }
    }
}
