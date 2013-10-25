<?php
/**
 *
 *
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2013 Knut Kohl
 * @license     GNU General Public License http://www.gnu.org/licenses/gpl.txt
 * @version     $Id$
 */
return array(

	/**
	 * Possible keys:
	 * - type     : (text|radio|textarea), default text
	 * - default  : a default value
	 * - required : (TRUE|FALSE), default FALSE
	 */

	'name' => array(
		'required' => TRUE,
	),

	'description' => array(
	),

	'serial' => array(
	),

	'channel' => array(
	),

	'resolution' => array(
		'required' => TRUE,
		'default'  => 1
	),

	'unit' => array(
	),

	'decimals' => array(
		'default'  => 2
	),

	'meter' => array(
		'type'     => 'radio',
		'required' => TRUE,
		'default'  => 0
	),

	'numeric' => array(
		'type'     => 'radio',
		'required' => TRUE,
		'default'  => 1
	),

	'cost' => array(
	),

	'threshold' => array(
	),

	'valid_from' => array(
	),

	'valid_to' => array(
	),

	'comment' => array(
		'type'     => 'textarea',
	),

);