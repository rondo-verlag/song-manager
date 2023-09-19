<?php

class File
{
	private $id;
	private $data;
	private $DB;

	function __construct($id = null) {
		$this->DB = $GLOBALS['DB'];
		if (!is_null($id)){
			$this->id = $id;
			$this->data = $this->DB->fetchAssociative("SELECT * FROM files WHERE id = ? AND isDeleted = 0", array($id));
		} else {
			$this->data = array();
		}
	}

	public function getId(){
		return $this->id;
	}

	public function getData(){
		return $this->data;
	}

	public function setData($data){
		$this->data = $data;
		return $this;
	}

	public function save(){
		if (is_null($this->id)) {
			$this->DB->insert('files', $this->data);
			$this->id = $this->DB->lastInsertId();
		} else {
			$this->DB->update('files', $this->data, array('id' => $this->id));
		}
		return $this;
	}

	public function delete(){
		return $this->DB->update('files', ['isDeleted' => 1], array('id' => $this->id));
	}
}
