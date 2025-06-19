<?php
/**
 * @author      Laurent Jouanneau
 *
 * @copyright   2015-2024 Laurent Jouanneau
 */
namespace Jelix\Database\ProfilePlugin;

use Jelix\Database\ConnectionInterface;
use \Jelix\Profiles\ProfileInstancePluginInterface;
use \Jelix\Profiles\ReaderPlugin;
use \Jelix\Database\Connection;
use \Jelix\Database\AccessParameters;


/**
 * Plugin to be used with \Jelix\Profiles\ProfilesReader
 */
class DbProfilePlugin extends ReaderPlugin implements ProfileInstancePluginInterface
{
    /**
     * @var string[][]
     */
    protected $pgUniqueProfiles = array();

    protected $pgTimeouts = array();

    protected $accessOptions = array();

    protected function consolidate($profile)
    {
        $parameters = new AccessParameters($profile, $this->accessOptions);

        $newProfile =  $parameters->getNormalizedParameters();
        if ($newProfile['driver'] != 'pgsql') {
            return $newProfile;
        }

        // we try to detect if the profile is already defined into another
        // profile. If this is the case, we declare it as an alias, so
        // jDb/jProfiles will use the same connector instead of creating two connectors.
        // If we don't do this, it duplicates connection, or, if the PHP extension
        // use the same real connection for both jDb connector, when jDb will
        // close connection, we will have an error on the second connection closing (because already closed)

        // parameters that are used for a connection and that identify a unique connection
        $connectionParameters = array( 'service', 'host', 'port', 'user', 'password', 'database',
            'timeout', 'pg_options', 'force_new');

        // parameters used to change some properties using the connection, and if there
        // are different between two profiles having same connection parameters, we should
        // have a different connection (by setting a different timeout)
        $settingParameters = array('search_path', 'session_role', 'single_transaction');

        $profileToTest = array_merge(array(
            'service' => '',
            'host' => '',
            'port' => 5432,
            'user' => '',
            'password' => '',
            'database' => '',
            'timeout' => 0,
            'pg_options' => '',
            'force_new' => 0,
            'search_path' => '',
            'session_role' => '',
            'single_transaction' => 0
        ), $newProfile);

        $connectionKey = '';
        foreach($connectionParameters as $p) {
            $connectionKey.='/'.$profileToTest[$p];
        }
        $settingKey = '';
        foreach($settingParameters as $p) {
            $settingKey.='/'.$profileToTest[$p];
        }

        if (isset($this->pgUniqueProfiles[$connectionKey])) {
            // we found a profile that have same connection parameters
            if (isset($this->pgUniqueProfiles[$connectionKey][$settingKey])) {
                // if search_path, session_role and single_transaction are the same values
                // then we can declare the profile as an alias
                $newProfile = $this->pgUniqueProfiles[$connectionKey][$settingKey];
            }
            else {
                // else, we modify the timeout to have a real different pgsql connection
                $timeout = $this->pgUniqueProfiles[$connectionKey]['timeout'];
                if ($timeout == 0) {
                    $timeout = 180;
                }
                while (in_array($timeout, $this->pgTimeouts)) {
                    $timeout++;
                }
                $this->pgTimeouts[] = $newProfile['timeout'] = $profileToTest['timeout'] = $timeout;

                $newConnectionKey = '';
                foreach($connectionParameters as $p) {
                    $newConnectionKey.='/'.$profileToTest[$p];
                }

                if (isset($this->pgUniqueProfiles[$newConnectionKey][$settingKey])) {
                    // maybe there is already a profile with the same new connection parameters,
                    // so we reuse same profile (aka, it is an alias).
                    $newProfile = $this->pgUniqueProfiles[$newConnectionKey][$settingKey];
                }
                else {
                    // we store the new profile, so other profiles having same connection parameter
                    // and new timeout will be an alias
                    $this->pgUniqueProfiles[$newConnectionKey][$settingKey] = $newProfile;
                    // we store the new profile as if it didn't change, so other profiles having same
                    // connection parameter and previous timeout will be an alias of the new profile
                    $this->pgUniqueProfiles[$connectionKey][$settingKey] = $newProfile;
                }
            }
        }
        else {
            // no profile with same connection parameters
            $this->pgUniqueProfiles[$connectionKey][$settingKey] = $newProfile;
            $timeout = intval($profileToTest['timeout']);
            if (!isset($this->pgUniqueProfiles[$connectionKey]['timeout'])) {
                $this->pgUniqueProfiles[$connectionKey]['timeout'] = $timeout;
            }
            $this->pgTimeouts[] = $timeout;
        }

        return $newProfile;
    }


    public function getInstanceForPool($name, $profile)
    {
        return Connection::createWithNormalizedParameters($profile);
    }

    /**
     * @param $name
     * @param ConnectionInterface $instance
     * @return void
     */
    public function closeInstanceForPool($name, $instance)
    {
        $instance->close();
    }

}