<?php
namespace Clevis\DatabaseBackup;

use MySQLDump;


class DatabaseBackup
{

	/** @var MySQLDump */
	protected $dumper;

	/** @var string */
	protected $backupDir;

	/** @var int */
	protected $maxBackups;

	/** @var string */
	protected $extension = 'sql.gz';

	/**
	 * @param MySQLDump
	 * @param string directory where backup will be stored
	 * @param int maximum number of backup which we keep in $backupDir
	 */
	public function __construct(MySQLDump $dumper, $backupDir, $maxBackups)
	{
		$this->dumper = $dumper;
		$this->backupDir = $backupDir;
		$this->maxBackups = $maxBackups;
	}

	/**
	 * @return MySQLDump
	 */
	public function getDumper()
	{
		return $this->dumper;
	}

	/**
	 * @return string
	 */
	public function getBackupDir()
	{
		return $this->backupDir;
	}

	/**
	 * @param string
	 */
	public function setBackupDir($backupDir)
	{
		$this->backupDir = $backupDir;
	}

	/**
	 * @return string
	 */
	public function getExtension()
	{
		return $this->extension;
	}

	/**
	 * @param string
	 */
	public function setExtension($extension)
	{
		$this->extension = $extension;
	}

	/**
	 * @return int
	 */
	public function getMaxBackups()
	{
		return $this->maxBackups;
	}

	/**
	 * @param int
	 */
	public function setMaxBackups($maxBackups)
	{
		$this->maxBackups = $maxBackups;
	}

	/**
	 * @return void
	 * @throws \Clevis\DatabaseBackup\IOException
	 */
	public function backupDatabase()
	{
		$path = $this->backupDir . '/' . $this->getDumpName();
		$this->dumper->save($path);
		$this->reduceBackupsCount();
	}

	/**
	 * Returns dump name based on current or given time. This is reverse function for getTime().
	 *
	 * @param  int|NULL timestamp or null for current time
	 * @return string
	 */
	protected function getDumpName($time = NULL)
	{
		if ($time === NULL) $time = time();
		return date('Y-m-d-H-i-s', $time) . '.' . $this->extension;
	}

	/**
	 * Determines timestamp from dump's filename. This is reverse function for getDumpName().
	 *
	 * @param  string
	 * @return int|FALSE
	 */
	protected function getTime($filename)
	{
		if (!preg_match('#^\d\d\d\d(?:-\d\d){5}#', $filename)) return FALSE;
		return strtotime(substr($filename, 0, 10) . ' ' . str_replace('-', ':', substr($filename, 11, 8)));
	}

	/**
	 * Intelligently decreases the number of existing backups to keep it less or equal to $maxBackups by deleting
	 * the least valuable backups.
	 *
	 * @return void
	 * @throws \Clevis\DatabaseBackup\IOException
	 */
	protected function reduceBackupsCount()
	{
		$dumps = $this->getExistingDumps();
		if (count($dumps) > $this->maxBackups)
		{
			usort($dumps, function ($a, $b) {
				return $a['time'] - $b['time'];
			});

			do
			{
				$dumps = array_values($dumps);
				$worseDump = $this->getTheLeastValuableBackup($dumps);
				if (!@unlink($dumps[$worseDump]['path']))
				{
					throw new IOException(sprintf('Unable to delete "%s".', $worseDump['path']));
				}
				unset($dumps[$worseDump]);
			}
			while (count($dumps) > $this->maxBackups);
		}
	}

	/**
	 * Finds the least valuable backup.
	 *
	 * @param  array (# => array('path' => path, 'time' => timestamp))
	 * @return int index of the least valuable backup
	 */
	protected function getTheLeastValuableBackup(array $dumps)
	{
		$dumpsCount = count($dumps);
		$worseRating = INF;
		$worseDump = -1;
		for ($i = 1; $i < $dumpsCount; $i++)
		{
			$rating = ($dumps[$i]['time'] - $dumps[$i - 1]['time']) / ($dumpsCount - $i);
			if ($rating < $worseRating)
			{
				$worseRating = $rating;
				$worseDump = $i;
			}
		}
		return $worseDump;
	}

	/**
	 * Returns info about existing dumps.
	 *
	 * @return array (# => array('path' => path, 'time' => timestamp))
	 */
	protected function getExistingDumps()
	{
		$files = glob($this->backupDir . '/*.' . $this->extension);
		$dumps = array();
		foreach ($files as $filename)
		{
			$time = $this->getTime(basename($filename));
			if ($time !== FALSE)
			{
				$dumps[] = array(
					'path' => $filename,
					'time' => $time,
				);
			}
		}
		return $dumps;
	}

}
