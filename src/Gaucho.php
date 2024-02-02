<?php

namespace Gaucho;

use Gaucho\Chaplin;
use Gaucho\Env;
use Gaucho\Mig;
use Gaucho\Route;
use Medoo\Medoo;
use mysqli;

class Gaucho
{
    function chaplin($name, $data = [], $print = true)
    {
        $Chaplin = new Chaplin();
        $filename = ROOT . '/view/' . $name . '.html';
        $rendered = $Chaplin->renderFromFile($filename, $data);
        if ($print) {
            return print $rendered;
        } else {
            return $rendered;
        }
    }

    private function createMysqlDB(mixed $id)
    {
        $prefix = 'DB' . $id;
        $host = $_ENV[$prefix . '_HOST'];
        $user = $_ENV[$prefix . '_USERNAME'];
        $password = $_ENV[$prefix . '_PASSWORD'];
        $dbname = $_ENV[$prefix . '_DATABASE'];
        if (!$this->dbMysqlExists($host, $user, $password, $dbname)) {
            $conn = new mysqli($host, $user, $password);
            $sql = 'CREATE DATABASE ' . $dbname . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;';
            if (mysqli_query($conn, $sql)) {
                print 'db "' . $dbname . '" criado com sucesso' . PHP_EOL;
            }
        }
    }

    function createSQLiteDB($id)
    {
        $prefix = 'DB' . $id;
        $filename = $_ENV[$prefix . '_DATABASE'];
        if (!file_exists($filename)) {
            $dir = ROOT . '/db';
            if (!file_exists($dir)) {
                if (mkdir($dir)) {
                    print 'dir db criado com sucesso'.PHP_EOL;
                } else {
                    print 'erro ao criar o dir dr ';
                    die(PHP_EOL);
                }
            }
            system('touch '.$filename);
            if (file_exists($filename)) {
                print 'db "' . $filename . '" criado com sucesso' . PHP_EOL;
            } else {
                print 'erro ao criar o db ' . $filename;
                die(PHP_EOL);
            }
        }
    }

    function db($id = false)
    {
        if (!$id) {
            $id = @$_ENV['DB_ID'];
        }
        $prefix = 'DB' . $id;
        $type = @$_ENV[$prefix . '_TYPE'];
        if ($type == 'mysql') {
            return new Medoo([
                'type' => 'mysql',
                'host' => @$_ENV[$prefix . '_HOST'],
                'database' => @$_ENV[$prefix . '_DATABASE'],
                'username' => @$_ENV[$prefix . '_USERNAME'],
                'password' => @$_ENV[$prefix . '_PASSWORD'],
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'port' => 3306
            ]);
        }
        if ($type == 'sqlite') {
            $database = @$_ENV[$prefix . '_DATABASE'];
            $database = ROOT .'/'. $database;
            return new Medoo([
                'type' => 'sqlite',
                'database' => $database
            ]);
        }
        die('DB' . $id . ' not found');
    }

    function dbMysqlExists($host, $user, $password, $dbname)
    {
        $conn = new mysqli($host, $user, $password);
        if (empty (mysqli_fetch_array(mysqli_query($conn, "SHOW DATABASES LIKE '$dbname'")))) {
            return false;
        } else {
            return $conn;
        }
    }

    function dir($dir)
    {
        $dirs = $this->dirs();
        if (isset($dirs[$dir])) {
            return $dirs[$dir];
        } else {
            return false;
        }
    }

    function dirs()
    {
        if ($this->isCli()) {
            return false;
        } else {
            $uri = $this->getNormalUri();
            $dirs = explode('/', $uri);
            $dirs = array_filter($dirs);
            if (empty($dirs)) {
                return ['1' => '/'];
            }
            return $dirs;
        }
    }

    function isCli()
    {
        if (php_sapi_name() == "cli") {
            return true;
        } else {
            return false;
        }
    }

    function getNormalUri()
    {
        $scheme = $_SERVER['REQUEST_SCHEME'];
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER["REQUEST_URI"];
        $uri = explode('?', $uri)[0];
        $full = $scheme . '://' . $host . $uri;
        $env = @$_ENV['SITE_URL'];
        if ($env) {
            $uri = explode($env, $full)[1];
            if (empty($uri)) {
                return '/';
            } else {
                return $uri;
            }
        } else {
            return $uri;
        }
    }

    function mig($id = false)
    {
        $prefix = 'DB' . $id;
        $dbType = @$_ENV[$prefix . '_TYPE'];
        if ($dbType == 'mysql') {
            $this->createMysqlDB($id);
        }
        if ($dbType == 'sqlite') {
            $this->createSQLiteDB($id);
        }
        $db = $this->db($id);
        $pdo = $db->pdo;
        $tableDirectory = glob(ROOT . '/table');
        $Mig = new Mig($pdo, $tableDirectory, $dbType);
        $Mig->mig();
    }

    function run($routes = false)
    {
        new Env(ROOT . '/.env');
        ini_set("memory_limit", $_ENV['SITE_MEMORY']);
        $this->showErrors($_ENV['SITE_ERRORS']);
        if ($routes) {
            new Route($routes);
        }
    }

    function showErrors($bool)
    {
        if ($bool) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            error_reporting(0);
        }
    }
}