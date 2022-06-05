<?php

namespace Anet\Controllers;

use Symfony\Component\Console\Command;
use Symfony\Component\Console\Formatter;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Anet\App\Bots;
use Anet\App\Helpers;
use Anet\App\YouTubeHelpers;
use Google\Service;

/**
 * **Cli** -- base realization of cli run boys by \Symfony\Component\Console\Command\Command
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
final class Cli extends Command\Command
{
    protected static $defaultName = 'bot:start';
    protected static $defaultDescription = 'start new bot session';

    /**
     * @var string[] list of current bots
     */
    private const BOTS = [
        'YOUTUBE' => 'youtube',
    ];

    protected function configure(): void
    {
        $this
            ->setHelp('This command starts in cli new bot session with specified parameters')
            ->addArgument('name', Input\InputArgument::REQUIRED, 'name of current bot')
            ->addArgument('params', Input\InputArgument::OPTIONAL, 'bot params')
            ->addArgument('mode', Input\InputArgument::OPTIONAL, 'mode for starting');
    }

    protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
    {
        $output->getFormatter()->setStyle('note', new Formatter\OutputFormatterStyle('#FFA500', null, ['bold', 'blink']));
        $output->getFormatter()->setStyle('erno', new Formatter\OutputFormatterStyle('red', null, ['bold', 'blink']));

        switch ($input->getArgument('name')) {
            case self::BOTS['YOUTUBE']:
                return $this->startYoutubeBot($input, $output);
        }

        $output->writeln([
            '<note>Unknown bot name, you can run following bot:</note>',
            ...array_map(fn ($name) => "<info>$name</info>",self::BOTS),
        ]);

        return Command\Command::FAILURE;
    }

    /**
     * **Method** is cli handler for statring youtube bot
     * @param \Symfony\Component\Console\Input\InputInterface $input console input
     * @param \Symfony\Component\Console\Output\OutputInterface $output console output
     * @return int exit code
     */
    private function startYoutubeBot(Input\InputInterface $input, Output\OutputInterface $output) : int
    {
        if ($input->getArgument('params') === null) {
            $output->writeln([
                '<error>    ------------------------------------------------------    </error>',
                '<error>    second argument $argv must be specified by Youtube url    </error>',
                '<error>    ------------------------------------------------------    </error>',
            ]);

            return Command\Command::INVALID;
        }

        $connectParams = new YouTubeHelpers\ConnectParams(
            YOUTUBE_APP_NAME,
            YOUTUBE_CLIENT_SECRET_JSON,
            YOUTUBE_OAUTH_TOKEN_JSON
        );
        $spareConnection = true;

        do {
            $restart = false;

            try {
                $youtubeBot = new Bots\YouTube($connectParams, $input->getArgument('params'));
            } catch (Service\Exception $error) {
                $output->writeln(sprintf('<erno>%s</erno>', $error->getMessage()));
                return Command\Command::INVALID;
            }

            if ($input->getArgument('mode') !== null) {
                switch ($input->getArgument('mode')) {
                    case 'test_connect':
                        $youtubeBot->testConnect();
                        return Command\Command::SUCCESS;
                    case 'test_send':
                        $youtubeBot->testSend();
                        return Command\Command::SUCCESS;
                    default:
                        $output->writeln([
                            '<error>-------------------------------------------------------------------------</error>',
                            '<error>                 Incorrect bot check parameter.                          </error>',
                            '<error>Enter "test_connect" to check the connection and display the current chat</error>',
                            '<error>        Or "test_send" to check if the message has been sent             </error>',
                            '<error>-------------------------------------------------------------------------</error>',
                        ]);
                        return Command\Command::INVALID;
                }
            }

            $youtubeBot->listen(15);

            $error = json_decode((string) Helpers\Logger::fetchLastNode($youtubeBot->getName(), 'error')->message, true)['error'] ?? null;

            if ($youtubeBot->isListening()) {
                if ($error !== null && $error['code'] === 401 && mb_strpos($error['message'], 'Request had invalid authentication credentials. Expected OAuth 2 access token') !== false) {
                    unset($youtubeBot);
                    sleep(60);
                    Helpers\Logger::print('System', 'system restarting script by code <failed oAuth>');
                    $restart = true;
                } elseif($spareConnection && $error !== null && $error['code'] === 403 && mb_strpos($error['message'], 'The request cannot be completed because you have exceeded your') !== false) {
                    $spareConnection = false;
                    unset($youtubeBot);
                    sleep(10);
                    Helpers\Logger::print('System', 'system restarting script by code <exceeded quota>');
                    $connectParams = new YouTubeHelpers\ConnectParams(
                        YOUTUBE_APP_NAME_RESERVE,
                        YOUTUBE_CLIENT_SECRET_JSON_RESERVE,
                        YOUTUBE_OAUTH_TOKEN_JSON_RESERVE
                    );
                    $restart = true;
                }
            }
        } while ($restart);

        return Command\Command::SUCCESS;
    }
}
