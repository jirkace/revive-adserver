<?php

/*
+---------------------------------------------------------------------------+
| Revive Adserver                                                           |
| http://www.revive-adserver.com                                            |
|                                                                           |
| Copyright: See the COPYRIGHT.txt file.                                    |
| License: GPLv2 or later, see the LICENSE.txt file.                        |
+---------------------------------------------------------------------------+
*/

require_once MAX_PATH . '/lib/OA/Dal/DataGenerator.php';

/**
 * A common DataObjects unit class for testing DataObjects
 *
 * @package    MaxDal
 * @subpackage TestSuite
 */
class DalUnitTestCase extends UnitTestCase
{
    /**
     *    Uses reflection to run every method within itself
     *    starting with the string "test" unless a method
     *    is specified.
     *    @param SimpleReporter $reporter    Current test reporter.
     *    @return boolean                    True if all tests passed.
     *    @access public
     */
    public function run(&$reporter)
    {
        $this->setUpFixture();
        $ret = parent::run($reporter);
        $this->tearDownFixture();
        return $ret;
    }
    
    /**
     * setUpFixture() method is executed once before running all the tests
     *
     */
    public function setUpFixture()
    {
    }
    
    /**
     * tearDownFixture() method is executed once after running all the tests
     *
     */
    public function tearDownFixture()
    {
    }
    
    /**
     * Should we compare DataObjects with or without "updated" fields? Default true means
     * it should be compared without "updated"
     *
     * @var boolean
     */
    public $stripUpdated = true;

    /**
     *    Will trigger a pass if the two DataObjects have
     *    the same value only (except private fields). Otherwise a fail.
     *    By private fields we mean all fields starting with "_"
     *    @param mixed $first          Value to compare.
     *    @param mixed $second         Value to compare.
     *    @param string $message       Message to display.
     *    @return boolean              True on pass
     *    @access public
     */
    public function assertEqualDataObjects($first, $second, $message = "%s")
    {
        return $this->assertExpectation(
            new EqualExpectation($this->stripUpdated($this->stripPrivateFields($first))),
            $this->stripUpdated($this->stripPrivateFields($second)),
            $message
        );
    }

    /**
     *    Will trigger a pass if the two DataObjects have
     *    a different value (after removing private fields from them). Otherwise a fail.
     *    By private fields we mean all fields starting with "_"
     *    @param mixed $first          Value to compare.
     *    @param mixed $second         Value to compare.
     *    @param string $message       Message to display.
     *    @return boolean              True on pass
     *    @access public
     */
    public function assertNotEqualDataObjects($first, $second, $message = "%s")
    {
        return $this->assertExpectation(
            new NotEqualExpectation($this->stripUpdated($this->stripPrivateFields($first))),
            $this->stripUpdated($this->stripPrivateFields($second)),
            $message
        );
    }

    /**
     *    Will be true if the value is empty.
     *    @param null $value       Supposedly null value.
     *    @param string $message   Message to display.
     *    @return boolean                        True on pass
     *    @access public
     */
    public function assertEmpty($value, $message = "%s")
    {
        $dumper = new SimpleDumper();
        $message = sprintf(
            $message,
            "[" . $dumper->describeValue($value) . "] should be empty"
        );
        return $this->assertTrue(empty($value), $message);
    }

    /**
     *    Will be true if the value is not empty.
     *    @param mixed $value           Supposedly set value.
     *    @param string $message        Message to display.
     *    @return boolean               True on pass.
     *    @access public
     */
    public function assertNotEmpty($value, $message = "%s")
    {
        $dumper = new SimpleDumper();
        $message = sprintf(
            $message,
            "[" . $dumper->describeValue($value) . "] should not be null"
        );
        return $this->assertTrue(!empty($value), $message);
    }

    /**
     *   Unset (before comparison) any non transent private fields in DataObject
     *   By private fields we mean all fields starting with "_"
     *
     *   @param DataObject $do
     *   @return DataObject
     */
    public function stripPrivateFields($do)
    {
        if (is_object($do)) {
            $fields = get_object_vars($do);
            foreach ($fields as $field => $v) {
                if (strpos($field, '_') === 0) {
                    unset($do->$field);
                }
            }
        }
        return $do;
    }

    /**
     *   Unset (before comparison) any Primary Keys which DataObject could have
     *
     *   @param DataObject $do
     *   @param bool $stripPrivateFields  Should we also strip private fields?
     *   @return DataObject
     */
    public function stripKeys($do)
    {
        if (is_a($do, 'DB_DataObject')) {
            $keys = $do->keys();
            foreach ($keys as $key) {
                unset($do->$key);
            }
        }
        return $do;
    }

    public function stripUpdated($do)
    {
        if ($this->stripUpdated && $do->refreshUpdatedFieldIfExists) {
            unset($do->updated);
        }
        return $do;
    }

    public function getPrefix()
    {
        return OA_Dal::getTablePrefix();
    }
}
