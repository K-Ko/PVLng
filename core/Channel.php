<?php
/**
 * Abstract base class for all channels
 *
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2013 Knut Kohl
 * @license     GNU General Public License http://www.gnu.org/licenses/gpl.txt
 * @version     1.0.0
 */
abstract class Channel
{
    /**
     *
     */
    public static $Database;

    /**
     * Mark that a channel is used as sub channel for readout
     */
    public $isChild = false;

    /**
     * Helper function to build an instance
     */
    public static function byId($id, $alias=true)
    {
        $channel = new \ORM\Tree($id);

        if (!$channel->getId()) {
            throw new \Exception('No channel found for Id: '.$id, 400);
        }

        $aliasOf = $channel->getAliasOf();

        if ($aliasOf && $alias) {
            // Is an alias channel, switch direct to the original channel
            return self::byId($aliasOf);
        }

        $model = $channel->getModelClass();
        return new $model($channel);
    }

    /**
     * Helper function to build an instance
     */
    public static function byGUID($guid, $alias=true)
    {
        if ($guid == '') {
            throw new \Exception('Missing channel GUID!', 400);
        }

        $channel = new \ORM\Tree;
        $channel->filter('guid', array('like' => $guid.'%'))->findOne();
        $aliasOf = $channel->getAliasOf();

        if ($aliasOf && $alias) {
            // Is an alias channel, switch direct to the original channel
            return self::byId($aliasOf);
        } elseif ($channel->getModel()) {
            // Channel is in tree
            $model = $channel->getModelClass();
            return new $model($channel);
        } else {
            // NOT in tree, may be a real writable channel?! "Fake" a tree entry
            $c = new \ORM\ChannelView;
            $c->filterByGuid($guid)->findOne();
            if ($c->getId() && $c->getWrite()) {
                $data = $c->asAssoc();
                $data['id'] = 0;
                $data['entity'] = $c->getId();
                $channel->set($data);
                $model = $c->getModelClass();
                return new $model($channel);
            }
        }

        throw new \Exception('No channel found for GUID: '.$guid, 404);
    }

    /**
     * Helper function to build an instance
     */
    public static function byChannel($id, $alias=true)
    {
        $channel = new \ORM\ChannelView($id);

        if ($channel->getGuid()) {
            return self::byGUID($channel->getGuid(), $alias);
        }

        throw new \Exception('No channel found for ID: '.$id, 400);
    }

    /**
     * Run additional code before a new channel is presented to the user
     */
    public static function beforeCreate(Array &$fields)
    {
    }

    /**
     * Run additional code before existing data presented to user
     */
    public static function beforeEdit(\ORM\Channel $channel, Array &$fields)
    {
    }

    /**
     * Run additional code after attributes was maintained by user
     *
     * @param $add2tree integer|null
     */
    public static function checkData(Array &$fields, $add2tree)
    {
        $ok = true;

        foreach ($fields as $name=>&$data) {
            // Don't check invisible fields
            if (!$data['VISIBLE']) continue;

            $data['VALUE'] = trim($data['VALUE']);

            if ($data['VALUE'] == '') {
                // Check required fields
                if ($data['REQUIRED']) {
                    $data['ERROR'][] = __('channel::ParamIsRequired');
                    $ok = false;
                }
                // No further checks for empty fields required
                continue;
            }

            // Check numeric fields
            switch ($data['TYPE']) {
                case 'numeric':
                    if (!is_numeric($data['VALUE'])) {
                        $data['ERROR'][] = __('channel::ParamMustNumeric');
                        $ok = false;
                    }
                    break;
                case 'integer':
                    if ((string) floor($data['VALUE']) != $data['VALUE']) {
                        $data['ERROR'][] = __('channel::ParamMustInteger');
                        $ok = false;
                    }
                    break;
            } // switch
        }

        return $ok;
    }

    /**
     * Run additional code before data saved to database
     */
    public static function beforeSave(Array&$fields, \ORM\Channel $channel)
    {
        foreach ($fields as $name=>$data) {
            $channel->set($name, $data['VALUE']);
        }
    }

    /**
     * Run additional code before channel will be added to hierarchy
     * Return false to skip!
     */
    public static function beforeAdd2Tree($parent)
    {
        return true;
    }

    /**
     * Run additional code after channel was created / changed
     * If $tree is set, channel was just created
     */
    public static function afterSave(\ORM\Channel $channel, $tree=null)
    {
        $ORMReadingCalculated = new \ORM\ReadingCalculated;
        $ORMReadingCalculated->filterById($channel->id)->delete();
    }

    /**
     *
     */
    public function addChild($channel)
    {
        $childs = $this->getChilds(true);

        // Root node (id == 1) accept always childs
        if ($this->id == 1 || $this->childs == -1 || count($childs) < $this->childs) {
            $c = new \ORM\ChannelView($channel);
            $model = $c->getModelClass();
            if ($model::beforeAdd2Tree($this) !== false) {
                return NestedSet::getInstance()->insertChildNode($channel, $this->id);
            }
        } else {
            Messages::Error(__('AcceptChild', $this->childs, $this->name), 400);
        }
        return false;
    }

    /**
     *
     */
    public function removeFromTree()
    {
        return NestedSet::getInstance()->DeleteBranch($this->id);
    }

    /**
     * Capture not defined attributes
     */
    public function __get($attribute)
    {
        throw new \Exception('Unknown attribute: '.$attribute, 400);
    }

    /**
     *
     */
    public function getAttributes($attribute=null)
    {
        if ($attribute != '') {
            // Accept attribute name 'factor' for resolution
            // Here WITHOUT check, will be handled by __get()
            return array(
                $attribute => $attribute == 'factor' ? $this->resolution : $this->$attribute
            );
        } else {
            return array_merge(
                $this->getAttributesShort(),
                array(
                    'start'       => $this->start,
                    'end'         => $this->end,
                    'consumption' => 0,
                    'costs'       => 0
                ),
                $this->attributes,
                array(
                    'datetime_start'=> $this->start ? date('Y-m-d H:i:s', $this->start) : '',
                    'datetime_end'  => $this->end   ? date('Y-m-d H:i:s', $this->end)   : ''
                )
            );
        }
    }

    /**
     *
     */
    public function getAttributesShort()
    {
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
    public function write($request, $timestamp=null)
    {
        // Default behavior
        $reading = \ORM\Reading::factory($this->numeric);

        $this->lastReading = \ORM\ReadingLast::f($this->entity)->getData();
#        $this->lastReading = $reading->getLastReading($this->entity, $timestamp);

        $this->before_write($request);

        if ($this->numeric) {
            // Check that new value is inside the valid range

            if ((!is_null($this->valid_from) && $this->value < $this->valid_from) ||
                (!is_null($this->valid_to)   && $this->value > $this->valid_to)) {

                $msg = sprintf('Value %1$s is outside of valid range (%2$s <= %1$f <= %3$s)',
                               $this->value, $this->valid_from, $this->valid_to);

                $cfg = new \ORM\Config('LogInvalid');

                if ($cfg->value != 0) \ORM\Log::save($this->name, $msg);

                throw new \Exception($msg, 200);
            }

            // Check that new reading value is inside the threshold range,
            // except 1st reading at all ($this->lastreading == null)
            if ($this->threshold > 0 && !is_null($this->lastReading) &&
                abs($this->value-$this->lastReading) > $this->threshold) {
                // Throw away invalid reading value
                throw new \Exception('Ignore invalid reading value: '.$this->value, 200);
            }

            // Check that new meter reading value can't be lower than before
            if ($this->meter && $this->lastReading && $this->value < $this->lastReading) {
                $this->value = $this->lastReading;
            }
        }

        // Write performance only for "real" savings if the program flow
        // came to here and not returned earlier
        $this->performance->setAction('write');

        $rc = $reading->setId($this->entity)->setTimestamp($timestamp)->setData($this->value)->insert();

        if ($rc == 0 && $timestamp > time()) {
            $rc = $this->update($request, $timestamp);
        }

        if ($rc) Hook::process('data.save.after', $this);

        return $rc;
    }

    /**
     *
     */
    public function update($request, $timestamp)
    {
        // Default behavior
        $reading = \ORM\Reading::factory($this->numeric);

        $this->lastReading = \ORM\ReadingLast::f($this->entity)->getData();

        $this->check_before_write($request, $timestamp);

        if ($this->numeric) {
            // Check that new value is inside the valid range
            if ((!is_null($this->valid_from) && $this->value < $this->valid_from) ||
                (!is_null($this->valid_to)   && $this->value > $this->valid_to)) {

                $msg = sprintf('Value %1$s is outside of valid range (%2$s <= %1$f <= %3$s)',
                               $this->value, $this->valid_from, $this->valid_to);

                $cfg = new \ORM\Config('LogInvalid');

                if ($cfg->value != 0) \ORM\Log::save($this->name, $msg);

                throw new \Exception($msg, 200);
            }

            // Check that new reading value is inside the threshold range,
            // except 1st reading at all ($this->lastreading == null)
            if ($this->threshold > 0 && !is_null($this->lastReading) &&
                abs($this->value-$this->lastReading) > $this->threshold) {
                // Throw away invalid reading value
                return 0;
            }

            // Check that new meter reading value can't be lower than before
            if ($this->meter && $this->lastReading && $this->value < $this->lastReading) {
                return 0;
            }
        }

        // Write performance only for "real" savings if the program flow
        // can to here and not returned earlier
        $this->performance->setAction('update');

        $reading->filterByIdTimestamp($this->entity, $timestamp)->findOne();
        $rc = $reading->getId() ? $reading->setData($this->value)->update() : 0;
/*
        if ($rc) {
            // Log successful updates only
            $msg = isset($this->lastReading)
                 ? sprintf('%s: %f > %f', date('Y-m-d H:i:s', $timestamp), $this->lastReading, $request['data'])
                 : sprintf('%s: %f', date('Y-m-d H:i:s', $timestamp), $request['data']);
            \ORM\Log::save($this->name, $msg);
        }
*/
        if ($rc) Hook::process('data.update.after', $this);

        return $rc;
    }

    /**
     *
     */
    public function read($request)
    {
        $this->performance->setAction('read');

        $this->before_read($request);

        $buffer = new Buffer;

        if ($this->period[1] == self::READLAST ||
            (!$this->meter && $this->period[1] == self::LAST)) {

            $q = $this->read_LastRow();

            // Write without Id so calculaters afterwards will find all
            if ($row = $this->db->queryRowArray($q)) {
                $buffer->write($row);
            }

        } elseif ($this->meter && !$this->childs && $this->period[1] == self::LAST) {

            $q = $this->read_LastMeter();

            // Write without Id so calculaters afterwards will find all
            if ($row = $this->db->queryRowArray($q)) {
                $buffer->write($row);
            }

        } else {

            $q = DBQuery::forge($this->table[$this->numeric]);

            if ($this->period[1] == self::LAST || $this->period[1] == self::ALL) {

                // Select plain data
                $q->get($q->FROM_UNIXTIME('timestamp'), 'datetime')
                  ->get('timestamp')
                  ->get('data')
                  ->get('data', 'min')
                  ->get('data', 'max')
                  ->get(1, 'count')
                  ->get(0, 'timediff')
                  ->get(0, 'consumption');

            } else {

                // Select data grouped by period
                $timestamp = $this->groupTimestampByPeriod();

                $q->get($q->FROM_UNIXTIME($timestamp), 'datetime')
                  ->get($timestamp, 'timestamp');

                switch (true) {
                    // Raw data for non-numeric channels
                    case !$this->numeric:
                        $q->get('data');
                        break;
                    // Max./Min. value for meters
                    case $this->meter:
                        $d = ($this->resolution > 0) ? $q->MAX('data') : $q->MIN('data');
                        $q->get($d, 'data');
                        break;
                    // Summarize counter ticks
                    case $this->counter:
                        $q->get($q->SUM('data'), 'data');
                        break;
                    // Average value of sensors/proxies
                    default:
                        $q->get($q->AVG('data'), 'data');
                } // switch

                $q->get($q->MIN('data'), 'min')
                  ->get($q->MAX('data'), 'max')
                  ->get($q->COUNT('id'), 'count')
                  ->get($q->MAX('timestamp').'-'.$q->MIN('timestamp'), 'timediff')
                  ->get(0, 'consumption')
                  ->group($timestamp);
            }

            $q->filter('id', $this->entity)->order('timestamp');

            $this->filterReadTimestamp($q);

            // Use bufferd result set
            $this->db->setBuffered();

            if ($res = $this->db->query($q)) {

                if ($this->meter) {
                    // Fetch 1st row for reference
                    if ($row = $res->fetch_assoc()) {
                        // Remember offset for further readings
                        $offset = $row['data'];

                        // If 1st reading is inside the selected period,
                        // store a zero value at 1st position!
                        if ($row['timestamp'] >= $this->start) {
                            $row['data'] = 0;
                            $row['consumption'] = 0;
                            $buffer->write($row, $row['timestamp']);
                        }

                        // Read remaining rows
                        $last = 0;
                        while ($row = $res->fetch_assoc()) {
                            // Correcvt reading values by offset
                            $row['data'] -= $offset;
                            $row['min']  -= $offset;
                            $row['max']  -= $offset;

                            // calc consumption from previous max value
                            $row['consumption'] = $row['data'] - $last;
                            $last = $row['data'];

                            $buffer->write($row, $row['timestamp']);
                        }
                    }
                } else {
                    // Use all readings as is
                    while ($row = $res->fetch_assoc()) {
                        $buffer->write($row, $row['timestamp']);
                    }
                }

                // Don't forget to close for buffered results!
                $res->close();
            }

            $this->db->setBuffered(false);
        }

        $this->SQLHeader($request, $q);

        return $this->after_read($buffer);
    }

    /**
     *
     */
    public function getTag($tag)
    {
        $tag = strtolower($tag);
        return array_key_exists($tag, $this->_tags)
             ? $this->_tags[$tag]
             : null;
    }

    /**
     *
     */
    public static function calcStartEnd(&$request)
    {
        // Prepare analysis of request
        $request = array_merge(
            array('start' => '', 'days' => null, 'end' => ''),
            $request
        );

        // Start timestamp
        if ($request['start'] == '') {
            $request['start'] = 'midnight';
        } elseif (preg_match('~^-(\d+)$~', $request['start'], $args)) {
            // Start ? days backwards
            $request['start'] = 'midnight -'.$args[1].'days';
        } elseif (preg_match('~^sunrise(?:[;-](\d+))*~', $request['start'], $args)) {
            $request['start'] = \ORM\Settings::getSunrise();
            if (isset($args[1])) $request['start'] -= $args[1]*60;
        }

        $start = is_numeric($request['start']) ? $request['start'] : strtotime($request['start']);
        if ($start === false) {
            throw new \Exception('Invalid start timestamp: '.$request['start'], 400);
        }
        $request['start'] = $start;

        // 1st days count ...
        if (is_numeric($request['days'])) {
            $request['end'] = $this->start + $request['days']*86400;
        } else
        // ... 2nd end timestamp
        if ($request['end'] == '') {
            $request['end'] = 'midnight next day';
        } elseif (preg_match('~^-(\d+)$~', $request['end'], $args)) {
            $request['end'] = 'midnight -'.$args[1].'days';
        } elseif (preg_match('~^sunset(?:[;+](\d+))*~', $request['end'], $args)) {
            $request['end'] = \ORM\Settings::getSunset();
            if (isset($args[1])) $request['end'] += $args[1]*60;
        }

        $end = is_numeric($request['end']) ? $request['end'] : strtotime($request['end']);
        if ($end === false) {
            throw new \Exception('Invalid end timestamp: '.$request['end'], 400);
        }
        $request['end'] = $end;
    }

    /**
     *
     */
    public function __destruct()
    {
        $time = (microtime(true) - $this->time) * 1000;

        if (!headers_sent()) Header(sprintf('X-Query-Time: %d ms', $time));

        // Check for real action to log
        if ($this->performance->getAction()) {
            $this->performance->setTime($time)->insert();
        }
    }

    // -------------------------------------------------------------------------
    // PROTECTED
    // -------------------------------------------------------------------------

    /**
     * Grouping
     */
    const NO        =  0;
    const SECOND    = 10;
    const MINUTE    = 20;
    const HOUR      = 30;
    const DAY       = 40;
    const WEEK      = 50;
    const MONTH     = 60;
    const QUARTER   = 61;
    const YEAR      = 70;
    const LAST      = 80;
    const READLAST  = 81;
    const ALL       = 99;

    /**
     * Period in seconds for each grouping period and SQL equivalent
     * Hold static only once in memory
     */
    protected static $secondsPerPeriod = array(
        self::NO        =>        1,
        self::SECOND    =>        1,
        self::MINUTE    =>       60,
        self::HOUR      =>     3600,
        self::DAY       =>    86400,
        self::WEEK      =>   604800,
        self::MONTH     =>  2678400,
        self::QUARTER   =>  7776000,
        self::YEAR      => 31536000,
        self::LAST      =>        1,
        self::READLAST  =>        1,
        self::ALL       =>        1
    );

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
    protected $db;

    /**
     *
     */
    protected $config;

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
     *
     */
    protected $_tags = array();

    /**
     *
     */
    protected function __construct(\ORM\Tree $channel)
    {
        $this->time   = microtime(true);
        $this->db     = $channel::getDatabase();

        foreach ($channel->asAssoc() as $key=>$value) {
            $this->$key = $value;
        }

        foreach (explode("\n", $this->tags) as $tag) {
            list($scope, $value) = explode(':', $tag.':');
            $scope = preg_replace('~\s+~', ' ', trim($scope));
            if ($scope) $this->_tags[strtolower($scope)] = trim($value);
        }

        $this->performance = new \ORM\Performance;
    }

    /**
     *
     */
    protected function groupTimestampByPeriod()
    {
        return $this->period[0] * self::$secondsPerPeriod[$this->period[1]] == 1
             ? '`timestamp`'
             : sprintf(
                   '`timestamp` DIV (%1$d * %2$d) * %1$d * %2$d',
                   $this->period[0],
                   self::$secondsPerPeriod[$this->period[1]]
               );
    }

    /**
     * Lazy load childs on request
     */
    protected function getChilds($refresh=false)
    {
        if ($refresh || is_null($this->_childs)) {
            $this->_childs = array();
            foreach (NestedSet::getInstance()->getChilds($this->id) as $child) {
                $child = self::byID($child['id']);
                $child->isChild = true;
                $this->_childs[] = $child;
            }
        }
        return $this->_childs;
    }

    /**
     * Lazy load child on request, 1 based!
     */
    protected function getChild($id)
    {
        $this->getChilds();
        return ($_=&$this->_childs[$id-1]) ?: false;
    }

    /**
     * Essential checks before write data
     */
    protected function check_before_write(&$request)
    {
        if (!$this->write) {
            throw new \Exception(
                'Can\'t write data to '.$this->name.', instance of '.get_class($this),
                400
            );
        }

        if (!isset($request['data']) || !is_scalar($request['data'])) {
            throw new \Exception($this->guid.' - Missing data value', 400);
        }

        // Check if a WRITEMAP::{...} exists to rewrite e.g. from numeric to non-numeric
        if (preg_match('~^WRITEMAP::(.*?)$~m', $this->tags, $args) &&
            ($map = json_decode($args[1], true))) {
            $request['data'] = $this->array_value($map, $request['data'], 'unknown ('.$request['data'].')');
        } elseif (preg_match('~^WRITEMAP::(.*?)$~m', $this->comment, $args) &&
            ($map = json_decode($args[1], true))) {
            $request['data'] = $this->array_value($map, $request['data'], 'unknown ('.$request['data'].')');
        }

        $this->value = $request['data'];
    }

    /**
     *
     */
    protected function before_write(&$request)
    {
        $this->check_before_write($request);

        Hook::process('data.save.before', $this);

        if ($this->numeric) {
            // Remove all non-numeric characters
            $this->value = preg_replace('~[^0-9.eE-]~', '', $this->value);

            // Interpret empty numeric value as invalid and ignore them
            if ($this->value == '') throw new \Exception(null, 200);

            $this->value = +$this->value;

            if ($this->meter) {
                if ($this->value == 0) {
                    throw new \Exception('Invalid meter reading: 0', 422);
                }

                if ($this->meter &&
                    $this->value + $this->offset < $this->lastReading &&
                    $this->adjust) {
                    // Auto-adjust channel offset
                    \ORM\Log::save(
                        $this->name,
                        sprintf("Adjust offset\nLast offset: %f\nLast reading: %f\nValue: %f",
                                $this->offset, $this->lastReading, $this->value)
                    );

                    // Update channel in database
                    $t = new \ORM\Channel($this->entity);
                    $t->offset = $this->lastReading;
                    $t->update();

                    $this->offset = $this->lastReading;
                }
            }

            // MUST also work for sensor channels
            // Apply offset
            $this->value += $this->offset;

            if ($this->meter && $this->value == $this->lastReading) {
                throw new \Exception('Ignore meter values which are equal last reading', 200);
            }
        }
    }

    /**
     *
     */
    protected function before_read(&$request)
    {
        // Readable channel?
        if (!$this->read)
            throw new \Exception('Can\'t read data from '.$this->name.', '
                                .'instance of '.get_class($this), 400);

        // Required number of child channels?
        if ($this->childs >= 0 && count($this->getChilds()) != $this->childs)
            throw new \Exception($this->name.' MUST have '.$this->childs.' child(s)', 400);

        self::calcStartEnd($request);

        $this->start = $request['start'];
        $this->end   = $request['end'];

        $request = array_merge(array('period' => ''), $request);

        // Normalize aggregation period
        if (preg_match(
                '~^([.\d]*)(|l|last|r|readlast|'.
                's|sec|seconds?|i|min|minutes?|h|hours?|'.
                'd|days?|w|weeks?|m|months?|q|quarters?|y|years|a|all)$~',
                strtolower($request['period']),
                $args
            )) {

            $this->period[0] = $args[1] ?: 1;

            switch (substr($args[2], 0, 2)) {
                default:              $this->period[1] = self::NO;       break;
                case 's': case 'se':  $this->period[1] = self::SECOND;   break;
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

        // Childs without proper "grouping" can't calculated by parent channels
        if ($this->isChild) {
            self::$secondsPerPeriod[self::NO] = 10;
        }
    }

    /**
     *
     */
    protected function after_read(\Buffer $buffer)
    {
        $datafile = new Buffer;

        $last = 0;
        $lastrow = false;
        $checkMeter = ($this->meter && count($buffer) > 1);

        foreach ($buffer as $id=>$row) {

            // Filter out data produced by meter channels with large periods
            if ($this->meter &&
                ($row['timestamp'] < $this->start || $row['timestamp'] > $this->end)) {
                continue;
            }

            if ($checkMeter) {
                /* check meter values raising */
                if ($this->resolution > 0 && $row['data'] < $last) {
                    $row['data'] = $last;
                }
                $last = $row['data'];
            }

            if ($this->numeric && $this->resolution != 1) {
                $row['data']        *= $this->resolution;
                $row['min']         *= $this->resolution;
                $row['max']         *= $this->resolution;
                $row['consumption'] *= $this->resolution;
            }

            if ($this->numeric) {
                // Skip invalid (numeric) rows
                // Apply valid_from and valid_to here only
                // - channel is NOT writable, this will be handled during write()
                // - NOT read as child channel
                if ($this->write || $this->isChild ||
                    ((is_null($this->valid_from) || $row['data'] >= $this->valid_from) &&
                     (is_null($this->valid_to)   || $row['data'] <= $this->valid_to))) {

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

        if ($this->period[1] == self::LAST && $lastrow) {
            $datafile = new \Buffer;
            $datafile->write($lastrow);
        }

        return $datafile;
    }

    /**
     * Time is only relevant for period != ALL
     */
    protected function filterReadTimestamp(&$q)
    {
        if ($this->period[1] == self::ALL) return;

        // Read one period before real start for meter calculations
        $start = $this->meter
               ? $this->start - $this->period[0] * self::$secondsPerPeriod[$this->period[1]]
               : $this->start;

        // End is midnight > minus 1 second
        $q->filter('timestamp', array('bt' => array($start, $this->end-1)));
    }

    /**
     *
     */
    protected function SQLHeader($request, $q)
    {
        if (headers_sent() || !array_key_exists('sql', $request) || !$request['sql']) return;

        $sql = 'X-SQL-' . substr(md5($q), -7) . ': ' . $this->name;
        if ($this->description) $sql .= ' (' . $this->description . ')';
        Header($sql . ': ' . preg_replace('~\n+~', ' ', $q));
    }

    /**
     * Shortcut method for save array access
     */
    protected function array_value(array $array, $key, $default=null)
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }

    // -------------------------------------------------------------------------
    // PRIVATE
    // -------------------------------------------------------------------------

    /**
     *
     */
    private $_childs;

    /**
     * Read last row
     */
    private function read_LastRow()
    {
        $q = $this->write && !$this->childs
             // Use special table for last readings for real channels
           ? DBQuery::forge('pvlng_reading_last')
           : DBQuery::forge($this->table[$this->numeric]);

        // Fetch last reading and set some data to 0 to get correct field order
        return $q->get($q->FROM_UNIXTIME('timestamp'), 'datetime')
          ->get('timestamp')
          ->get('data')
          ->get(0, 'min')
          ->get(0, 'max')
          ->get(0, 'count')
          ->get(0, 'timediff')
          ->get(0, 'consumption')
          ->filter('id', $this->entity)
          ->orderDescending('timestamp')
          ->limit(1);
    }

    /**
     * Read last value for meter channels
     * Read direct the difference of the max. and min. values
     */
    private function read_LastMeter()
    {
        $q = DBQuery::forge($this->table[$this->numeric]);

        $value = $q->MAX('data') . ' - ' . $q->MIN('data');

        $q->get($q->FROM_UNIXTIME($q->MAX('timestamp')), 'datetime')
          ->get($q->MAX('timestamp'), 'timestamp')
          ->get($value, 'data')
          ->get(0, 'min')
          ->get($value, 'max')
          ->get(1, 'count')
          ->get($q->MAX('timestamp').' - '.$q->MIN('timestamp'), 'timediff')
          ->get($value, 'consumption')
          ->filter('id', $this->entity);

        $this->filterReadTimestamp($q);

        return $q;
    }

}
