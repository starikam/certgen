<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\DatabaseService;
use Spatie\Async\Pool;
use App\Core\CertGen;

class CertGenRunCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:certgen-run';
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

    protected function wordform(float $num, array $w) {
        $num = abs($num) % 100;
        $num_x = $num % 10;
        if ($num > 10 && $num < 20)
            return $w[2];
        if ($num_x > 1 && $num_x < 5)
            return $w[1];
        if ($num_x == 1)
            return $w[0];
        return $w[2];
    }

    protected function replaceMonth($s) {
        $months = [
            '00' => '', 
            '01' => 'января',
            '02' => 'февраля',
            '03' => 'марта',
            '04' => 'апреля',
            '05' => 'мая', 
            '06' => 'июня', 
            '07' => 'июля', 
            '08' => 'августа', 
            '09' => 'сентября', 
            '10' => 'октября',
            '11' => 'ноября', 
            '12' => 'декабря',
        ];

        foreach ($months as $key => $value) {
            $s = str_replace('.' . $key, $value, $s);
        }

        return $s;
    }
    

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (file_exists('/tmp/certgen.lock')) return Command::SUCCESS;

        $db = new DatabaseService();
        $conn = $db->getDatabaseConnection();

        // TODO: return is_completed = false
        $stmt = $conn->query('select id, type from tasks where is_completed = false order by id asc limit 1');
        $task = $stmt->fetch();

        if (!$task) return Command::SUCCESS;

        file_put_contents('/tmp/certgen.lock', date("F j, Y, g:i a"));

        if ($task['type'] == 'certificate') {
            $stmt = $conn->prepare('
                select
                    p.id as certid,
                    p.issuance_date as date,
                    p.fullname,
                    p.gender,
                    p.gender_str,
                    p.hours,
                    p.hours_str,
                    tmp.svg_path as template,
                    c.name as coursename,
                    i.name as instname
                from certificates p
                inner join templates tmp on tmp.id = p.template_id
                inner join course c on c.id = p.course_id
                inner join institution i on i.id = p.institution_id
                inner join tasks t on t.id = p.task_id
                where p.task_id = :id and t.is_completed = false
            ');
            // TODO: return is_completed = false

            $stmt->execute(['id' => $task['id'], ]);
            $lines = $stmt->fetchall();

            $pool = Pool::create()->autoload(__DIR__ . '/../../vendor/autoload.php')->concurrency(5);
            // $pool = Pool::create()->autoload('/var/www/domains/certgen.local/certgen/vendor/autoload.php')->concurrency(5);

            $executeErr = null;

            $templatesPath = $_ENV['templatespath'];
            $certsPath = $_ENV['certpath'];

            foreach ($lines as $line) {
                $hours_placeholder = '(' . str_replace('.', ',', $line['hours']) . ' ' . $this->wordform(
                    floatval($line['hours']), [
                            'академичесикй час',
                            'академических часа',
                            'академических часов',
                        ]
                ) . ')';

                if ($line['hours_str'] != null && $line['hours_str'] != 'null' && $line['hours_str'] != '' && mb_strlen($line['hours_str']) > 0) {
                    $hours_placeholder = $line['hours_str'];
                }

                $gender_placeholder = ($line['gender'] == 'm' ? 'прослушал': 'прослушала') . ' курс по теме:';

                if ($line['gender_str'] != null && $line['gender_str'] != 'null' && $line['gender_str'] != '' && mb_strlen($line['gender_str']) > 0) {
                    $gender_placeholder = $line['gender_str'];
                }

                $placeholders = [
                    'template' => $line['template'],
                    'name' => $line['fullname'],
                    'certid' => $line['certid'],
                    'number' => '№ ' . $line['certid'] . $this->replaceMonth(date(' от d .m Yг.', strtotime($line['date']))),
                    'gender' => $gender_placeholder,
                    'coursename' => '«' . $line['coursename'] . '»',
                    'hours' => $hours_placeholder,
                ];

                $template = str_replace('templates/', '', $line['template']);

                $cert_id = $line['certid'];

                $pool->add(function () use ($template, $placeholders, $templatesPath, $certsPath) {
                    $cert = new CertGen($template, $templatesPath, $certsPath);
                    return $cert->create($placeholders);
                })->then(function ($output) use ($db, $cert_id) {
                    $conn = $db->getDatabaseConnection();
                    $stmt = $conn->prepare('update certificates set pdf_path = :p where id = :id');
                    $stmt->execute(['p' => preg_replace('/.+\/public\//', '', $output), 'id' => $cert_id, ]);
                    $conn = null;
                    array_push($this->files, $output);
                })->catch(function (\Exception $exception) use ($executeErr) {
                    $executeErr = $exception;
                });
            }

            $pool->wait();
        } else if ($task['type'] == 'letter') {
            $stmt = $conn->prepare('
                select
                    l.id,
                    fullname,
                    shortname,
                    position,
                    text,
                    gender,
                    tmp.svg_path as template
                from letter l
                inner join templates tmp on tmp.id = template_id
                inner join tasks t on t.id = l.task_id
                where l.task_id = :id and t.is_completed = false
            ');
            // TODO: return is_completed = false

            $stmt->execute(['id' => $task['id'], ]);
            $lines = $stmt->fetchall();
            
            $pool = Pool::create()->autoload(__DIR__ . '/../../vendor/autoload.php')->concurrency(5);
            // $pool = Pool::create()->autoload('/var/www/domains/certgen.local/certgen/vendor/autoload.php')->concurrency(5);

            $executeErr = null;

            $templatesPath = $_ENV['templatespath'];
            $certsPath = $_ENV['certpath'];

            foreach ($lines as $line) {
                $placeholders = [
                    'template' => $line['template'],
                    'name_00' => $line['fullname'],
                    'name_01' => ($line['gender'] == 'm' ? 'Дорогой': 'Дорогая') . ' ' . $line['shortname'],
                    'rank' => $line['position'],
                    'text' => $line['text'],
                    'certid' => $line['id'],
                    'name' => $line['fullname'],
                ];

                $template = str_replace('templates/', '', $line['template']);

                $letter_id = $line['id'];

                $pool->add(function () use ($template, $placeholders, $templatesPath, $certsPath) {
                    $cert = new CertGen($template, $templatesPath, $certsPath);
                    return $cert->create($placeholders);
                })->then(function ($output) use ($db, $letter_id) {
                    $conn = $db->getDatabaseConnection();
                    $stmt = $conn->prepare('update letter set pdf_path = :p where id = :id');
                    $stmt->execute(['p' => preg_replace('/.+\/public\//', '', $output), 'id' => $letter_id, ]);
                    $conn = null;
                    array_push($this->files, $output);
                })->catch(function (\Exception $exception) use ($executeErr) {
                    $executeErr = $exception;
                });
            }

            $pool->wait();
        }

        if ($executeErr) {
            print_r($executeErr);
            return Command::FAILURE;
        }

        $date = new \DateTime();
        $outPdf = $certsPath . $task['id'] . '_' . $date->format('Y_m_d') . '.pdf';

        if ($this->files) {
            sort($this->files);
            $cmd = 'pdfunite ' . implode(' ', $this->files) . ' ' . $outPdf;

            $r = exec($cmd, $output, $return);

            if ($return) {
                echo 'Execute error, ' . $return;
                exit();
            }

            $stmt = $conn->prepare('update tasks set is_completed = true, pdf_path = :p where id = :id');
            $stmt->execute(['id' => $task['id'], 'p' => preg_replace('/.+\/public\//', '', $outPdf)]);
        }

        unlink('/tmp/certgen.lock');
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;
    }
}