<?php

declare(strict_types=1);

namespace DIH;

require_once 'DB.php';
require_once 'Solr.php';

function dbConnectionDetails(string $url) : string { //{{{
		$matches = [];
		if (1 !== preg_match('/jdbc:([a-z]+):\/\/([^\/:]+):?([0-9]*)\/(.*)/', 
												 $url, $matches)) {
				exit(3);
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

// turn <field column="surname" name="surname_t"/> into surname => surname_t
function fieldMappings(\DomNodelist $fields) : array { //{{{
		$arr = [];
		foreach ($fields as $f) {
				$arr[$f->getAttribute('column')] = $f->getAttribute('name');
		}
		
		return $arr;
}
//}}}

// create SolrInputDocuments from DB rows and mapping
function solrDocs(array $fields, array $rows) : array { //{{{
		$docs = [];
		
		foreach ($row as $r) {
				$doc = new \SolrInputDocument();
				foreach ($r as $col => $val) {
						$doc->addField($fields[$col], $val);
				}
				
				$docs[] = $doc;
		}
		
		return $docs;
}
//}}}

// need arguments on command line to run
if ($_SERVER['argc'] < 3) {
		exit(1);
}

// get core name and path to DIH XML config
$core = $_SERVER['argv'][1];
$dihFile = $_SERVER['argv'][2];

$dom = new \DomDocument();
if (!$dom->load($dihFile)) {
		exit(2);
}

// use XPath to locate nodes of interest
$xp = new \DomXPath($dom);

// connect to database using details in dataSource element
$dataSource = $xp->query('/dataConfig/dataSource[@type="JdbcDataSource"]')->item(0);

$dsn = dbConnectionDetails($dataSource->getAttribute('url'));

$db = new DB($dsn,
						 $dataSource->getAttribute('user'),
						 $dataSource->getAttribute('password'));

// connect to Solr using given core
$solr = new Solr($core);

$entities = $xp->query('/dataConfig/document/entity');

// loop over entity nodes
foreach ($entities as $e) {
		$sql = $e->getAttribute('query');
		$pk = $e->getAttribute('pk');
		
		$fields = $xp->query('field', $e);
		$fieldMap = fieldMapping($fields);
		
		// create Solr input documents using column/field mappings
		$docs = solrDocs($fieldMap, $db->query($sql));
		
		$solr->addDocuments($docs, $pk);
}

?>