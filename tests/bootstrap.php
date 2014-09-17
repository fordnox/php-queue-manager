<?php
error_reporting(E_ALL);
require __DIR__ . '/../vendor/autoload.php';

function getConnection()
{
    static $dbh = null;
    if(null === $dbh) {
        $structure = file_get_contents(__DIR__ . '/../extra/structure.sqlite.sql');
        $dbh = new PDO('sqlite::memory:');

//        $structure = file_get_contents(__DIR__ . '/../extra/structure.mysql.sql');
//        $dbh = new PDO('mysql:host=localhost;dbname=queue', 'root', 'root');

        $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
        $dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $dbh->exec($structure);
    }
    return $dbh;
}