<?php

declare(strict_types=1);

namespace DIH;

class DB {
    private $conn = null;
    private static $instance = null;
    
    public function __construct(string $dsn, string $user, string $password) { //{{{
        if (!$this->conn = new \PDO($dsn, $user, $password)) {
            throw new \Exception('Problem connecting to database');
        }
    }
    //}}}

		// run query
		public function query(string $sql) : array { //{{{
				$stmt = $this->conn->prepare($sql);
				
				if (!$stmt->execute()) {
						throw new \Exception('Problem with running query');
				}
				
				return $stmt->fetchAll(\PDO::FETCH_ASSOC);
		}
		//}}}
}
