<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DB;

/**
 * Class Enum represents an enumeration of a set of strings.
 *
 * See {@link DropdownField} for a {@link FormField} to select enum values.
 */
class DBEnum extends DBString
{

    /**
     * List of enum values
     *
     * @var array
     */
    protected $enum = array();

    /**
     * Default value
     *
     * @var string|null
     */
    protected $default = null;

    private static $default_search_filter_class = 'ExactMatchFilter';

    /**
     * Create a new Enum field.
     *
     * Example usage in {@link DataObject::$db} with comma-separated string
     * notation ('Val1' is default)
     *
     * <code>
     *  "MyField" => "Enum('Val1, Val2, Val3', 'Val1')"
     * </code>
     *
     * Example usage in in {@link DataObject::$db} with array notation
     * ('Val1' is default)
     *
     * <code>
     * "MyField" => "Enum(array('Val1', 'Val2', 'Val3'), 'Val1')"
     * </code>
     *
     * @param string $name
     * @param string|array $enum A string containing a comma separated list of options or an array of Vals.
     * @param string $default The default option, which is either NULL or one of the items in the enumeration.
     */
    public function __construct($name = null, $enum = null, $default = null)
    {
        if ($enum) {
            $this->setEnum($enum);

            // If there's a default, then
            if ($default) {
                if (in_array($default, $this->getEnum())) {
                    $this->setDefault($default);
                } else {
                    user_error("Enum::__construct() The default value '$default' does not match any item in the"
                        . " enumeration", E_USER_ERROR);
                }

            // By default, set the default value to the first item
            } else {
                $enum = $this->getEnum();
                $this->setDefault(reset($enum));
            }
        }

        parent::__construct($name);
    }

    /**
     * @return void
     */
    public function requireField()
    {
        $charset = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'charset');
        $collation = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'collation');

        $parts = array(
            'datatype' => 'enum',
            'enums' => $this->getEnum(),
            'character set' => $charset,
            'collate' => $collation,
            'default' => $this->getDefault(),
            'table' => $this->getTable(),
            'arrayValue' => $this->arrayValue
        );

        $values = array(
            'type' => 'enum',
            'parts' => $parts
        );

        DB::require_field($this->getTable(), $this->getName(), $values);
    }

    /**
     * Return a dropdown field suitable for editing this field.
     *
     * @param string $title
     * @param string $name
     * @param bool $hasEmpty
     * @param string $value
     * @param string $emptyString
     * @return DropdownField
     */
    public function formField($title = null, $name = null, $hasEmpty = false, $value = "", $emptyString = null)
    {

        if (!$title) {
            $title = $this->getName();
        }
        if (!$name) {
            $name = $this->getName();
        }

        $field = new DropdownField($name, $title, $this->enumValues(false), $value);
        if ($hasEmpty) {
            $field->setEmptyString($emptyString);
        }

        return $field;
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return $this->formField($title);
    }

    /**
     * @param string
     *
     * @return DropdownField
     */
    public function scaffoldSearchField($title = null)
    {
        $anyText = _t('SilverStripe\\ORM\\FieldType\\DBEnum.ANY', 'Any');
        return $this->formField($title, null, true, $anyText, "($anyText)");
    }

    /**
     * Returns the values of this enum as an array, suitable for insertion into
     * a {@link DropdownField}
     *
     * @param boolean
     *
     * @return array
     */
    public function enumValues($hasEmpty = false)
    {
        return ($hasEmpty)
            ? array_merge(array('' => ''), ArrayLib::valuekey($this->getEnum()))
            : ArrayLib::valuekey($this->getEnum());
    }

    /**
     * Get list of enum values
     *
     * @return array
     */
    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * Set enum options
     *
     * @param string|array $enum
     * @return $this
     */
    public function setEnum($enum)
    {
        if (!is_array($enum)) {
            $enum = preg_split(
                '/\s*,\s*/',
                // trim commas only if they are on the right with a newline following it
                ltrim(preg_replace('/,\s*\n\s*$/', '', $enum))
            );
        }
        $this->enum = $enum;
        return $this;
    }

    /**
     * Get default vwalue
     *
     * @return string|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set default value
     *
     * @param string $default
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }
}
