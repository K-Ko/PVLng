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
namespace Core;

/**
 *
 */
class NestedSet
{
    /**
     * Set up parameters
     *
     * Expect for parameter "table" an raay with
     * 't'  => table name
     * 'n'  => number (primary)
     * 'l'  => left
     * 'r'  => right,
     * 'm'  => moved
     * 'p'  => payload
     *
     * @param  object $db     Object of mysqli-Connection
     * @param  array  $params Array with Database-Table Vars
     * @return void
     */
    public function __construct(MySQLi $db, $params)
    {
        if (!isset($params['table']) || !is_array($params['table'])) {
            // Load english messages to show error message
            $this->msg = include __DIR__ . DIRECTORY_SEPARATOR . 'NestedSet.en.php';
            $this->error(1);
        }

        $this->db = $db;

        $params = array_merge([
                'table' => null,
                'lang'  => 'en',
                'debug' => false
            ], $params);

        extract($params, EXTR_PREFIX_ALL, 'p');

        $this->table = $p_table;

        // Prepare lock SQL with table name
        $this->lockSql = 'LOCK TABLES `' . $p_table['t'] . '` WRITE';

        // Load lang. specific messages
        if (in_array($p_lang, ['en', 'de'])) {
            $this->msg = include __DIR__ . DIRECTORY_SEPARATOR . 'NestedSet.' . $p_lang . '.php';
        } else {
            $this->msg = include __DIR__ . DIRECTORY_SEPARATOR . 'NestedSet.en.php';
        }

        $this->debug = $p_debug;
    }

    /**
     * Checks whether a Root Node exists
     *
     * @return boolean     TRUE or FALSE
     */
    public function checkRootNode()
    {
        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'SELECT `%2$s` FROM `%1$s` WHERE `%3$s` = 1 LIMIT 1',
            $t_t,
            $t_n,
            $t_l
        );

        if (!($result = $this->query($sql)) || !$result->num_rows) {
            $this->error(200, null, $sql);
        }

        return true;
    }

    /**
     * Creates the Root Node, if its not exists
     *
     * @param  string $nodeName Name of the Node
     * @return boolean
     */
    public function insertRootNode($nodeName)
    {
        if (true === $this->checkRootNode()) {
            $this->error(300, $nodeName);
        }

        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'INSERT INTO `%1$s` (`%2$s`, `%3$s`, `%4$s`) VALUES (1, 2, "%5$s")',
            $t_t,
            $t_l,
            $t_r,
            $t_p,
            $this->db->real_escape_string($nodeName)
        );

        if (!($result = $this->query($sql, true))) {
            $this->error(100, $nodeName);
        }

        if (!$this->db->affected_rows) {
            $this->error(301, $nodeName);
        }

        return true;
    }

    /**
     * Insert a new Node
     *
     * @param  string  $nodeName Name of the Node
     * @param  integer $parentId Id of the Parent Node
     * @return boolean            TRUE or FALSE
     */
    public function insertChildNode($nodeName, $parentId)
    {
        if (!($parentData = $this->getNode($parentId))) {
            $this->error(302, $nodeName);
        }

        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'UPDATE `%1$s` SET `%2$s` = `%2$s` + 2 WHERE `%2$s` >= %5$d;
             UPDATE `%1$s` SET `%3$s` = `%3$s` + 2 WHERE `%3$s` >  %5$d;
             INSERT INTO `%1$s` (`%4$s`, `%3$s`, `%2$s`) VALUES ("%6$s", %5$d, %5$d+1)',
            $t_t,
            $t_r,
            $t_l,
            $t_p,
            $parentData[$t_r],
            $this->db->real_escape_string($nodeName)
        );

        if (!$this->multiQuery($sql)) {
            $this->error(100, $nodeName);
        }

        if (!($result = $this->query('SELECT LAST_INSERT_ID()'))) {
            $this->error(100, $nodeName);
        }

        $row = $result->fetch_array();

        return $row[0];
    }


    /**
     * Delete a node and all its Children
     *
     * @param  integer $branchId Id of the Node
     * @return boolean            TRUE or FALSE
     */
    public function deleteBranch($branchId)
    {
        if (!($branchData = $this->getNode($branchId))) {
            $this->error(202, $branchId);
        }

        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'DELETE FROM `%1$s` WHERE `%2$s` BETWEEN %4$d AND %5$d;
             UPDATE `%1$s` SET `%2$s` = `%2$s` - %5$d + %4$d - 1 WHERE `%2$s` > %5$d;
             UPDATE `%1$s` SET `%3$s` = `%3$s` - %5$d + %4$d - 1 WHERE `%3$s` > %5$d',
            $t_t,
            $t_l,
            $t_r,
            $branchData[$t_l],
            $branchData[$t_r]
        );

        if (!$this->multiQuery($sql)) {
            $this->error(303, $branchId, $sql);
        }

        return true;
    }

    /**
     * Delete a single Node
     *
     * @param  integer $nodeId Id of the Node
     * @return boolean          TRUE or FALSE
     */
    public function deleteNode($nodeId)
    {
        if (!($nodeData = $this->getNode($nodeId))) {
            $this->error(202, $nodeId);
        }

        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'DELETE FROM `%1$s` WHERE `%2$s` = %4$d;
             UPDATE `%1$s` SET `%2$s` = `%2$s` - 1, `%3$s` = `%3$s` - 1 WHERE `%2$s` BETWEEN %4$d AND %5$d;
             UPDATE `%1$s` SET `%2$s` = `%2$s` - 2 WHERE `%2$s` > %5$d;
             UPDATE `%1$s` SET `%3$s` = `%3$s` - 2 WHERE `%3$s` > %5$d',
            $t_t,
            $t_l,
            $t_r,
            $nodeData[$t_l],
            $nodeData[$t_r]
        );

        if (!$this->multiQuery($sql)) {
            $this->error(303, $nodeId, $sql);
        }

        return true;
    }

    /**
     * Edit the Name of a Node
     *
     * @param  interger $nodeId   Id of the Node
     * @param  string   $nodeName New Name of the Node
     * @return boolean  TRUE or FALSE
     */
    public function renameNode($nodeName, $nodeId)
    {
        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'UPDATE `%1$s` SET `%2$s` = "%3$s" WHERE `%4$s`= %5$d',
            $t_t,
            $t_p,
            $this->db->real_escape_string($nodeName),
            $t_n,
            +$nodeId
        );

        if (!($result = $this->query($sql))) {
            $this->error(100, $nodeName . ', ' . $nodeId, $sql);
        }

        if ($this->db->affected_rows == 0) {
            $this->error(304, $nodeName . ', ' . $nodeId, $sql);
        }

        return true;
    }

    /**
     * Move a Node/Branch LEFT
     *
     * @param  integer $nodeId Id of the Node
     * @return boolean TRUE or FALSE
     *
     * @deprecated Swap space with the left Brother
     */
    public function moveLeft($nodeId, $required = false)
    {
        $nodeLevel = $this->getNodeLevel($nodeId);

        if ($nodeLevel == 1) {
            $this->error(305, $nodeId);
        }

        extract($this->table, EXTR_PREFIX_ALL, 't');

        $a = $this->getNode($nodeId);
        $a_l = $a[$t_l];
        $a_r = $a[$t_r];

        if (!($b_id = $this->getId($a_l - 1, 'r'))) {
            if (!$required) {
                return false;
            } else {
                 $this->error(306, $nodeId);
            }
        }

        if (!($b = $this->getNode($b_id))) {
            $this->error(306, $nodeId);
        }

        $b_l = $b[$t_l];
        $b_r = $b[$t_r];

        $d_r = $a_r - $b_r;
        $d_l = $a_l - $b_l;

        $sql = sprintf(
            'UPDATE `%1$s` SET `%2$s` = 0;
             UPDATE `%1$s` SET `%3$s` = `%3$s` + %5$d,`%4$s` = `%4$s` + %5$d, `%2$s` = 1
              WHERE `%4$s` BETWEEN %9$d AND %10$d;
             UPDATE `%1$s` SET `%3$s` = `%3$s` - %6$d,`%4$s` = `%4$s` - %6$d
              WHERE `%4$s` BETWEEN %7$d AND %8$d AND `%2$s` = 0;
             UPDATE `%1$s` SET `%2$s` = 0',
            $t_t,
            $t_m,
            $t_r,
            $t_l,
            $d_r,
            $d_l,
            $a_l,
            $a_r,
            $b_l,
            $b_r
        );

        if (!$this->multiQuery($sql)) {
            $this->error(100, $nodeId, $sql);
        }

        return true;
    }

    /**
     * Move a Node/Branch RIGHT
     *
     * @param  integer $nodeId Id of the Node
     * @return boolean TRUE or FALSE
     */
    public function moveRight($nodeId, $required = false)
    {
        $nodeLevel = $this->getNodeLevel($nodeId);

        if ($nodeLevel == 1) {
            $this->error(305, $nodeId);
        }

        extract($this->table, EXTR_PREFIX_ALL, 't');

        $a = $this->getNode($nodeId);
        $a_l = $a[$t_l];
        $a_r = $a[$t_r];

        if (!($b_id = $this->getId($a_r + 1, 'l'))) {
            if (!$required) {
                return false;
            } else {
                $this->error(307, $nodeId);
            }
        }

        if (!($b = $this->getNode($b_id))) {
            $this->error(307, $nodeId);
        }

        $b_l = $b[$t_l];
        $b_r = $b[$t_r];

        $d_r = $b_r - $a_r;
        $d_l = $b_l - $a_l;

        $this->lockDb();

        $sql = sprintf(
            'UPDATE `%1$s` SET `%2$s` = 0;
             UPDATE `%1$s` SET `%4$s` = `%4$s` - %5$d, `%3$s` = `%3$s` - %5$d, `%2$s` = 1
              WHERE `%3$s` BETWEEN %7$d AND %8$d;
             UPDATE `%1$s` SET `%4$s` = `%4$s` + %6$d, `%3$s` = `%3$s` + %6$d
              WHERE `%3$s` BETWEEN %9$d AND %10$d AND `%2$s` = 0;
             UPDATE `%1$s` SET `%2$s` = 0',
            $t_t,
            $t_m,
            $t_l,
            $t_r,
            $d_l,
            $d_r,
            $b_l,
            $b_r,
            $a_l,
            $a_r
        );

        if (!$this->multiQuery($sql)) {
            $this->error(100, $nodeId, $sql);
        }

        return true;
    }

    /**
     * Move a Node/Branch UP
     *
     * @param  integer $nodeId Id of the Node
     * @return boolean TRUE or FALSE
     */
    public function moveUp($nodeId, $required = false)
    {
        $nodeLevel = $this->getNodeLevel($nodeId);

        if ($nodeLevel == 1) {
            $this->error(305, $nodeId);
        }

        if ($nodeLevel == 2) {
            $this->error(307, $nodeId);
        }

        do {
            if (!$moved = $this->moveRgt($nodeId)) {
                break;
            }
        } while ($moved);

        extract($this->table, EXTR_PREFIX_ALL, 't');

        $a = $this->getNode($nodeId);
        $a_l = $a[$t_l];
        $a_r = $a[$t_r];

        if (!($b_id = $this->getId($a_r + 1, 'r'))) {
            if (!$required) {
                return false;
            } else {
                $this->error(308, $nodeId);
            }
        }

        if (!($b = $this->getNode($b_id))) {
            $this->error(308, $nodeId);
        }

        $b_l = $b[$t_l];
        $b_r = $b[$t_r];

        $nodeWidth = $a_r - $a_l + 1;

        $sql = sprintf(
            'UPDATE `%1$s` SET `%2$s` = `%2$s` + 1,`%3$s` = `%3$s` + 1
              WHERE `%3$s` BETWEEN %5$d AND %6$d;
             UPDATE `%1$s` SET `%2$s` = `%2$s` - %7$d WHERE `%4$s` = %8$d',
            $t_t,
            $t_r,
            $t_l,
            $t_n,
            $a_l,
            $a_r,
            $nodeWidth,
            $b_id
        );

        if (!$this->multiQuery($sql)) {
            $this->error(100, $nodeId, $sql);
        }

        return true;
    }

    /**
     * Move a Node/Branch DOWN
     *
     * @param  integer $nodeId Id of the Node
     * @return boolean TRUE or FALSE
     */
    public function moveDown($nodeId, $required = false)
    {
        $nodeLevel = $this->getNodeLevel($nodeId);

        if ($nodeLevel == 1) {
            $this->error(305, $nodeId);
        }

        extract($this->table, EXTR_PREFIX_ALL, 't');

        $a = $this->getNode($nodeId);
        $a_l = $a[$t_l];
        $a_r = $a[$t_r];

        if (!$b_id = $this->getId($a_l - 1, 'r')) {
            if (!$required) {
                return false;
            } else {
                $this->error(306, $nodeId);
            }
        }
        if (!$b = $this->getNode($b_id)) {
            $this->error(306, $nodeId);
        }

        $b_l = $b[$t_l];
        $b_r = $b[$t_r];

        $nodeWidth = $a_r - $a_l + 1;

        $this->lockDb();

        $sql = sprintf(
            'UPDATE `%1$s` SET `%2$s` = `%2$s` - 1, `%3$s` = `%3$s` - 1
              WHERE `%3$s` BETWEEN %5$d AND %6$d;
             UPDATE `%1$s` SET `%2$s` = `%2$s` + %7$d WHERE `%4$s` = %8$d',
            $t_t,
            $t_r,
            $t_l,
            $t_n,
            $a_l,
            $a_r,
            $nodeWidth,
            $b_id
        );

        if (!$this->multiQuery($sql)) {
            $this->error(100, $nodeId, $sql);
        }

        return true;
    }

    /**
     * Get a NestedSet Tree as Array beging from $id
     *
     * @return mixed    Multidimensional Array with Tree data or boolean FALSE
     */
    public function getTree($id)
    {
        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'SELECT `o`.*,
                    count(`p`.`%4$s`) AS level
               FROM `%3$s` `n`,
                    `%3$s` `p`,
                    `%3$s` `o`
              WHERE `o`.`%1$s` BETWEEN `p`.`%1$s` AND `p`.`%2$s`
                AND `o`.`%1$s` BETWEEN `n`.`%1$s` AND `n`.`%2$s`
                AND `n`.`%4$s` = %5$d
              GROUP BY `o`.`%1$s`
              ORDER BY `o`.`%1$s`',
            $t_l,
            $t_r,
            $t_t,
            $t_n,
            $id
        );

        if (!($result = $this->query($sql)) || !$result->num_rows) {
            $this->error(100, $id, $sql);
        }

        $rows = [];
        $i = 1;

        while ($row = $result->fetch_assoc()) {
            $rows[$i++] = $row;
        }

        return $rows;
    }

    /**
     * Get a NestedSet Tree as Array beging from $id
     *
     * @return mixed    Multidimensional Array with Tree data or boolean FALSE
     */
    public function getChilds($id)
    {
        $rows = $this->getTree($id);

        $return = [];

        if (count($rows) > 1) {
            // Skip root
            array_shift($rows);

            // Remember level of 1st child
            $level = $rows[0]['level'];

            foreach ($rows as $row) {
                if ($row['level'] == $level) {
                    $return[] = $row;
                }
            }
        }

        return $return;
    }

    /**
     * Get count of direct childs
     *
     * @return int
     */
    public function getChildCount($id)
    {
        return count($this->getChilds($id));
    }

    /**
     * Get count of direct childs
     *
     * @return int
     */
    public function getParent($id)
    {
        $path = $this->getPathFromRoot($id);
        $parent = array_splice($path, -2, 1);
        return $parent[0];
    }

    /**
     * Get a full NestedSet Tree as Array
     *
     * @return mixed    Multidimensional Array with Tree data or boolean FALSE
     */
    public function getFullTree()
    {
        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'SELECT `n`.*,
                    round((`n`.`%2$s` - `n`.`%1$s` - 1) / 2, 0) AS `childs`,
                    count(*) - 1 +(`n`.`%1$s` > 1) + 1 AS `level`,
                    ((min(`p`.`%2$s`) - `n`.`%2$s` -(`n`.`%1$s` > 1)) / 2) > 0 AS `lower`,
                    (((`n`.`%1$s` - max(`p`.`%1$s`) > 1))) AS `upper`
               FROM `%3$s` `n`,
                    `%3$s` `p`
              WHERE `n`.`%1$s` BETWEEN `p`.`%1$s` AND `p`.`%2$s`
                AND (`p`.`%4$s` != `n`.`%4$s` OR `n`.`%1$s` = 1)
              GROUP BY `n`.`%4$s`
              ORDER BY `n`.`%1$s`',
            $t_l,
            $t_r,
            $t_t,
            $t_n
        );

        if (!($result = $this->query($sql)) || !$result->num_rows) {
            $this->error(100, null, $sql);
        }

        $rows = [];
        $i = 1;

        while ($row = $result->fetch_assoc()) {
            $rows[$i++] = $row;
        }

        return $rows;
    }

    /**
     * Get the Path from Root Level to the selected Node
     *
     * @param  integer $nodeId Id of the Node
     * @return mixed          array Path Values or boolean FALSE
     */
    public function getPathFromRoot($nodeId)
    {
        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'SELECT p.*
               FROM `%1$s` AS n,
                    `%1$s` AS p
              WHERE n.`%2$s` BETWEEN p.`%2$s` AND p.`%3$s`
                AND n.`%4$s` = %5$d
              ORDER BY p.`%2$s`',
            $t_t,
            $t_l,
            $t_r,
            $t_n,
            +$nodeId
        );

        if (!($result = $this->query($sql))) {
            $this->error(100, $nodeId, $sql);
        }

        if (!$result->num_rows) {
            $this->error(204, $nodeId, $sql);
        }

        $rows = [];
        $i = 1;

        while ($row = $result->fetch_assoc()) {
            $rows[$i++] = $row;
        }

        return $rows;
    }

    // -----------------------------------------------------------------------
    // PROTECTED
    // -----------------------------------------------------------------------

    /**
     *
     */
    protected $db;

    /**
     * Prebuild lock SQL statement
     *
     * @var string
     */
    protected $lockSql;

    /**
     * Array with Erros Messages, there are stored in an external File
     *
     * @var array
     */
    protected $msg;

    /**
     *
     */
    protected $debug;

    /**
     * Query wrapper with flag to lock table for write
     *
     * @param  string $sql Id of the Node
     * @return mixed   Query result
     */
    protected function query($sql, $lock = false)
    {
        if ($lock && !$this->lockDb()) {
            $this->error(100, null, $this->lockSql);
        }

        $result = $this->db->query($sql);

        if ($lock && !$this->unlockDb()) {
            $this->error(100, null, $sql, false);
        }

        return $result;
    }

    /**
     * Multi query wrapper
     * Always lock table for write
     *
     * @param  string $sql Id of the Node
     * @return mixed   Query result
     */
    protected function multiQuery($sql)
    {
        $this->lockDb();

        if ($this->db->multi_query($sql)) {
            while ($this->db->more_results() && $this->db->next_result()) {
            }
            $return = true;
        } else {
            $return = false;
        }

        $this->unlockDb();

        return $return;
    }

    /**
     * Get the Id of a Node depending on its left or right Value
     *
     * @param  integer $directionValue Value of left or right Border
     * @param  string  $direction      left or right Border("l" for left, "r" for right)
     * @return mixed              integer Id of the Node or boolean FALSE
     */
    protected function getId($directionValue, $direction)
    {
        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'SELECT `%2$s` FROM `%1$s` WHERE `%3$s` = %4$d LIMIT 1',
            $t_t,
            $t_n,
            $this->table[$direction],
            +$directionValue
        );

        if (($result = $this->query($sql)) && ($result->num_rows > 0)) {
            return $result->fetch_row()[0];
        }

        return false;
    }

    /**
     * Get an Array with id,lft,rgt values of a Node
     *
     * @param  integer $nodeId Id of the Node
     * @return mixed           array Values of the Node or boolean FALSE
     */
    protected function getNode($nodeId)
    {
        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'SELECT `%1$s`,`%2$s`,`%3$s` FROM `%4$s` WHERE `%1$s` = %5$d LIMIT 1',
            $t_n,
            $t_l,
            $t_r,
            $t_t,
            +$nodeId
        );

        if (!($result = $this->query($sql))) {
            return $this->error(100, $nodeId, $sql);
        }

        if (!$result->num_rows) {
            return $this->error(202, $nodeId, $sql);
        }

        return $result->fetch_assoc();
    }

    /**
     * Get the Level of a Node
     *
     * @param  integer $nodeId Id of the Node
     * @return mixed    integer   integer Level of the Node(0 = Root) or boolean FALSE
     */
    protected function getNodeLevel($nodeId)
    {
        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf(
            'SELECT COUNT(1)
               FROM `%1$s` AS p, `%1$s` AS n
              WHERE n.`%3$s` BETWEEN p.`%3$s` AND p.`%4$s`
              GROUP BY n.`%3$s`
              ORDER BY ABS(n.`%2$s` - %5$d)',
            $t_t,
            $t_n,
            $t_l,
            $t_r,
            +$nodeId
        );

        if (!($result = $this->query($sql))) {
            return $this->error(100, $nodeId, $sql);
        }

        if (!$result->num_rows) {
            return $this->error(202, $nodeId, $sql);
        }

        return $result->fetch_row()[0];
    }

    /**
     * Count all Nodes in the NestedSet-Table
     *
     * @return mixed  integer Count or boolean FALSE
     */
    protected function countNodes()
    {
        extract($this->table, EXTR_PREFIX_ALL, 't');

        $sql = sprintf('SELECT COUNT(1) FROM `%1$s` AS `count`', $t_t);

        if (!($result->$this->query($sql))) {
            return $this->error(100, null, $sql);
        }

        if (!$result->num_rows) {
            return $this->error(201, null, $sql);
        }

        return $result->fetch_row()[0];
    }

    /**
     * Throw Exception with formated error message
     *
     * @param  string $error The Error Message
     * @return void
     */
    protected function error($err, $param = null, $sql = null, $unlock = true)
    {
        $unlock && $this->unlockDb();

        $dbg = debug_backtrace(0, 2);

        $error = sprintf(
            '%s::%s(%s) %s',
            $dbg[1]['class'],
            $dbg[1]['function'],
            $param,
            $this->msg[$err]
        );

        if ($this->debug && $this->db && $sql) {
            $error .= ' [' . $sql . '] ' . $this->db->error;
        }

        throw new Exception($error, $err);
    }

    /**
     * Lock the NestedSet-Table to write
     *
     * @return void
     */
    protected function lockDb()
    {
        if (!$this->db->query($this->lockSql)) {
            $this->error(100, null, $sql);
        }
    }

    /**
     * Unlock the NestedSet-Table
     *
     * @return void
     */
    protected function unlockDb()
    {
        if ($this->db && !$this->db->query('UNLOCK TABLES')) {
            $this->error(100, null, $sql, false);
        }
    }
}
