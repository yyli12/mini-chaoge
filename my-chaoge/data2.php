<?php
/**
 * Created by PhpStorm.
 * User: yyli
 * Date: 2015/7/20
 * Time: 16:03
 */

class DB_conn {
    private static $conn = null;
    public static function getConn() {
        if ( self::$conn == NULL ) {
            self::$conn = new PDO("mysql:host=localhost;dbname=chaoge","root","");
            self::$conn->query('set names utf8');
        }
        return self::$conn;
    }
}

class Data {
    public $key, $table, $col;

    public function init($opt) {
        $this->key = $opt['key'];
        $this->table = $opt['table'];
        $this->col = $opt['col'];
    }

    public function reset() {
        foreach ($this->col as $objCol => $dbCol) {
            $this->$objCol = NULL;
        }
    }

    public function load($id = NULL) {
        $key = $this->key;
        if($id == NULL) {
            $id = $this->$key;
        }
        $pdo = DB_conn::getConn();
        $sql = "select * from $this->table where {$this->col[$key]} = ?";
        $sth = $pdo->prepare($sql);
        $sth->execute(array($id));
        $row = $sth->fetch();
        if($row) {
            foreach ($this->col as $objCol => $dbCol) {
                $this->$objCol = $row[$dbCol];
            }
            return $this;
        }
        else {
            return NULL;
        }
    }

    public function find() {
        $ret = array();

        $cond = " where 1 = 1 ";

        foreach ($this->col as $objCol => $dbCol) {
            if(isset($this->$objCol)) {
                $cond .= " and $dbCol = {$this->$objCol} ";
            }
        }

        $pdo = DB_conn::getConn();
        $sql = "select * from $this->table $cond";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $row = $sth->fetch();
        while($row) {
            $o = clone $this;
            foreach($o->col as $objCol => $dbCol) {
                $o->$objCol = $row[$dbCol];
            }
            $ret []= $o;
            $row = $sth->fetch();
        }
        return $ret;
    }
}

class Tree extends Data {
    public $pkey;

    public function init($opt)
    {
        parent::init($opt);
        $this->pkey = $opt['pkey'];
    }

    public function parent() {
        $p = clone $this;
        $p->reset();
        $p->{$p->key} = $this->{$this->pkey};
        return $p->load();
        // copy pid of this to id of parent,
        // then load parent by id
    }

    public function children() {
        $c = clone $this;
        $c->reset();
        $c->{$c->pkey} = $this->{$this->key};
        return $c->find();
        // copy id of this to pid of children,
        // then find children by pid
    }

    public function toRoot() {
        $ret = array();
        $p = clone $this;
        do {
            $ret []= $p;
            $p = $p->parent();
        } while($p);
        return array_reverse($ret);
    }
}

class Category extends Tree {
    public function __construct() {
        $opt = array(
            'key' => 'id',
            'table' => 'babel_node',
            'pkey' => 'pid',
            'col' => array(
                'id' => 'node_id',
                'pid' => 'nod_pid',
                'name' => 'nod_title'
            )
        );
        parent::init($opt);
    }

    public function ads() {
        $a = new Ad();
        $a->categoryId = $this->id;
        return $a->find();
    }
}

class Area extends Tree {
    public function __construct() {
        $opt = array(
            'key' => 'id',
            'table' => 'babel_area',
            'pkey' => 'pid',
            'col' => array(
                'id' => 'area_id',
                'pid' => 'area_pid',
                'name' => 'area_title'
            )
        );
        parent::init($opt);
    }

    public function ads() {
        $a = new Ad();
        $a->areaId = $this->id;
        return $a->find();
    }

}

class Ad extends Data {
    public $user, $area, $category;
    public function __construct() {
        $opt = array(
            'key' => 'id',
            'table' => 'babel_topic',
            'col' => array(
                'id' => 'tpc_id',
                'catagoryId' => 'tpc_pic',
                'name' => 'tpc_title',
                'areaId' => 'tpc_area',
                'userId' => 'tpc_uid',
                'content' => 'tpc_content'
            )
        );
        parent::init($opt);
    }

    public function load($id = NULL) {
        parent::load($id);
        $this->category = new Category();
        $this->category->id = $this->categoryId;
        $this->area = new Area();
        $this->area->id = $this->areaId;
        $this->user = new User();
        $this->user->id = $this->userId;
    }

    public function comments() {
        $c = new Comment();
        $c->adId = $this->id;
        return $c->find();
    }
}