<?php
/**
 *
 *
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2013 Knut Kohl
 * @license     GNU General Public License http://www.gnu.org/licenses/gpl.txt
 * @version     1.0.0
 */
namespace Controller;

/**
 *
 */
class Channel extends \Controller {

    /**
     *
     */
    public function before() {
        parent::before();

        $q = \DBQuery::forge('pvlng_channel_view');
        $this->view->Entities = $this->rows2view($this->db->queryRows($q));

        $this->fields = array();

        foreach (include __DIR__ . DS . 'Channel' . DS . 'default.php' as $key=>$field) {
            $this->fields[$key] = array_merge(array(
                'FIELD'    => $key,
                'TYPE'     => 'text',
                'VISIBLE'  => TRUE,
                'REQUIRED' => FALSE,
                'READONLY' => FALSE,
                'DEFAULT'  => NULL
            ), array_change_key_case($field, CASE_UPPER));
        }

        $this->view->Id = $this->app->params->get('id');
    }

    /**
     *
     */
    public function afterPost() {
        if (!$this->ignore_returnto) {
            // Handle returnto (Edit from Overview) ...
            parent::afterPOST();
            // ... or redirect to channels list
            $this->app->redirect('/channel');
        }
    }

    /**
     *
     */
    public function Index_Action() {
        $this->view->SubTitle = __('Channels');
    }

    /**
     *
     */
    public function AddPOST_Action() {

        if (($type = $this->request->post('type')) == '') return;

        if (is_numeric($type)) {
            // Type Id
            $this->prepareFields($type);
            foreach ($this->fields as &$data) {
                $data['VALUE'] = $data['DEFAULT'];
            }

            $this->view->Type = $type;

            // Preset channel unit
            $type = new \ORM\ChannelType($type);
            $this->fields['unit']['VALUE'] = $type->unit;

            $model = $type->ModelClass();
            $model::beforeCreate($this->fields);

            $this->ignore_returnto = TRUE;
            $this->app->foreward('Edit');
        } else {
            // Get from template
            $channels = include $type;
            $channels = $channels['channels'];

            // 1st save channels
            $oChannel = new \ORM\Channel;
            $oChannel->throwException();

            try {

                foreach ($channels as $id=>$channel) {
                    $oChannel->reset();
                    foreach ($channel as $key=>$value) {
                        $oChannel->set($key, $value);
                    }
                    $oChannel->insert();
                    // Remember id for hierarchy build
                    $channels[$id]['id'] = $oChannel->id;
                }
                \Messages::Success(__('ChannelsSaved', count($channels)));

            } catch (\Exception $e) {
                \Messages::Error($e->getMessage());

                // Rollback in case of error
                $oChannel->reset();
                foreach ($channels as $id=>$channel) {
                    if (isset($channels[$id]['id'])) {
                        $oChannel->id = $channels[$id]['id'];
                        $oChannel->delete();
                    }
                }

                $this->app->redirect('/channel');
            }

            // Build hierarchy
            $tree = \NestedSet::getInstance();

            foreach ($channels as $id=>$channel) {
                if ($id == 0) {
                    $groupId = $tree->insertChildNode($channel['id'], 1);
                } else {
                    $tree->insertChildNode($channel['id'], $groupId);
                }
            }
            $this->app->cache->delete('Tree');
            \Messages::Success(__('HierarchyCreated', count($channels)));

            $this->app->redirect('/overview');
        }
    }

    /**
     *
     */
    public function Add_Action() {
        $this->view->SubTitle = __('CreateChannel');

        if ($clone = $this->app->params->get('clone')) {

            $channel = new \ORM\Channel($clone);

            if ($channel->id) {
                unset($channel->id, $channel->guid);
                $channel->name = 'Copy of '.$channel->name;
                $this->prepareFields($channel);

                $type = new \ORM\ChannelType($channel->type);
                $model = $type->ModelClass();
                $model::beforeEdit($channel, $this->fields);
            }

            $this->app->foreward('Edit');

        } else {

            // Get all channel types
            $q = \DBQuery::forge('pvlng_type')->whereGT('id', 0);
            $types = array();
            foreach ($this->db->queryRows($q) as $type) {
                $type->description = __($type->description);
                $types[] = array_change_key_case((array) $type, CASE_UPPER);
            }

            $this->view->EntityTypes = $types;

            // Search for equipment templates
            $templates = array();
            foreach (glob(CORE_DIR . DS . 'Channel' . DS . 'Templates' . DS . '*.php') as $file) {
                $template = include $file;
                $type = new \ORM\ChannelType($template['channels'][0]['type']);
                $templates[] = array(
                    'FILE'         => $file,
                    'NAME'         => $template['name'] . ' (Type: ' . $type->name . ')',
                    'DESCRIPTION'  => $template['description'],
                    'ICON'         => $type->icon
                );
            }

            $this->view->Templates = $templates;
        }
    }

    /**
     *
     */
    public function AliasPOST_Action() {

        $entity = new \ORM\Tree;
        $entity->find('entity', $this->request->post('id'));

        if ($entity->id) {
            if ($entity->alias) {
                \Messages::Error(__('AliasStillExists'));
            } else {
                $alias = new \ORM\Channel;
                $alias->name = $entity->name;
                $alias->description = $entity->description;
                $alias->channel = $entity->guid;
                $alias->unit = $entity->unit;
                $alias->private = $entity->private;
                $alias->type = 0;
                $alias->comment = 'Alias of ['.$entity->id.'] '.$entity->name;
                if ($entity->description) {
                    $alias->comment .= ' ('.$entity->description.')';
                }
                $alias->insert();

                if (!$alias->isError()) {
                    \Messages::Success(__('ChannelSaved'));
                } else {
                    \Messages::Error($entity->Error());
                    \Messages::Info(implode(";\n", $entity->queries()).';');
                }
            }
        }

        $this->app->redirect('/overview');
    }

    /**
     *
     */
    public function EditPOST_Action() {
        if ($channel = $this->request->post('c')) {

            $entity = new \ORM\Channel($channel['id']);

            // set values
            foreach ($channel as $key=>$value) {
                $entity->set($key, trim($value));
            }

            $this->prepareFields($entity);

            $type = new \ORM\ChannelType($channel['type']);
            $model = $type->ModelClass();
            $ok = $model::checkData($this->fields, $this->request->post('add2tree'));

            if ($ok) {
                $entity->throwException();
                try {
                    // CAN'T simply replace because of the foreign key in the tree!
                    if (!$entity->id) {
                        $model::beforeSave($this->fields, $entity);
                        $entity->insert();
                        \Messages::Success(__('ChannelSaved'));

                        $tree = 0;

                        if ($this->request->post('add2tree') AND $addTo = $this->request->post('tree')) {
                            $tree = \NestedSet::getInstance()->insertChildNode($entity->id, $addTo);
                            $this->app->cache->delete('Tree');
                            \Messages::Success(__('HierarchyCreated', 1));
                        }

                        $model::afterSave($entity, $tree);

                    } else {
                        $model::beforeSave($this->fields, $entity);
                        $entity->update();
                        $this->app->cache->delete('ChannelView'.$entity->id);
                        \Messages::Success(__('ChannelSaved'));

                        $model::afterSave($entity);

                        // Update possible alias channel!
                        $tree = new \ORM\Tree;

                        // Find channel itself in tree to get alias Id
                        if ($tree->find('entity', $entity->id)->alias) {
                            // Alias channel
                            $alias = new \ORM\Channel($tree->alias);
                            $alias->name = $entity->name;
                            $alias->description = $entity->description;
                            $alias->public = $entity->public;
                            $alias->unit = $entity->unit;
                            $alias->update();
                            $this->app->cache->delete('ChannelView'.$tree->alias);
                            \Messages::Success(__('AliasesUpdated'));

                            if (\slimMVC\Config::getInstance()->get('Log.SQL')) {
                                \ORM\Log::save('Update Alias', implode(";\n", $tree->queries()).';');
                                \ORM\Log::save('Update Alias', implode(";\n", $alias->queries()).';');
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Messages::Error('['.$e->getCode().'] '.$e->getMessage());
                    \Messages::Info(implode(";\n", $entity->queries()).';');
                    $ok = FALSE;
                }
            }

            $this->ignore_returnto = !$ok;

            $this->view->Id = $entity->id;
            $this->view->Type = $entity->type;
        }
    }

    /**
     *
     */
    public function Edit_Action() {
        $this->view->SubTitle = __('EditChannel');

        if ($this->view->Id) {
            $channel = new \ORM\Channel($this->view->Id);
            $this->prepareFields($channel);

            $type = new \ORM\ChannelType($channel->type);
            $model = $type->ModelClass();
            $model::beforeEdit($channel, $this->fields);
        }

        // Move comment to the end of list
        $comment = $this->fields['comment'];
        unset($this->fields['comment']);
        $this->fields['comment'] = $comment;

        $this->view->Fields = $this->fields;

        if (!$this->view->Id) {
            // Add mode
            $q = new \DBQuery('pvlng_tree_view');
            $q->get('id')
              ->get('CONCAT(REPEAT("&nbsp; &nbsp; ", `level`-2), IF(`haschilds`,"&bull; ","&rarr;"), "&nbsp;")', 'indent')
              ->get('name')
              ->get('`childs` = -1 OR `haschilds` < `childs`', 'available') // Unused child slots?
              ->whereNE('childs', 0);
            $this->view->AddTree = $this->rows2view($this->db->queryRows($q));
        }
    }

    /**
     *
     */
    public function DeletePOST_Action() {
        $entity = new \ORM\Channel($this->request->post('id'));

        if ($entity->id) {
            $name = $entity->name;
            $entity->delete();
            if (!$entity->isError()) {
                \Messages::Success(__('ChannelDeleted', $name));
            } else {
                \Messages::Error(__($entity->Error(), $entity->name), TRUE);
            }
        }

        $this->app->redirect('/channel');
    }

    // -------------------------------------------------------------------------
    // PROTECTED
    // -------------------------------------------------------------------------

    /**
     *
     */
    protected $fields = array();

    /**
     *
     */
    protected $ignore_returnto;

    /**
     *
     * @param $entity integer|object Type Id or Channel object
     */
    protected function prepareFields( $entity=NULL ) {

        $addMode = !is_object($entity);

        if ($addMode) {
            // Got channel type number to create new channel
            $type = new \ORM\ChannelType($entity);
        } else {
            // Got real channel object to edit
            $type = new \ORM\ChannelType($entity->type);
        }

        if ($type->id == '') {
            \Messages::Error('Unknown entity');
            $this->app->redirect('/channel');
        }

        $model = $type->ModelClass();

        if ($addMode) {
            // Get prefered type settings from Model
            if ($model::TYPE != UNDEFINED_CHANNEL) {
                $this->applyFieldSettings('numeric');
                if ($model::TYPE == SENSOR_CHANNEL) {
                    $this->applyFieldSettings('sensor');
                } elseif ($model::TYPE == METER_CHANNEL) {
                    $this->applyFieldSettings('meter');
                }
            }
        } else {
            // Get type settings from entity
            $this->applyFieldSettings($entity->numeric ? 'numeric' : 'non-numeric');
            if ($entity->numeric) {
                $this->applyFieldSettings($entity->meter ? 'meter' : 'sensor');
            }
        }

        if ($model::TYPE == GROUP_CHANNEL) {
            // A grouping channel
            $this->applyFieldSettings('group');
        } elseif ($type->write AND $type->read) {
            // No specials for writable AND readable channels
        } elseif ($type->write) {
            // Write only
           $this->applyFieldSettings('write');
        } elseif ($type->read) {
            // Read only
           $this->applyFieldSettings('read');
        } else {
            // A grouping channel, not write, not read
            $this->applyFieldSettings('group');
        }

        // Last apply model specific settings
        $this->applyFieldSettings(str_replace('\\', DS, $type->model));

        foreach ($this->fields as $key=>&$data) {
            $h = 'model::'.$type->model.'_'.$key;
            $name = __($h);
            $data['NAME'] = ($name != $h) ? $name : __('channel::'.$key);

            $h = 'model::'.$type->model.'_'.$key.'Hint';
            $name = __($h);
            $data['HINT'] = ($name != $h) ? $name : __('channel::'.$key.'Hint');

//             $data['VALUE'] = !$addMode
//                            ? htmlspecialchars($entity->$key)
//                            : htmlspecialchars(trim($data['DEFAULT']));
            $data['VALUE'] = !$addMode ? $entity->$key : trim($data['DEFAULT']);

            if (strpos($data['TYPE'], 'select') === 0) {
                $options = explode(';', $data['TYPE']);
                $data['TYPE'] = trim(array_shift($options)); // 'select'
                foreach ($options as $option) {
                    $option = explode(':', $option);
                    $data['OPTIONS'][] = array(
                        'VALUE'    => trim($option[0]),
                        'OPTION'   => __(trim($option[1])),
                        'SELECTED' => (trim($option[0]) == $data['VALUE'])
                    );
                }
            }
        }

        $this->view->Type = $type->id;
        $this->view->TypeName = $type->name;
        if ($type->unit) $this->view->TypeName .= ' (' . $type->unit . ')';
        $this->view->Icon = $type->icon;

        $h = $type->description.'Help';
        $help = __($h);
        // Check if a help text exists
        $this->view->TypeHelp = $help != $h ? $help : '';
    }

    /**
     *
     */
    protected function applyFieldSettings( $conf ) {
        // 1st try general config
        $config = __DIR__ . DS . 'Channel' . DS . $conf . '.php';
        if (!file_exists($config)) {
            // 2nd try model specific config
            $config = CORE_DIR . DS . 'Channel' . DS . $conf . '.conf.php';
            if (!file_exists($config)) return;
        }

        $attr = include $config;

        // check all fields
        foreach ($attr as $key=>$data) {
            // apply settings for this field
            $this->fields[$key] = isset($this->fields[$key])
                                ? array_merge(
                                      $this->fields[$key],
                                      array_change_key_case($data, CASE_UPPER)
                                  )
                                : array_merge(
                                      array(
                                        'FIELD'    => $key,
                                        'TYPE'     => 'text',
                                        'VISIBLE'  => TRUE,
                                        'REQUIRED' => FALSE,
                                        'READONLY' => FALSE,
                                        'DEFAULT'  => NULL
                                      ),
                                      array_change_key_case($data, CASE_UPPER)
                                  );
        }
    }
}
