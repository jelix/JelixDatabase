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
 * represents a unique key.
 */
class UniqueKey extends AbstractConstraint
{
    public function __construct($name, $columns = null)
    {
        // for previous version <1.6.16, where $columns was $type
        if ($columns === null) {
            parent::__construct($name, array());
        } else {
            parent::__construct($name, $columns);
        }
    }
}