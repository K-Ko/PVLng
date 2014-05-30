<?php
/**
 *
 *
 * @author     Knut Kohl <github@knutkohl.de>
 * @copyright  2012-2014 Knut Kohl
 * @license    MIT License (MIT) http://opensource.org/licenses/MIT
 * @version    1.0.0
 */
namespace Channel;

/**
 *
 */
class History extends InternalCalc {

    /**
     *
     */
    public static function checkData( Array &$fields, $add2tree ) {
        $ok = parent::checkData($fields, $add2tree);

        if ($fields['valid_from']['VALUE'] >= 0) {
            $fields['valid_from']['ERROR'][] = __('Must be lower than 0');
            $ok = FALSE;
        }
        if ($fields['valid_to']['VALUE'] < 0) {
            $fields['valid_to']['ERROR'][] = __('Must be greater or equal 0');
            $ok = FALSE;
        }

        return $ok;
    }

    /**
     *
     */
    public function read( $request ) {

        $this->before_read($request);

        $child = $this->getChild(1);

        $result = new \Buffer;

        if (!$child) return $this->after_read($result);

        // Inherit properties from child
        $this->meter = $child->meter;

        // Fetch all data, compress later
        unset($request['period']);

        if ($this->valid_to == 0) {
            // Last x days, move request start backwards
            $request['start'] = $this->start + $this->valid_from * 86400;
            // Save data into temp. table
            $this->saveValues($child->read($request));
        } else {
            // Find earliest data
            $q = new \DBQuery('pvlng_reading_num');
            $q->get($q->FROM_UNIXTIME($q->MIN('timestamp'), '"%Y"'));

            for ($i=$this->db->queryOne($q)-date('Y'); $i<=0; $i++) {
                $request['start'] = strtotime(date('Y-m-d ', $this->start + $this->valid_from * 86400).$i.'years');
                $request['end']   = strtotime(date('Y-m-d ', $this->end + $this->valid_to * 86400).$i.'years');
                // Save data into temp. table
                $this->saveValues($child->read($request));
            }
        }

        if ($this->period[0] * $this->TimestampMeterOffset[$this->period[1]] < 600) {
            // Smooth result at least 10 minutes
            $this->period = array(10, self::MINUTE);
        } elseif ($this->threshold AND $this->period[1] == self::MINUTE) {
            // Smooth result by cut period by "threshold", only for minutes
            $this->period[0] *= $this->threshold;
        }

        $q = new \DBQuery('pvlng_reading_num_tmp');
        $q->get($q->FROM_UNIXTIME('timestamp', '"%H"'), 'hour')
          ->get($q->FROM_UNIXTIME('timestamp', '"%i"'), 'minute');

        if ($this->meter) {
            $q->get($q->MAX('data'), 'data');
        } elseif ($this->counter) {
            $q->get($q->SUM('data'), 'data');
        } else {

            switch (\slimMVC\Config::getInstance()->get('Model.History.Average')) {
                default:
                    // Linear average
                    $q->get($q->AVG('data'), 'data');
                    break;
                case 1:
                    // harm. avg.: count(val) / sum(1/val)
                    $q->get($q->COUNT('data').'/'.$q->SUM('1/`data`'), 'data');
                    break;
                case 2:
                    // geom. avg.: exp(avg(ln(val)))
                    $q->get($q->EXP($q->AVG($q->LN('data'))), 'data');
                    break;
            }
        }

        $q->get($q->MIN('data'), 'min')
          ->get($q->MAX('data'), 'max')
          ->get($q->COUNT(0), 'count')
          ->get($this->periodGrouping(), 'g')
          ->filter('id', $this->entity)
          ->groupBy('g');
        $inner = $q->SQL();
        $q->select('('.$inner.') t')
          ->groupBy('hour')
          ->groupBy('minute');

#echo $q;

        $day   = date('d', ($this->start+$this->end)/2);
        $month = date('m', ($this->start+$this->end)/2);
        $year  = date('Y', ($this->start+$this->end)/2);

        if ($res = $this->db->query($q)) {
            while ($row = $res->fetch_object()) {
                $ts = mktime($row->hour, $row->minute, 0, $month, $day, $year);
                $result->write(array(
                    'datetime'    => date('Y-m-d H:i:s', $ts),
                    'timestamp'   => $ts,
                    'data'        => +$row->data,
                    'min'         => +$row->min,
                    'max'         => +$row->max,
                    'count'       => +$row->count,
                    'timediff'    => 0,
                    'consumption' => 0
                ), $row->g);
            }
        }

        // Skip validity handling of after_read!
        $this->valid_from = $this->valid_to = NULL;

        return $this->after_read($result);
    }

}
