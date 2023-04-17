<?php
/**
 * @author     Philippe Villiers
 * @contributor Laurent Jouanneau
 * @copyright  2013 Philippe Villiers, 2017-2023 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Connector\Oci;

use Jelix\Database\AbstractConnection;
use Jelix\Database\Exception;
use Psr\Log\LoggerInterface;

/**
 */
class Connection extends AbstractConnection
{
    // Charsets equivalents
    protected $_charsets = array('UTF-8' => 'AL32UTF8', 'ISO-8859-1' => 'WE8ISO8859P1');

    public function __construct($profile, LoggerInterface $logger = null)
    {
        if (!function_exists('oci_connect')) {
            throw new Exception('Oci extension is not installed in PHP', 405);
        }
        parent::__construct($profile, $logger);

        $this->dbms = 'oci';
    }

    protected function _connect()
    {
        $funcConnect = (isset($this->profile['persistent']) && $this->profile['persistent'] ? 'oci_pconnect' : 'oci_connect');

        if (isset($this->profile['dsn'])) {
            $connString = $this->profile['dsn'];
        } else {
            $connString = $this->profile['host'];
            if (isset($this->profile['port'])) {
                $connString .= ':'.$this->profile['port'];
            }
            $connString .= '/'.$this->profile['database'];
        }
        if (isset($this->_charsets[$this->_profile['charset']])) {
            $charset = $this->_charsets[$this->_profile['charset']];
        } else {
            $charset = 'AL32UTF8';
        }

        $conn = $funcConnect($this->profile['user'], $this->profile['password'], $connString, $charset);
        if (!$conn) {
            //$err = oci_error();
            throw new Exception('Error during the connection on '.$this->_profile['host'], 402);
        }

        return $conn;
    }

    protected function _disconnect()
    {
        oci_close($this->_connection);
    }

    protected function _doQuery($queryString)
    {
        if ($stId = oci_parse($this->_connection, $queryString)) {
            $rs = new ociDbResultSet($stId, $this->_connection);
            if ($res = $rs->execute()) {
                return $rs;
            }
        }
        $err = oci_error();
        throw new Exception('invalid query: '.$err['message'].'('.$queryString.')', 403);
    }

    protected function _doLimitQuery($queryString, $offset, $number)
    {
        $offset = $offset + 1; // rnum begins at 1
        $queryString = 'SELECT * FROM ( SELECT ocilimit.*, rownum rnum FROM ('.$queryString.') ocilimit WHERE
            rownum<'.(intval($offset) + intval($number)).'  ) WHERE rnum >='.intval($offset);

        return $this->_doQuery($queryString);
    }

    protected function _doExec($query)
    {
        if ($rs = $this->_doQuery($query)) {
            return oci_num_rows($rs->id());
        }

        return 0;
    }

    public function prepare($query, $driverOptions = [])
    {
        $stId = oci_parse($this->_connection, $query);
        if ($stId) {
            $rs = new ResultSet($stId, $this->_connection);
        } else {
            $err = oci_error();

            throw new Exception('invalid query: '.$err['message'].'('.$query.')', 403);
        }

        return $rs;
    }

    public function beginTransaction()
    {
        return true;
    }

    public function commit()
    {
        return oci_commit($this->_connection);
    }

    public function rollback()
    {
        return oci_rollback($this->_connection);
    }

    public function errorInfo()
    {
        $err = oci_error();

        return array('HY000', $err['code'], $err['message']);
    }

    public function errorCode()
    {
        $err = oci_error();

        return $err['code'];
    }

    public function lastInsertId($seqName = '')
    {
        if ($seqName == '') {
            trigger_error(get_class($this).'::lastInstertId invalid sequence name', E_USER_WARNING);

            return false;
        }
        $cur = $this->query('select '.$seqName.'.currval as "id" from dual');
        if ($cur) {
            $res = $cur->fetch();
            if ($res) {
                return $res->id;
            }

            return false;
        }
        trigger_error(get_class($this).'::lastInstertId invalid sequence name', E_USER_WARNING);

        return false;
    }

    public function getAttribute($id)
    {
        return '';
    }

    public function setAttribute($id, $value)
    {
    }

    protected function _autoCommitNotify($state)
    {
        $this->_doExec('SET AUTOCOMMIT '.($state ? 'ON' : 'OFF'));
    }

    protected function _getSchema()
    {
        return new \Jelix\Database\Schema\Oci\Schema($this);
    }
}
