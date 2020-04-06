<?php
declare(strict_types=1);
namespace Neos\BaseDistribution\Composer;

use Composer\Console\Application;
use Composer\Script\Event;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * A composer post-create-project script to allow a choice of adding the demo site (and possible other things in the future)
 */
class InstallSitePackage
{
    /**
     * Setup the neos distribution
     *
     * @param Event $event
     * @return void
     */
    public static function setupDistribution(Event $event): void
    {
        $distributionReadyMessagesBase = [
            '',
            'Your Neos was prepared successfully.',
            '',
            'For local development you still have to:',
            '1. Add database credentials to Configuration/Development/Settings.yaml (or start the setup by calling the /setup url)',
            '2. Migrate database "./flow doctrine:migrate"',
        ];

        $io = $event->getIO();
        $io->write([
            '',
            'Welcome to Neos',
            ''
        ]);

        if (!$io->isInteractive()) {
            $io->write('Non-interactive installation, installing no additional package(s).');
            $io->write($distributionReadyMessagesBase);
            return;
        }

        $distributions = self::getInstallableDistributions();

        $choices = [];
        foreach ($distributions as $key => $distribution) {
            $choices[] = $key . ' - ' . $distribution['description'];
        }

        $selection = (int)$io->select('How would you like your Neos configured?', $choices, false);
        $selectedDistribution = array_values($distributions)[$selection];

        $output = new ConsoleOutput();
        $composerApplication = new Application();
        try {
            $composerApplication->doRun(new ArrayInput([
                'command' => 'require',
                'packages' => $selectedDistribution['packages']
            ]), $output);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Failed to require package(s) "%s": %s', $selectedDistribution['packages'], $e->getMessage()), 1586159671);
        }

        // success
        $io->write($distributionReadyMessagesBase);
        if (!empty($selectedDistribution['post-install-notice'])) {
            $io->write('3. ' . $selectedDistribution['post-install-notice']);
        }
    }

    /**
     * Returns a list of distribution packages that are installable
     *
     * @return array in the form ['<unique key>' => ['description' => '<some description>', 'packages' => ['<composer package key1>', '<composer package key2>', ...], 'post-install-notice' => '<some notice>']]
     */
    protected static function getInstallableDistributions(): array
    {
        return [
            'Bare bones' => [
                'description' => 'Minimal Installation of Neos CMS, only installing the required packages',
                'packages' => [],
                'post-install-notice' => 'Create your own site package using "./flow package:create" and "./flow site:create"'
            ],
            'Neos demo site' => [
                'description' => 'The Neos.Demo site and some suggested packages for better SEO and Redirect support',
                'packages' => ['neos/seo', 'neos/redirecthandler-neosadapter', 'neos/redirecthandler-databasestorage', 'neos/demo'],
                'post-install-notice' => 'Import site data "./flow site:import --package-key Neos.Demo"'
            ],
        ];
    }
}
