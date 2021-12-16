<?php
/**
 * @author     Laurent Jouanneau
 * @contributor Gwendal Jouannic, Thomas, Julien Issler
 *
 * @copyright  2005-2021 Laurent Jouanneau
 * @copyright  2008 Gwendal Jouannic, 2009 Thomas
 * @copyright  2009 Julien Issler
 *
 * @see      http://www.jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Connector\PDO;

use Jelix\Database\AbstractResultSet;

/**
 * A resultset based on PDOStatement
 *
 * @package  jelix
 * @subpackage db
 */
class ResultSet  extends AbstractResultSet
{
    /**
     * @var \PDOStatement
     */
    protected $_idResult;

    /**
     * @var bool true if the next call to fetch should fetch the first record
     */
    protected $doRewind = false;

    /**
     * @inheritDoc
     */
    public function setFetchMode($fetchmode, $param = null, $ctoargs = null)
    {
        parent::setFetchMode($fetchmode, $param, $ctoargs);

        // depending the mode, original setFetchMode throw an error if wrong arguments
        // are given, even if there are null
        if ($param === null && $ctoargs === null) {
            $this->_idResult->setFetchMode($fetchmode);
        }
        else if ($ctoargs === null) {
            $this->_idResult->setFetchMode($fetchmode, $param);
        }
        else {
            $this->_idResult->setFetchMode($fetchmode, $param, $ctoargs);
        }
    }

    /**
     * @inheritDoc
     */
    public function fetch()
    {
        $rec = $this->_fetch();
        if ($rec) {
            $this->applyModifiers($rec);
        }
        return $rec;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($attr)
    {
        return $this->_idResult->getAttribute($attr);
    }

    /**
     * @inheritDoc
     */
    public function setAttribute($attr, $value)
    {
        $this->_idResult->setAttribute($attr, $value);
    }

    /**
     * @inheritDoc
     */
    public function bindColumn($column, &$param, $type = null)
    {
        return $this->_idResult->bindColumn($column, $param, $type);
    }

    /**
     * @inheritDoc
     */
    public function bindParam($parameterName, &$variable, $data_type = \PDO::PARAM_STR, $length = null, $driver_options = null)
    {
        return $this->_idResult->bindParam($parameterName, $variable, $data_type, $length, $driver_options);
    }

    /**
     * @inheritDoc
     */
    public function bindValue($parameterName, $value, $data_type = \PDO::PARAM_STR)
    {
        return $this->_idResult->bindValue($parameterName, $value, $data_type);
    }

    /**
     * @inheritDoc
     */
    public function columnCount()
    {
        return $this->_idResult->columnCount();
    }

    /**
     * @inheritDoc
     */
    public function execute($parameters = null)
    {
        return $this->_idResult->execute($parameters);
    }

    /**
     * @inheritDoc
     */
    public function rowCount()
    {
        return $this->_idResult->rowCount();
    }

    /**
     * @inheritDoc
     */
    protected function _free()
    {
        $this->_idResult->closeCursor();
    }

    /**
     * @inheritDoc
     */
    protected function _fetch()
    {
        if ($this->doRewind) {
            $this->doRewind = false;
            return $this->_idResult->fetch($this->_fetchMode, \PDO::FETCH_ORI_ABS, 0);
        }
        return $this->_idResult->fetch();
    }

    /**
     * @inheritDoc
     */
    protected function _fetchAssoc()
    {
        if ($this->doRewind) {
            $this->doRewind = false;
            return $this->_idResult->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, 0);
        }
        return $this->_idResult->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @inheritDoc
     */
    protected function _rewind()
    {
        $this->doRewind = true;
    }
}
