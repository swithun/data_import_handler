<?php

declare(strict_types=1);

namespace DIH;

require_once 'Solr.php';
require_once 'DB.php';

class DIH {
		private $xp;
		private $db;
		private $solr;
		
		// constructor, load DIH file
		public function __construct(string $dihFile, string $core) { //{{{
				if (!file_exists($dihFile)) {
						throw new \Exception('DIH file not found');
				}
				
				$dom = new \DomDocument();
				if (!$dom->load($dihFile)) {
						throw new \Exception('DIH file not XML');
				}
				
				$this->xp = new \DomXPath($dom);

				// connect to database
				$this->db();
				
				// connect to Solr
				$this->solr = new Solr($core);
		}
		//}}}

		// do import defined by action with optional value
		public function import(string $action, string $option='') { //{{{
				// loop over entities
				foreach ($this->xp->query('/dataConfig/document/entity') as $e) {
						$this->entity($e, $action, $option);
				}
		}
		//}}}
		
		// handle SQL query and field mapping for entity
		private function entity(\DomElement $e, string $action, string $option) { //{{{
				// get SQL
				$sql = DIH::fmtSQL($e->getAttribute($action), $action, $option);
				
				// map database columns to Solr document fields
				$fields = $this->xp->query('field', $e);
				$fieldMap = DIH::fieldMappings($fields);
				
				// generate Solr input documents
				$docs = DIH::solrDocs($fieldMap, $this->db->query($sql));
				// add add to Solr
				$this->solr->addDocuments($docs,
																	$e->getAttribute('pk'));
		}
		//}}}
		
		// connect to database
		private function db() {
				$ds = '/dataConfig/dataSource[@type="JdbcDataSource"]';
				$dataSource = $this->xp->query($ds)->item(0);
				
				$dsn = DIH::dbConnectionDetails($dataSource->getAttribute('url'));
				
				$this->db = new DB($dsn,
													 $dataSource->getAttribute('user'),
													 $dataSource->getAttribute('password'));
		}
		
		// get database PDO DSN from JDBC string
		static function dbConnectionDetails(string $url) : string { //{{{
				$matches = [];
				if (1 !== preg_match('/jdbc:([a-z]+):\/\/([^\/:]+):?([0-9]*)\/(.*)/',
														 $url, $matches)) {
						throw new \Exception('Problem getting DB connection details');
				}

				$details = ['host' => $matches[2], 'dbname' => $matches[4]];
				if ($matches[3]) {
						$details['port'] = (int) $matches[3];
				}

				// put together DSN
				return sprintf('%s:%s',
											 $matches[1],
											 http_build_query($details, '', ';'));
		}
		//}}}

		// turn <field column="surname" name="surname_t"/> into surname => [surname_t]
		static function fieldMappings(\DomNodelist $fields) : array { //{{{
				$arr = [];
				foreach ($fields as $f) {
						$dbCol = $f->getAttribute('column');
						
						if (!isset($arr[$dbCol])) {
								$arr[$dbCol] = [];
						}
						
						$arr[$dbCol][] = $f->getAttribute('name');
				}

				return $arr;
		}
		//}}}

		// create SolrInputDocuments from DB rows and mapping
		static function solrDocs(array $fields, array $rows) : array { //{{{
				$docs = [];

				// loop over rows (one per document)
				foreach ($rows as $r) {
						$doc = new \SolrInputDocument();
						// loop over columns in row
						foreach ($r as $col => $val) {
								// loop over Solr document fields for given column
								foreach ($fields[$col] as $f) {
										$doc->addField($f, (string) $val);
								}
						}

						$docs[] = $doc;
				}

				return $docs;
		}
		//}}}

		// format SQL - replace possible placeholder with value
		static function fmtSQL(string $sql, string $action, string $option) : string { //{{{
				$pat = '';
				
				switch ($action) {
				 case 'deltaImportQuery':
						$pat = '${dih.delta.id}';
						break;
				 case 'deltaQuery':
						$pat = '${dih.last_index_time}';
						break;
				 default:
						break;
				}

				if ($pat) {
						$sql = str_replace($pat, $option, $sql);
				}

				return $sql;
		}
		//}}}
}

?>