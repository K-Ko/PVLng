<?php
/**
 * Abstract base class for table "pvlng_reading_last"
 *
 *****************************************************************************
 *                       NEVER EVER EDIT THIS FILE!
 *****************************************************************************
 * To extend functionallity edit "ReadingLast.php"
 * If you make changes here, they will be lost on next upgrade!
 *
 * @author     Knut Kohl <github@knutkohl.de>
 * @copyright  2017 Knut Kohl
 * @license    MIT License (MIT) http://opensource.org/licenses/MIT
 *
 * @author     ORM class builder
 * @version    2.0.0 / 2017-08-17
 */
namespace ORM;

/**
 *
 */
use Core\ORM;

/**
 *
 */
abstract class ReadingLastBase extends ORM
{

    // -----------------------------------------------------------------------
    // PUBLIC
    // -----------------------------------------------------------------------

    // -----------------------------------------------------------------------
    // Setter methods
    // -----------------------------------------------------------------------

    /**
     * Basic setter for field "id"
     *
     * @param  mixed    $id Id value
     * @return Instance For fluid interface
     */
    public function setId($id)
    {
        $this->fields['id'] = $id;
        return $this;
    }

    /**
     * Raw setter for field "id", for INSERT, REPLACE and UPDATE
     *
     * @param  mixed    $id Id value
     * @return Instance For fluid interface
     */
    public function setIdRaw($id)
    {
        $this->raw['id'] = $id;
        return $this;
    }

    /**
     * Basic setter for field "timestamp"
     *
     * @param  mixed    $timestamp Timestamp value
     * @return Instance For fluid interface
     */
    public function setTimestamp($timestamp)
    {
        $this->fields['timestamp'] = $timestamp;
        return $this;
    }

    /**
     * Raw setter for field "timestamp", for INSERT, REPLACE and UPDATE
     *
     * @param  mixed    $timestamp Timestamp value
     * @return Instance For fluid interface
     */
    public function setTimestampRaw($timestamp)
    {
        $this->raw['timestamp'] = $timestamp;
        return $this;
    }

    /**
     * Basic setter for field "data"
     *
     * @param  mixed    $data Data value
     * @return Instance For fluid interface
     */
    public function setData($data)
    {
        $this->fields['data'] = $data;
        return $this;
    }

    /**
     * Raw setter for field "data", for INSERT, REPLACE and UPDATE
     *
     * @param  mixed    $data Data value
     * @return Instance For fluid interface
     */
    public function setDataRaw($data)
    {
        $this->raw['data'] = $data;
        return $this;
    }

    // -----------------------------------------------------------------------
    // Getter methods
    // -----------------------------------------------------------------------

    /**
     * Basic getter for field "id"
     *
     * @return mixed Id value
     */
    public function getId()
    {
        return $this->fields['id'];
    }

    /**
     * Basic getter for field "timestamp"
     *
     * @return mixed Timestamp value
     */
    public function getTimestamp()
    {
        return $this->fields['timestamp'];
    }

    /**
     * Basic getter for field "data"
     *
     * @return mixed Data value
     */
    public function getData()
    {
        return $this->fields['data'];
    }

    // -----------------------------------------------------------------------
    // Filter methods
    // -----------------------------------------------------------------------

    /**
     * Filter for field "id"
     *
     * @param  mixed    $id Filter value
     * @return Instance For fluid interface
     */
    public function filterById($id)
    {
        return $this->filter('id', $id);
    }

    /**
     * Filter for field "timestamp"
     *
     * @param  mixed    $timestamp Filter value
     * @return Instance For fluid interface
     */
    public function filterByTimestamp($timestamp)
    {
        return $this->filter('timestamp', $timestamp);
    }

    /**
     * Filter for field "data"
     *
     * @param  mixed    $data Filter value
     * @return Instance For fluid interface
     */
    public function filterByData($data)
    {
        return $this->filter('data', $data);
    }

    // -----------------------------------------------------------------------
    // PROTECTED
    // -----------------------------------------------------------------------

    /**
     * Update fields on insert on duplicate key
     */
    protected function onDuplicateKey()
    {
        return '`timestamp` = VALUES(`timestamp`)
              , `data` = VALUES(`data`)';
    }

    /**
     * Call create table sql on class creation and set to false
     */
    protected static $memory = false;

    /**
     * SQL for creation
     *
     * @var string $createSQL
     */
    // @codingStandardsIgnoreStart
    protected static $createSQL = '
        CREATE TABLE IF NOT EXISTS `pvlng_reading_last` (
          `id` smallint(5) unsigned NOT NULL DEFAULT \'0\',
          `timestamp` int(10) unsigned NOT NULL DEFAULT \'0\',
          `data` varchar(50) NOT NULL DEFAULT \'\',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT=\'Numeric readings\'
    ';
    // @codingStandardsIgnoreEnd

    /**
     * Table name
     *
     * @var string $table Table name
     */
    protected $table = 'pvlng_reading_last';

    /**
     *
     */
    protected $fields = [
        'id'        => '',
        'timestamp' => '',
        'data'      => ''
    ];

    /**
     *
     */
    protected $nullable = [
        'id'        => false,
        'timestamp' => false,
        'data'      => false
    ];

    /**
     *
     */
    protected $primary = ['id'];

    /**
     *
     */
    protected $autoinc = '';
}
