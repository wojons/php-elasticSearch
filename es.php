<?php

class elasticSearch	{
		
		public $metaStream = array();
		
		function __construct($url, $port=9200, $index=null, $type=null)	{ //set up the connection
			$this->url = $url;
			$this->port = $port;
			$this->conn();
			if($index != null)	{ //set a few things up
				$this->set_type($index);
				if($type != null)	{
					$this->set_type($type);
				}
			}
		}
		function set_indices($indexs)	{ // set the index
			if(is_array($indexes) == true)	{
				$indexs = explore(',', $indexs);
			}
			
			$this->indices = $indexs;
			$this->conn();
			return $this;
		}
		function set_type($type)	{ //set the type
			if(is_array($type) == true)	{
				$type = explore(',', $type);
			}
			$this->types = $type;
			$this->conn();
			return $this;
		}
		
		private function conn()	{ //make the conn path used for each connection
			$this->conn = $this->url.':'.$this->port.'/'.$this->indices.'/'.$this->types.'/';
		}
		
		function index($key, $value, $rules=null)	{
			if($key != null)	{
				return $this->stream($key, 'PUT', $rules, $value);
			} else {
				return $this->stream('', 'POST', $rules, $value);
			}
		}
		function delete($key, $rules=null)	{
			return $this->stream($key, 'DELETE', $rules);
		}
		function get($key, $rules=null)	{
			return $this->stream($key, 'GET', $rules);
		}
		//function multiGet() {}
		//function update()	{}
		function search($query, $rules=null)	{
			if (is_array($query) == true)	{
				return $this->stream('_search', 'POST', $rules, $query);
				//return $this->stream('_search', 'GET', $rules, $query);
			}
			$rules['q'] = $query;
			return $this->stream('_search', 'GET', $rules);
		}
		
		function searchResults($result)	{
			return new elasticSearchResults($result);
		}
		
		
		//function multiSearch()	{}
		//function precolate()	{}
		
		function bulkIndex($data, $id=null, $index=null, $type=null)	{
			$this->bulkItem('index', $data, $id, $index, $type);
		}
		
		function bulkItem($action, $data=null, $id=null, $index=null, $type=null)	{
			if($id != null) { $set['_id'] =  $id; } 
			$set['index'] = ($index != null) ? $index : $this->indices;
			$set['type'] = ($type != null) ? $type : $this->types;
			$this->bulkItems[] = array($action => $set);
			if($data != null)	{ $this->bulkItems[] = $data; }
		}
		
		function bulkItem_count()	{
			return count($this->bulkItems);
		}
		
		function bulk($rules=null)	{
			foreach($this->bulkItems as $dex => $dat)	{
				$bulkData[$dex] = json_encode($dat);
			}
			$this->bulkItems = array();
			return $this->stream('_bulk', 'POST', $rules, implode("\n", $bulkData)."\n");
		}
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
		
		function makeQuery($opts)	{
			if(count($opts) > 0)	{
				foreach($opts as $dex=>$dat)	{
					$query .= $dex.'='.$dat.'&';
				}
				return '?'.substr($query, 0, -1);
			}
			return "";
		}
		
		private function stream($path, $method="GET", $rules=null, $payload="") {
			$context = stream_context_create(array('http' => array('method' => $method, 'ignore_errors' => true, 'content'=>(is_array($payload) == true) ? json_encode($payload) : $payload)));
			$stream = fopen($this->conn.$path.(($rules != null) ? $this->makeQuery($rules) : ""), 'r', false, $context);
			$this->metaStream(stream_get_meta_data($stream)['wrapper_data']); //parse the meta data from the steam
			return json_decode(stream_get_contents($stream), true); //send the normal return as normal
		}
		
		private function metaStream($meta)	{
			//first line has some info in it that not like the rest
			$chop = explode(' ', $meta[0]);
			$this->metaStream['protocol'] = $chop[0];
			$this->metaStream['statusCode'] = $chop[1];
			$this->metaStream['statusText'] = implode(' ', array_slice($chop, 2));
			
			unset($meta[0]); //remvove it since we took care of it
			foreach($meta as $dat)	{ //loop and collect the rest of the data
				$chop = explode(': ', $dat);
				$this->metaStream[$chop[0]] = $chop[1];
			}
		}
		
		
	}
	
	class elasticSearchDSL	{
		public $DSL = array();
		
		function __construct($data, $type='json')	{
			return $this->import($data, $type);
		}
		
		function add($type, $key, $value)	{
			$this->DSL[$type][$key] = $value;
			return $this;
		}
		
		function import($data, $type='json')	{
			$this->DSL = json_decode($data, true);
		}
		
		function export($type='json')	{
			return $this->DSL;
		}
	}
	
	class elasticSearchResults	{
		public $hits = array();
		public $shards = array();
		public $stats = array();
		public $data = array();
		public $total = array();
		
		function __construct($result)	{
			$this->data = $result['hits']['hits'];
			unset($result['hits']['hits']);
			$this->hits = $result['hits'];
			$this->shards = $result['_shards'];
			$this->stats = array('took' => $result['took'], 'timed_out' => $result['timed_out']);
			$this->total =& $this->hits['total'];
		}
	}
	
	/*(class elasticSearch_mapReduce	{
		public $workingSet = array();
		
		function __construct($url='http://127.0.0.1', $port=9200, $index=null, $type=null)	{
			$es = new elasticSearch($url, $port, $index, $type);
		}
		
		function query($dsl, $rules, $output='query')	{
			$this->workingSet[$output] = new elasticSearchResults($this->es->search($dsl))->data;
		}
		function map($func, $args, $input='query', $output='map')	{
			$this->workingSet[$output] = array_map($func, $this->workingSet[$input], $args); 
		}
		
		function reduce($func, $input='map', $output='reduce') {
			$this->reduce[$output] = array_reduce($this->workingSet[$input], $func);
		}
	}*/
