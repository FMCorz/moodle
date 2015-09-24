<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Abstract class for tool_lp objects saved to the DB.
 *
 * @package    tool_lp
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_lp;

use coding_exception;
use invalid_parameter_exception;
use lang_string;
use stdClass;

/**
 * Abstract class for tool_lp objects saved to the DB.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class persistent {

    /** The table name. */
    const TABLE = null;

    /** The model data. */
    private $data = array();

    /** @var boolean The list of validation errors. */
    private $errors = array();

    /** @var boolean If the data was already validated. */
    private $validated = false;

    /**
     * Create an instance of this class.
     * @param int $id If set, this is the id of an existing record, used to load the data.
     * @param \stdClass $record If set, the data for this class will be taken from the record.
     */
    public function __construct($id = 0, $record = null) {
        if ($id > 0) {
            $this->id = $id;
            $this->read();
        }
        if (!empty($record)) {
            $this->from_record($record);
        }
    }

    /**
     * Magic method to capture getters and setters.
     *
     * @param  string $name Callee.
     * @param  array $arguments List of arguments.
     * @return mixed
     */
    public function __call($method, $arguments) {
        if (strpos($method, 'get_') === 0) {
            return $this->get(substr($method, 4));
        } else if (strpos($method, 'set_') === 0) {
            return $this->set(substr($method, 4), $arguments[0]);
        }
        throw new coding_exception('Unexpected method call');
    }

    /**
     * Data getter.
     *
     * This is protected and final to force developers to call this method
     * if they want to implement their own getter.
     *
     * @param  string $property The property name.
     * @return mixed
     */
    final protected function get($property) {
        if (!static::has_property($property)) {
            throw new coding_exception('Unexpected property \'' . s($property) .'\' requested.');
        }
        if (!isset($this->data[$property])) {
            $this->set($property, static::get_property_default_value($property));
        }
        return $this->data[$property];
    }

    /**
     * Data setter.
     *
     * This is protected and final to force developers to call this method
     * if they want to implement their own setter.
     *
     * @param  string $property The property name.
     * @return mixed
     */
    final protected function set($property, $value) {
        if (!static::has_property($property)) {
            throw new coding_exception('Unexpected property \'' . s($property) .'\' requested.');
        }
        if (!isset($this->data[$property]) || $this->data[$property] != $value) {
            // If the value is changing, we invalidate the model.
            $this->validated = false;
        }
        $this->data[$property] = $value;
    }

    /**
     * Return the definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * Example:
     *
     * array(
     *     'property_name' => array(
     *         'default' => 'Default value',        // Defaults to null.
     *         'message' => new lang_string(...)    // Defaults to invalid data error message.
     *         'type' => PARAM_TYPE                 // Mandatory.
     *     )
     * )
     *
     * @return array Where keys are the property names.
     */
    public static function properties_definition() {
        return array();
    }

    /**
     * Private validation definition.
     *
     * @return array
     */
    final private static function _properties_definition() {
        global $CFG;

        static $def = null;
        if ($def !== null) {
            return $def;
        }

        $def = static::properties_definition();
        $def['id'] = array(
            'default' => 0,
            'type' => PARAM_INT,
        );
        $def['timecreated'] = array(
            'default' => 0,
            'type' => PARAM_INT,
        );
        $def['timemodified'] = array(
            'default' => 0,
            'type' => PARAM_INT
        );
        $def['usermodified'] = array(
            'default' => 0,
            'type' => PARAM_INT
        );

        // Warn the developers when they are doing something wrong.
        if ($CFG->debugdeveloper) {
            foreach ($def as $property => $definition) {
                if (!array_key_exists('type', $definition)) {
                    throw new coding_exception('Missing type for: ' . $property);

                } else if (array_key_exists('message', $definition) && !($definition['message'] instanceof lang_string)) {
                    throw new coding_exception('Invalid error message for: ' . $property);

                }
            }
        }

        return $def;
    }

    /**
     * Gets the default value for a property.
     *
     * This assumes that the property exists.
     *
     * @param string $property The property name.
     * @return mixed
     */
    final protected static function get_property_default_value($property) {
        $properties = static::_properties_definition();
        if (!isset($properties[$property]['default'])) {
            return null;
        }
        return $properties[$property]['default'];
    }

    /**
     * Gets the error message for a property.
     *
     * This assumes that the property exists.
     *
     * @param string $property The property name.
     * @return lang_string
     */
    final protected static function get_property_error_message($property) {
        $properties = static::_properties_definition();
        if (!isset($properties[$property]['message'])) {
            return new lang_string('invaliddata', 'error');
        }
        return $properties[$property]['message'];
    }

    /**
     * Returns whether or not a property was defined.
     *
     * @param  string $property The property name.
     * @return boolean
     */
    final public static function has_property($property) {
        $properties = static::_properties_definition();
        return isset($properties[$property]);
    }

    /**
     * Populate this class with data from a DB record.
     *
     * @param \stdClass $record A DB record.
     * @return persistent
     */
    final public function from_record($record) {
        $record = (array) $record;
        foreach ($record as $property => $value) {
            $this->set($property, $value);
        }
        return $this;
    }

    /**
     * Create a DB record from this class.
     *
     * @return \stdClass
     */
    final public function to_record() {
        $data = new stdClass();
        $properties = static::_properties_definition();
        foreach ($properties as $property => $definition) {
            $data->$property = $this->get($property);
        }
        return $data;
    }

    /**
     * Reload the data for this class from the DB.
     *
     * @return persistent
     */
    public function read() {
        return $this->_read();
    }

    /**
     * Internal method to fetch the model's data.
     *
     * This is made private and final to force developers to call the parent of
     * {@link self::read()} if they desire to override it.
     *
     * @return persistent
     */
    final private function _read() {
        global $DB;

        if ($this->id <= 0) {
            throw new \coding_exception('id is required to load');
        }
        $record = $DB->get_record(static::TABLE, array('id' => $this->id), '*', MUST_EXIST);
        $this->from_record($record);

        // Validate the data as it comes from the database.
        $this->validated = true;

        return $this;
    }

    /**
     * Insert a record in the DB
     *
     * @return persistent
     */
    public function create() {
        return $this->_create();
    }

    /**
     * Internal method to create a new entry in the database.
     *
     * This is made private and final to force developers to call the parent of
     * {@link self::create()} if they desire to override it.
     *
     * @return persistent
     */
    final private function _create() {
        global $DB, $USER;

        if (!$this->is_valid()) {
            throw new invalid_persistent_exception();
        }

        // We can safely set those values bypassing the validation because we know what we're doing.
        $now = time();
        $this->set('id', 0);
        $this->set('timecreated', $now);
        $this->set('timemodified', $now);
        $this->set('usermodified', $USER->id);

        $record = $this->to_record();

        $id = $DB->insert_record(static::TABLE, $record);
        $this->set('id', $id);

        // We ensure that this is validated because the above call to set() would have invalidated the model.
        $this->validated = true;

        return $this;
    }

    /**
     * Update the existing record in the DB.
     *
     * @return bool True on success.
     */
    public function update() {
        return $this->_update();
    }

    /**
     * Internal method to update an existing entry in the database.
     *
     * This is made private and final to force developers to call the parent of
     * {@link self::update()} if they desire to override it.
     *
     * @return bool True on success.
     */
    final private function _update() {
        global $DB, $USER;

        if ($this->id <= 0) {
            throw new \coding_exception('id is required to update');
        } else if (!$this->is_valid()) {
            throw new invalid_persistent_exception();
        }

        // We can safely set those values bypassing the validation because we know what we're doing.
        $this->set('timemodified', time());
        $this->set('usermodified', $USER->id);

        $record = $this->to_record();
        unset($record->timecreated);
        $record = (array) $record;

        $result = $DB->update_record(static::TABLE, $record);

        // We ensure that this is validated because the above call to set() would have invalidated the model.
        $this->validated = true;

        return $result;
    }

    /**
     * Delete the existing record in the DB.
     *
     * @return bool True on success.
     */
    public function delete() {
        return $this->_delete();
    }

    /**
     * Internal method to delete an existing entry from the database.
     *
     * This is made private and final to force developers to call the parent of
     * {@link self::delete()} if they desire to override it.
     *
     * @return bool True on success.
     */
    final private function _delete() {
        global $DB;

        if ($this->id <= 0) {
            throw new \coding_exception('id is required to delete');
        }
        return $DB->delete_records(static::TABLE, array('id' => $this->id));
    }

    /**
     * Validates the data.
     *
     * Developers can implement their own validation method by defining a method as follows. Note that
     * the method MUST return a lang_string() when there is an error, or true otherwise.
     *
     * public function validate_propertyname($value) {
     *     if ($value !== 'My expected value') {
     *         return new lang_string('invaliddata', 'error');
     *     }
     *     return true
     * }
     *
     * @return array|true Returns true when the validation passed, or an array of properties with errors.
     */
    final public function validate() {
        if ($this->validated === true) {
            return empty($this->errors) ? true : $this->errors;
        }

        $errors = array();
        $properties = static::_properties_definition();
        foreach ($properties as $property => $definition) {
            $value = $this->get($property);

            // Check that type of value is respected.
            try {
                validate_param($value, $definition['type']);
            } catch (invalid_parameter_exception $e) {
                $errors[$property] = static::get_property_error_message($property);
                continue;
            }

            // Check that the value is part of a list of allowed values.
            if (isset($definition['choices']) && !in_array($value, $definition['choices'])) {
                $errors[$property] = static::get_property_error_message($property);
                continue;
            }

            // Call custom validation method.
            $method = 'validate_' . $property;
            if (method_exists($this, $method)) {
                $valid = $this->{$method}($value);
                if ($valid !== true) {
                    if (!($valid instanceof lang_string)) {
                        throw new coding_exception('Unexpected error message.');
                    }
                    $errors[$property] = $valid;
                    continue;
                }
            }
        }

        $this->validated = true;
        $this->errors = $errors;
        return empty($this->errors) ? true : $this->errors;
    }

    /**
     * Returns whether or not the model is valid.
     *
     * @return boolean True when it is.
     */
    final public function is_valid() {
        return $this->validate() === true;
    }

    /**
     * Returns the validation errors.
     *
     * @return array
     */
    final public function geterrors() {
        $this->validate();
        return $this->errors;
    }

    /**
     * Load a list of records.
     *
     * @param array $filters Filters to apply.
     * @param string $sort Field to sort by.
     * @param string $order Sort order.
     * @param int $skip Limitstart.
     * @param int $limit Number of rows to return.
     *
     * @return persistent[]
     */
    public static function get_records($filters = array(), $sort = '', $order = 'ASC', $skip = 0, $limit = 0) {
        global $DB;

        $orderby = '';
        if (!empty($sort)) {
            $orderby = $sort . ' ' . $order;
        }

        $records = $DB->get_records(static::TABLE, $filters, $orderby, '*', $skip, $limit);
        $instances = array();

        foreach ($records as $record) {
            $newrecord = new static(0, $record);
            array_push($instances, $newrecord);
        }
        return $instances;
    }

    /**
     * Load a list of records based on a select query.
     *
     * @param string $select
     * @param array $params
     * @param string $sort
     * @param string $fields
     * @param int $limitfrom
     * @param int $limitnum
     * @return \tool_lp\plan[]
     */
    public static function get_records_select($select, $params = null, $sort = '', $fields = '*', $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $records = $DB->get_records_select(static::TABLE, $select, $params, $sort, $fields, $limitfrom, $limitnum);

        // We return class instances.
        $instances = array();
        foreach ($records as $record) {
            array_push($instances, new static(0, $record));
        }

        return $instances;

    }

    /**
     * Count a list of records.
     *
     * @return int
     */
    public static function count_records() {
        global $DB;

        $count = $DB->count_records(static::TABLE);
        return $count;
    }

    /**
     * Count a list of records.
     *
     * @param string $select
     * @param array $params
     * @return int
     */
    public static function count_records_select($select, $params = null) {
        global $DB;

        $count = $DB->count_records_select(static::TABLE, $select, $params);
        return $count;
    }
}
