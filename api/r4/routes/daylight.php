<?php
/**
 * Sunrise, Sunset and Daylight routes
 *
 * @author     Knut Kohl <github@knutkohl.de>
 * @copyright  2012-2014 Knut Kohl
 * @license    MIT License (MIT) http://opensource.org/licenses/MIT
 * @version    1.0.0
 */

/**
 *
 */
$api->get('/sunrise/(/:date)', $checkLocation, function($date=NULL) use ($api) {
    $date = isset($date) ? strtotime($date) : time();
    $api->render(array(
        'sunrise' =>
        date_sunrise($date, SUNFUNCS_RET_TIMESTAMP, $api->Latitude, $api->Longitude, 90, date('Z')/3600)
    ));
})->conditions(array(
    'date' => '\d{4}-\d{2}-\d{2}'
))->name('sunrise intern')->help = array(
    'since'       => 'r3',
    'description' => 'Get sunrise of day, using configured loaction'
);

/**
 *
 */
$api->get('/sunrise/:latitude/:longitude(/:date)', function($latitude, $longitude, $date=NULL) use ($api) {
    $date = isset($date) ? strtotime($date) : time();
    $api->render(array(
        'sunrise' =>
        date_sunrise($date, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90, date('Z')/3600)
    ));
})->conditions(array(
    'latitude'  => '[\d.-]+',
    'longitude' => '[\d.-]+',
    'date'      => '\d{4}-\d{2}-\d{2}'
))->name('sunrise')->help = array(
    'since'       => 'r3',
    'description' => 'Get sunrise for location and day'
);

/**
 *
 */
$api->get('/sunset/(/:date)', $checkLocation, function($date=NULL) use ($api) {
    $date = isset($date) ? strtotime($date) : time();
    $api->render(array(
        'sunrise' =>
        date_sunset($date, SUNFUNCS_RET_TIMESTAMP, $api->Latitude, $api->Longitude, 90, date('Z')/3600)
    ));
})->conditions(array(
    'date' => '\d{4}-\d{2}-\d{2}'
))->name('sunset intern')->help = array(
    'since'       => 'r3',
    'description' => 'Get sunset of day, using configured loaction'
);

/**
 *
 */
$api->get('/sunset/:latitude/:longitude(/:date)', function($latitude, $longitude, $date=NULL) use ($api) {
    $date = isset($date) ? strtotime($date) : time();
    $api->render(array(
        'sunset' =>
        date_sunset($date, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90, date('Z')/3600)
    ));
})->conditions(array(
    'latitude'  => '[\d.-]+',
    'longitude' => '[\d.-]+',
    'date'      => '\d{4}-\d{2}-\d{2}'
))->name('sunset')->help = array(
    'since'       => 'r3',
    'description' => 'Get sunset of day'
);

/**
 *
 */
$api->get('/daylight/(/:offset)', $checkLocation, function($offset=0) use ($api) {
    $offset *= 60; // Minutes to seconds
    $now     = time();
    $sunrise = date_sunrise($now, SUNFUNCS_RET_TIMESTAMP, $api->Latitude, $api->Longitude, 90, date('Z')/3600);
    $sunset  = date_sunset($now, SUNFUNCS_RET_TIMESTAMP, $api->Latitude, $api->Longitude, 90, date('Z')/3600);
    $api->render(array(
        'daylight' => (int) ($sunrise-$offset <= $now AND $now <= $sunset+$offset)
    ));
})->conditions(array(
    'offset' => '\d+'
))->name('daylight intern')->help = array(
    'since'       => 'r3',
    'description' => 'Check for daylight for configured location, accept additional minutes before/after',
);

/**
 *
 */
$api->get('/daylight/:latitude/:longitude(/:offset)', function($latitude, $longitude, $offset=0) use ($api) {
    $offset *= 60; // Minutes to seconds
    $now     = time();
    $sunrise = date_sunrise(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90, date('Z')/3600);
    $sunset  = date_sunset(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90, date('Z')/3600);
    $api->render(array(
        'daylight' => (int) ($sunrise-$offset <= $now AND $now <= $sunset+$offset)
    ));
})->conditions(array(
    'latitude'  => '[\d.-]+',
    'longitude' => '[\d.-]+',
    'offset'    => '\d+',
))->name('daylight')->help = array(
    'since'       => 'r3',
    'description' => 'Check for daylight, accept additional minutes before/after',
);
