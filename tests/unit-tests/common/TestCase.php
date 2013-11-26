<?php
namespace Clevis\DatabaseBackup\Tests;

use Mockery;
use Tester;


abstract class TestCase extends Tester\TestCase
{

	protected function tearDown()
	{
		parent::tearDown();
		Mockery::close();
	}

}
