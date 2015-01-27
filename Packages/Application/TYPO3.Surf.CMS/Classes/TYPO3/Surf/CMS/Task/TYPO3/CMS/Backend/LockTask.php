<?php
namespace TYPO3\Surf\CMS\Task\TYPO3\CMS\Backend;

use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\InvalidConfigurationException;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;

use TYPO3\Flow\Annotations as Flow;

/**
 * Class Lock
 * @package TYPO3\Surf\CMS\Task\TYPO3\CMS
 */
class LockTask extends Task {
    /**
     * @Flow\Inject
     * @var \TYPO3\Surf\Domain\Service\ShellCommandService
     */
    protected $shell;

    /**
     * Execute this task
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @return void
     * @throws InvalidConfigurationException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
        $currentPath = $application->getDeploymentPath() . '/releases/current';
        if(is_dir($currentPath)) {
           $commands[] = "'{$currentPath}/typo3/cli_dispatch.phpsh' lowlevel_admin setBElock";
           $this->shell->executeOrSimulate($commands, $node, $deployment);
        }
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
     * Rollback the task
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @return void
     */
    public function rollback(Node $node, Application $application, Deployment $deployment, array $options = array()) {
        $currentPath = $application->getDeploymentPath() . '/releases/current';
        $commands[] = "'{$currentPath}/typo3/cli_dispatch.phpsh' lowlevel_admin clearBElock";

        $this->shell->executeOrSimulate($commands, $node, $deployment);
    }
}