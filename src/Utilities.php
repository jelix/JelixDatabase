<?php
/**
 * @author      Laurent Jouanneau
 *
 * @copyright   2011-2020 Laurent Jouanneau
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database;

/**
 * sql and connections utilities.
 *
 */
class Utilities
{
    /**
     * perform a convertion float to str. It takes care about the decimal separator
     * which should be a '.' for SQL. Because when doing a native convertion float->str,
     * PHP uses the local decimal separator, and so, we don't want that.
     *
     * @param mixed $value
     */
    public static function floatToStr($value)
    {
        if (is_float($value)) {// this is a float
            $val =  rtrim(rtrim(sprintf('%.20F', $value), '0'), '.'); // %F to not format with the local decimal separator

            // because of precision issues, we could have 1.239999999456 instead of 1.24
            // or 1.2300000000456 instead of 1.23. Let's convert with a precision number.
            if (($p =strpos($val, '0000')) === false &&
                ($p =strpos($val, '9999')) === false
            ) {
                return $val;
            }
            $pPoint = strpos($val, '.');
            if ($pPoint === false) {
                if (strlen($val) > 15) {
                    $val = rtrim(rtrim(sprintf('%.20e', $value), '0'), '.');
                    if (($p =strpos($val, '0000')) === false &&
                        ($p =strpos($val, '9999')) === false
                    ) {
                        $val = str_replace('e+', 'E', $val);
                        return $val;
                    }
                    $val = rtrim(rtrim(sprintf('%.'.($p -1).'e', $value), '0'), '.');
                    $val = str_replace('e+', 'E', $val);
                }
                return $val;
            }
            $precision = $p - $pPoint -1;
            return rtrim(rtrim(sprintf('%.'.$precision.'F', $value), '0'), '.'); // %F to not format with the local decimal separator
        }

        if (is_integer($value)) {
            return sprintf('%d', $value);
        }
        // this is probably a string, so we expect that it contains a numerical value
        // is_numeric is true if the separator is ok for SQL
        // (is_numeric doesn't accept thousand separators nor other character than '.' as decimal separator)
        if (is_numeric($value)) {
            return $value;
        }

        // we probably have a malformed float number here
        // if so, floatval will ignore all character after an invalid character (a ',' for example)
        // no warning, no exception here, to keep the same behavior of previous Jelix version
        // in order to no break stable applications.
        // FIXME: do a warning in next versions
        return (string) (floatval($value));
    }
}
