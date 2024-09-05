<?php

declare(strict_types=1);

namespace DIH;

class Solr {
    const SOLR_HOST = 'localhost';
    const SOLR_PORT = 8983;
    const SOLR_PATH = '/solr/%s';
		
    private $client = null;
    
    public function __construct(string $core) { //{{{
        $opts = array('hostname' => self::SOLR_HOST,
                      'port' => self::SOLR_PORT,
                      'path' => sprintf(self::SOLR_PATH, $core));
        
        if (!$this->client = new \SolrClient($opts)) {
            throw new \Exception('Problem creating Solr object');
        }
    }
    //}}}
    
    // add array of documents to Solr
    public function addDocuments(array $docs) { //{{{
        foreach ($docs as $doc) {
						if (!$this->client->addDocument($doc)->success()) {
								throw new \Exception('Problem adding document to Solr');
						}
        }
        
        $this->client->commit();
    }
    //}}}
}

?>
