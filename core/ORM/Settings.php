<?php
/**
 * Real access class for table 'pvlng_settings'
 *
 * To extend the functionallity, edit here
 *
 * @author     Knut Kohl <github@knutkohl.de>
 * @copyright  2014 Knut Kohl
 * @license    MIT License (MIT) http://opensource.org/licenses/MIT
 * @version    1.0.0
 *
 * 1.0.0
 * - Initial creation
 */
namespace ORM;

/**
 *
 */
class Settings extends SettingsBase {

    /**
     *
     */
    public function checkValueType( $scope, $name, $key, $value ) {
        $this->reset()
             ->filterByScopeNameKey($scope, $name, $key)
             ->findOne();

        switch ($this->getType()) {
            case 'num':  return is_numeric($value);
            case 'bool': return (is_numeric($value) AND ($value == 0 OR $value == 1));
            default:     return TRUE;
        }
    }

    /**
     *
     */
    public function getCoreValue( $name, $key ) {
        return $this->getScopeValue('core', $name, $key);
    }

    /**
     *
     */
    public function getControllerValue( $name, $key ) {
        return $this->getScopeValue('controller', $name, $key);
    }

    /**
     *
     */
    public function getModelValue( $name, $key ) {
        return $this->getScopeValue('model', $name, $key);
    }

    /**
     *
     */
    public function getSunrise( $day ) {
        $lat = $this->getScopeValue('core', '', 'Latitude');
        $lon = $this->getScopeValue('core', '', 'Longitude');
        return date_sunrise($day, SUNFUNCS_RET_TIMESTAMP, +$lat, +$lon, 90, date('Z')/3600);
    }

    /**
     *
     */
    public function getSunset( $day ) {
        $lat = $this->getScopeValue('core', '', 'Latitude');
        $lon = $this->getScopeValue('core', '', 'Longitude');
        return date_sunset($day, SUNFUNCS_RET_TIMESTAMP, +$lat, +$lon, 90, date('Z')/3600);
    }

    /**
     *
     */
    protected function getScopeValue( $scope, $name, $key ) {
        return $this->reset()
                    ->filterByScopeNameKey($scope, $name, $key)
                    ->findOne()
                    ->getValue();
    }


}