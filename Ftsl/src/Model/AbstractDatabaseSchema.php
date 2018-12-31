<?php
/**
 * @link      http://github.com/ftslorgbr/framework for the canonical source repository
 * @copyright Copyleft 2018 FTSL. (http://www.ftsl.org.br)
 * @license   https://www.gnu.org/licenses/agpl-3.0.en.html GNU Affero General Public License
 */
namespace Ftsl\Model;

use Fgsl\Mock\Db\Adapter\Mock;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Metadata\Source\Factory;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Ddl\Column\Boolean;
use Zend\Db\Sql\Ddl\Column\Date;
use Zend\Db\Sql\Ddl\Column\Integer;
use Zend\Db\Sql\Ddl\Column\Time;
use Zend\Db\Sql\Ddl\Column\Varchar;
use Zend\Db\Sql\Ddl\Constraint\PrimaryKey;
use Zend\Db\Sql\Ddl\Column\Blob;

abstract class AbstractDatabaseSchema {

    /**
     * Returns false if some table doesn't exist
     * @param AdapterInterface $adapter
     * @return boolean
     */
    public static function checkTables($adapter) {
        if ($adapter instanceof Mock) { // test don't check tables
            return true;
        }

        $schema = self::getSchema();

        $metadata = Factory::createSourceFromAdapter($adapter);
        $tableNames = $metadata->getTableNames();

        foreach ($schema as $tableName => $tableSchema) {
            if (!in_array($tableName, $tableNames)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param AdapterInterface $adapter
     * @param boolean $verbose
     * @param Logger | null $log
     */
    public static function createTables(AdapterInterface $adapter, $verbose = false, $log = null) {
        $schema = self::getSchema();

        $metadata = Factory::createSourceFromAdapter($adapter);
        $tableNames = $metadata->getTableNames();
        $sql = new Sql($adapter);

        foreach ($schema as $tableName => $tableSchema) {
            if (in_array($tableName, $tableNames)) {
                if ($verbose && $log != null) {
                    $log->info("Table $tableName already exists!");
                }
                continue;
            }

            $table = new CreateTable($tableName);
            foreach ($tableSchema['fields'] as $fieldName => $field) {
                $field[3] = (array_key_exists(3,$field) ?  $field[3] : []);
                $column = $field[0];
                $table->addColumn(new $column($fieldName, $field[1], $field[2], $field[3]));
            }
            foreach ($tableSchema['constraints'] as $constraint => $value) {
                $table->addConstraint(new $constraint($value));
            }
            if ($verbose && $log != null) {
                $log->info($sql->buildSqlString($table));
            }

            $adapter->query($sql->buildSqlString($table), $adapter::QUERY_MODE_EXECUTE);
        }
    }

    /**
     * [
     *     '(table name)' => [
     *         'fields' => [
     *             '(field name)' => [(type class), (nullable), (default), (options)]
     *         ]
     *     ]
     * ]
     * @return multitype:multitype:multitype:string  multitype:multitype:boolean NULL string multitype:string   multitype:number boolean NULL string    multitype:multitype:multitype:string   multitype:multitype:boolean NULL string    multitype:multitype:string  multitype:multitype:boolean NULL string multitype:string   multitype:boolean NULL string    multitype:multitype:string  multitype:multitype:boolean NULL string multitype:string   multitype:number boolean NULL string  multitype:boolean NULL string
     */
    abstract public static function getSchema();
}