<?php
/**
 *
 *
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2013 Knut Kohl
 * @license     GNU General Public License http://www.gnu.org/licenses/gpl.txt
 * @version     $Id: v1.0.0.2-14-g2a8e482 2013-05-01 20:44:21 +0200 Knut Kohl $
 */
namespace Channel;

/**
 *
 */
class Ratio extends \Channel {

	/**
	 *
	 */
	public function read( $request, $attributes=FALSE ) {
		$this->before_read($request);

		$childs = $this->getChilds();

		$child1 = $childs[0]->read($request);
		$child2 = $childs[1]->read($request);

		$row1 = $child1->rewind()->current();
		$row2 = $child2->rewind()->current();

		$result = new \Buffer;

		while (!empty($row1) OR !empty($row2)) {

			if ($child1->key() == $child2->key()) {

				$row1['data'] = $row2['data'] != 0
				              ? $row1['data'] / $row2['data']
				              : 0;

				$row1['min']  = $row2['min'] != 0
				              ? $row1['min'] / $row2['min']
				              : 0;

				$row1['max']  = $row2['max'] != 0
				              ? $row1['max'] / $row2['max']
				              : 0;

				$result->write($row1, $child1->key());

				// read both next rows
				$row1 = $child1->next()->current();
				$row2 = $child2->next()->current();

			} elseif ($child1->key() AND $child1->key() < $child2->key() OR
			          $child2->key() == '') {

				// read only row 1
				$row1 = $child1->next()->current();

			} else /* $child1->key() > $child2->key() */ {

				// read only row 2
				$row2 = $child2->next()->current();

			}
		}
		$child1->close();
		$child2->close();

		return $this->after_read($result, $attributes);
	}

}