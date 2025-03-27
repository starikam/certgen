<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\DatabaseService;
use Spatie\Async\Pool;
use App\Core\CertGen;

class CleanUpCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:cleanup';
    private $projectDir;
    private $files;

    public function __construct($projectDir) 
    {
        $this->projectDir = $projectDir;
        $this->files = [];

        parent::__construct();
    }

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = new DatabaseService();
        $conn = $db->getDatabaseConnection();

        $path = 'public/certificates/';

        $files = scandir($path);

        foreach ($files as $file) {
            $filePath = $path . $file;
            $dbPath = 'certificates/' . $file;

            $q = $conn->prepare('select id from certificates where pdf_path = :path');
            $q->execute(['path' => $dbPath, ]);

            $resCertificate = $q->fetch();

            $q = $conn->prepare('select id from tasks where pdf_path = :path');
            $q->execute(['path' => $dbPath, ]);

            $resTask = $q->fetch();

            if (!$resCertificate && !$resTask) {
                echo "file " . $dbPath . " not found, deleted\n";
                try {
                    unlink($filePath);
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
        }

        return Command::SUCCESS;
    }
}