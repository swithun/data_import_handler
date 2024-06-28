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
    
    // add single document to Solr
    private function addDocument(\SolrInputDocument $doc, string $pk) { //{{{
        $id = $doc->getField($pk);
        
        // add document
        if (!$this->client->addDocument($doc)->success()) {
            throw new \Exception('Problem adding document to Solr');
        }
        
        //return $id;
    }
    //}}}
    
    // add array of documents to Solr
    public function addDocuments(array $docs, string $pk)  { //{{{
        $ids = array();
        
        foreach ($docs as $doc) {
            //$ids[] = $this->addDocument($doc, $pk);
            $this->addDocument($doc, $pk);
        }
        
        $this->client->commit();
        
        //return $ids;
    }
    //}}}
}

?>
