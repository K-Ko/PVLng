<?php
/**
 * Abstract base class for table 'pvlng_tariff_view'
 *
 * *** NEVER EVER EDIT THIS FILE! ***
 *
 * To extend the functionallity, edit "TariffView.php"
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
abstract class TariffViewBase extends \slimMVC\ORM {

    // -----------------------------------------------------------------------
    // PUBLIC
    // -----------------------------------------------------------------------

    // -----------------------------------------------------------------------
    // Setter methods
    // -----------------------------------------------------------------------

    /**
     * 'pvlng_tariff_view' is a view, no setters
     */

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
     * Basic getter for field 'name'
     *
     * @return mixed Name value
     */
    public function getName() {
        return $this->fields['name'];
    }   // getName()

    /**
     * Basic getter for field 'tariff_comment'
     *
     * @return mixed TariffComment value
     */
    public function getTariffComment() {
        return $this->fields['tariff_comment'];
    }   // getTariffComment()

    /**
     * Basic getter for field 'date'
     *
     * @return mixed Date value
     */
    public function getDate() {
        return $this->fields['date'];
    }   // getDate()

    /**
     * Basic getter for field 'cost'
     *
     * @return mixed Cost value
     */
    public function getCost() {
        return $this->fields['cost'];
    }   // getCost()

    /**
     * Basic getter for field 'time'
     *
     * @return mixed Time value
     */
    public function getTime() {
        return $this->fields['time'];
    }   // getTime()

    /**
     * Basic getter for field 'days'
     *
     * @return mixed Days value
     */
    public function getDays() {
        return $this->fields['days'];
    }   // getDays()

    /**
     * Basic getter for field 'tariff'
     *
     * @return mixed Tariff value
     */
    public function getTariff() {
        return $this->fields['tariff'];
    }   // getTariff()

    /**
     * Basic getter for field 'time_comment'
     *
     * @return mixed TimeComment value
     */
    public function getTimeComment() {
        return $this->fields['time_comment'];
    }   // getTimeComment()

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
     * Filter for field 'name'
     *
     * @param  mixed    $name Filter value
     * @return Instance For fluid interface
     */
    public function filterByName( $name ) {
        $this->filter[] = '`name` = "'.$this->quote($name).'"';
        return $this;
    }   // filterByName()

    /**
     * Filter for field 'tariff_comment'
     *
     * @param  mixed    $tariff_comment Filter value
     * @return Instance For fluid interface
     */
    public function filterByTariffComment( $tariff_comment ) {
        $this->filter[] = '`tariff_comment` = "'.$this->quote($tariff_comment).'"';
        return $this;
    }   // filterByTariffComment()

    /**
     * Filter for field 'date'
     *
     * @param  mixed    $date Filter value
     * @return Instance For fluid interface
     */
    public function filterByDate( $date ) {
        $this->filter[] = '`date` = "'.$this->quote($date).'"';
        return $this;
    }   // filterByDate()

    /**
     * Filter for field 'cost'
     *
     * @param  mixed    $cost Filter value
     * @return Instance For fluid interface
     */
    public function filterByCost( $cost ) {
        $this->filter[] = '`cost` = "'.$this->quote($cost).'"';
        return $this;
    }   // filterByCost()

    /**
     * Filter for field 'time'
     *
     * @param  mixed    $time Filter value
     * @return Instance For fluid interface
     */
    public function filterByTime( $time ) {
        $this->filter[] = '`time` = "'.$this->quote($time).'"';
        return $this;
    }   // filterByTime()

    /**
     * Filter for field 'days'
     *
     * @param  mixed    $days Filter value
     * @return Instance For fluid interface
     */
    public function filterByDays( $days ) {
        $this->filter[] = '`days` = "'.$this->quote($days).'"';
        return $this;
    }   // filterByDays()

    /**
     * Filter for field 'tariff'
     *
     * @param  mixed    $tariff Filter value
     * @return Instance For fluid interface
     */
    public function filterByTariff( $tariff ) {
        $this->filter[] = '`tariff` = "'.$this->quote($tariff).'"';
        return $this;
    }   // filterByTariff()

    /**
     * Filter for field 'time_comment'
     *
     * @param  mixed    $time_comment Filter value
     * @return Instance For fluid interface
     */
    public function filterByTimeComment( $time_comment ) {
        $this->filter[] = '`time_comment` = "'.$this->quote($time_comment).'"';
        return $this;
    }   // filterByTimeComment()

    // -----------------------------------------------------------------------
    // PROTECTED
    // -----------------------------------------------------------------------

    /**
     * Table name
     *
     * @var string $table Table name
     */
    protected $table = 'pvlng_tariff_view';

}