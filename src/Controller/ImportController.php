<?php

namespace App\Controller;

use \PDO;
use App\Core\CertGen;
use App\Service\DatabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;
use Spatie\Async\Pool;

class ImportController extends AbstractController
{
    public function __construct(DatabaseService $db) {
        $this->db = $db;
    }

    public function search(Request $request) {
        $number = $request->request->get('number');
        $name = $request->request->get('name');
        $course_id = $request->request->get('course_id');
        $source_id = $request->request->get('source_id');
        $institution_id = $request->request->get('institution_id');
        $date = $request->request->get('date');
        $created = $request->request->get('created');

        $where = [];

        if ($number) {
            array_push($where, 'c.id = ' . $number);
        }

        if ($name) {
            array_push($where, 'fullname like "%' . $name . '%"');
        }

        if ($course_id) {
            array_push($where, 'course_id = ' . $course_id);
        }

        if ($source_id) {
            array_push($where, 'source_id = ' . $source_id);
        }

        if ($institution_id) {
            array_push($where, 'institution_id = ' . $institution_id);
        }

        if ($date) {
            array_push($where, 'issuance_date = "' . date('Y-m-d', strtotime($date)) . '"');
        }

        if ($created) {
            array_push($where, 'с.created_at = "' . date('Y-m-d', strtotime($created)) . '"');
        }

        $collectedWhere = '';

        if ($where)
            $collectedWhere = ' where ' . implode(' and ', $where);

        // echo sprintf('select * from certificates %s', $collectedWhere);
        // exit();

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare(sprintf('
            select 
                c.*,
                r.name as regname,
                i.name as instname,
                i.id as inst_id,
                c.gender as gender,
                s.name as source,
                c.hours as hours,
                c.issuance_date as date,
                c.template_id as template_id,
                cs.name as coursename,
                cs.id as course_id,
                cs.teacher_fullname  as teacher
            from
                certificates c
                left join sources s on s.id = c.source_id
                left join course cs on cs.id = c.course_id
                left join institution i on i.id = c.institution_id
                left join region r on r.id = i.region_id
            %s order by c.created_at desc, c.id desc
        ', $collectedWhere));
        $stmt->execute();
        $result = $stmt->fetchall();

        return $this->json($result);
    }

    public function searchLetter(Request $request) {
        $name = $request->request->get('name');
        $created = $request->request->get('created');

        $where = [];

        if ($name) {
            array_push($where, 'l.fullname like "%' . $name . '%"');
        }

        if ($created) {
            array_push($where, 'l.created_at = "' . date('Y-m-d', strtotime($created)) . '"');
        }

        $collectedWhere = '';

        if ($where)
            $collectedWhere = ' where ' . implode(' and ', $where);

        // echo sprintf('select * from certificates %s', $collectedWhere);
        // exit();

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare(sprintf('
            select 
                l.*
            from letter l
            %s order by l.created_at desc, l.id desc
        ', $collectedWhere));
        $stmt->execute();
        $result = $stmt->fetchall();

        return $this->json($result);
    }

    public function getTask(Request $request, int $id) {
        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('
            select 
                tasks.*,
                (select issuance_date from certificates where task_id = tasks.id order by issuance_date desc limit 1) as issuance_date,
                (select min(id) from certificates where task_id = tasks.id) as first_id,
                (select max(id) from certificates where task_id = tasks.id) as last_id
            from tasks where id = :id
        ');
        $stmt->execute(['id' => $id, ]);

        $result = $stmt->fetch();

        if ($result && $result['type'] == 'certificate') {
            $stmt = $conn->prepare('select * from certificates where task_id = :id');
            $stmt->execute(['id' => $id, ]);
            $certs = $stmt->fetchall();

            $result['certs'] = $certs;

            $stmt = $conn->prepare('
                select
                    (select count(id) from certificates where task_id = :id) as total,
                    (select count(id) from certificates where task_id = :id and pdf_path is not null) as total_completed
            ');
            $stmt->execute(['id' => $id, ]);
            $certs = $stmt->fetch();

            $result['total'] = (int) $certs['total'];
            $result['total_completed'] = (int) $certs['total_completed'];
        } elseif ($result && $result['type'] == 'letter') {
            $stmt = $conn->prepare('select * from letter where task_id = :id');
            $stmt->execute(['id' => $id, ]);
            $certs = $stmt->fetchall();

            $result['certs'] = $certs;

            $stmt = $conn->prepare('
                select
                    (select count(id) from letter where task_id = :id) as total,
                    (select count(id) from letter where task_id = :id and pdf_path is not null) as total_completed
            ');
            $stmt->execute(['id' => $id, ]);
            $certs = $stmt->fetch();

            $result['total'] = (int) $certs['total'];
            $result['total_completed'] = (int) $certs['total_completed'];
        }

        return $this->json($result);
    }

    public function getReport(Request $request) {
        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->query('
            select
                c.id as number,
                issuance_date as date,
                fullname,
                s.name as sourcename,
                i.name as homename
            from certificates c
            left join sources s on s.id = source_id
            left join institution i on i.id = institution_id
            order by c.id desc
        ');
        $certificates = $stmt->fetchall();

        $file = $_ENV['certpath'] . 'report.csv';

        $fp = fopen($file, 'w');
  
        foreach ($certificates as $cert) {
            fputcsv($fp, $cert, ',');
        }
        
        fclose($fp);

        header('Content-Description: File Transfer');
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'. basename($file). '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    private function human_filesize($bytes, $dec = 2) 
    {
        $size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    public function getMeta(Request $request) {
        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->query('
            select 
                institution.*, 
                region.id as regid, 
                region.name as regname,
                (select count(id) from certificates c where c.institution_id = institution.id) as certs
            from institution 
            left join region 
            on region.id = institution.region_id
            order by institution.id desc
        ');
        $institutions = $stmt->fetchall();

        $stmt = $conn->query('
            select 
                *,
                (select count(id) from certificates c where c.course_id = course.id) as certs
            from
                course
            order by
                id 
            desc');
        $courses = $stmt->fetchall();

        $stmt = $conn->query('
            select
                r.*,
                (select count(id) from certificates c where c.institution_id = i.id) as certs
            from region r left join institution i on i.region_id = r.id order by r.id desc
        ');
        $regions = $stmt->fetchall();

        $stmt = $conn->query('
            select
                t.*,
                (select count(id) from certificates c where c.template_id = t.id) as certs
            from templates t order by t.id desc
        ');
        $templates = $stmt->fetchall();

        $stmt = $conn->query('
            select
                s.*,
                (select count(id) from certificates c where c.source_id = s.id) as certs
            from sources s order by s.id desc
        ');
        $sources = $stmt->fetchall();

        $stmt = $conn->query('
            select
                (select min(id) from certificates) as id_min,
                (select max(id) from certificates) as id_max,
                (select count(*) from certificates) as total,
                (select count(*) from certificates where created_at >= (NOW() - INTERVAL 1 WEEK) and created_at <= now()) as total_week,
                (select count(*) from certificates where created_at >= (NOW() - INTERVAL 1 MONTH) and created_at <= now()) as total_month
        ');
        $stat = $stmt->fetch();

        $stat['free_mem'] = $this->human_filesize(disk_free_space('/'));
        $stat['total_mem'] = $this->human_filesize(disk_total_space('/'));
        
        return $this->json([
            'institutions' => $institutions,
            'courses' => $courses,
            'regions' => $regions,
            'templates' => $templates,
            'sources' => $sources,
            'stat' => $stat,
        ]);
    }

    public function getSingleCerts(Request $request) {
        $conn = $this->db->getDatabaseConnection();
        $stmt = $conn->prepare('
            select 
            certificates.*,
                c.name as coursename,
                i.name as instname
            from
                certificates
            inner join course c on c.id = course_id
            inner join institution i on i.id = institution_id
            where task_id is null
        ');
        $stmt->execute();
        $certs = $stmt->fetchall();

        return $this->json($certs);
    }

    public function getSingleLetters(Request $request) {
        $conn = $this->db->getDatabaseConnection();
        $stmt = $conn->prepare('
            select 
                *
            from
                letter
            where task_id is null
        ');
        $stmt->execute();
        $certs = $stmt->fetchall();

        return $this->json($certs);
    }

    public function getCompletedCertsCount(Request $request, int $id) {
        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('
            select
                (select count(id) from certificates where task_id = :id) as total,
                (select count(id) from certificates where task_id = :id and pdf_path is not null) as total_completed
        ');
        $stmt->execute(['id' => $id, ]);

        $result = $stmt->fetch();

        if ($result) {
            $result['total'] = (int) $result['total'];
            $result['total_completed'] = (int) $result['total_completed'];
        }

        return $this->json($result);
    }

    public function getTasks(Request $request) {
        $conn = $this->db->getDatabaseConnection();

        $number = $request->request->get('number');
        $number_from = $request->request->get('number_from');
        $number_to = $request->request->get('number_to');
        $date = $request->request->get('date');

        $where = [];

        if ($number) {
            array_push($where, 'tasks.id = ' . $number);
        }

        if ($number_from) {
            array_push($where, '(select min(id) from certificates where task_id = tasks.id) >= ' . $number_from);
        }

        if ($number_to) {
            array_push($where, '(select max(id) from certificates where task_id = tasks.id) <= ' . $number_to);
        }

        if ($date) {
            array_push($where, 'DATE_FORMAT(created_at, \'%Y-%m-%d\') = "' . date('Y-m-d', strtotime($date)) . '"');
        }

        $collectedWhere = '';

        if ($where)
            $collectedWhere = ' where ' . implode(' and ', $where);

        $stmt = $conn->prepare(sprintf('
            select
                tasks.*,
                sources.name as source,
                course.name as course,
                case 
                    when tasks.type = \'certificate\' then (select count(id) from certificates where task_id = tasks.id)
                    when tasks.type = \'letter\' then (select count(id) from letter where task_id = tasks.id)
                end as total,
                (select issuance_date from certificates where task_id = tasks.id order by issuance_date desc limit 1) as issuance_date,
                (select min(id) from certificates where task_id = tasks.id) as first_id,
                (select max(id) from certificates where task_id = tasks.id) as last_id,
                case 
                    when tasks.type = \'certificate\' then (select count(id) from certificates where task_id = tasks.id and pdf_path is not null)
                    when tasks.type = \'letter\' then (select count(id) from letter where task_id = tasks.id and pdf_path is not null)
                end as total_completed 
            from
                tasks %s
            left join sources on sources.id = (select source_id from certificates where task_id = tasks.id limit 1)
            left join course on course.id = (select course_id from certificates where task_id = tasks.id limit 1)
            order by created_at desc
        ', $collectedWhere));
        $stmt->execute();

        $result = $stmt->fetchall();

        return $this->json($result);
    }

    public function create(Request $request) {
        if (!$request->isMethod('POST'))
            return $this->json(false);

        $course_id = $request->request->get('course_id');
        $institution_id = $request->request->get('institution_id');
        $template_id = $request->request->get('template_id');
        $hours = $request->request->get('hours');
        $hours_str = $request->request->get('hours_str');
        $date = $request->request->get('date');
        $number = $request->request->get('number');
        $name = $request->request->get('name');
        $gender = $request->request->get('gender');
        $gender_str = $request->request->get('gender_str');

        $conn = $this->db->getDatabaseConnection();

        if ($number) {
            $stmt = $conn->prepare('delete from certificates where id = :id');
            $result = $stmt->execute(['id' => $number, ]);

            if (!$result) return $this->json(false);
        }

        if ($number) {
            $stmt = $conn->prepare('
                insert into certificates (
                    id,
                    fullname, 
                    institution_id,
                    template_id,
                    hours,
                    hours_str,
                    course_id,
                    gender,
                    gender_str,
                    issuance_date
                ) values (:number, :fn, :inst, :template_id, :hours, :hours_str, :course, :gender, :gender_str, :date)'
            );
    
            $result = $stmt->execute([
                'fn' => $name,
                'inst' => $institution_id,
                'template_id' => $template_id,
                'hours' => $hours,
                'hours_str' => $hours_str,
                'course' => $course_id,
                'date' => date('Y-m-d', strtotime($date)),
                'gender' => $gender,
                'gender_str' => $gender_str,
                'number' => $number,
            ]);
        } else {
            $stmt = $conn->prepare('
                insert into certificates (
                    fullname, 
                    institution_id,
                    template_id,
                    hours,
                    hours_str,
                    course_id,
                    gender,
                    gender_str,
                    issuance_date
                ) values (:fn, :inst, :template_id, :hours, :hours_str, :course, :gender, :gender_str, :date)'
            );
    
            $result = $stmt->execute([
                'fn' => $name,
                'inst' => $institution_id,
                'template_id' => $template_id,
                'hours' => $hours,
                'hours_str' => $hours_str,
                'course' => $course_id,
                'date' => date('Y-m-d', strtotime($date)),
                'gender' => $gender,
                'gender_str' => $gender_str,
            ]);
        }

        if (!$result) return $this->json(false);

        $cert_id = (int) $conn->lastInsertId();

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
            where p.id = :id
        ');

        $stmt->execute(['id' => $cert_id, ]);
        $line = $stmt->fetch();

        $gender_placeholder = ($line['gender'] == 'm' ? 'прослушал': 'прослушала') . ' курс по теме:';
        if ($line['gender_str'] != null && $line['gender_str'] != 'null' && $line['gender_str'] != '' && mb_strlen($line['gender_str']) > 0) {
            $gender_placeholder = $line['gender_str'];
        }

        $hous_placeholder = '(' . str_replace('.', ',', $line['hours']) . ' ' . $this->wordform(
            floatval($line['hours']), [
                    'академичесикй час',
                    'академических часа',
                    'академических часов',
                ]
        ) . ')';
        if ($line['hours_str'] != null && $line['hours_str'] != 'null' && $line['hours_str'] != '' && mb_strlen($line['hours_str']) > 0) {
            $hous_placeholder = $line['hours_str'];
        }

        $placeholders = [
            'template' => $line['template'],
            'name' => $line['fullname'],
            'certid' => $line['certid'],
            'number' => '№ ' . $line['certid'] . $this->replaceMonth(date(' от d .m Yг.', strtotime($line['date']))),
            'gender' => $gender_placeholder,
            'coursename' => '«' . $line['coursename'] . '»',
            'hours' => $hous_placeholder,
        ];

        $template = str_replace('templates/', '', $line['template']);

        $templatesPath = $_ENV['templatespath'];
        $certsPath = $_ENV['certpath'];

        $cert = new CertGen($template, $templatesPath, $certsPath);
        $pdf = $cert->create($placeholders);

        if (!$pdf) return $this->json(false);

        $stmt = $conn->prepare('update certificates set pdf_path = :p where id = :id');
        $stmt->execute(['p' => preg_replace('/.+\/public\//', '', $pdf), 'id' => $cert_id, ]);

        return $this->json(['cert_id' => $cert_id, 'pdf' => preg_replace('/.+\/public\//', '', $pdf), ]);
    }

    public function createLetter(Request $request) {
        if (!$request->isMethod('POST'))
            return $this->json(false);

        $template_id = $request->request->get('template_id');
        $number = $request->request->get('number');
        $name = $request->request->get('name');
        $shortname = $request->request->get('shortname');
        $gender = $request->request->get('gender');
        $text = $request->request->get('text');
        $position = $request->request->get('position');

        $conn = $this->db->getDatabaseConnection();

        if ($number) {
            $stmt = $conn->prepare('delete from letter where id = :id');
            $result = $stmt->execute(['id' => $number, ]);

            if (!$result) return $this->json(false);
        }

        $stmt = $conn->prepare('
            insert into letter (
                fullname, 
                shortname,
                template_id,
                gender,
                text,
                position
            ) values (:fn, :sn, :template_id, :gender, :text, :pos)'
        );

        $result = $stmt->execute([
            'fn' => $name,
            'sn' => $shortname,
            'template_id' => $template_id,
            'gender' => $gender,
            'text' => $text,
            'pos' => $position,
        ]);

        if (!$result) return $this->json(false);

        $cert_id = (int) $conn->lastInsertId();

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
            where l.id = :id
        ');

        $stmt->execute(['id' => $cert_id, ]);
        $line = $stmt->fetch();

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

        $templatesPath = $_ENV['templatespath'];
        $certsPath = $_ENV['certpath'];

        $cert = new CertGen($template, $templatesPath, $certsPath);
        $pdf = $cert->create($placeholders);

        if (!$pdf) return $this->json(false);

        $letter_id = $line['id'];

        $stmt = $conn->prepare('update letter set pdf_path = :p where id = :id');
        $stmt->execute(['p' => preg_replace('/.+\/public\//', '', $pdf), 'id' => $letter_id, ]);

        return $this->json(['cert_id' => $letter_id, 'pdf' => preg_replace('/.+\/public\//', '', $pdf), ]);
    }

    public function importLetter(Request $request) {
        if (!$request->isMethod('POST'))
            return $this->json(false);

        $template_id = $request->request->get('template_id');
        $csvfile = $request->files->get('csvfile');

        $lines = file($csvfile->getPathName());

        if (!$lines) return $this->json(false);

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('insert into tasks(csv, type) values (:csv, :t)');
        $result = $stmt->execute(['csv' => json_encode([]), 't' => 'letter', ]);

        if ($result) {
            $task_id = (int) $conn->lastInsertId();

            $certs = [];

            foreach ($lines as $line) {
                $line = explode(';', $line);

                if (count($line) != 5 || !$line[0] || !$line[1] || !$line[2] || !$line[3] || !$line[4])
                    return $this->json(false);

                    
                $line[0] = preg_replace('/[^а-яёЁА-Яa-zA-Z\s-]+/u', '', trim($line[0]));
                $line[1] = trim($line[1]);
                    
                $stmt = $conn->prepare('
                    insert into letter (
                        fullname, 
                        shortname,
                        position,
                        text,
                        gender,
                        template_id,
                        task_id
                    ) values (:fn, :sn, :position, :text, :gender, :template_id, :task)'
                );

                $result = $stmt->execute([
                    'fn' => $line[0],
                    'sn' => $line[1],
                    'position' => $line[2],
                    'text' => $line[3],
                    'gender' => ($line[4] == 'дорогой' ? 'm' : 'f'),
                    'template_id' => $template_id,
                    'task' => $task_id,
                ]);

                $cert_id = (int) $conn->lastInsertId();

                $placeholders = [
                    'name' => $line[0],
                ];
            }
            
            return $this->json(['task_id' => $task_id, 'lines' => count($lines), ]);
        }

        return $this->json(false);
    }

    public function import(Request $request) {
        if (!$request->isMethod('POST'))
            return $this->json(false);

        $course_id = $request->request->get('course_id');
        $source_id = $request->request->get('source_id');
        $institution_id = $request->request->get('institution_id');
        $template_id = $request->request->get('template_id');
        $hours = $request->request->get('hours');
        $hours_str = $request->request->get('hours_str');
        $date = $request->request->get('date');
        $csvfiles = $request->files->get('csvfile');

        $tasks = [];

        foreach ($csvfiles as $csvfile) {
            $lines = file($csvfile->getPathName());
    
            if (!$lines) return $this->json(false);
    
            $conn = $this->db->getDatabaseConnection();
    
            $stmt = $conn->prepare('insert into tasks(csv) values (:csv)');
            $result = $stmt->execute(['csv' => json_encode([]), ]);
    
            if ($result) {
                $task_id = (int) $conn->lastInsertId();
    
                $certs = [];
    
                foreach ($lines as $line) {
                    $line = explode(';', $line);
    
                    if (count($line) < 2 || !$line[0] || !$line[1])
                        return $this->json(false);
    
                        
                    $line[0] = preg_replace('/[^а-яёЁА-Яa-zA-Z\s-]+/u', '', trim($line[0]));
                    $line[1] = trim($line[1]);
                        
                    $stmt = $conn->prepare('
                        insert into certificates (
                            fullname, 
                            institution_id,
                            template_id,
                            source_id,
                            task_id,
                            hours,
                            hours_str,
                            course_id,
                            gender,
                            gender_str,
                            issuance_date
                        ) values (:fn, :inst, :template_id, :source_id, :task, :hours, :hours_str, :course, :gender, :gender_str, :date)'
                    );
    
                    $result = $stmt->execute([
                        'fn' => $line[0],
                        'inst' => $institution_id,
                        'template_id' => $template_id,
                        'source_id' => $source_id,
                        'task' => $task_id,
                        'hours' => $hours,
                        'hours_str' => $hours_str != '' ? $hours_str : null,
                        'course' => $course_id,
                        'gender' => ($line[1] == 'прослушал' ? 'm' : 'f'),
                        'gender_str' => count($line) > 2 ? $line[2] : null,
                        'date' => date('Y-m-d', strtotime($date)),
                    ]);
    
                    $cert_id = (int) $conn->lastInsertId();
    
                    $placeholders = [
                        'name' => $line[0],
                    ];
                }
                
                array_push($tasks, ['task_id' => $task_id, 'lines' => count($lines), ]);
            }
        }

        if ($tasks)
            return $this->json($tasks);

        return $this->json(false);
    }

    public function reissuance(Request $request, int $id) {
        if (!$request->isMethod('POST'))
            return $this->json(false);

        $course_id = $request->request->get('course_id');
        $source_id = $request->request->get('source_id');
        $institution_id = $request->request->get('institution_id');
        $template_id = $request->request->get('template_id');
        $hours = $request->request->get('hours');
        $date = $request->request->get('date');

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('update tasks set is_completed = false, pdf_path = null where id = :id');
        $result = $stmt->execute(['id' => $id, ]);

        if (!$result) return $this->json(false); 

        $stmt = $conn->prepare('
            update certificates set 
                course_id = :course,
                source_id = :source_id,
                institution_id = :inst,
                template_id = :template_id,
                hours = :hours,
                issuance_date = :date,
                pdf_path = null
            where task_id = :id
        ');

        $result = $stmt->execute([
            'inst' => $institution_id,
            'template_id' => $template_id,
            'source_id' => $source_id,
            'hours' => $hours,
            'course' => $course_id,
            'date' => date('Y-m-d', strtotime($date)),
            'id' => $id,
        ]);

        if ($result)
            return $this->json(true);

        return $this->json(false);
    }

    public function createCourse(Request $request) {
        if (!$request->isMethod('POST') || !$request->request->get('name')|| !$request->request->get('teacher'))
            return $this->json(false);

        $name = $request->request->get('name');
        $teacher = $request->request->get('teacher');

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('insert into course(name, teacher_fullname) values (:name, :teacher)');
        $result = $stmt->execute(['name' => $name, 'teacher' => $teacher, ]);

        if ($result) {
            $id = (int) $conn->lastInsertId();
            return $this->json(['id' => $id, ]);
        }

        return $this->json(false);
    }

    public function createTemplate(Request $request, SluggerInterface $slugger) {
        if (!$request->isMethod('POST') || !$request->request->get('name')|| !$request->files->get('svgfile'))
            return $this->json(false);

        $name = $request->request->get('name');
        $svgfile = $request->files->get('svgfile');

        $fileInfo = pathinfo($svgfile->getClientOriginalName());

        $safeFilename = $slugger->slug($fileInfo['filename'] . '-' . $name);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $fileInfo['extension'];

        try {
            $svgfile->move($_ENV['templatespath'], $newFilename);
        } catch (FileException $e) {
            return $this->json(false);
        }

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('insert into templates(name, svg_path) values (:name, :f)');
        $result = $stmt->execute(['name' => $name, 'f' => preg_replace('/.+\/public\//', '', $_ENV['templatespath'] . $newFilename), ]);

        if ($result) {
            $id = (int) $conn->lastInsertId();
            return $this->json(['id' => $id, ]);
        }

        return $this->json(false);
    }

    public function saveTemplate(Request $request, int $id, SluggerInterface $slugger) {
        if (!$request->isMethod('POST') || !$request->request->get('name'))
            return $this->json(false);

        $name = $request->request->get('name');
        $svgfile = $request->files->get('svgfile');
        $currentSvgpath = $request->request->get('currentSvgfile');

        if ($currentSvgpath) {
            $svgpath = $currentSvgpath;
        }

        if ($svgfile) {
            $fileInfo = pathinfo($svgfile->getClientOriginalName());
    
            $safeFilename = $slugger->slug($fileInfo['filename'] . '-' . $name);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $fileInfo['extension'];
    
            try {
                $svgfile->move($_ENV['templatespath'], $newFilename);
            } catch (FileException $e) {
                return $this->json(false);
            }

            $svgpath = preg_replace('/.+\/public\//', '', $_ENV['templatespath'] . $newFilename);
        }

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('update templates set name = :name, svg_path = :f where id = :id');
        $result = $stmt->execute(['name' => $name, 'f' => $svgpath, 'id' => $id, ]);

        if ($result) {
            return $this->json(true);
        }

        return $this->json(false);
    }

    public function removeTemplate(Request $request, int $id) {
        if (!$request->isMethod('GET') || !$id)
            return $this->json(false);

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('delete from templates where id = :id');
        $result = $stmt->execute(['id' => $id, ]);

        if ($result) {
            return $this->json(true);
        }

        return $this->json(false);
    }

    public function removeCert(Request $request, int $id) {
        if (!$request->isMethod('GET') || !$id)
            return $this->json(false);

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('delete from certificates where id = :id');
        $result = $stmt->execute(['id' => $id, ]);

        if ($result) {
            return $this->json(true);
        }

        return $this->json(false);
    }

    public function removeLetter(Request $request, int $id) {
        if (!$request->isMethod('GET') || !$id)
            return $this->json(false);

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('delete from letter where id = :id');
        $result = $stmt->execute(['id' => $id, ]);

        if ($result) {
            return $this->json(true);
        }

        return $this->json(false);
    }

    public function removeCourse(Request $request, int $id) {
        if (!$request->isMethod('GET') || !$id)
            return $this->json(false);

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('delete from course where id = :id');
        $result = $stmt->execute(['id' => $id, ]);

        if ($result) {
            return $this->json(true);
        }

        return $this->json(false);
    }

    public function saveCourse(Request $request, int $id) {
        if (!$request->isMethod('POST') || !$id || !$request->request->get('name') || !$request->request->get('teacher'))
            return $this->json(false);

        $name = $request->request->get('name');
        $teacher = $request->request->get('teacher');

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('update course set name = :n, teacher_fullname = :teacher where id = :id');
        $result = $stmt->execute(['id' => $id, 'n' => $name, 'teacher' => $teacher]);

        if ($result) {
            return $this->json(true);
        }

        return $this->json(false);
    }

    public function createRegion(Request $request) {
        if (!$request->isMethod('POST') || !$request->request->get('name'))
            return $this->json(false);

        $name = $request->request->get('name');

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('insert into region(name) values (:name)');
        $result = $stmt->execute(['name' => $name, ]);

        if ($result) {
            $id = (int) $conn->lastInsertId();
            return $this->json(['id' => $id, ]);
        }

        return $this->json(false);
    }

    public function removeRegion(Request $request, int $id) {
        if (!$request->isMethod('GET') || !$id)
            return $this->json(false);

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('delete from region where id = :id');
        $result = $stmt->execute(['id' => $id, ]);

        if ($result) {
            return $this->json(true);
        }

        return $this->json(false);
    }

    public function saveRegion(Request $request, int $id) {
        if (!$request->isMethod('POST') || !$id || !$request->request->get('name'))
            return $this->json(false);

        $name = $request->request->get('name');

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('update region set name = :n where id = :id');
        $result = $stmt->execute(['id' => $id, 'n' => $name, ]);

        if ($result) {
            return $this->json(true);
        }

        return $this->json(false);
    }

    public function createSource(Request $request) {
        if (!$request->isMethod('POST') || !$request->request->get('name'))
            return $this->json(false);
    
        $name = $request->request->get('name');
    
        $conn = $this->db->getDatabaseConnection();
    
        $stmt = $conn->prepare('insert into sources(name) values (:name)');
        $result = $stmt->execute(['name' => $name, ]);
    
        if ($result) {
            $id = (int) $conn->lastInsertId();
            return $this->json(['id' => $id, ]);
        }
    
        return $this->json(false);
    }
    
    public function removeSource(Request $request, int $id) {
        if (!$request->isMethod('GET') || !$id)
            return $this->json(false);
    
        $conn = $this->db->getDatabaseConnection();
    
        $stmt = $conn->prepare('delete from sources where id = :id');
        $result = $stmt->execute(['id' => $id, ]);
    
        if ($result) {
            return $this->json(true);
        }
    
        return $this->json(false);
    }
    
    public function saveSource(Request $request, int $id) {
        if (!$request->isMethod('POST') || !$id || !$request->request->get('name'))
            return $this->json(false);
    
        $name = $request->request->get('name');
    
        $conn = $this->db->getDatabaseConnection();
    
        $stmt = $conn->prepare('update sources set name = :n where id = :id');
        $result = $stmt->execute(['id' => $id, 'n' => $name, ]);
    
        if ($result) {
            return $this->json(true);
        }
    
        return $this->json(false);
    }

    public function createHome(Request $request) {
        if (!$request->isMethod('POST') || !$request->request->get('name') || !$request->request->get('region_id'))
            return $this->json(false);

        $name = $request->request->get('name');
        $region_id = $request->request->get('region_id');

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('insert into institution(name, region_id) values (:name, :id)');
        $result = $stmt->execute(['name' => $name, 'id' => $region_id, ]);

        if ($result) {
            $id = (int) $conn->lastInsertId();
            return $this->json(['id' => $id, ]);
        }

        return $this->json(false);
    }

    public function removeHome(Request $request, int $id) {
        if (!$request->isMethod('GET') || !$id)
            return $this->json(false);

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('delete from institution where id = :id');
        $result = $stmt->execute(['id' => $id, ]);

        if ($result) {
            return $this->json(true);
        }

        return $this->json(false);
    }

    public function saveHome(Request $request, int $id) {
        if (!$request->isMethod('POST') || !$id || !$request->request->get('name') || !$request->request->get('region_id'))
            return $this->json(false);

        $name = $request->request->get('name');
        $region_id = $request->request->get('region_id');

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('update institution set name = :n, region_id = :r where id = :id');
        $result = $stmt->execute(['id' => $id, 'n' => $name, 'r' => $region_id, ]);

        if ($result) {
            return $this->json(true);
        }

        return $this->json(false);
    }

    public function removeTask(Request $request, int $id) {
        if (!$request->isMethod('GET') || !$id)
            return $this->json(false);

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('
            select
                min(c.id) as min
            from tasks
            inner join certificates c on c.task_id = task_id
            where task_id = (
                select max(id) from tasks
            ) and task_id = :id
        ');

        $stmt->execute(['id' => $id, ]);

        $taskCheckResult = $stmt->fetch();

        $stmt = $conn->prepare('delete from tasks where id = :id');
        $result = $stmt->execute(['id' => $id, ]);

        if ($taskCheckResult && array_key_exists('min', $taskCheckResult) && $taskCheckResult['min']) {
            $stmt = $conn->query('alter table certificates AUTO_INCREMENT = 1');
        }

        if ($result) {
            return $this->json(true);
        }

        return $this->json(false);
    }

    public function checkCert(Request $request, int $id) {
        if (!$request->isMethod('GET'))
            return $this->json(false);

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('select * from certificates where id = :id');
        $stmt->execute(['id' => $id, ]);
        $result = $stmt->fetch();

        if ($result) {
            return $this->json(['id' => $result['id'], ]);
        }

        return $this->json(false);
    }

    public function checkLetter(Request $request, int $id) {
        if (!$request->isMethod('GET'))
            return $this->json(false);

        $conn = $this->db->getDatabaseConnection();

        $stmt = $conn->prepare('select * from letter where id = :id');
        $stmt->execute(['id' => $id, ]);
        $result = $stmt->fetch();

        if ($result) {
            return $this->json(['id' => $result['id'], ]);
        }

        return $this->json(false);
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
}