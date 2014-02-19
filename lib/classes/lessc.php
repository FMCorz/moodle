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
 * Moodle implementation of lessc.
 *
 * @package    core
 * @copyright  2014 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/lessphp/lessc.inc.php');

/**
 * Moodle lessc class.
 *
 * @package    core
 * @copyright  2014 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_lessc extends lessc {

    /**
     * The code to be compiled.
     * @var string
     */
    protected $code = '';

    /**
     * Store if we enter a mixin.
     * @var int
     */
    protected $inmixin = 0;

    /**
     * Compile a prop and update $lines or $blocks appropriately.
     *
     * We override this method to filter the variable assignment that
     * have been defined using {@link self::setVariables.}.
     *
     * @param array $prop The property.
     * @param string $block The block.
     * @param array $out The output variable.
     * @return void
     */
    protected function compileProp($prop, $block, $out) {
        $proceed = true;

        if ($prop[0] == 'mixin') {
            // We enter a mixin.
            $this->inmixin++;
        } else if ($prop[0] == 'assign') {
            list(, $name, $value) = $prop;

            // This is a @variable assignment.
            if ($name[0] == $this->vPrefix) {

                // Remove the @ from the variable name.
                $var = substr($name, 1);

                // This has been defined, we can ignore its definition, except when we are in a mixin
                // because it has its own scope.
                if (isset($this->registeredVars[$var]) && $this->inmixin == 0) {
                    $proceed = false;
                }
            }
        } else if ($prop[0] == 'moodle_custom') {
            // This is a custom [[xxxx:yyyy]] value reserved for our usage, we leave it as it. See core_lessc_parser.
            // Though we need to add it to its own fake block, otherwise it gets reordered.
            $env = $this->pushEnv();
            $out2 = $this->makeOutputBlock(null, null);
            $out2->lines[] = $prop[1];
            $this->scope->children[] = $out2;
            $this->popEnv();
            $proceed = false;
        }

        if ($proceed) {
            parent::compileProp($prop, $block, $out);
        }

        if ($prop[0] == 'mixin') {
            // We leave a mixin.
            $this->inmixin--;
        }
    }

    /**
     * Make parser.
     *
     * @param string $name
     * @return core_lessc_parser object.
     */
    protected function makeParser($name) {
        $parser = new core_lessc_parser($this, $name);
        $parser->writeComments = $this->preserveComments;
        return $parser;
    }

    /**
     * Return the formatter class object.
     *
     * We override this to allow for classes outside the compiler file to be used.
     *
     * @return object Formatter class object.
     */
    protected function newFormatter() {
        if (!empty($this->formatterName) && class_exists($this->formatterName)) {
            return new $this->formatterName;
        }
        return parent::newFormatter();
    }

    /**
     * Compile the code.
     *
     * @return string The compiled code.
     */
    public function proceed() {
        return $this->compile($this->code);
    }

    /**
     * Add some code.
     *
     * Due to limitations of the version 0.4 of lessphp, this code should not
     * be used to override existing variables. Any override of variable should
     * be done using the method {@link self::setVariables()}.
     *
     * @param string $code Some LESS code.
     * @return void
     */
    public function add_code($code) {
        $this->code .= "\n" . $code;
    }

    /**
     * Add a less file.
     *
     * This also sets updates the import directories so that the @import
     * will still work. Beware that if you add several files where some
     * of their @imports point to the same file, the path is unlikely
     * to be resolved properly. Using the method {@link self::import_file()}
     * is preferred.
     *
     * @param string $filepath The path to a LESS file.
     * @return void
     */
    public function add_file($filepath) {
        // Add the import of the file to presever relative @imports.
        $this->addImportDir(dirname($filepath));

        // Get the content of the less file.
        $this->add_file_content($filepath);
    }

    /**
     * Add the content of a file.
     *
     * Use this method when you are sure that @imports are not present
     * in the content of the file, or if you are sure that they will be
     * resolved.
     *
     * In general you will want to use this to import the content of a CSS file.
     *
     * @param string $filepath The path to the file.
     * @return void
     */
    public function add_file_content($filepath) {
        $this->add_code(file_get_contents($filepath));
    }

    /**
     * Add a rule to import a file.
     *
     * This is useful when you want to import the content of a LESS files but
     * you are not sure if it contains @imports or not. This method will
     * import the file using @import with a relative path from your
     * main file to compile. Less does not support absolute paths.
     *
     * @param string $filepath The path to the LESS file to import.
     * @param string $relativeto The path from which the relative path should be built.
     *                           Typically this would be the path to a file passed
     *                           to {@link self::add_file()}.
     *
     * @return void
     */
    public function import_file($filepath, $relativeto) {
        global $CFG;

        if (!is_readable($filepath) || !is_readable($relativeto)) {
            throw new coding_exception('Could not read the files');
        }

        $filepath = realpath($filepath);
        $relativeto = realpath($relativeto);

        if (strtolower(substr($filepath, -5)) != '.less') {
            throw new coding_exception('Imports only work with LESS files.');
        } else if (strpos(realpath($filepath), $CFG->dirroot) !== 0 ||
                strpos(realpath($relativeto), $CFG->dirroot) !== 0) {
            throw new coding_exception('Files must be in CFG->dirroot.');
        }

        // Simplify the file path the start of dirroot.
        $filepath = trim(substr($filepath, strlen($CFG->dirroot)), '/');
        $relativeto = trim(substr($relativeto, strlen($CFG->dirroot)), '/');

        // Split the file path and remove the file name.
        $dirs = explode('/', $relativeto);
        array_pop($dirs);

        // Generate the relative path.
        $relativepath = str_repeat('../', count($dirs)) . $filepath;

        $this->add_code('@import "' . $relativepath . '";');
    }

}
