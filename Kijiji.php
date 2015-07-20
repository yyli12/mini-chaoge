<?php
class DataConnection {

    private static $conn = null;
    public static function getConnection() {
        if (self::$conn == null) {
            self::$conn = new PDO("mysql:host=localhost;dbname=chaoge","root","");
            self::$conn->query('set names utf8');
        }
        return self::$conn;
    }

// origin mysql version
//    private static $connection = null;
//	public static function getConnection() {
//		if (self::$connection == null) {
//			self::$connection = mysql_connect('localhost', 'root', '') or die(mysql_error());
//			mysql_select_db('chaoge') or die(mysql_error());
//			mysql_query('set names utf8') or die(mysql_error());
//		}
//		return self::$connection;
//	}

}


function debug($a) {
//    echo "<br>----------<br>";
//    print_r($a);
}


class Data {
	public $key, $table, $columns;

	public function init($options) {
		$this->key = $options['key'];
		$this->table = $options['table'];
		$this->columns = $options['columns'];
	}

	public function reset() {
		foreach ($this->columns as $objCol => $dbCol) {
			$this->$objCol = null;
		}
//        print_r($this);
	}

	public function load($id = null) {
		$key = $this->key;
		if ($id == null) {
			$id = $this->$key;
		}
// origin mysql version
//		$sql = "select * from {$this->table} where {$this->columns[$key]} = $id";
//		DataConnection::getConnection();
//		$rs = mysql_query($sql) or die(mysql_error());
//		$row = mysql_fetch_assoc($rs);
//		if ($row) {
//			foreach ($this->columns as $objCol => $dbCol) {
//				$this->$objCol = $row["$dbCol"];
//			}
//			return $this;
//		} else {
//			return null;
//		}
        $conn = DataConnection::getConnection();
        $sql = "select * from {$this->table} where {$this->columns[$key]} = ?";
        $sth = $conn->prepare($sql);
        $sth->execute(array($id));
		$row = $sth->fetch();
		if ($row) {
            // print_r($row);
			foreach ($this->columns as $objCol => $dbCol) {
				$this->$objCol = $row["$dbCol"];
			}
			return $this;
		} else {
			return null;
		}
	}

	public function find() {
		$result = array();
		$where = 'where 1=1 ';
//        debug($this);
		foreach ($this->columns as $objCol => $dbCol) {
		// if there is restriction in objCol, add to WHERE cond
//             echo $this->$objCol;
			if (isset($this->$objCol)) {
				$where .= " and $dbCol = {$this->$objCol}";
			}
		}
        // debug($this);
        $conn = DataConnection::getConnection();
		$sql = "select * from $this->table $where";
//        echo $sql;
		$sth = $conn->prepare($sql);
        $sth->execute(array());
		$row = $sth->fetch();
		while ($row) {
			$o = clone $this;
			foreach ($o->columns as $objCol => $dbCol) {
				$o->$objCol = $row[$dbCol];
			}
			$result[] = $o;
            $row = $sth->fetch();
		}
// origin mysql version
//		$sql = "select * from {$this->table} $where";
//		DataConnection::getConnection();
//		$rs = mysql_query($sql) or die(mysql_error());
//		$row = mysql_fetch_assoc($rs);
//		while ($row) {
//			$o = clone $this;
//			foreach ($o->columns as $objCol => $dbCol) {
//				$o->$objCol = $row[$dbCol];
//			}
//			$result[] = $o;
//			$row = mysql_fetch_assoc($rs);
//		}

//		print_r($result);
		return $result;
	}
}

class Tree extends Data {
	public $pkey;

	public function init($options) {
		parent::init($options);
		$this->pkey = $options['pkey'];
	}

	public function parent() {
		$o = clone $this;
		$o->reset();
        debug($this->pkey);
		$o->{$o->key} = $this->{$this->pkey};
		return $o->load();
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
		} while ($o);
		return array_reverse($result);
	}
}

class Category extends Tree {
	public function __construct() {
		$options = array(
			'key' => 'id',
			'pkey' => 'pid',
			'table' => 'babel_node',
			'columns' => array(
				'id' => 'node_id',
				'pid' => 'nod_pid',
				'name' => 'nod_title'
			)
		);
		parent::init($options);
	}

	public function ads() {
		$a = new Ad();
		$a->categoryId = $this->id;
        // debug($a);
        // debug(($a->find()));
		return $a->find();
	}
}

class Area extends Tree {
	public function __construct() {
		$options = array(
			'key' => 'id',
			'pkey' => 'pid',
			'table' => 'babel_area',
			'columns' => array(
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

	public function __construct() {
		$options = array(
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
		parent::init($options);
	}

	public function load($id = null) {
        debug($this);
		parent::load($id);
        debug($this);
		$this->category = new Category();
		$this->category->id = $this->categoryId;
		$this->area = new Area();
		$this->area->id = $this->areaId;
        debug($this->area->load());
		$this->user = new User();
		$this->user->id = $this->userId;
	}

	public function comments() {
		$a = new Comment();
		$a->adId = $this->id;
		return $a->find();
	}

}

class User extends Data {
	public function __construct() {
		$options = array(
			'key' => 'id',
			'table' => 'babel_user',
			'columns' => array(
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
