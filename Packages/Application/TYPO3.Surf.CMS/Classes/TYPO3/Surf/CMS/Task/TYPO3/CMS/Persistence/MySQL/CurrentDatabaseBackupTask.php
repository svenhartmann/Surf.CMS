<?php
namespace TYPO3\Surf\CMS\Task\TYPO3\CMS\Persistence\MySQL;
/**
 * Created by PhpStorm.
 * User: shartmann
 * Date: 26.01.15
 * Time: 19:51
 */
use TYPO3\Flow\Composer\Exception\InvalidConfigurationException;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Task;

class CurrentDatabaseBackupTask extends Task {
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
        $mysqlDump = 'mysqldump -u' . $options['targetUser'] . ' -p' . $options['targetPassword'] . ' -h' . $options['targetHost'];
        $mysqlDump .= ' --default-character-set=utf8 --opt --skip-lock-tables --skip-add-locks --lock-tables=false ' . $options['targetDatabase'] . ' > '.$currentPath.'/backup.sql';

        $commands[] = $mysqlDump;

        $this->shell->executeOrSimulate($commands, $node, $deployment);
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
        $commands[] = 'mysql -u' . getenv('DB_TARGET_USER') . ' -p' . getenv('DB_TARGET_PASS') . ' -h' . getenv('DB_TARGET_HOST') . getenv('DB_TARGET_DBNAME') . ' < '.$currentPath.'/backup.sql;';

        $this->shell->executeOrSimulate($commands, $node, $deployment);
    }
}