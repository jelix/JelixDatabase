<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2010-2020 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

/**
 * used to declare a primary key.
 */
class PrimaryKey extends AbstractConstraint
{
    public function __construct($name, $columns = '')
    {
        // for previous version <1.6.16, where there was only one argument, $columns
        if ($columns == '') {
            $columns = $name;
            $name = '';
        }

        parent::__construct($name, $columns);
    }
}
