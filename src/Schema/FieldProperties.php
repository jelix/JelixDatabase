<?php
/**
 * @author     Gérald Croes, Laurent Jouanneau
 * @contributor Laurent Jouanneau, Gwendal Jouannic
 * @copyright  2001-2005 CopixTeam, 2005-2020 Laurent Jouanneau, 2008 Gwendal Jouannic
 *
 * @see        https://jelix.org
 * @licence    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

/**
 * Description of a field of a table.
 * @deprecated
 */
class FieldProperties
{
    /**
     * native type of the field.
     *
     * @var string
     */
    public $type;

    /**
     * unified type of the field.
     *
     * @var string
     */
    public $unifiedtype;

    /**
     * field name.
     *
     * @var string
     */
    public $name;

    /**
     * says if the field can be null or not.
     *
     * @var bool
     */
    public $notNull = true;

    /**
     * says if the field is the primary key.
     *
     * @var bool
     */
    public $primary = false;

    /**
     * says if the field is auto incremented.
     *
     * @var bool
     */
    public $autoIncrement = false;

    /**
     * default value.
     *
     * @var string
     */
    public $default = '';

    /**
     * says if there is a default value.
     *
     * @var bool
     */
    public $hasDefault = false;

    public $length = 0;

    /**
     * if there is a sequence.
     *
     * @var string
     */
    public $sequence = false;

    public $unsigned = false;

    public $minLength;

    public $maxLength;

    public $minValue;

    public $maxValue;

    /**
     * dao and form use this feature.
     */
    public $comment;
}
