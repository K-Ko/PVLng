<?php
/**
 * Abstract base class for table 'pvlng_log'
 *
 * *** NEVER EVER EDIT THIS FILE! ***
 *
 * To extend the functionallity, edit "Log.php"
 *
 * If you make changes here, they will be lost on next upgrade PVLng!
 *
 * @author     Knut Kohl <github@knutkohl.de>
 * @copyright  2014 Knut Kohl
 * @license    MIT License (MIT) http://opensource.org/licenses/MIT
 *
 * @author     PVLng ORM class builder
 * @version    1.1.0 / 2014-06-04
 */
namespace ORM;

/**
 *
 */
abstract class LogBase extends \slimMVC\ORM {

    // -----------------------------------------------------------------------
    // PUBLIC
    // -----------------------------------------------------------------------

    // -----------------------------------------------------------------------
    // Setter methods
    // -----------------------------------------------------------------------

    /**
     * 'id' is AutoInc, no setter
     */

    /**
     * Basic setter for field 'timestamp'
     *
     * @param  mixed    $timestamp Timestamp value
     * @return Instance For fluid interface
     */
    public function setTimestamp( $timestamp ) {
        $this->fields['timestamp'] = $timestamp;
        return $this;
    }   // setTimestamp()

    /**
     * Basic setter for field 'scope'
     *
     * @param  mixed    $scope Scope value
     * @return Instance For fluid interface
     */
    public function setScope( $scope ) {
        $this->fields['scope'] = $scope;
        return $this;
    }   // setScope()

    /**
     * Basic setter for field 'data'
     *
     * @param  mixed    $data Data value
     * @return Instance For fluid interface
     */
    public function setData( $data ) {
        $this->fields['data'] = $data;
        return $this;
    }   // setData()

    // -----------------------------------------------------------------------
    // Getter methods
    // -----------------------------------------------------------------------

    /**
     * Basic getter for field 'id'
     *
     * @return mixed Id value
     */
    public function getId() {
        return $this->fields['id'];
    }   // getId()

    /**
     * Basic getter for field 'timestamp'
     *
     * @return mixed Timestamp value
     */
    public function getTimestamp() {
        return $this->fields['timestamp'];
    }   // getTimestamp()

    /**
     * Basic getter for field 'scope'
     *
     * @return mixed Scope value
     */
    public function getScope() {
        return $this->fields['scope'];
    }   // getScope()

    /**
     * Basic getter for field 'data'
     *
     * @return mixed Data value
     */
    public function getData() {
        return $this->fields['data'];
    }   // getData()

    // -----------------------------------------------------------------------
    // Filter methods
    // -----------------------------------------------------------------------

    /**
     * Filter for field 'id'
     *
     * @param  mixed    $id Filter value
     * @return Instance For fluid interface
     */
    public function filterById( $id ) {
        $this->filter[] = '`id` = "'.$this->quote($id).'"';
        return $this;
    }   // filterById()

    /**
     * Filter for field 'timestamp'
     *
     * @param  mixed    $timestamp Filter value
     * @return Instance For fluid interface
     */
    public function filterByTimestamp( $timestamp ) {
        $this->filter[] = '`timestamp` = "'.$this->quote($timestamp).'"';
        return $this;
    }   // filterByTimestamp()

    /**
     * Filter for field 'scope'
     *
     * @param  mixed    $scope Filter value
     * @return Instance For fluid interface
     */
    public function filterByScope( $scope ) {
        $this->filter[] = '`scope` = "'.$this->quote($scope).'"';
        return $this;
    }   // filterByScope()

    /**
     * Filter for field 'data'
     *
     * @param  mixed    $data Filter value
     * @return Instance For fluid interface
     */
    public function filterByData( $data ) {
        $this->filter[] = '`data` = "'.$this->quote($data).'"';
        return $this;
    }   // filterByData()

    // -----------------------------------------------------------------------
    // PROTECTED
    // -----------------------------------------------------------------------

    /**
     * Table name
     *
     * @var string $table Table name
     */
    protected $table = 'pvlng_log';

}