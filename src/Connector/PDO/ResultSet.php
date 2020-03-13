<?php
/**
 * @author     Laurent Jouanneau
 * @contributor Gwendal Jouannic, Thomas, Julien Issler
 *
 * @copyright  2005-2020 Laurent Jouanneau
 * @copyright  2008 Gwendal Jouannic, 2009 Thomas
 * @copyright  2009 Julien Issler
 *
 * @see      http://www.jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Connector\PDO;

use Jelix\Database\ResultSetInterface;

/**
 * a resultset based on PDOStatement.
 *
 * @package  jelix
 * @subpackage db
 */
class ResultSet extends \PDOStatement implements ResultSetInterface
{
    protected $_fetchMode = 0;

    public function fetch($fetch_style = null, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        // we take a shortcut: unused parameters are ignored by parent::fetch
        // let the parent::setFetchMode override as needed, and PHP use its default
        if ($fetch_style) {
            $rec = parent::fetch($fetch_style, $cursor_orientation, $cursor_offset);
        } else {
            $rec = parent::fetch();
        }

        if ($rec) {
            $this->applyModifiers($rec);
        }

        return $rec;
    }

    /**
     * return all results from the statement.
     *
     * @param int   $fetch_style
     * @param int   $fetch_argument
     * @param array $ctor_arg
     *
     * @return object[] list of object which contain all rows
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, $ctor_arg = null)
    {
        // if the user requested to override the style set with setFetchMode, use it
        $final_style = ($fetch_style ?: $this->_fetchMode);

        // Check how many arguments, if available should be given
        if (!$final_style) {
            $records = parent::fetchAll(\PDO::FETCH_OBJ);
        } elseif ($ctor_arg) {
            $records = parent::fetchAll($final_style, $fetch_argument, $ctor_arg);
        } elseif ($fetch_argument) {
            $records = parent::fetchAll($final_style, $fetch_argument);
        } else {
            $records = parent::fetchAll($final_style);
        }

        if (count($this->modifier)) {
            foreach ($records as $rec) {
                $this->applyModifiers($rec);
            }
        }

        return $records;
    }

    protected function applyModifiers($result)
    {
        if (count($this->modifier)) {
            foreach ($this->modifier as $m) {
                call_user_func_array($m, array($result, $this));
            }
        }
    }

    /**
     * Set the fetch mode.
     *
     * @param int   $mode the mode, a \PDO::FETCH_* constant
     * @param mixed $arg1 a parameter for the given mode
     * @param mixed $arg2 a parameter for the given mode
     *
     * @return bool true if the fetch mode is ok
     */
    public function setFetchMode($mode, $arg1 = null, $arg2 = null)
    {
        $this->_fetchMode = $mode;
        // depending the mode, original setFetchMode throw an error if wrong arguments
        // are given, even if there are null
        if ($arg1 === null) {
            return parent::setFetchMode($mode);
        }
        if ($arg2 === null) {
            return parent::setFetchMode($mode, $arg1);
        }

        return parent::setFetchMode($mode, $arg1, $arg2);
    }

    /**
     * @param string $text a binary string to unescape
     *
     * @return string the unescaped string
     */
    public function unescapeBin($text)
    {
        return $text;
    }

    /**
     * a callback function which will modify on the fly record's value.
     *
     * @var callable[]
     */
    protected $modifier = array();

    /**
     * @param callable $function a callback function
     *                           the function should accept in parameter the record,
     *                           and the resulset object
     */
    public function addModifier($function)
    {
        $this->modifier[] = $function;
    }
}