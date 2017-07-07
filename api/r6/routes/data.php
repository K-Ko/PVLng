<?php
/**
 * PVLng - PhotoVoltaic Logger new generation
 *
 * @link       https://github.com/KKoPV/PVLng
 * @link       https://pvlng.com/
 * @author     Knut Kohl <github@knutkohl.de>
 * @copyright  2012 Knut Kohl
 * @license    MIT License (MIT) http://opensource.org/licenses/MIT
 */

/**
 *
 */
$api->put(
    '/data/:guid',
    $APIkeyRequired,
    function ($guid) use ($api) {
        $request = json_decode($api->request->getBody(), true);

        // Check request for 'timestamp' attribute, take as is if numeric,
        // otherwise convert datetime to timestamp
    //        $timestamp = isset($request['timestamp'])
    //                   ? ( is_numeric($request['timestamp'])
    //                     ? $request['timestamp']
    //                     : strtotime($request['timestamp'])
    //                     )
    //                   : NULL;

        try {
            $cnt = Channel\Channel::byGUID($guid)->write($request);
        } catch (Exception $e) {
            $api->stopAPI($e->getMessage(), $e->getCode() ?: 400);
        }

        if ($cnt) {
            $api->stopAPI($cnt.' reading(s) added', 201);
        }
    }
)
->name('PUT /data/:guid')
->help = array(
    'since'       => 'r2',
    'description' => 'Save a reading value',
    'apikey'      => true,
    'payload'     => array(
        '{"data":"<value>"}'                           => 'JSON encoded value, use server time',
        '{"data":"<value>","timestamp":"<timestamp>"}' => 'JSON encoded value, use provided timestamp',
        '{"data":"<value>","timestamp":"<date time>"}' => 'JSON encoded value, use provided date and time'
    ),
);

/**
 *
 */
$api->post(
    '/data/:guid',
    $APIkeyRequired,
    function ($guid) use ($api) {
        $request = json_decode($api->request->getBody(), true);
        // Check request for 'data' attribute
        if (!isset($request['data'])) {
            $api->stopAPI('Data required for data update', 400);
        }
        // Check request for 'timestamp' attribute, take as is if numeric,
        // otherwise convert datetime to timestamp
        $timestamp = isset($request['timestamp'])
               ? ( is_numeric($request['timestamp'])
                 ? $request['timestamp']
                 : strtotime($request['timestamp'])
                 )
               : false;
        if (!$timestamp) {
            $api->stopAPI('Timestamp required for data update', 400);
        }
        if (!Channel\Channel::byGUID($guid)->update($request, $timestamp)) {
            $api->stopAPI('Invalid data', 405);
        }
    }
)
->name('POST /data/:guid')
->help = array(
    'since'       => 'r4',
    'description' => 'Update a reading value, timestamp is required here',
    'apikey'      => true,
    'payload'     => array('{"data":"<value>","timestamp":"<timestamp>"}' => 'JSON encoded value'),
);

/**
 *
 */
$api->put(
    '/data/raw/:guid',
    $APIkeyRequired,
    function ($guid) use ($api) {
        // Channel handles raw data
        $cnt = Channel\Channel::byGUID($guid)->write($api->request->getBody());
        if ($cnt) {
            $api->stopAPI($cnt.' reading(s) added', 201);
        }
    }
)
->name('PUT /data/raw/:guid')
->help = array(
    'since'       => 'r4',
    'description' => 'Save raw data, channel decide what to do with them',
    'apikey'      => true,
    'payload'     => array('raw data in any format' => 'Channel have to handle it'),
);

/**
 *
 */
$api->get(
    '/data/:period/:guid',
    $accessibleChannel,
    function ($period, $guid) use ($api) {
        $request = $api->request->get();

        if ($period == 'monthly') {
            if (empty($request['period'])) {
                $request['period'] = 'last';
            }

            Channel\Channel::calcStartEnd($request);

            // Remember originals
            $start   = $request['start'];
            $end     = $request['end'];

            $_result = new Buffer;
            $first   = true;
            $attr    = isset($request['attributes']) && $request['attributes'];
            $cons    = 0;

            while ($request['start'] < $end) {
                list($y, $m) = explode('-', date('Y-m', $request['start']));
                $request['start'] = strtotime($y . '-' . $m . '-1');

                if ($m < 12) {
                    $m++;
                } else {
                    $y++;
                    $m = 1;
                }
                $request['end'] = strtotime($y . '-' . $m . '-1') - 1;

                $res = $api->readData($guid, $request);
                $id = 0;

                foreach ($res as $row) {
                    if (($id == 0) && $attr) {
                        $cons += $row['consumption'];
                    }
                    if (($id > 0) || ($first && $attr)) {
                        if ($id > 0) {
                            $row['timestamp'] = $request['start'];
                            $row['datetime']  = date('Y-m-d H:i:s', $request['start']);
                        }
                        $_result->write($row);
                    }

                    $id++;
                }

                $res->close();

                // Move to next month
                $request['start'] = $request['end'] + 1;

                $first = false;
            }

            if ($attr) {
                $result = new Buffer;
                $id = 0;

                foreach ($_result as $row) {
                    if ($id++ == 0) {
                        // Adjust attributes row
                        $row['consumption'] = $cons;
                        $row['start'] = $start;
                        $row['end']   = $end;
                        $row['datetime_start'] = date('Y-m-d H:i:s', $start);
                        $row['datetime_end']   = date('Y-m-d H:i:s', $end);
                    }
                    $result->write($row);
                }
                $_result->close();
            } else {
                $result = $_result;
            }
        } else {
            $request['period'] = $period;
            $result = $api->readData($guid, $request);
        }

        $api->response->headers->set('X-Data-Rows', count($result));
        if (is_object($result) && method_exists($result, 'size')) {
            $api->response->headers->set('X-Data-Size', $result->size() . ' Bytes');
        }

        $api->render($result);
    }
)
->name('GET /data/:period/:guid')
->help = array(
    'since'       => 'r6',
    'description' => 'Read reading values for special periods',
    'parameters'  => array(
        'attributes' => array(
            'description' => 'Return channel attributes as 1st line',
            'value'       => array( 1, 'true' ),
        ),
        'full' => array(
            'description' => 'Return all data, not only timestamp and value',
            'value'       => array( 1, 'true' ),
        ),
        'short' => array(
            'description' => 'Return data as array, not object',
            'value'       => array( 1, 'true' ),
        ),
    ),
);

/**
 *
 */
$api->get(
    '/data/:guid(/:p1(/:p2))',
    $accessibleChannel,
    function ($guid, $p1 = '', $p2 = '') use ($api) {
        $request = $api->request->get();
        $request['p1'] = $p1;
        $request['p2'] = $p2;

        $result = $api->readData($guid, $request);

        if (is_string($result)) {
            $array = json_decode($result, true);
            if ($array && json_last_error() == JSON_ERROR_NONE) {
                $result = $array;
            }
        }

        $api->response->headers->set('X-Data-Rows', count($result));
        if (is_object($result) && method_exists($result, 'size')) {
            $api->response->headers->set('X-Data-Size', $result->size() . ' Bytes');
        }
        $api->render($result);
    }
)
->name('GET /data/:guid(/:p1(/:p2))')
->help = array(
    'since'       => 'r2',
    'description' => 'Read reading values',
    'parameters'  => array(
        'start' => array(
            'description' => 'Start timestamp for readout, default today 00:00',
            'value'       => array(
                'YYYY-mm-dd HH:ii:ss',
                'seconds since 1970',
                'relative from now, see http://php.net/manual/en/datetime.formats.relative.php',
                'sunrise - needs location in config/config.php'
            ),
        ),
        'end' => array(
            'description' => 'End timestamp for readout, default today midnight',
            'value'       => array(
                'YYYY-mm-dd HH:ii:ss',
                'seconds since 1970',
                'relative from now, see http://php.net/manual/en/datetime.formats.relative.php',
                'sunset - needs location in config/config.php'
            ),
        ),
        'period' => array(
            'description' => 'Aggregation period, default none',
            'value'       => array( '[0-9.]+minutes', '[0-9.]+hours',
                                    '[0-9.]+days',  '[0-9.]+weeks',
                                    '[0-9.]+month', '[0-9.]+quarters',
                                    '[0-9.]+years', 'last', 'readlast', 'all' ),
        ),
        'attributes' => array(
            'description' => 'Return channel attributes as 1st line',
            'value'       => array( 1, 'true' ),
        ),
        'full' => array(
            'description' => 'Return all data, not only timestamp and value',
            'value'       => array( 1, 'true' ),
        ),
        'short' => array(
            'description' => 'Return data as array, not object',
            'value'       => array( 1, 'true' ),
        ),
    ),
);

/**
 *
 */
$api->get(
    '/data/actual/:guid+',
    function ($guid) use ($api) {
        $guids = $guid;
        $request = $api->request->get();
        $request['period'] = 'last';

        $result = array();
        foreach ($guids as $guid) {
            try {
                $channel = Channel\Channel::byGUID($guid);
            } catch (Exception $e) {
                $api->stopAPI($e->getMessage(), 404);
            }

                $request['start'] = $channel->meter ? 'midnight' : '-10min';

                // Loop the result to get the last
                $row = null;
            foreach ($api->readData($guid, $request) as $row) {
            }

                $name = $channel->name;
            if ($channel->description) {
                $name .= ' ('.$channel->description.')';
            }
                $result[Core\Slug::encode($name)] = $row;
        }

        $api->render($result);
    }
)
->name('GET /data/actual/:guid+')
->help = array(
    'since'       => 'r6',
    'description' => 'Read reading values for many GUIDs',
    'parameters'  => array(
        'attributes' => array(
            'description' => 'Return channel attributes as 1st line',
            'value'       => array( 1, 'true' ),
        ),
        'full' => array(
            'description' => 'Return all data, not only timestamp and value',
            'value'       => array( 1, 'true' ),
        ),
        'short' => array(
            'description' => 'Return data as array, not object',
            'value'       => array( 1, 'true' ),
        ),
    ),
);

/**
 *
 */
$api->get(
    '/data/stats',
    $APIkeyRequired,
    function () use ($api) {
        $api->render($api->db->queryRowsArray('SELECT * FROM `pvlng_reading_stats`'));
    }
)
->name('GET /data/stats')
->help = array(
    'since'       => 'r5',
    'description' => 'Fetch readings statistics',
    'apikey'      => true,
);

/**
 *
 */
$api->delete(
    '/data/:guid/:timestamp',
    $APIkeyRequired,
    function ($guid, $timestamp) use ($api) {
        $channel = Channel\Channel::byGUID($guid);
        $tbl = $channel->numeric ? new ORM\ReadingNum : new ORM\ReadingStr;
        if ($tbl->filterByIdTimestamp($channel->entity, $timestamp)->findOne()->getId()) {
            $tbl->delete();
            $api->halt(204);
        } else {
            $api->stopAPI('Reading not found', 400);
        }
    }
)
->name('DELETE /data/:guid/:timestamp')
->help = array(
    'since'       => 'r2',
    'description' => 'Delete a reading value',
    'apikey'      => true,
);

/**
 * Undocumented
 */
$api->delete(
    '/data',
    $APIkeyRequired,
    function () use ($api) {
        if (!ORM\Settings::getCoreValue(null, 'EmptyDatabaseAllowed')) {
            $api->stopAPI('Delete all data is not allowed, '
                         .'change the "Empty database allowed" flag first!');
        }

        $tables = array(
        'pvlng_reading_last',
        'pvlng_reading_num',      'pvlng_reading_str',
        'pvlng_reading_num_tmp',  'pvlng_reading_str_tmp',
        'pvlng_reading_num_calc', 'pvlng_reading_tmp'
        );

        foreach ($tables as $table) {
            $api->db->truncate($table);
        }

        $api->db->query('UPDATE `pvlng_channel` SET `offset` = 0 WHERE `adjust`');

        ORM\Settings::setCoreValue(null, 'EmptyDatabaseAllowed', 0);

        $api->stopAPI('All data deleted', 200);
    }
);
