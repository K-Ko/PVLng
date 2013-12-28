<?php
/**
 *
 *
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2013 Knut Kohl
 * @license     GNU General Public License http://www.gnu.org/licenses/gpl.txt
 * @version     1.1.0
 *
 * 1.1.0
 * - change child channel logic to save child channel GUID in channel attribute
 *
 * 1.0.0
 * - initial creation
 */
namespace Channel;

/**
 *
 */
class Dashboard extends Calculator {

    /**
     * Inherit name and description from wrapped channel
     */
    public static function afterEdit( \ORM\Channel &$channel ) {
        $channel_for = \Channel::byGUID($channel->channel);
        $channel->name = $channel_for->name;
        $channel->description = $channel_for->description;
    }

    /**
     *
     */
    public function read( $request, $attributes=FALSE ) {

        $channel = \Channel::byGUID($this->channel);

        // Get some properties from child
        $this->meter = $channel->meter;

        // Set some proerties of child
        // If valid range is smaller than dashbord should display,
        // extend to at least dashboard range
        // e.g. performance ratio for graphs 95 .. 100, but dashboard 50 .. 100
        if (!is_null($channel->valid_from) AND $channel->valid_from > $this->valid_from) {
            $channel->valid_from = $this->valid_from;
        }
        if (!is_null($channel->valid_to) AND $channel->valid_to < $this->valid_to) {
            $channel->valid_to = $this->valid_to;
        }

        // Simply pass-through
        return $this->after_read($channel->read($request), $attributes);
    }

}
