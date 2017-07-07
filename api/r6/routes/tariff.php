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
 * Helper function
 */
$checkTariffId = function (Slim\Route $route) use ($api) {
    $id = $route->getParam('id');
    if (!(new ORM\Tariff($id))->getId()) {
        $api->stopAPI('Unknown tariff Id: '.$id);
    }
};

/**
 *
 */
$api->get(
    '/tariff',
    $APIkeyRequired,
    function () use ($api) {
        $result = array();

        foreach (new ORM\Tariff as $row) {
            $result[$row->getId()] = array(
                'name'    => $row->getName(),
                'comment' => $row->getComment()
            );
        }

        $api->render($result);
    }
)
->name('GET /tariff')
->help = array(
    'since'       => 'r4',
    'description' => 'Extract all tariffs',
    'apikey'      => true,
);

/**
 *
 */
$api->get(
    '/tariff/:id',
    $APIkeyRequired,
    $checkTariffId,
    function ($id) use ($api) {
        $result = array();

        $tbl = new ORM\TariffView;
        foreach ($tbl->findMany('id', $id) as $row) {
            $result[] = array(
                'name'           => $row->getName(),
                'comment'        => $row->getTariffComment(),
                'date'           => $row->getDate(),
                'cost'           => $row->getCost(),
                'time'           => $row->getTime(),
                'days'           => $row->getDays(),
                'tariff'         => $row->getTariff(),
                'tarrif_comment' => $row->getTariffComment()
            );
        }

        $api->render($result);
    }
)
->name('GET /tariff/:id')
->help = array(
    'since'       => 'r4',
    'description' => 'Extract a tariff',
    'apikey'      => true,
);

/**
 *
 */
$api->get(
    '/tariff/:id/:date',
    $APIkeyRequired,
    $checkTariffId,
    function ($id, $date) use ($api) {
        $api->render((new ORM\Tariff($id))->getTariffDay(strtotime($date), ORM\Tariff::DAY));
    }
)
->name('GET /tariff/:id/:date')
->conditions(array(
    'date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}'
))
->help = array(
    'since'       => 'r4',
    'description' => 'Extract tariff for a day',
    'apikey'      => true,
);

/**
 *
 */
$api->get(
    '/tariff/:id/time/:date(/:to)',
    $APIkeyRequired,
    $checkTariffId,
    function ($id, $date, $to = null) use ($api) {
        $api->render((new ORM\Tariff($id))->getTariffTimes(strtotime($date), $to ? strtotime($to) : $to));
    }
)
->name('GET /tariff/:id/time/:date(/:to)')
->conditions(array(
    'date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}',
    'to'   => '[0-9]{4}-[0-9]{2}-[0-9]{2}'
))
->help = array(
    'since'       => 'r4',
    'description' => 'Extract tariff for a day',
    'apikey'      => true,
);
