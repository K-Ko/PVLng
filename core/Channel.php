<?php
/**
 * Abstract base class for all channels
 *
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2013 Knut Kohl
 * @license     GNU General Public License http://www.gnu.org/licenses/gpl.txt
 * @version     1.0.0
 */
abstract class Channel {

    /**
     * Mark that a channel is used as sub channel for readout
     */
    public $isChild = FALSE;

    /**
     * Helper function to build an instance
     */
    public static function byId( $id, $alias=TRUE ) {
        $channel = new ORM\Tree($id);

        if (!$channel->getId()) {
            throw new Exception('No channel found for Id: '.$id, 400);
        }

        if ($channel->getAliasOf() AND $alias) {
            // Is an alias channel, switch direct to the original channel
            return self::byId($channel->getAliasOf());
        }

        $model = $channel->ModelClass();
        return new $model($channel);
    }

    /**
     * Helper function to build an instance
     */
    public static function byGUID( $guid, $alias=TRUE ) {
        $channel = new ORM\Tree;
        $channel->filterByGuid($guid)->findOne();

        if ($channel->getAliasOf() AND $alias) {
            // Is an alias channel, switch direct to the original channel
            return self::byId($channel->getAliasOf());
        } elseif ($channel->getModel()) {
            // Channel is in tree
            $model = $channel->ModelClass();
            return new $model($channel);
        } else {
            // NOT in tree, may be a real writable channel?! "Fake" a tree entry
            $c = new ORM\ChannelView;
            $c->filterByGuid($guid)->findOne();
            if ($c->getId() AND $c->getWrite()) {
                $data = $c->asAssoc();
                $data['id'] = 0;
                $data['entity'] = $c->getId();
                $channel->set($data);
                $model = $c->ModelClass();
                return new $model($channel);
            }
        }

        throw new Exception('No channel found for GUID: '.$guid, 400);
    }

    /**
     * Helper function to build an instance
     */
    public static function byChannel( $id, $alias=TRUE ) {
        $channel = new ORM\ChannelView($id);

        if ($channel->getGuid()) {
            return self::byGUID($channel->getGuid(), $alias);
        }

        throw new Exception('No channel found for ID: '.$id, 400);
    }

    /**
     * Run additional code before a new channel is presented to the user
     */
    public static function beforeCreate( Array &$fields ) {}

    /**
     * Run additional code before existing data presented to user
     */
    public static function beforeEdit( \ORM\Channel $channel, Array &$fields ) {}

    /**
     * Run additional code after attributes was maintained by user
     *
     * @param $add2tree integer|null
     */
    public static function checkData( Array &$fields, $add2tree ) {
        $ok = TRUE;

        foreach ($fields as $name=>&$data) {
            // Don't check invisible fields
            if (!$data['VISIBLE']) continue;

            $data['VALUE'] = trim($data['VALUE']);

            if ($data['VALUE'] == '') {
                // Check required fields
                if ($data['REQUIRED']) {
                    $data['ERROR'][] = __('channel::ParamIsRequired');
                    $ok = FALSE;
                }
                // No further checks for empty fields required
                continue;
            }

            // Check numeric fields
            switch ($data['TYPE']) {
                case 'numeric':
                    if (!is_numeric($data['VALUE'])) {
                        $data['ERROR'][] = __('channel::ParamMustNumeric');
                        $ok = FALSE;
                    }
                    break;
                case 'integer':
                    if ((string) floor($data['VALUE']) != $data['VALUE']) {
                        $data['ERROR'][] = __('channel::ParamMustInteger');
                        $ok = FALSE;
                    }
                    break;
            } // switch
        }

        return $ok;
    }

    /**
     * Run additional code before data saved to database
     */
    public static function beforeSave( Array &$fields, \ORM\Channel $channel ) {
        foreach ($fields as $name=>$data) {
            $channel->set($name, $data['VALUE']);
        }
    }

    /**
     * Run additional code after channel was created / changed
     * If $tree is set, channel was just created
     */
    public static function afterSave( \ORM\Channel $channel, $tree=NULL ) {}

    /**
     *
     */
    public function addChild( $channel ) {
        $childs = $this->getChilds(TRUE);

        // Root node (id == 1) accept always childs
        if ($this->id == 1 OR
            $this->childs == -1 OR count($this->getChilds()) < $this->childs) {
            return NestedSet::getInstance()->insertChildNode($channel, $this->id);
        }

        Messages::Error(__('AcceptChild', $this->childs, $this->name), 400);
    }

    /**
     *
     */
    public function removeFromTree() {
        $tree = NestedSet::getInstance();

        // Remember parent node
        $parent = $tree->getParent($this->id);

        if (!$tree->DeleteBranch($this->id)) return FALSE;

        if ($tree->getChildCount($parent['id']) == 0) {
            // Reset parent channel icon
            $this->db->query(
                'UPDATE `pvlng_channel` AS c,
                        `pvlng_type` AS t
                    SET c.`icon` = t.`icon`
                  WHERE c.`id` = {1} AND c.`type` = t.`id`',
                $parent['entity']
            );
        }
    }

    /**
     *
     */
    public function __get( $attribute ) {
        throw new Exception('Unknown attribute: '.$attribute, 400);
    }

    /**
     *
     */
    public function getAttributes( $attribute=NULL ) {
        if ($attribute != '') {
            // Accept attribute name 'factor' for resolution
            // Here WITHOUT check, will be handled by __get()
            return array($attribute => $attribute == 'factor' ? $this->resolution : $this->$attribute);
        } else {
            return array_merge(
                $this->getAttributesShort(),
                array(
                    'start'       => $this->start,
                    'end'         => $this->end,
                    'consumption' => 0,
                    'costs'       => 0
                ),
                $this->attributes
            );
        }
    }

    /**
     *
     */
    public function getAttributesShort() {
        return array(
            'guid'        => $this->guid,
            'name'        => $this->name,
            'serial'      => $this->serial,
            'channel'     => $this->channel,
            'description' => $this->description,
            'type'        => $this->type,
            'unit'        => $this->unit,
            'decimals'    => $this->decimals,
            'numeric'     => $this->numeric,
            'meter'       => $this->meter,
            'resolution'  => $this->resolution,
            'threshold'   => $this->threshold,
            'valid_from'  => $this->valid_from,
            'valid_to'    => $this->valid_to,
            'cost'        => $this->cost,
            'childs'      => $this->childs,
            'read'        => $this->read,
            'write'       => $this->write,
            'graph'       => $this->graph,
            'public'      => $this->public,
            'icon'        => $this->icon,
            'extra'       => is_array($this->extra) ? implode("\n", $this->extra) : $this->extra,
            'comment'     => trim($this->comment)
        );
    }

    /**
     *
     */
    public function write( $request, $timestamp=NULL ) {

        $this->before_write($request);

        // Default behavior
        $reading = ORM\Reading::factory($this->numeric);

        if ($this->numeric) {
            // Check that new value is inside the valid range
            if ((!is_null($this->valid_from) AND $this->value < $this->valid_from) OR
                (!is_null($this->valid_to)   AND $this->value > $this->valid_to)) {

                $msg = sprintf('Value %1$s is outside of valid range (%2$s <= %1$f <= %3$s)',
                               $this->value, $this->valid_from, $this->valid_to);

                $cfg = new ORM\Config('LogInvalid');

                if ($cfg->value != 0) ORM\Log::save($this->name, $msg);

                throw new Exception($msg, 200);
            }

            $lastReading = $reading->getLastReading($this->entity, $timestamp);

            // Check that new reading value is inside the threshold range
            if ($this->threshold > 0 AND abs($this->value-$lastReading) > $this->threshold) {
                // Throw away invalid reading value
                return 0;
            }

            // Check that new meter reading value can't be lower than before
            if ($this->meter AND $lastReading AND $this->value < $lastReading) {
                $this->value = $lastReading;
            }
        }

        // Write performance only for "real" savings if the program flow
        // can to here and not returned earlier
        $this->performance->setAction('write');

        $rc = $reading
              ->setId($this->entity)
              ->setTimestamp($timestamp)
              ->setData($this->value)
              ->insert();

        if ($rc) Hook::process('data.save.after', $this);

        return $rc;
    }

    /**
     *
     */
    public function read( $request ) {

        $logSQL = slimMVC\Config::getInstance()->get('Log.SQL');

        $this->performance->setAction('read');

        $this->before_read($request);

        if ($this->isChild AND $this->period[1] == self::NO) {
            // For channels used as childs set period to at least 1 minute
            $this->period[1] = self::ASCHILD;
        }

        $q = DBQuery::forge($this->table[$this->numeric]);

        $buffer = new Buffer;

        if ($this->period[1] == self::READLAST OR
            // Simply read also last data set for sensor channels
            (!$this->meter AND $this->period[1] == self::LAST)) {

            // Fetch last reading and set some data to 0 to get correct field order
            $q->get($q->FROM_UNIXTIME('timestamp'), 'datetime')
              ->get('timestamp')
              ->get('data')
              ->get(0, 'min')
              ->get(0, 'max')
              ->get(0, 'count')
              ->get(0, 'timediff')
              ->get($this->meter ? 'data' : 0, 'consumption')
              ->filter('id', $this->entity)
              ->order('timestamp', TRUE)
              ->limit(1);

            if ($this->period[1] != self::READLAST) {
                $this->filterReadTimestamp($q);
            }

            $row = (array) $this->db->queryRow($q);

            if (!$row) return $this->after_read($buffer);

            $row = (array) $row;

            if ($this->period[1] != self::READLAST) {
                if ($logSQL) ORM\Log::save('Read data', $this->name . ' (' . $this->description . ")\n\n" . $q);
                $this->SQLHeader($request, $q);

                // Reset query and read add. data
                $q->select($this->table[$this->numeric])
                  ->get($q->MIN('data'), 'min')
                  ->get($q->MAX('data'), 'max')
                  ->get($q->COUNT('id'), 'count')
                  ->get($q->MAX('timestamp').'-'.$q->MIN('timestamp'), 'timediff')
                  ->filter('id', $this->entity)
                  ->limit(1);
                $this->filterReadTimestamp($q);
                $row = array_merge($row, (array) $this->db->queryRow($q));
            }

            $buffer->write($row);

        } else {

            if ($this->period[1] == self::LAST OR $this->period[1] == self::ALL) {

                $q->get($q->FROM_UNIXTIME('timestamp'), 'datetime')
                  ->get('timestamp')
                  ->get('data')
                  ->get('data', 'min')
                  ->get('data', 'max')
                  ->get(1, 'count')
                  ->get(0, 'timediff')
                  ->get('timestamp', 'g');

            } else {

                $q->get($q->FROM_UNIXTIME($q->MIN('timestamp')), 'datetime')
                  ->get($q->MIN('timestamp'), 'timestamp');

                switch (TRUE) {
                    case !$this->numeric:
                        // Raw data for non-numeric channels
                        $q->get('data');  break;
                    case $this->meter:
                        // Max. value for meters
                        $q->get($q->MAX('data'), 'data');  break;
                    case $this->counter:
                        // Summarize counter ticks
                        $q->get($q->SUM('data'), 'data');  break;
                    default:
                        // Average value of sensors/proxies
                        $q->get($q->AVG('data'), 'data');
                } // switch

                $q->get($q->MIN('data'), 'min')
                  ->get($q->MAX('data'), 'max')
                  ->get($q->COUNT('id'), 'count')
                  ->get($q->MAX('timestamp').'-'.$q->MIN('timestamp'), 'timediff')
                  ->get($this->periodGrouping(), 'g')
                  ->group('g');
            }

            $this->filterReadTimestamp($q);
            $q->filter('id', $this->entity)->order('timestamp');

            // Use bufferd result set
            $this->db->Buffered = TRUE;

            if ($res = $this->db->query($q)) {

                if ($this->meter) {
                    if ($this->TimestampMeterOffset[$this->period[1]] > 0) {
                        $row = $res->fetch_assoc();
                        $offset = $row['data'];
                    } else {
                        $offset = 0;
                    }
                    $last = 0;
                }

                while ($row = $res->fetch_assoc()) {

                    $row['consumption'] = 0;

                    if ($this->meter) {

                        if ($offset === 0) {
                            // 1st row, calculate start data
                            $offset = $row['data'];
                        }

                        $row['data'] -= $offset;
                        $row['min']  -= $offset;
                        $row['max']  -= $offset;

                        // calc consumption from previous max value
                        $row['consumption'] = $row['data'] - $last;
                        $last = $row['data'];
                    }

                    // remove grouping value and save
                    $id = $row['g'];
                    unset($row['g']);
                    $buffer->write($row, $id);
                }

                // Don't forget to close for buffered results!
                $res->close();
            }

            $this->db->Buffered = FALSE;
        }

        if ($logSQL) ORM\Log::save('Read data', $this->name . ' (' . $this->description . ")\n\n" . $q);

        $this->SQLHeader($request, $q);

        return $this->after_read($buffer);
    }

    /**
     *
     */
    protected function filterReadTimestamp( &$q ) {
        if ($this->period[1] != self::ALL) {
            // Time is only relevant for period != ALL
            if ($this->start) {
                if (!$this->meter) {
                    $q->filter('timestamp', array('min'=>$this->start));
                } else {
                    // Fetch also period before start for correct consumption calculation!
                    $q->filter('timestamp', array('min'=>$this->start-$this->TimestampMeterOffset[$this->period[1]]));
                }
            }
            if ($this->end < time()) {
                $q->filter('timestamp', array('max'=>$this->end-1));
            }
        }
    }

    /**
     *
     */
    protected function SQLHeader( $request, $q ) {
        if (!array_key_exists('sql', $request) OR !$request['sql']) return;

        $sql = $this->name;
        if ($this->description) $sql .= ' (' . $this->description . ')';
        Header('X-SQL-' . uniqid() . ': ' . $sql . ': ' . $q);
    }

    /**
     *
     */
    public function __destruct() {
        $time = (microtime(TRUE) - $this->time) * 1000;

        Header(sprintf('X-Query-Time: %d ms', $time));

        // Check for real action to log
        if ($this->performance->getAction()) {
            $this->performance->setTime($time)->insert();
        }
    }

    // -------------------------------------------------------------------------
    // PROTECTED
    // -------------------------------------------------------------------------

    /**
     *
     */
    protected $db;

    /**
     *
     */
    protected $table = array(
        'pvlng_reading_str', // numeric == 0
        'pvlng_reading_num', // numeric == 1
    );

    /**
     *
     */
    protected $counter = 0;

    /**
     *
     */
    protected $start;

    /**
     *
     */
    protected $end;

    /**
     *
     */
    protected $period = array( 0, self::NO );

    /**
     *
     */
    protected $time;

    /**
     * Extra attributes
     */
    protected $attributes = array();

    /**
     * Grouping
     */
    const NO        =  0;
    const ASCHILD   =  1; // Required for grouping by at least 1 minute
    const MINUTE    = 10;
    const HOUR      = 20;
    const DAY       = 30;
    const WEEK      = 40;
    const MONTH     = 50;
    const QUARTER   = 60;
    const YEAR      = 70;
    const LAST      = 80;
    const READLAST  = 81;
    const ALL       = 90;

    /**
     *
     */
    protected $TimestampMeterOffset = array(
        self::NO        =>        0,
        self::ASCHILD   =>        0,
        self::MINUTE    =>       60,
        self::HOUR      =>     3600,
        self::DAY       =>    86400,
        self::WEEK      =>   604800,
        self::MONTH     =>  2678400,
        self::QUARTER   =>  7776000,
        self::YEAR      => 31536000,
        self::LAST      =>        0,
        self::READLAST  =>        0,
        self::ALL       =>        0,
    );

    /**
     *
     */
    protected function __construct( ORM\Tree $channel ) {
        $this->time   = microtime(TRUE);
        $this->db     = slimMVC\MySQLi::getInstance();
        $this->config = slimMVC\Config::getInstance();

        foreach ($channel->asAssoc() as $key=>$value) {
            $this->$key = $value;
        }

        $this->performance = new ORM\Performance;
    }

    /**
     *
     */
    protected function periodGrouping() {
        static $GroupBy = array(
            self::NO        => '`timestamp`',
            self::ASCHILD   => '`timestamp` DIV 60',
            self::MINUTE    => '`timestamp` DIV (60 * %d)',
            self::HOUR      => 'FROM_UNIXTIME(`timestamp`, "%%Y%%j%%H") DIV %d',
            self::DAY       => 'FROM_UNIXTIME(`timestamp`, "%%Y%%j") DIV %d',
            self::WEEK      => 'FROM_UNIXTIME(`timestamp`, "%%x%%v") DIV %d',
            self::MONTH     => 'FROM_UNIXTIME(`timestamp`, "%%Y%%m") DIV %d',
            self::QUARTER   => 'FROM_UNIXTIME(`timestamp`, "%%Y%%m") DIV (3 * %d)',
            self::YEAR      => 'FROM_UNIXTIME(`timestamp`, "%%Y") DIV %d',
            self::LAST      => '`timestamp`',
            self::READLAST  => '`timestamp`',
            self::ALL       => '`timestamp`',
        );
        return sprintf($GroupBy[$this->period[1]], $this->period[0]);
    }

    /**
     * Lazy load childs on request
     */
    protected function getChilds( $refresh=FALSE ) {
        if ($refresh OR is_null($this->_childs)) {
            $this->_childs = array();
            foreach (NestedSet::getInstance()->getChilds($this->id) as $child) {
                $child = self::byID($child['id']);
                $child->isChild = TRUE;
                $this->_childs[] = $child;
            }
        }
        return $this->_childs;
    }

    /**
     * Lazy load child on request, 1 based!
     */
    protected function getChild( $id ) {
        $this->getChilds();
        return ($_=&$this->_childs[$id-1]) ?: FALSE;
    }

    /**
     *
     */
    protected function before_write( $request ) {

        if (!$this->write) {
            throw new \Exception('Can\'t write data to '.$this->name.', '
                                .'instance of '.get_class($this), 400);
        }

        if (!isset($request['data']) OR !is_scalar($request['data'])) {
            throw new Exception('Missing data value', 400);
        }

        // Check if a WRITEMAP::{...} exists to rewrite e.g. from numeric to non-numeric
        if (preg_match('~^WRITEMAP::(.*?)$~m', $this->comment, $args) AND
            $map = json_decode($args[1], TRUE)) {
            $request['data'] = ($_=&$map[$request['data']]) ?: 'unknown ('.$request['data'].')';
        }

        $this->value = $request['data'];

        Hook::process('data.save.before', $this);

        if ($this->numeric) {
            // Remove all non-numeric characters
            $this->value = preg_replace('~[^0-9.eE-]~', '', $this->value);

            // Interpret empty numeric value as invalid and ignore them
            if ($this->value == '') throw new Exception(NULL, 200);

            $this->value = +$this->value;

            if ($this->meter) {
                if ($this->value == 0) {
                    throw new Exception('Invalid meter reading: 0', 422);
                }

                $lastReading = ORM\Reading::factory($this->numeric)->getLastReading($this->entity);

                if ($this->meter AND $this->value + $this->offset < $lastReading AND $this->adjust) {
                    // Auto-adjust channel offset
                    ORM\Log::save(
                        $this->name,
                        sprintf("Adjust offset\nLast offset: %f\nLast reading: %f\nValue: %f",
                                $this->offset, $lastReading, $this->value)
                    );

                    // Update channel in database
                    $t = new ORM\Channel($this->entity);
                    $t->offset = $lastReading;
                    $t->update();

                    $this->offset = $lastReading;
                }
            }

            // MUST also work for sensor channels
            // Apply offset
            $this->value += $this->offset;

            if ($this->meter AND $this->value == $lastReading) {
                // Ignore for meters values which are equal last reading
                throw new Exception(NULL, 200);
            }
        }
    }

    /**
     *
     */
    protected function before_read( $request ) {
        // Readable channel?
        if (!$this->read)
            throw new \Exception('Can\'t read data from '.$this->name.', '
                                .'instance of '.get_class($this), 400);

        // Required number of child channels?
        if ($this->childs >= 0 AND count($this->getChilds()) != $this->childs)
            throw new \Exception($this->name.' must have '.$this->childs.' child(s)', 400);

        // Prepare analysis of request
        $request = array_merge(
            array(
                'start'  => '00:00',
                'end'    => '24:00',
                'period' => ''
            ),
            $request
        );

        $latitude  = $this->config->get('Location.Latitude');
        $longitude = $this->config->get('Location.Longitude');

        // Start timestamp
        if ($request['start'] == 'sunrise') {
            if ($latitude == '' OR $longitude == '') {
                throw new \Exception('Invalid start timestamp: "sunrise", missing Location in config/config.php', 400);
            }
            $this->start = date_sunrise(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90, date('Z')/3600);
        } else {
            $this->start = ($request['start'] == '' OR is_numeric($request['start']))
                         ? $request['start']
                         : strtotime($request['start']);
        }

        if ($this->start === FALSE)
            throw new \Exception('Invalid start timestamp: '.$request['start'], 400);

        // End timestamp
        if ($request['end'] == 'sunset') {
            if ($latitude == '' OR $longitude == '') {
                throw new \Exception('Invalid end timestamp: "sunset", missing Location in config/config.php', 400);
            }
            $this->end = date_sunset(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90, date('Z')/3600);
        } else {
            $this->end = is_numeric($request['end'])
                       ? $request['end']
                       : strtotime($request['end']);
        }

        if ($this->end === FALSE)
            throw new \Exception('Invalid end timestamp: '.$request['end'], 400);

        // Consolidation period
        if ($request['period'] != '') {
            // normalize aggr. periods
            if (preg_match('~^([.\d]*)(|l|last|r|readlast|i|min|minutes?|h|hours?|d|days?|w|weeks?|m|months?|q|quarters?|y|years|a|all?)$~',
                           $request['period'], $args)) {
                $this->period = array($args[1]?:1, self::NO);
                switch (substr($args[2], 0, 2)) {
                    case 'i': case 'mi':  $this->period[1] = self::MINUTE;   break;
                    case 'h': case 'ho':  $this->period[1] = self::HOUR;     break;
                    case 'd': case 'da':  $this->period[1] = self::DAY;      break;
                    case 'w': case 'we':  $this->period[1] = self::WEEK;     break;
                    case 'm': case 'mo':  $this->period[1] = self::MONTH;    break;
                    case 'q': case 'qa':  $this->period[1] = self::QUARTER;  break;
                    case 'y': case 'ye':  $this->period[1] = self::YEAR;     break;
                    case 'l': case 'la':  $this->period[1] = self::LAST;     break;
                    case 'r': case 're':  $this->period[1] = self::READLAST; break;
                    case 'a': case 'al':  $this->period[1] = self::ALL;      break;
                }
            } else {
                throw new \Exception('Unknown aggregation period: ' . $request['period'], 400);
            }
        }
    }

    /**
     *
     */
    protected function after_read( Buffer $buffer ) {

        $datafile = new Buffer;

        $last = 0;
        $lastrow = FALSE;

        foreach ($buffer as $id=>$row) {

            if ($this->meter) {
                /* check meter values raising */
                if ($this->resolution > 0 AND $row['data'] < $last OR
                    $this->resolution < 0 AND $row['data'] > $last) {
                    $row['data'] = $last;
                }
                $last = $row['data'];
            }

            if ($this->numeric AND $this->resolution != 1) {
                $row['data']        *= $this->resolution;
                $row['min']         *= $this->resolution;
                $row['max']         *= $this->resolution;
                $row['consumption'] *= $this->resolution;
            }

            if ($this->numeric) {
                // Skip invalid (numeric) rows
                // Apply valid_from and valid_to here ONLY if channel
                // is NOT writable, this will be handled during write()
                if ($this->write OR
                    ((is_null($this->valid_from) OR $row['data'] >= $this->valid_from) AND
                     (is_null($this->valid_to)   OR $row['data'] <= $this->valid_to))) {

                    $this->value = $row['data'];
                    Hook::process('data.read.after', $this);
                    $row['data'] = $this->value;

                    $datafile->write($row, $id);
                    $lastrow = $row;
                }
            } else {
                $this->value = $row['data'];
                Hook::process('data.read.after', $this);
                $row['data'] = $this->value;

                $datafile->write($row, $id);
                $lastrow = $row;
            }
        }
        $buffer->close();

        if ($this->period[1] == self::LAST AND $lastrow) {
            $datafile = new \Buffer;
            $datafile->write($lastrow);
        }

        return $datafile;
    }

    // -------------------------------------------------------------------------
    // PROTECTED
    // -------------------------------------------------------------------------

    /**
     *
     */
    protected $config;

    // -------------------------------------------------------------------------
    // PRIVATE
    // -------------------------------------------------------------------------

    /**
     *
     */
    private $_childs;

}
