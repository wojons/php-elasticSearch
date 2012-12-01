<?php

	$es = new elasticSearch('http://127.0.0.1');
	$esx = $es->index('meep', 'rawr');
	$esd = $esx->document('meep'.time());
	$esd->setData($_SERVER);
	print_r($esd->indexDoc());
	
	//lets get a docment
	$find = $esx->document('meep1353904493')->getDoc();
	print_r($find->data);
	
	$find->data[] = rand();
	$find->indexDoc();
	
	print_r($find->delDoc());
	
	
	class elasticSearch2()	{
		function __construct($url, $port)	{ //set up the connection
			$this->url = $url;
			$this->port = $port;
			$this->conn();
		}
		function indeces($indexes)	{ // set the index
			if(is_array($indexes) == true)	{
				$indexes = explore(',', $indexes);
			}
			$this->indecs = $indexes;
		}
		function type($type)	{ //set the type
			$if(is_array($type) == true)	{
				$type = explore(',', $type);
			}
			$this->types = $type;
			$this->conn();
		}
		
		private function conn()	{ //make the conn path used for each connection
			$this->conn = $this->url.'/'.$this->port.'/'.$this->indecs.'/'.$this->types.'/';
		}
		
		function index($key, $value, $rules=null)	{
			return $this->stream($key, 'POST', $rules, $value);
		}
		function delete($key, $rules=null)	{
			return $this->steram($key, 'DELETE', $rules);
		}
		function get($key, $rules[=null)	{
			return $this->straem($key, 'GET', $rules);
		}
		//function multiGet() {}
		//function update()	{}
		function search($query, $rules=null)	{
			if (is_array($query) == true)	{
				return $this->stream('_search', 'POST', $rules, $query);
			}
			return $this->stream('_search'.$query, 'GET', $rules);
		}
		//function multiSearch()	{}
		//function precolate()	{}
		function bulk($indexes, $rules)	{}
		//function bulkUDP()	{}
		function count($query, $rules)	{
			return $this->stream('_count', 'DELETE', $rules, $query);
		}
		function deleteQuery($query, $rules)	{
			if (is_array($query) == true)	{
				return $this->stream('_query', 'DELETE', $rules, $query);
			}
			return $this->stream('_query'.$query, 'DELETE', $rules);
		}
		//function morelikethis()	{}
		//function vaildate()	{}
		//function explain()	{}
		
		function makeQuery(array $opts)	{
			if(count($opts) > 0)	{
				foreach($opts as $dex=>$dat)	{
					$query .= $dex.'='.$dat.'&';
				}
				return '?'.substr($query, -1);
			}
			return "";
		}
		
		private function stream($path, $method="GET", $rules=null $payload="") {
			$context = stream_context_create(array('http' => array('method' => $method, 'content'=>is_array($payload) == true) ? json_encode($payload) : $payload))));
			return json_decode(file_get_contents($this->conn.$path.($rules != null) ? $this->makeQuery($rules) : "", 0, $context));
		}
	}
	

	class elasticSearch	{
		/*
		 * Base class does not really hold any elastic search stuff but useful for creating network connections
		*/
		
		function __construct($url, $port=9200)	{
			$this->connect($url, $port);
		}
		
		function connect($url, $port=9200)	{
			$this->conn = $url.':'.$port.'/';
			return $this;
		}
		
		function index($name, $type)	{
			return new esIndex($this, $name, $type);
		}
		
		function makePath(array $opts)	{
			if(count($opts) > 0)	{
				foreach($opts as $dex=>$dat)	{
					$query .= $dex.'='.$dat.'&';
				}
				return '?'.substr($query, -1);
			}
			return "";
		}
		
		function search($search, $rules)	{
			
		}
		
		function stream($path, $method='GET', $payload)	{
			$context = stream_context_create(array('http' => array('method' => $method, 'content'=>$payload)));
			return json_decode(file_get_contents($this->conn.$path, 0, $context));
		}
	}
	
	class esIndex	{
		function __construct($conn, $index, $type="")	{
			$this->conn = $conn;
			$this->setIndex($index); //set $this->index
			$this->setType($type);
			$this->opts = array();
		}
		
		function getIndexType()	{
			return $this->index.'/'.$this->type.'/';
		}
		
		function getType()	{
			return $this->type;
		}
		
		function getIndex($asArray=false)	{
			if($asArray == true)	{
				return explode(',', $this->index);
			} else {
				return $this->index;
			}
		}
		
		function setIndex($index)	{ //set the index
			if(is_array($index) == true)	{
				$this->index = implode(',', array_keys($index));
			} else {
				$this->index = $index;
			}
			return $this;
		}
		
		function setType($type)	{
			$this->type = $type;
			return $this;
		}
		
		function document($id, $data=array())	{
			return new esIndexDoc($this, $id, $data);
		}
		
		function setOpt($key, $value)	{
			$this->opts[$key] = $value;
		}
		
		function setOptions($opts)	{
			foreach($opts as $dex => $dat)	{
				$this->setOpt($dex, $dat);
			}
			return $this;
		}
		
		function getOptions($opts=array())	{
			if($opt == array())	{
				return $this->opts;
			}
			else	{
				return array_intersec_key(array_flip($opts), $this->opts);
			}
		}
		
		function stream($input, $method, $payload=null)	{
			return $this->conn->stream($this->getIndexType().$input.$this->conn->makePath($this->opts), $method, $payload);
		}
		
		function mGet($docs, $fields=null)	{
			foreach($docs as $dex=>$dat)	{
				if(isset($dat['_index']) == false)	{
					$docs[$dex]['_index'] = $this->getIndex();
				}
				if(isset($dat['_type']) == false)	{
					$docs[$dex]['_type'] = $this->getType();
				}
				if($fields != null && isset($dat['_fields']) == false)	{
					$docs[$dex]['fields'] = $fields;
				}
			}
			return $this->conn->stream('_mget', 'POST', $docs);
		}
	}
	
	class esIndexDoc {
		public $data = "";
		
		function __construct($index, $id, $data=array())	{
			$this->index = $index;
			$this->id = $id;
			$this->opts =& $this->index->opts;
			$this->setData($data);
		}
		
		function indexDoc()	{ //index the document
			return $this->index->stream($this->id, 'POST', $this->encodeData($this->data));
		}
		
		function delDoc()	{
			return $this->decodeData($this->index->stream($this->id, 'DELETE', null));
		}
		
		function getDoc($type=null)	{
			if($type != null)	{ //back up current type
				$typeBK = $this->index->getType();
				$this->index->setType($type);
			}
			$result  = $this->index->stream($this->id, 'GET', null);
			if($type != null)	{ //replace type
				$this->setType($typeBK);
			}
			//var_dump($result);
			$this->data = $this->decodeData($result);
			return $this;
		}
		
		function updateDoc($update=null, $retry=0)	{
			if ($update != null)	{
				$this->setData($update);
			}
			if($retry > 0)	{
				$this->index->setOpt('retry_on_confict', $retry);
			}
			return $this->index->stream($this->id.'/_update', 'POST', $this->encodeData($this->data));
		}
		
		function setOptions($opts)	{
			return $this->index->setOpt($opts);
		}
		
		function getOptions($opts=array())	{
			return $this->index->getOptions();
		}
		
		function setData($data)	{
			$this->data = $data;
			return $this;
		}
		
		function encodeData($data=null)	{
			$data = ($data == null) ? $this->data : $data;
			return json_encode($data);
		}
		
		function decodeData($data=null)	{
			$data = ($data == null) ? $this->data : $data;
			return json_decode($data, true);
		}
		
		function setVersion($version)	{
			$this->index->setOpt('version', $version);
			return $this;
		}
		
		function setRoute($route)	{
			$this->index->setOpt('route', $route);
			return $this;
		}
		 
		function setParent($parent)	{
			$this->index->setOpt('parent', $parent);
			return $this;
		}
		
		function setTimestamp($timestamp)	{
			$this->index->setOpt('timestamp', $timestamp);
			return $this;
		}
		
		function setTTL($ttl)	{
			$this->index->setOpt('ttl', $ttl);
			return $this;
		}
		
		function setTimeout($timeout)	{
			$this->index->setOpt('timeout', $timeout);
			return $this;
		}
		
		function setRealtime($realtime) 	{
			$this->index->setOpt('realtime', $realtime);
			return $this;
		}
		
		function setFields(array $fields) 	{
			$this->index->setOpt('fields', implode(',', $fields));
			return $this;
		}
	}
	
	class queryDSL	{
		
		function import($array)	{ //just import the array we want to use until we have time to make a full system
			$this->queryArray = $array;
		}
		
		function export()	{
			$this->$queryArray;
		}
	}
	
	class esIndcies 	{
		/*
		 * This class is for administationing the indexes them selves and not really running querys agaenst
		*/
	}
	
	class esCluster	{
		/*
		 * This class is for administationing the cluster them selves and not really running querys agaenst
		*/
	}
	
	
	

?>
