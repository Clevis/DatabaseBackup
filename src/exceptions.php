<?php
namespace Clevis\DatabaseBackup;


/**
 * Base class for exceptions thrown by this library
 */
abstract class Exception extends \Exception
{

}

/**
 * Exception that represents error in the program logic. This kind of exceptions should directly lead to a fix in your code.
 */
class LogicException extends Exception
{

}

/**
 * Exception thrown if an error which can only be found in runtime and therefore can not be prevented.
 */
abstract class RuntimeException extends Exception
{

}

/**
 * The exception that is thrown when an I/O error occurs.
 */
class IOException extends RuntimeException
{

}
