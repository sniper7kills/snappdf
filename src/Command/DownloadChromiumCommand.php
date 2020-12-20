<?php


namespace Beganovich\ChromiumPdf\Command;

use Beganovich\ChromiumPdf\Exception\PlatformNotSupported;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class DownloadChromiumCommand extends Command
{
    protected static $defaultName = 'download';

    public $revisionUrl = 'https://download-chromium.appspot.com/rev/%s?type=snapshots';

    protected function configure()
    {
        $this->setDescription('Downloads a latest version of Chromium');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = json_decode(
            file_get_contents(sprintf($this->revisionUrl, $this->generatePlatformCode()))
        );

        $latestVersion = $response->content;

        $output->writeln('Starting download. Revision: ' . $latestVersion);

        $platformRevision = "{$latestVersion}-{$this->generatePlatformCode()}";

        if (!file_exists("versions/{$platformRevision}.zip")) {
            file_put_contents(
                "versions/{$platformRevision}.zip",
                fopen("https://download-chromium.appspot.com/dl/{$this->generatePlatformCode()}?type=snapshots", 'r')
            );
        }

        if (file_exists("versions/{$platformRevision}/chrome-linux/chrome")) {
            $output->writeln('Latest version already downloaded & extracted.');

            return Command::SUCCESS;
        }

        $output->writeln('Download completed. Extracting the zip archive.');

        $archive = new ZipArchive();

        if ($archive->open("versions/{$platformRevision}.zip")) {
            mkdir("versions/{$platformRevision}");

            $archive->extractTo("versions/{$platformRevision}");
            $archive->close();
        }

        $output->writeln("Archive extracted. Creating symlink for {$platformRevision}");

        $this->markAsActiveBinary($platformRevision, $output);

        $output->writeln("Completed! {$platformRevision} currently in use.");

        return Command::SUCCESS;
    }


    /**
     * @throws PlatformNotSupported
     */
    private function generatePlatformCode(): string
    {
        if (PHP_OS == 'Linux') {
            return 'Linux_x64';
        }

        if (PHP_OS == 'Windows' || PHP_OS == 'WIN32' || PHP_OS == 'WINNT') {
            return 'Win_x64';
        }

        if (PHP_OS == 'Darwin') {
            return 'Mac';
        }

        throw new PlatformNotSupported('Platform not supported.');
    }

    private function markAsActiveBinary(string $revision, OutputInterface $output)
    {
        $command = $this->getApplication()->find('use');

        $arguments = [
            'revision' => $revision,
        ];

        $command->run(new ArrayInput($arguments), $output);
    }
}
