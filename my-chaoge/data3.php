<?php
/**
 * Created by PhpStorm.
 * User: yyli
 * Date: 2015/7/21
 * Time: 9:14
 */


class DataConnection {
    private static $conn = null;
    public static function getConnection() {
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
        foreach ($this->col as $objCol => $dbCol ) {
            $this->$objCol = NULL;
        }

    }
    public function load($id = NULL) {
        $key = $this->key;
        if($id == NULL) {
            $id = $this->$key;
        }
        $sql = "select * from {$this->table} where {$key} = ?";
        $conn = DataConnection::getConnection();
        $sth = $conn->prepare($sql);
        $sth->execute(array($id));
        $row = $sth->fetch();
        if($row) {
            foreach ($this->col as $objCol => $dbCol) {
                $this->$objCol = $row[$dbCol];
            }
            return $this; // ??
        }
        else {
            return NULL;
        }
    }
    public function find() {
        $ret = array();
        $cond = "where 1 = 1 ";
        foreach ($this->col as $objCol => $dbCol) {
            if(isset($this->$objCol)) {
                $cond .= " {$dbCol} = {$this->$objCol} ";
            }
        }
        $sql = "select * from $this->table $cond";
        $conn = DataConnection::getConnection();
        $sth = $conn->prepare($sql);
        $sth->execute(array());
        $row = $sth->fetch();
        while($row) {
            $o = clone $this;
            foreach ($o->col as $objCol => $dbCol) {
                $o->$objCol = $row[$dbCol];
            }
            $ret[] = $o;
            $row = $sth->fetch();
        }
        return $ret;
    }
}

class Tree extends Data {
    public $pkey;
    public function init($opt) {
        parent::init($opt);
        $this->pkey = $opt['pkey'];
    }
    public function parent() {
        $p = clone $this;
        $p->reset();
        $p->{$p->key} = $this->{$this->pkey};
        return $p->load();
    }
    public function children() {
        $c = clone $this;
        $c->reset();
        $c->{$c->pkey} = $this->{$this->key};
        return $c->find();
    }
    public function toRoot() {
        $ret = array();
        $p = clone $this;
        do {
            $ret[] = $p;
            $p = $p->parent();
        } while($p);
        return array_reverse($ret);
    }

}

class Category extends Tree {
    public function __construct() {
        $opt = array(
            'key' => 'id',
            'pkey' => 'pid',
            'table' => 'babel_node',
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
            'pkey' => 'pid',
            'table' => 'babel_area',
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
    public $user, $category, $area;
    public function __construct() {
        $opt = array(
            'key' => 'id',
            'table' => 'babel_topic',
            'col' => array(
                'id' => 'tpc_id',
                'name' => 'tpc_title',
                'content' => 'tpc_content',
                'categoryId' => 'tpc_pid',
                'areaId' => 'tpc_area',
                'userId' => 'tpc_uid'
            )
        );
        parent::init($opt);
    }

    public function load($id = NULL) {
        parent::load($id);
        $this->user = new User();
        $this->user->id = $this->userId;
        $this->area = new Area();
        $this->area->id = $this->areaId;
        $this->category = new Category();
        $this->category->id = $this->categoryId;
    }

    public function comments() {
        $c = new Comment();
        $c->adId = $this->id;
        return $c->find();
    }
}

class User extends Data {
    public function __construct() {
        $opt = array(
            'key' => 'id',
            'table' => 'babel_user',
            'col' => array(
                'id' => 'usr_id',
                'email' => 'usr_email',
                'name' => 'usr_nick'
            )
        );
        parent::init($opt);
    }

    public function ads() {
        $a = new Ad();
        $a->userId = $this->id;
        return $a->find();
    }
}

class Comment extends Data {
    public function __construct() {
        $opt = array(
            'key' => 'id',
            'table' => 'babel_reply',
            'col' => array(
                'id' => 'rpl_id',
                'userId' => 'rpl_post_usr_id',
                'userNick' => 'rpl_post_nick',
                'adId' => 'rpl_tpc_id',
                'content' => 'rpl_content'
            )
        );
        parent::init($opt);
    }
}