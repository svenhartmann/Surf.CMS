<?php
namespace TYPO3\Surf\CMS\Task;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf.CMS".*
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Composer\Exception\InvalidConfigurationException;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Task;

/**
 * This task dumps a complete database from a source system to a target system
 */
class DumpDatabaseTask extends Task {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * @var array
	 */
	protected $requiredOptions = array('sourceHost', 'sourceUser', 'sourcePassword', 'sourceDatabase','targetHost', 'targetUser', 'targetPassword', 'targetDatabase');

	/**
	 * Execute this task
	 *
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Domain\Model\Application $application
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @throws InvalidConfigurationException
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$this->assertRequiredOptionsExist($options);

		$username = isset($options['username']) ? $options['username'] . '@' : '';
		$hostname = $node->getHostname();
		$port = $node->hasOption('port') ? '-P ' . escapeshellarg($node->getOption('port')) : '';

		$mysqlInsertOnTargetCmd = "mysql -h {$options['targetHost']} -u{$options['targetUser']} -p{$options['targetPassword']} {$options['targetDatabase']}";
		$mysqlTargetCommand = isset($options['mysqlTargetIsRemoteHost']) ? "ssh {$port} {$username}{$hostname} '{$mysqlInsertOnTargetCmd}'" : $mysqlInsertOnTargetCmd;
		$commands[] = "mysqldump -h {$options['sourceHost']} -u{$options['sourceUser']} -p{$options['sourcePassword']} {$options['sourceDatabase']} | {$mysqlTargetCommand} ";

		$localhost = new Node('localhost');
		$localhost->setHostname('localhost');

		$this->shell->executeOrSimulate($commands, $localhost, $deployment);
	}

	/**
	 * Simulate this task
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function simulate(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$this->execute($node, $application, $deployment, $options);
	}

	/**
	 * @param array $options
	 * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
	 */
	protected function assertRequiredOptionsExist(array $options) {
		foreach ($this->requiredOptions as $optionName) {
			if (!isset($options[$optionName])) {
				throw new \TYPO3\Surf\Exception\InvalidConfigurationException(sprintf('Required option "%s" is not set!', $optionName), 1405592631);
			}
		}
	}
}
