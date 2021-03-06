<?php
namespace X\Service\Database\Test\Service\Query;
use X\Core\X;
use PHPUnit\Framework\TestCase;
use X\Service\Database\Database;
use X\Service\Database\Test\Util\DatabaseServiceTestTrait;
use X\Service\Database\Query;
use X\Service\Database\Driver\DatabaseDriver;
use X\Service\Database\Table\Column;
class AlterTableTest extends TestCase {
    /***/
    use DatabaseServiceTestTrait;
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown() {
        $this->cleanAllDatabase();
    }
    
    /***/
    private function doTestAlterTable( $dbName ) {
        $driver = $this->getDatabase($dbName)->getDriver();
        
        # rename
        if ( $driver->getOption(DatabaseDriver::OPT_ALTER_TABLE_RENAME, true) ) {
            $this->createTestTableUser($dbName);
            Query::alterTable($dbName)->table('users')->rename('new_users')->exec();
            $this->assertTrue(in_array('new_users', $this->getDatabase($dbName)->tableList()));
            Query::dropTable($dbName)->table('new_users')->exec();
        }
        
        # addColumn
        $this->createTestTableUser($dbName);
        $newColumn = new Column();
        $newColumn->setType(Column::T_STRING);
        Query::alterTable($dbName)->table('users')->addColumn('newCol', $newColumn)->exec();
        $columnName = 'newCol';
        if ( $driver->getOption(DatabaseDriver::OPT_UPPERCASE_COLUMN_NAME, false) ) {
            $columnName = strtoupper($columnName);
        }
        $this->assertArrayHasKey($columnName, $this->getDatabase($dbName)->columnList('users'));
        $this->dropTestTableUser($dbName);
        
        # changeColumn
        if ( $driver->getOption(DatabaseDriver::OPT_ALTER_TABLE_CHANGE_COLUMN, true) ) {
            $this->createTestTableUser($dbName);
            
            $newColumn = new Column();
            $newColumn->setType(Column::T_STRING);
            Query::alterTable($dbName)->table('users')->changeColumn('name',$newColumn)->exec();
            $columnName = 'name';
            if ( $driver->getOption(DatabaseDriver::OPT_UPPERCASE_COLUMN_NAME, false) ) {
                $columnName = strtoupper($columnName);
            }
            $this->assertArrayHasKey($columnName, $this->getDatabase($dbName)->columnList('users'));
            $this->dropTestTableUser($dbName);
        }
        
        # drop column
        if ( $this->getDatabase($dbName)->getDriver()->getOption(DatabaseDriver::OPT_ALTER_TABLE_DROP_COLUMN, true) ) {
            $this->createTestTableUser($dbName);
            Query::alterTable($dbName)->table('users')->dropColumn('name')->exec();
            $columnName = 'name';
            if ( $driver->getOption(DatabaseDriver::OPT_UPPERCASE_COLUMN_NAME, false) ) {
                $columnName = strtoupper($columnName);
            }
            $this->assertArrayNotHasKey($columnName, $this->getDatabase($dbName)->columnList('users'));
            $this->dropTestTableUser($dbName);
        }
        
        # add/drop index
        $this->createTestTableUser($dbName);
        Query::alterTable($dbName)->table('users')->addIndex('idx_001', array('name'))->exec();
        Query::alterTable($dbName)->table('users')->dropIndex('idx_001')->exec();
        $this->assertTrue(true);
    }
    
    /** */
    public function test_mysql() {
        $this->checkTestable(TEST_DB_NAME_MYSQL);
        $this->doTestAlterTable(TEST_DB_NAME_MYSQL);
    }

    /** */
    public function test_sqlite() {
        $this->checkTestable(TEST_DB_NAME_SQLITE);
        $this->doTestAlterTable(TEST_DB_NAME_SQLITE);
    }
    
    /** */
    public function test_postgresql() {
        $this->checkTestable(TEST_DB_NAME_POSTGRESQL);
        $this->doTestAlterTable(TEST_DB_NAME_POSTGRESQL);
    }
    
    /** */
    public function test_oracle() {
        $this->checkTestable(TEST_DB_NAME_ORACLE);
        $this->doTestAlterTable(TEST_DB_NAME_ORACLE);
    }
    
    /** */
    public function test_mssql() {
        $this->checkTestable(TEST_DB_NAME_MSSQL);
        $this->doTestAlterTable(TEST_DB_NAME_MSSQL);
    }
    
    /** */
    public function test_firebird() {
        $this->checkTestable(TEST_DB_NAME_FIREBIRD);
        $this->doTestAlterTable(TEST_DB_NAME_FIREBIRD);
    }
}