<?php

declare(strict_types=1);

namespace DIH;

require_once 'DB.php';
require_once 'Solr.php';
require_once 'DIH.php';

function usage() { //{{{
		printf('Usage: %s solrCore /path/to/data-config.xml action optionalValue%s',
					 $_SERVER['argv'][0], PHP_EOL);
}
//}}}

// need arguments on command line to run
if ($_SERVER['argc'] < 3) {
		usage();
		throw new \Exception('Command line arguments not given');
}

// get core name and path to DIH XML config
$core = $_SERVER['argv'][1];
$dihFile = $_SERVER['argv'][2];

// action, defaults to query
$action = isset($_SERVER['argv'][3]) ? $_SERVER['argv'][3] : 'query';
// optional value for other actions, ID or timestamp
$option = isset($_SERVER['argv'][4]) ? $_SERVER['argv'][4] : '';

$dih = new DIH($dihFile, $core);
$dih->import($action, $option);

?>