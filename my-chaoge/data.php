<?php
/**
 * Created by PhpStorm.
 * User: yyli
 * Date: 2015/7/20
 * Time: 14:25
 */

class DB_conn {
    private static $conn = null;
    public static function getConn() {
        if( self::$conn == NULL ) {
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
        foreach($this->col as $objCol => $dbCol) {
            $this->$objCol = NULL;
        }
    }

    public function load($id = NULL) {
        $key = $this->key;
        if($id == NULL) {
            $id = $this->key;
        }

        $conn = DB_conn::getConn();
        $sql = "select * from {$this->table} where {$this->col[$key]} = ?";
        $sth = $conn->prepare($sql);
        $sth->execute();
        $row = $sth->fetch();
        if($row) {
            foreach($this->col as $objCol => $dbCol) {
                $this->$objCol = $row[$dbCol];
            }
            return $this;
        }
        else {
            return NULL;
        }
    }

    public function find() {
        $result = array();
        $cond = "where 1 = 1 ";
        foreach($this->col as $objCol => $dbCol) {
            if(isset($this->objCol)) {
                $cond .= " and $dbCol = {$this->objCol} ";
            }
        }
        $conn = DB_conn::getConn();
        $sql = "select * from $this->table $cond";
        $sth = $conn->prepare($sql);
        $sth->execute();
        $row = $sth->fetch();
        while($row) {
            $o = clone $this;
            foreach($o->col as $objCol => $dbCol) {
                $o->$objCol = $row[$dbCol];
            }
            $result[] = $o;
            $row = $sth->fetch();
        }

        return $result;
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
        $o = clone $this;
        $o->reset();
        $o->{$o->key} = $this->{$this->pkey};
        return $o->load;
    }

    public function children() {
        $o = clone $this;
        $o->reset();
        $o->{$o->pkey} = $this->{$this->key};
        return $o->find();
    }

    public function toRoot() {
        $o = clone $this;
        do {
            $result[] = $o;
            $o = $o->parent();
        } while($o);
        return array_reverse($result);

    }



}

class Category extends Tree{
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
        $options = array(
            'key' => 'id',
            'pkey' => 'pid',
            'table' => 'babel_area',
            'col' => array(
                'id' => 'area_id',
                'pid' => 'area_pid',
                'name' => 'area_title'
            )
        );
        parent::init($options);
    }

    public function ads() {
        $a = new Ad();
        $a->areaId = $this->id;
        return $a->find();
    }

}

class Ad extends Data {
    public $user, $area, $category;

    public function  __construct() {
        $opt = array(
            'key' => 'id',
            'table' => 'babel_topic',
            'columns' => array(
                'id' => 'tpc_id',
                'categoryId' => 'tpc_pid',
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

class User extends Data {
    public function __construct() {
        $options = array(
            'key' => 'id',
            'table' => 'babel_user',
            'col' => array(
                'id' => 'usr_id',
                'email' => 'usr_email',
                'name' => 'usr_nick'
            )
        );
        parent::init($options);
    }

    public function ads() {
        $a = new Ad();
        $a->userId = $this->id;
        return $a->find();
    }
}

class Comment extends Data {
    public function __construct() {
        $options = array(
            'key' => 'id',
            'table' => 'babel_reply',
            'columns' => array(
                'id' => 'rpl_id',
                'userId' => 'rpl_post_usr_id',
                'userNick' => 'rpl_post_nick',
                'adId' => 'rpl_tpc_id',
                'content' => 'rpl_content'
            )
        );
        parent::init($options);
    }
}





?>