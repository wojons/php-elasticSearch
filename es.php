<?php

	$es = new elasticSearch('http://127.0.0.1');
	$esx = $es->index('meep', 'rawr');
	$esd = $esx->index('meep'.time());
	$esd->setData($_SERVER);
	print_r($esd->index());

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
			foreach($opts as $dex=>$dat)	{
				$query .= $dex.'='.$dat.'&';
			}
			return '?'.substr($query, -1);
		}
		
		function stream($path, $method='GET', $payload)	{
			$context = stream_context_create(array('http' => array('method' => $method, 'content'=>$payload)));
			return file_get_contents($this->conn.$path, 0, $context);
		}
	}
	
	class esIndex	{
		function __construct($conn, $index, $type="")	{
			$this->conn = $conn;
			$this->index = $this->setIndex($index);
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
			return $this->conn->stream($this->index->getIndexType().$input.$this->conn->makePath($this->opts), $method, $payload);
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
		function __construct($index, $id, $data=array())	{
			$this->index = $index;
			$this->id = $id;
			$this->opts =& $this->index->opts;
			$this->setData($data);
		}
		
		function indexDoc()	{ //index the document
			return $this->index->stream($this->id, 'POST', $this->data);
		}
		
		function delDoc()	{
			return $this->index->stream($this->id, 'GET', null);
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
			return $result;
		}
		
		function updateDoc($update, $retry=0)	{
			if($retry > 0)	{
				$this->index->setOpt('retry_on_confict', $retry);
			}
			return $this->index->stream($this->id.'/_update', 'POST', $update);
		}
		
		function setOptions($opts)	{
			return $this->index->setOpt($opts);
		}
		
		function getOptions($opts=array())	{
			return $this->index->getOptions();
		}
		
		function setData($data)	{
			$this->data = json_encode($data);
			return $this;
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
