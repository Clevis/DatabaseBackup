# DatabaseBackup Tool

Tiny wrapper around [Clevis/MySQLDump](https://github.com/Clevis/MySQL-dump) library which can limit the maximum number of kept backups. The kept backups are efficiently distributed over the whole period of time this tool is being used. The older the backups are the larger time gap is kept between them.

**Visualisation of backups distribution in time**

![Backups distribution in time](docs/images/time-distribution.png)


## Installation

The recommended way to install this library is to use [Composer](http://getcomposer.org/).

	composer install clevis/database-backup


## Basic Usage

	$mysqli = new mysqli('localhost', 'root', 'password', 'database');
	$dumper = new MySQLDump($mysqli);

	// store backups to directory './backups' and keep at most 100 backups
	$backup = new Clevis\DatabaseBackup\DatabaseBackup($dumper, __DIR__ . '/backups', 100);
	$backup->backupDatabase();

If you use [dibi](http://dibiphp.com), then you can get the `$mysqli` object this way:

	$mysqli = $dibiConnection->getDriver()->getResource();


## Advanced Usage

You can customize the created dumps by configuring the `$dumper` instance.

1. Do not dump table `foo`:

		$dumper->tables['foo'] = $dumper::NONE;

2. Dump only structure, but not data of table `foo`:

		// CREATE TABLE `foo` (...)
		$dumper->tables['foo'] = $dumper::CREATE;

		// DROP TABLE `foo` IF EXISTS + CREATE TABLE `foo` (...)
		$dumper->tables['foo'] = $dumper::CREATE | $dumper::DROP;

3. Dump only some rows in table `foo`:

		$dumper->setCustomDataSelect('foo', 'SELECT * FROM `foo` WHERE `bar` = 1');

4. Do not lock tables while dumping tables:

		$dumper->setUseLock(FALSE);

5. For more examples see [documentation of Clevis/MySQLDump](https://github.com/Clevis/MySQL-dump) library.
