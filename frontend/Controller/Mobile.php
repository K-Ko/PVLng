<?php
/**
 *
 *
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2014 Knut Kohl
 * @license     MIT License (MIT) http://opensource.org/licenses/MIT
 * @version     1.0.0
 */
namespace Controller;

/**
 *
 */
class Mobile extends \Controller {

    /**
     *
     */
    public function Index_Action() {
        // Switch layout
        $this->Layout = 'mobile';

        // Get views
        $q = \DBQuery::forge('pvlng_view')->whereEQ('public', 2)->order('name');
        $views = array();
        $tree = new \ORM\Tree;

        foreach ($this->db->queryRows($q) as $row) {

            $data = json_decode($row->data);

            $new_data = array();
            foreach ($data as $id=>$presentation) {
                if ($id == 'p') continue;

                // Get entity attributes
                $tree->reset()->filterById($id)->findOne();
                $new_data[] = array(
                    'id'           => +$tree->id,
                    'guid'         => $tree->guid,
                    'unit'         => $tree->unit,
                    'public'       => +$tree->public,
                    'presentation' => addslashes($presentation)
                );
            }

            $views[] = array(
                'name'   => $row->name,
                'period' => $data->p,
                'data'   => json_encode($new_data)
            );

            if ($this->view->View1st == '') $this->view->View1st = $row->name;
        }

        $this->view->Views = $views;
    }

}
