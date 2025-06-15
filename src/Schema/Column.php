<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2010-2025 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Schema;

class Column
{
    /**
     * native type of the field.
     *
     * @var string
     */
    public $type;

    /**
     * internal use.
     *
     * @internal
     */
    public $nativeType;

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
    public $notNull = false;

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
    public $default;

    /**
     * says if there is a default value.
     *
     * @var bool
     */
    public $hasDefault = false;

    /**
     * says if the column is generated.
     *
     * @var bool
     */
    public $generated = false;

    /**
     * The length for a string.
     *
     * @var int
     */
    public $length = 0;

    /**
     * The precision for a number.
     *
     * @var int
     */
    public $precision = 0;

    /**
     * The scale for a number (value after the coma, in the precision).
     *
     * @var int
     */
    public $scale = 0;

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

    public $comment = '';

    public $arrayDims = 0;

    /**
     * @var string indicate which kind of autoincremented the column is.
     *              Content depends on the adapter.
     */
    public $autoIncrementFlavor = '';

    public function __construct(
        $name,
        $type,
        $length = 0,
        $hasDefault = false,
        $default = null,
        $notNull = false,
        $generated = false
    ) {
        $this->type = $type;
        $this->name = $name;
        $this->length = $length;
        $this->hasDefault = $hasDefault;
        $this->generated = $generated;
        if ($hasDefault) {
            $this->default = ($notNull && $default === null ? '' : $default);
        } else {
            $this->default = ($notNull ? '' : null);
        }
        $this->notNull = $notNull;
    }

    public function isEqualTo($column)
    {
        return
            $this->name == $column->name &&
            $this->_isEqualToExceptName($column)
            ;
    }

    public function hasOnlyDifferentName($otherColumn)
    {
        return
            $this->name != $otherColumn->name &&
            $this->_isEqualToExceptName($otherColumn)
            ;
    }

    protected function _isEqualToExceptName($column)
    {
        $isAutoIncremented = false;
        if ($column->nativeType && $this->nativeType) {
            if ($column->nativeType != $this->nativeType) {
                $isAutoIncremented =  ($this->isAutoincrementedColumn() && $column->isAutoincrementedColumn()) ||
                    ($this->isBigAutoincrementedColumn() && $column->isBigAutoincrementedColumn());
                if (!$isAutoIncremented) {
                    return false;
                }
            }
        } elseif ($this->type != $column->type) {
            $isAutoIncremented =  ($this->isAutoincrementedColumn() && $column->isAutoincrementedColumn()) ||
                ($this->isBigAutoincrementedColumn() && $column->isBigAutoincrementedColumn());
            if (!$isAutoIncremented) {
                return false;
            }
        }

        if (!$isAutoIncremented && ($this->sequence || $column->sequence) &&
            $this->sequence != $column->sequence) {
            return false;
        }

        return
            $this->notNull == $column->notNull &&
            $this->autoIncrement == $column->autoIncrement &&
            $this->default == $column->default &&
            $this->hasDefault == $column->hasDefault &&
            $this->length == $column->length &&
            $this->scale == $column->scale &&
            $this->unsigned == $column->unsigned
            ;
    }

    public function isAutoincrementedColumn()
    {
        if ($this->nativeType) {
            return (
                ($this->autoIncrement && (
                        $this->nativeType == 'integer' ||
                        $this->nativeType == 'int')
                ) ||
                $this->nativeType == 'serial'
            );
        }

        if (
            ($this->autoIncrement && (
                    $this->type == 'integer' ||
                    $this->type == 'int' )
            ) ||
            $this->type == 'serial' ||
            $this->type == 'autoincrement'
        ) {
            return true;
        }

        return false;
    }

    public function isBigAutoincrementedColumn()
    {
        if ($this->nativeType) {
            return (
                ($this->autoIncrement && (
                        $this->nativeType == 'bigint' ||
                        $this->nativeType == 'numeric')
                ) ||
                $this->nativeType == 'bigserial'
            );
        }

        if (
            ($this->autoIncrement && (
                    $this->type == 'bigint' )
            ) ||
            $this->type == 'bigserial'  ||
            $this->type == 'bigautoincrement'
        ) {
            return true;
        }

        return false;
    }

    public function isArray()
    {
        return $this->arrayDims > 0;
    }
}
