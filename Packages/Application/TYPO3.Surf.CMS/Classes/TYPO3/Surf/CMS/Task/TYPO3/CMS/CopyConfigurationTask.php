<?php
namespace TYPO3\Surf\CMS\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf.CMS".*
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Utility\Files;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;

use TYPO3\Flow\Annotations as Flow;

/**
 * A task to copy host/context specific configuration
 */
class CopyConfigurationTask extends \TYPO3\Surf\Domain\Model\Task {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * Executes this task
	 *
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Domain\Model\Application $application
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$options['username'] = isset($options['username']) ? $options['username'] . '@' : '';
		$targetReleasePath = $deployment->getApplicationReleasePath($application);
		$configurationPath = $deployment->getDeploymentConfigurationPath() . '/';
		if (!is_dir($configurationPath)) {
			return;
		}
		$configurations = \TYPO3\Flow\Utility\Files::readDirectoryRecursively($configurationPath);
		$commands = array();
		foreach ($configurations as $configuration) {
			$targetConfigurationPath = dirname(str_replace($configurationPath, '', $configuration));
			$deploymentCachePath = $application->getDeploymentPath().'/cache/transfer/typo3conf/';

			if ($node->isLocalhost()) {
				$commands[] = "mkdir -p '{$targetReleasePath}/Configuration/{$targetConfigurationPath}/'";
				$commands[] = "cp {$configuration} {$targetReleasePath}/Configuration/{$targetConfigurationPath}/";
				// copy configuration from Build/Surf/##NAME##/Configuration to cache and rls folder
				$commands[] = "cp {$configuration} {$deploymentCachePath}";
				$commands[] = "cp {$configuration} {$targetReleasePath}/typo3conf/";
			} else {
				$username = $options['username'];
				$hostname = $node->getHostname();
				$port = $node->hasOption('port') ? '-P ' . escapeshellarg($node->getOption('port')) : '';
				$commands[] = "ssh {$port} {$username}{$hostname} 'mkdir -p {$targetReleasePath}/Configuration/{$targetConfigurationPath}/'";
				$commands[] = "scp {$port} {$configuration} {$username}{$hostname}:{$targetReleasePath}/Configuration/{$targetConfigurationPath}/";
			}
		}

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

}
?>