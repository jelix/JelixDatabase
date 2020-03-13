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
 * Represents an index on some columns.
 */
class Index
{
    /**
     * @var string the index name
     */
    public $name;

    /**
     *  the type of index : 'btree', 'hash'...
     *
     * @var string
     */
    public $type;

    /**
     * @var string[] list of indexed columns
     */
    public $columns = array();

    /**
     * @var string SQL where clause for the index
     */
    //public $predicat = '';

    public $isUnique = false;

    /**
     * jDbIndex constructor.
     *
     * @param string   $name    the index name
     * @param string[] $columns the list of column names
     * @param mixed    $type
     */
    public function __construct($name, $type = '', $columns = array())
    { //, $predicat='', ) {
        $this->name = $name;
        $this->columns = $columns;
        $this->type = $type;
        //$this->predicat = $predicat;
    }
}
