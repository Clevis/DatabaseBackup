<?php
namespace Clevis\DatabaseBackup\Tests;

use Clevis\DatabaseBackup\DatabaseBackup;
use Mockery;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';


/**
 * @testCase
 */
class DatabaseBackupTest extends TestCase
{

	/** @var \MySQLDump|\Mockery\MockInterface */
	private $dumper;

	/** @var DatabaseBackup */
	private $tool;

	protected function setUp()
	{
		parent::setUp();
		date_default_timezone_set('UTC');
		$this->dumper = Mockery::mock('MySQLDump');
		$this->tool = new DatabaseBackup($this->dumper, 'bd', 7);
	}

	public function testDefaultExtension()
	{
		Assert::same('sql.gz', $this->tool->getExtension());
	}

	public function testBackupDatabase()
	{
		$mock = $this->getMock(array('getDumpName'));
		$mock->shouldReceive('getDumpName')->withNoArgs()->once()->andReturn('2013-01-01-05-00-00.sql.gz');

		touch(TEMP_DIR . '/2013-01-01-01-00-00.sql.gz');
		touch(TEMP_DIR . '/2013-01-01-02-00-00.sql.gz');
		touch(TEMP_DIR . '/2013-01-01-03-00-00.sql.gz');
		touch(TEMP_DIR . '/2013-01-01-04-00-00.sql.gz');
		touch(TEMP_DIR . '/abc.sql.gz');
		touch(TEMP_DIR . '/abc.xyz');

		$this->dumper->shouldReceive('save')->once()->with(TEMP_DIR . '/2013-01-01-05-00-00.sql.gz')
			->andReturnUsing(function () {
				touch(TEMP_DIR . '/2013-01-01-05-00-00.sql.gz');
			});
		$mock->setMaxBackups(3);
		$mock->setBackupDir(TEMP_DIR);
		$mock->backupDatabase();

		Assert::equal(
			array(
				array('path' => TEMP_DIR . '/2013-01-01-01-00-00.sql.gz', 'time' => 1357002000),
				array('path' => TEMP_DIR . '/2013-01-01-03-00-00.sql.gz', 'time' => 1357009200),
				array('path' => TEMP_DIR . '/2013-01-01-05-00-00.sql.gz', 'time' => 1357016400),
			),
			Access($mock, 'getExistingDumps')->call()
		);
	}

	public function testGetDumpName()
	{
		$method = Access($this->tool, 'getDumpName');

		$this->tool->setExtension('a');
		Assert::same('1970-01-01-00-00-00.a', $method->call(0));

		$this->tool->setExtension('b');
		Assert::same('1970-01-01-00-30-00.b', $method->call(1800));
	}

	public function testGetTime()
	{
		$method = Access($this->tool, 'getTime');

		Assert::same(0, $method->call('1970-01-01-00-00-00.sql.gz'));
		Assert::same(0, $method->call('1970-01-01-00-00-00.xyz'));
		Assert::false($method->call('1900-01-01-00-00-00.sql.gz'));
		Assert::false($method->call('foo'));
	}

	public function testReduceBackupsCount()
	{
		$mock = $this->getMock(array('getExistingDumps', 'getTheLeastValuableBackup'));

		$mock->shouldReceive('getExistingDumps')->once()->andReturn(array(

		));

		$method = Access($mock, 'reduceBackupsCount');
		$method->call();
	}

	public function testGetTheLeastValuableBackup()
	{
		$method = Access($this->tool, 'getTheLeastValuableBackup');

		Assert::same(1, $method->call(array(
			0 => array('time' => 0),
			1 => array('time' => 1),
			2 => array('time' => 2),
			3 => array('time' => 3),
			4 => array('time' => 4),
		)));

		Assert::same(2, $method->call(array(
			0 => array('time' => 0),
			1 => array('time' => 2),
			2 => array('time' => 3),
			3 => array('time' => 4),
		)));

		Assert::same(1, $method->call(array(
			0 => array('time' => 0),
			1 => array('time' => 2),
			2 => array('time' => 4),
		)));
	}

	public function testGetExistingDumps()
	{
		$method = Access($this->tool, 'getExistingDumps');
		$this->tool->setBackupDir(TEMP_DIR);

		touch(TEMP_DIR . '/2013-01-01-01-00-00.sql.gz');
		touch(TEMP_DIR . '/2013-01-01-02-00-00.sql.gz');
		touch(TEMP_DIR . '/2013-01-01-03-00-00.sql.gz');
		touch(TEMP_DIR . '/abc.sql.gz');
		touch(TEMP_DIR . '/abc.xyz');

		Assert::same(
			array(
				array('path' => TEMP_DIR . '/2013-01-01-01-00-00.sql.gz', 'time' => 1357002000),
				array('path' => TEMP_DIR . '/2013-01-01-02-00-00.sql.gz', 'time' => 1357005600),
				array('path' => TEMP_DIR . '/2013-01-01-03-00-00.sql.gz', 'time' => 1357009200),
			),
			$method->call()
		);
	}

	/**
	 * @return DatabaseBackup|Mockery\MockInterface
	 */
	private function getMock(array $mockedMethods)
	{
		$mock = Mockery::mock(
			'Clevis\DatabaseBackup\DatabaseBackup[' . implode(',', $mockedMethods) . ']',
			array($this->dumper, 'bd', 2)
		);
		$mock->shouldAllowMockingProtectedMethods();
		return $mock;
	}

}

run(new DatabaseBackupTest);
