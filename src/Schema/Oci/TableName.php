<?php

/**
 * @author     Laurent Jouanneau
 * @copyright  2024 Laurent Jouanneau
 *
 * @see        https://www.jelix.org
 * @licence    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */

namespace Jelix\Database\Schema\Oci;

class TableName extends \Jelix\Database\Schema\TableName
{
    protected $supportSchema = false;

    protected $encloseCharacterLeft = '';
    protected $encloseCharacterRight = '';
}