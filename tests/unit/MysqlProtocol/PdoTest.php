<?php
declare(strict_types=1);
namespace MysqlProtocol;

use PHPUnit\Framework\TestCase;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/10/9
 * Time: 07:35
 */
class PdoTest extends TestCase
{
	protected $pdo;
	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$mysql_config = load_config("app");
		\Wing\Bin\Mysql::$debug = true;

		$this->pdo = new \Wing\Library\Mysql\PDO(
			$mysql_config["mysql"]["host"],
			$mysql_config["mysql"]["user"],
			$mysql_config["mysql"]["password"],
			$mysql_config["mysql"]["db_name"],
			$mysql_config["mysql"]["port"]
		);
	}

	public function testcharacter_set_name()
	{
		$this->assertNotEmpty($this->pdo->character_set_name());
	}
}