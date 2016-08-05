<?php
/**
 * @author      Patrick Feisthammel <patrick.feisthammel@citrin.ch>
 * @copyright   2014 Patrick Feisthammel
 * @license     MIT License (MIT) http://opensource.org/licenses/MIT
 * @version     1.0.0
 *
 * 1.0.0
 * - Inital creation
 */
return array(

    'name' => 'Tesla Motors',

    'description' => '
        Vehicle Information from Tesla Motors with pre-configured sensors:
        <ul>
            <li>Charging State (Disconnected / Charging)</li>
            <li>Charge Limit SOC</li>
            <li>Max Range Counter</li>
            <li>Battery Range (miles)</li>
            <li>Charge miles added rated</li>
            <li>Charger volrage (V)</li>
            <li>Charger current (A)</li>
            <li>Charger pilot current (A)</li>
            <li>Charger actual current (A)</li>
            <li>Charge power (kW)</li>
            <li>Charger Rate (miles/h)</li>
            <li>Charger phases</li>
            <li> ... </li>
        </ul>
    ',

    'channels' => array(

        // Grouping channel
        array(
            'type'        => 47, // Tesla Motors
            'name'        => 'Tesla Motors',
        ),

        // Real channels
        array(
            'type'        => 91, // Switch
            'name'        => 'Charging state',
            'channel'     => 'charging_state',
            'numeric'     => 0,
        ),

        array(
            'type'        => 91, // Switch
            'name'        => 'Charge to max range',
            'channel'     => 'charge_to_max_range',
            'numeric'     => 0,
        ),

        array(
            'type'        => 91, // Switch
            'name'        => 'Battery heater on',
            'channel'     => 'battery_heater_on',
            'numeric'     => 0,
        ),

        array(
            'type'        => 91, // Switch
            'name'        => 'Not enough power to heat',
            'channel'     => 'not_enough_power_to_heat',
            'numeric'     => 0,
        ),

        array(
            'type'        => 91, // Switch
            'name'        => 'Fast charger present',
            'channel'     => 'fast_charger_present',
            'numeric'     => 0,
        ),

        array(
            'type'        => 91, // Switch
            'name'        => 'Fast charger type',
            'channel'     => 'fast_charger_type',
            'numeric'     => 0,
        ),

        array(
            'type'        => 91, // Switch
            'name'        => 'Charge port door open',
            'channel'     => 'charge_port_door_open',
            'numeric'     => 1,
        ),

        array(
            'type'        => 69, // Percent
            'name'        => 'Charge Limit SOC',
            'channel'     => 'charge_limit_soc',
            'unit'        => '%',
            'decimals'    => 0,
            'valid_from'  => 0,
            'valid_to'    => 100,
        ),

        array(
            'type'        => 92, // Event Counter
            'name'        => 'Max Range Counter',
            'channel'     => 'max_range_charge_counter',
            'unit'        => '',
            'decimals'    => 0,
        ),

        array(
            'type'        => 93, // Distance
            'name'        => 'Battery Range',
            'channel'     => 'battery_range',
            'unit'        => 'miles',
            'decimals'    => 0,
        ),

        array(
            'type'        => 93, // Distance
	    'name'        => 'Estimated battery range',
            'channel'     => 'est_battery_range',
            'unit'        => 'miles',
            'decimals'    => 0,
        ),

        array(
            'type'        => 93, // Distance
            'name'        => 'Ideal battery range',
            'channel'     => 'ideal_battery_range',
            'unit'        => 'miles',
            'decimals'    => 0,
        ),

        array(
            'type'        => 69, // Percent
            'name'        => 'Battery level',
            'channel'     => 'battery_level',
            'unit'        => '%',
            'decimals'    => 0,
            'valid_from'  => -5,
            'valid_to'    => 105,
        ),

        array(
            'type'        => 69, // Percent
            'name'        => 'Usable battery level',
            'channel'     => 'usable_battery_level',
            'unit'        => '%',
            'decimals'    => 0,
            'valid_from'  => -5,
            'valid_to'    => 105,
        ),

        array(
            'type'        => 53, // current sensor
            'name'        => 'Battery current',
            'channel'     => 'battery_current',
            'unit'        => 'A',
            'decimals'    => 1,
        ),

        array(
            'type'        => 50, // energy
            'name'        => 'Charge energy added',
            'channel'     => 'charge_energy_added',
            'unit'        => 'kWh',
            'decimals'    => 2,
        ),

       array(
            'type'        => 93, // Distance
            'name'        => 'Charge miles added rated',
            'channel'     => 'charge_miles_added_rated',
            'unit'        => 'miles',
            'decimals'    => 1,
        ),

       array(
            'type'        => 93, // Distance
            'name'        => 'Charge miles added ideal',
            'channel'     => 'charge_miles_added_ideal',
            'unit'        => 'miles',
            'decimals'    => 1,
        ),

        array(
            'type'        => 52, // voltage sensor
            'name'        => 'Charger voltage',
            'channel'     => 'charger_voltage',
            'unit'        => 'V',
            'decimals'    => 0,
        ),

        array(
            'type'        => 53, // current sensor
            'name'        => 'Charger current',
            'channel'     => 'charger_actual_current',
            'unit'        => 'A',
            'decimals'    => 0,
            'valid_from'  => 0,
        ),

        array(
            'type'        => 53, // current sensor
            'name'        => 'Charger pilot current',
            'channel'     => 'charger_pilot_current',
            'unit'        => 'A',
            'decimals'    => 0,
            'valid_from'  => 0,
        ),

        array(
            'type'        => 51, // power sensor
            'name'        => 'Charge power',
            'channel'     => 'charger_power',
            'unit'        => 'kW',
            'decimals'    => 0,
        ),

        array(
            'type'        => 92, // counter
            'name'        => 'Charger phases',
            'channel'     => 'charger_phases',
            'unit'        => '',
            'decimals'    => 0,
        ),

        array(
            'type'        => 60, // temperature sensor
            'name'        => 'Inside Temperature',
            'channel'     => 'inside_temp',
            'unit'        => '°C',
            'decimals'    => 1,
        ),

        array(
            'type'        => 60, // temperature sensor
            'name'        => 'Outside Temperature',
            'channel'     => 'outside_temp',
            'unit'        => '°C',
            'decimals'    => 1,
        ), 

        array(
            'type'        => 60, // temperature sensor
            'name'        => 'Driver Temperature Setting',
            'channel'     => 'driver_temp_setting',
            'unit'        => '°C',
            'decimals'    => 1,
        ),

        array(
            'type'        => 60, // temperature sensor
            'name'        => 'Passenger Temperature Setting',
            'channel'     => 'passenger_temp_setting',
            'unit'        => '°C',
            'decimals'    => 1,
        ),

        array(
            'type'        => 91, // Switch
            'name'        => 'Is auto conditioning on',
            'channel'     => 'is_auto_conditioning_on',
            'numeric'     => 0,
        ),

        array(
            'type'        => 91, // Switch
            'name'        => 'Is front defroster on',
            'channel'     => 'is_front_defroster_on',
            'numeric'     => 0,
        ),

        array(
            'type'        => 91, // Switch
            'name'        => 'Is rear defroster on',
            'channel'     => 'is_rear_defroster_on',
            'numeric'     => 0,
        ),  

        array(
            'type'        => 91, // Switch
            'name'        => 'Fan status',
            'channel'     => 'fan_status',
            'numeric'     => 1,
        )
    )
);
