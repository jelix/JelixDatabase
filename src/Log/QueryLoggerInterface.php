<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2021 Laurent Jouanneau
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Log;


interface QueryLoggerInterface
{

    /**
     * @param string $query the SQL query that is launched
     *
     * @return void
     */
    public function startQuery($query);

    /**
     * @param  string  $realQuery the real SQL query that was launched if the
     *                            original query was modified during its launch.
     * @return void
     */
    public function endQuery($realQuery = '');
}
